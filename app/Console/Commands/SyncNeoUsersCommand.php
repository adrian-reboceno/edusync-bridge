<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Academic\Student\Application\SyncNeoUsers\SyncNeoUsersCommand as SyncNeoUsersInput;
use Academic\Student\Application\SyncNeoUsers\SyncNeoUsersUseCase;
use Illuminate\Console\Command;

final class SyncNeoUsersCommand extends Command
{
    protected $signature = 'neo:sync-users {--org= : ID de organización} {--dry-run : Solo muestra cuántos usuarios se procesarían}';

    protected $description = 'Sincroniza usuarios desde NEO LMS API a neo_users para analítica';

    public function handle(SyncNeoUsersUseCase $useCase): int
    {
        $orgId = $this->option('org') !== null ? (int) $this->option('org') : null;
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Sincronizando usuarios desde NEO LMS...');

        if ($dryRun) {
            $this->warn('[DRY RUN] No se guardarán cambios');

            return self::SUCCESS;
        }

        $result = $useCase->execute(new SyncNeoUsersInput(
            organizationId: $orgId,
            triggeredBy: 'artisan',
        ));

        $this->table(
            ['Total', 'Sincronizados', 'Sin cambios', 'Errores'],
            [[$result->total, $result->synced, $result->skipped, count($result->errors)]]
        );

        if ($result->hasErrors()) {
            $this->error('Errores:');
            foreach ($result->errors as $err) {
                $this->line("  neo_id={$err['neo_id']}: {$err['message']}");
            }

            return self::FAILURE;
        }

        $this->info('Sincronización completada exitosamente.');

        return self::SUCCESS;
    }
}
