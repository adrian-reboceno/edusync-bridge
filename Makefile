# ─────────────────────────────────────────────────────────────
# Makefile — atajos de desarrollo para el backend LMS Sync Bridge
# Uso: make <comando>  |  make help
# ─────────────────────────────────────────────────────────────

.PHONY: help \
        up up-tools down restart build \
        logs logs-app logs-horizon logs-scheduler \
        shell shell-postgres shell-postgres-ce shell-redis \
        artisan migrate fresh seed \
        horizon-status horizon-pause horizon-continue \
        horizon-terminate horizon-clear horizon-publish \
        queue-failed queue-retry queue-flush \
        test test-unit test-integration test-coverage \
        lint lint-check \
        setup

# Colores
GREEN  = \033[0;32m
YELLOW = \033[1;33m
RED    = \033[0;31m
NC     = \033[0m

help: ## Muestra esta ayuda
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-22s$(NC) %s\n", $$1, $$2}'

# ── Docker ────────────────────────────────────────────────────

up: ## Levanta todos los servicios en segundo plano
	docker compose up -d

up-tools: ## Levanta todos los servicios + redis-commander (profile tools)
	docker compose --profile tools up -d

down: ## Detiene y elimina los contenedores (preserva volúmenes)
	docker compose down

down-volumes: ## Detiene contenedores Y elimina volúmenes (reset completo)
	docker compose down -v

restart: ## Reinicia todos los servicios
	docker compose restart

build: ## Reconstruye las imágenes sin caché (tras cambiar Dockerfiles)
	docker compose build --no-cache

logs: ## Logs en tiempo real de todos los servicios
	docker compose logs -f

logs-app: ## Logs del contenedor app (PHP-FPM / Laravel)
	docker compose logs -f app

logs-horizon: ## Logs del contenedor horizon (workers de colas)
	docker compose logs -f horizon

logs-scheduler: ## Logs del contenedor scheduler
	docker compose logs -f scheduler

# ── Shell ─────────────────────────────────────────────────────

shell: ## Abre bash en el contenedor app
	docker compose exec app bash

shell-horizon: ## Abre bash en el contenedor horizon
	docker compose exec horizon bash

shell-postgres: ## Abre psql en la BD local del backend
	docker compose exec postgres psql -U lms_user -d lms_sync_bridge

shell-postgres-ce: ## Abre psql en la BD del Control Escolar (simulada)
	docker compose exec postgres_ce psql -U ce_user -d control_escolar

shell-redis: ## Abre Redis CLI
	docker compose exec redis redis-cli

# ── Laravel / Artisan ─────────────────────────────────────────

artisan: ## Ejecuta un comando artisan. Uso: make artisan CMD="migrate:status"
	docker compose exec app php artisan $(CMD)

migrate: ## Ejecuta migraciones pendientes
	docker compose exec app php artisan migrate --force

fresh: ## Recrea la BD local y corre migraciones + seeders
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Corre solo los seeders
	docker compose exec app php artisan db:seed

# ── Horizon ───────────────────────────────────────────────────
# Los comandos horizon:* se ejecutan en el contenedor 'horizon',
# no en 'app', ya que es donde corre el proceso supervisor.

horizon-status: ## Estado de Horizon: running | paused | inactive
	docker compose exec horizon php artisan horizon:status

horizon-pause: ## Pausa todos los workers (los Jobs en curso terminan)
	docker compose exec horizon php artisan horizon:pause

horizon-continue: ## Reanuda workers pausados
	docker compose exec horizon php artisan horizon:continue

horizon-terminate: ## Apaga Horizon de forma ordenada (graceful shutdown)
	docker compose exec horizon php artisan horizon:terminate

horizon-clear: ## Limpia Jobs fallidos y métricas del dashboard
	docker compose exec horizon php artisan horizon:clear

horizon-publish: ## (Horizon 5.x — ya no requiere publicar assets)
	@echo "Horizon 5.x incluye assets embebidos. Este paso ya no es necesario."

# ── Jobs fallidos ─────────────────────────────────────────────

queue-failed: ## Lista Jobs fallidos en la tabla failed_jobs
	docker compose exec app php artisan queue:failed

queue-retry: ## Reintenta todos los Jobs fallidos
	docker compose exec app php artisan queue:retry all

queue-flush: ## Elimina permanentemente todos los Jobs fallidos
	docker compose exec app php artisan queue:flush

# ── Testing ───────────────────────────────────────────────────

test: ## Corre toda la suite de tests
	docker compose exec app php artisan test

test-unit: ## Solo tests unitarios (Domain + Application)
	docker compose exec app php artisan test --testsuite=Unit

test-integration: ## Solo tests de integración (Adapters + BD)
	docker compose exec app php artisan test --testsuite=Integration

test-coverage: ## Tests con reporte de cobertura HTML (requiere Xdebug)
	docker compose exec app php artisan test --coverage --coverage-html=coverage

# ── Calidad de código ─────────────────────────────────────────

lint: ## Formatea el código con Laravel Pint
	docker compose exec app ./vendor/bin/pint

lint-check: ## Verifica estilo sin modificar archivos (para CI)
	docker compose exec app ./vendor/bin/pint --test

# ── Primer arranque ───────────────────────────────────────────

setup: ## Configuración inicial completa del proyecto (solo la primera vez)
	@echo "$(YELLOW)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(NC)"
	@echo "$(YELLOW)  LMS Sync Bridge — Setup inicial$(NC)"
	@echo "$(YELLOW)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(NC)"
	@echo ""
	@echo "$(YELLOW)[1/8] Copiando .env.docker → .env$(NC)"
	cp .env.docker .env
	@echo "$(YELLOW)[2/8] Construyendo imágenes Docker$(NC)"
	docker compose build
	@echo "$(YELLOW)[3/8] Levantando servicios$(NC)"
	docker compose up -d
	@echo "$(YELLOW)[4/8] Esperando que PostgreSQL esté listo...$(NC)"
	@until docker compose exec postgres pg_isready -U lms_user -d lms_sync_bridge > /dev/null 2>&1; do \
		echo "  Esperando PostgreSQL..."; sleep 3; \
	done
	@echo "  $(GREEN)✓ PostgreSQL listo$(NC)"
	@echo "$(YELLOW)[5/8] Instalando dependencias PHP$(NC)"
	docker compose exec app composer update --no-interaction --prefer-dist --optimize-autoloader
	@echo "$(YELLOW)[6/8] Generando APP_KEY$(NC)"
	docker compose exec app php artisan key:generate
	@echo "$(YELLOW)[7/8] Ejecutando migraciones$(NC)"
	docker compose exec app php artisan migrate --force
	@echo "$(YELLOW)[8/8] Ejecutando horizon:publish$(NC)"
	@echo "  (Horizon 5.x no requiere publicar assets — omitido)"
	@echo ""
	@echo "$(GREEN)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(NC)"
	@echo "$(GREEN)  ✓ Setup completado$(NC)"
	@echo "$(GREEN)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(NC)"
	@echo ""
	@echo "  Backend:          http://localhost:8000"
	@echo "  Horizon:          http://localhost:8000/horizon"
	@echo "  Mailpit:          http://localhost:8026"
	@echo "  Redis Commander:  http://localhost:8083  (make up-tools)"
	@echo ""