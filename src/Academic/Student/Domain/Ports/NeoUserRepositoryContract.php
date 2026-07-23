<?php

declare(strict_types=1);

namespace Academic\Student\Domain\Ports;

use Academic\Student\Application\DTOs\NeoUserDTO;
use Academic\Student\Application\DTOs\NeoUserSessionDTO;
use DateTimeImmutable;

interface NeoUserRepositoryContract
{
    /**
     * Inserta o actualiza un usuario en neo_users.
     * Usa INSERT ... ON CONFLICT (neo_id) DO UPDATE para idempotencia.
     */
    public function upsert(NeoUserDTO $dto): void;

    /**
     * Verifica si el usuario cambió comparando el checksum almacenado.
     */
    public function hasChanged(int $neoId, string $newChecksum): bool;

    /**
     * Retorna el total de usuarios almacenados por rol.
     *
     * @return array<string, int>
     */
    public function countByRole(): array;

    /**
     * Retorna usuarios sin primer login (no han activado su cuenta).
     * joined_at IS NOT NULL AND first_login_at IS NULL
     */
    public function findNeverLoggedIn(?int $organizationId = null): array;

    /**
     * Retorna usuarios inactivos: last_login_at < now() - $days días.
     */
    public function findInactiveSince(int $days, ?int $organizationId = null): array;

    /**
     * Inserta sesiones de usuario. Solo inserta nuevas — nunca sobreescribe.
     * Una sesión es inmutable una vez creada en NEO.
     *
     * @param NeoUserSessionDTO[] $sessions
     * @return int número de sesiones nuevas insertadas
     */
    public function insertSessions(array $sessions): int;

    /**
     * Retorna el ID de la última sesión conocida para un usuario.
     * Usado como cursor $after para paginación incremental.
     */
    public function getLastSessionId(int $neoUserId): ?int;

    /**
     * Retorna el conteo de sesiones por usuario en un rango de fechas.
     */
    public function countSessionsByUser(int $neoUserId, ?DateTimeImmutable $from = null): int;
}
