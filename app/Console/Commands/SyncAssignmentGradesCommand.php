<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Grades\Grade\Application\SyncAssignmentGrades\SyncAssignmentGradesCommand as SyncAssignmentGradesInput;
use Grades\Grade\Application\SyncAssignmentGrades\SyncAssignmentGradesUseCase;
use Illuminate\Console\Command;

final class SyncAssignmentGradesCommand extends Command
{
    protected $signature = 'neo:sync-grades
                          {--class= : ID de clase específica}
                          {--assignment= : ID de assignment específico}';

    protected $description = 'Sincroniza calificaciones por assignment desde NEO LMS';

    public function handle(SyncAssignmentGradesUseCase $useCase): int
    {
        $this->info('Sincronizando calificaciones desde NEO LMS...');

        $result = $useCase->execute(new SyncAssignmentGradesInput(
            classId: $this->option('class') !== null ? (int) $this->option('class') : null,
            assignmentId: $this->option('assignment') !== null ? (int) $this->option('assignment') : null,
            triggeredBy: 'artisan',
        ));

        $this->table(
            ['Sincronizadas', 'Sin cambios', 'Errores'],
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
