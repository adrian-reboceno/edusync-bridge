<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Grades\Grade\Application\SyncAssignmentResults\SyncAssignmentResultsCommand as SyncAssignmentResultsInput;
use Grades\Grade\Application\SyncAssignmentResults\SyncAssignmentResultsUseCase;
use Illuminate\Console\Command;

final class SyncAssignmentResultsCommand extends Command
{
    protected $signature = 'neo:sync-assignment-results
                          {--class= : ID de clase específica}
                          {--assignment= : ID de assignment específico}';

    protected $description = 'Sincroniza resultados (respuestas individuales) de assignments desde NEO LMS';

    public function handle(SyncAssignmentResultsUseCase $useCase): int
    {
        $this->info('Sincronizando resultados de assignments desde NEO LMS...');

        $result = $useCase->execute(new SyncAssignmentResultsInput(
            classId: $this->option('class') !== null ? (int) $this->option('class') : null,
            assignmentId: $this->option('assignment') !== null ? (int) $this->option('assignment') : null,
            triggeredBy: 'artisan',
        ));

        $this->table(
            ['Sincronizados', 'Sin cambios', 'Errores'],
            [[$result->synced, $result->skipped, count($result->errors)]],
        );

        if ($result->hasErrors()) {
            foreach ($result->errors as $err) {
                $this->error("  class={$err['class_id']} assignment={$err['assignment_id']}: {$err['message']}");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
