<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Enrollment\Enrollment\Application\SyncClassStudents\SyncClassStudentsCommand as SyncClassStudentsInput;
use Enrollment\Enrollment\Application\SyncClassStudents\SyncClassStudentsUseCase;
use Illuminate\Console\Command;

final class SyncClassStudentsCommand extends Command
{
    protected $signature = 'neo:sync-class-students {--class= : ID de clase específica}';

    protected $description = 'Sincroniza inscripciones de alumnos desde NEO LMS API a neo_enrollments';

    public function handle(SyncClassStudentsUseCase $useCase): int
    {
        $this->info('Sincronizando alumnos por clase...');

        $classIds = $this->option('class') !== null ? [(int) $this->option('class')] : null;

        $result = $useCase->execute(new SyncClassStudentsInput(
            classIds: $classIds,
            triggeredBy: 'artisan',
        ));

        $this->table(
            ['Sincronizados', 'Sin cambios', 'Errores'],
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
