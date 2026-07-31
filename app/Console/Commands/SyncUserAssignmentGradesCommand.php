<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Grades\Grade\Application\SyncUserAssignmentGrades\SyncUserAssignmentGradesCommand as SyncUserAssignmentGradesInput;
use Grades\Grade\Application\SyncUserAssignmentGrades\SyncUserAssignmentGradesUseCase;
use Illuminate\Console\Command;

final class SyncUserAssignmentGradesCommand extends Command
{
    protected $signature = 'neo:sync-user-grades
                          {--user= : ID de usuario específico}';

    protected $description = 'Sincroniza grades con timestamps y calcula métricas de seguimiento por alumno';

    public function handle(SyncUserAssignmentGradesUseCase $useCase): int
    {
        $this->info('Sincronizando grades y calculando métricas por usuario...');

        $result = $useCase->execute(new SyncUserAssignmentGradesInput(
            userId: $this->option('user') !== null ? (int) $this->option('user') : null,
            triggeredBy: 'artisan',
        ));

        $this->table(
            ['Grades actualizados', 'Sin cambios', 'Errores'],
            [[$result->synced, $result->skipped, count($result->errors)]],
        );

        if ($result->hasErrors()) {
            foreach ($result->errors as $err) {
                $this->error("  user_id={$err['user_id']}: {$err['message']}");
            }

            return self::FAILURE;
        }

        $this->info('Métricas calculadas y guardadas en neo_assignment_grade_metrics.');

        return self::SUCCESS;
    }
}
