<?php

declare(strict_types=1);

namespace Infrastructure\Providers;

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
    }
    
    public function boot(): void
    {
        $this->loadMigrationsFrom(
            base_path('src/NeoLms/NeoSync/Infrastructure/Persistence/Migrations')
        );
        $this->loadMigrationsFrom(
            base_path('src/Scheduler/JobScheduler/Infrastructure/Persistence/Migrations')
        );
    }
}
