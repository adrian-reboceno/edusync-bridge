<?php

declare(strict_types=1);

namespace Infrastructure\Providers;

use Academic\Student\Application\SyncNeoUsers\SyncNeoUsersUseCase;
use Academic\Student\Domain\Ports\NeoUserAnalyticsRepositoryContract;
use Academic\Student\Domain\Ports\NeoUserRepositoryContract;
use Academic\Student\Infrastructure\Persistence\Eloquent\EloquentNeoUserAnalyticsRepository;
use Academic\Student\Infrastructure\Persistence\Eloquent\EloquentNeoUserRepository;
use Illuminate\Support\ServiceProvider;
use NeoLms\NeoSync\Domain\Ports\NeoLmsApiContract;
use NeoLms\NeoSync\Infrastructure\Http\NeoApiAdapter\NeoHttpAdapter;

final class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NeoLmsApiContract::class, function (): NeoHttpAdapter {
            return new NeoHttpAdapter(
                baseUrl: (string) config('integration.neo_lms.base_url'),
                apiKey: (string) config('integration.neo_lms.api_key'),
                timeout: (int) config('integration.neo_lms.timeout', 30),
                pageSize: (int) config('integration.neo_lms.page_size', 100),
                retryTimes: (int) config('integration.neo_lms.retry_times', 3),
                retrySleepMs: (int) config('integration.neo_lms.retry_sleep', 2000),
            );
        });

        $this->app->bind(NeoUserRepositoryContract::class, EloquentNeoUserRepository::class);
        $this->app->bind(SyncNeoUsersUseCase::class, SyncNeoUsersUseCase::class);
        $this->app->bind(NeoUserAnalyticsRepositoryContract::class, EloquentNeoUserAnalyticsRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            base_path('src/NeoLms/NeoSync/Infrastructure/Persistence/Migrations')
        );
        $this->loadMigrationsFrom(
            base_path('src/Scheduler/JobScheduler/Infrastructure/Persistence/Migrations')
        );
        $this->loadMigrationsFrom(
            base_path('src/Academic/Student/Infrastructure/Persistence/Migrations')
        );
    }
}
