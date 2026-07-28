<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Grades\Grade\Application\SyncClassAssignments\SyncClassAssignmentsCommand as SyncClassAssignmentsInput;
use Grades\Grade\Application\SyncClassAssignments\SyncClassAssignmentsUseCase;
use Illuminate\Console\Command;

final class SyncClassAssignmentsCommand extends Command
{
    protected $signature = 'neo:sync-class-assignments {--class= : ID de clase específica}';

    protected $description = 'Sincroniza assignments por clase desde NEO LMS';

    public function handle(SyncClassAssignmentsUseCase $useCase): int
    {
        $this->info('Sincronizando assignments...');

        $classIds = $this->option('class') !== null ? [(int) $this->option('class')] : null;

        $result = $useCase->execute(new SyncClassAssignmentsInput(
            classIds: $classIds,
            triggeredBy: 'artisan',
        ));

        $this->table(
            ['Assignments sincronizados', 'Sin cambios', 'Errores'],
            [[$result->synced, $result->skipped, count($result->errors)]],
        );

        if ($result->hasErrors()) {
            foreach ($result->errors as $err) {
                $this->error("  class_id={$err['class_id']}: {$err['message']}");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
