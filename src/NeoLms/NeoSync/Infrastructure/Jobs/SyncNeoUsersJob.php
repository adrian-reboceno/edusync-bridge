<?php

declare(strict_types=1);

namespace NeoLms\NeoSync\Infrastructure\Jobs;

use Academic\Student\Application\SyncNeoUsers\SyncNeoUsersCommand;
use Academic\Student\Application\SyncNeoUsers\SyncNeoUsersUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SyncNeoUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public int $backoff = 60;

    public string $queue = 'neo-sync-default';

    public function __construct(
        public readonly ?int $organizationId = null,
        public readonly string $triggeredBy = 'scheduler',
    ) {}

    public function handle(SyncNeoUsersUseCase $useCase): void
    {
        Log::channel('sync')->info('SyncNeoUsersJob iniciado', [
            'triggered_by' => $this->triggeredBy,
            'organization_id' => $this->organizationId,
        ]);

        $start = microtime(true);
        $result = $useCase->execute(new SyncNeoUsersCommand(
            organizationId: $this->organizationId,
            triggeredBy: $this->triggeredBy,
        ));

        $ms = round((microtime(true) - $start) * 1000);

        Log::channel('sync')->info('SyncNeoUsersJob completado', [
            'total' => $result->total,
            'synced' => $result->synced,
            'skipped' => $result->skipped,
            'errors' => count($result->errors),
            'ms' => $ms,
        ]);

        if ($result->hasErrors()) {
            Log::channel('sync')->warning('SyncNeoUsersJob errores parciales', [
                'errors' => $result->errors,
            ]);
        }
    }

    public function failed(Throwable $e): void
    {
        Log::channel('sync')->error('SyncNeoUsersJob falló', [
            'error' => $e->getMessage(),
            'triggered_by' => $this->triggeredBy,
        ]);
    }
}
