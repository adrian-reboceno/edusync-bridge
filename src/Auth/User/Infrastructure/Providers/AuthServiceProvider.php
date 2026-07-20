<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Providers;

use Auth\AuditLog\Domain\Ports\AuditLogRepositoryContract;
use Auth\AuditLog\Infrastructure\Persistence\Eloquent\EloquentAuditLogRepository;
use Auth\Role\Domain\Ports\RoleRepositoryContract;
use Auth\Role\Infrastructure\Persistence\Eloquent\SpatieRoleRepository;
use Auth\User\Domain\Ports\PasswordHistoryRepositoryContract;
use Auth\User\Domain\Ports\SessionRepositoryContract;
use Auth\User\Domain\Ports\TokenServiceContract;
use Auth\User\Domain\Ports\TotpServiceContract;
use Auth\User\Domain\Ports\UserRepositoryContract;
use Auth\User\Infrastructure\Persistence\Eloquent\EloquentPasswordHistoryRepository;
use Auth\User\Infrastructure\Persistence\Eloquent\EloquentSessionRepository;
use Auth\User\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use Auth\User\Infrastructure\Services\SanctumTokenService;
use Auth\User\Infrastructure\Services\TotpService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Shared\Domain\Contracts\EventBusContract;
use Shared\Infrastructure\Events\LaravelEventBus;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryContract::class, EloquentUserRepository::class);
        $this->app->bind(SessionRepositoryContract::class, EloquentSessionRepository::class);
        $this->app->bind(PasswordHistoryRepositoryContract::class, EloquentPasswordHistoryRepository::class);
        $this->app->bind(TokenServiceContract::class, SanctumTokenService::class);
        $this->app->bind(TotpServiceContract::class, TotpService::class);
        $this->app->bind(RoleRepositoryContract::class, SpatieRoleRepository::class);
        $this->app->bind(AuditLogRepositoryContract::class, EloquentAuditLogRepository::class);

        $this->app->bind(EventBusContract::class, fn ($app): LaravelEventBus => new LaravelEventBus($app->make(Dispatcher::class)));
    }

    public function boot(): void
    {
        // __DIR__ = src/Auth/User/Infrastructure/Providers; three levels up is src/Auth
        $authPath = dirname(__DIR__, 3);

        $this->loadMigrationsFrom($authPath.'/User/Infrastructure/Persistence/Migrations');
        $this->loadMigrationsFrom($authPath.'/Role/Infrastructure/Persistence/Migrations');
        $this->loadMigrationsFrom($authPath.'/AuditLog/Infrastructure/Persistence/Migrations');
    }
}
