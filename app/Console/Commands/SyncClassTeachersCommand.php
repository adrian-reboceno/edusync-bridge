<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Enrollment\Enrollment\Application\SyncClassTeachers\SyncClassTeachersCommand as SyncClassTeachersInput;
use Enrollment\Enrollment\Application\SyncClassTeachers\SyncClassTeachersUseCase;
use Illuminate\Console\Command;

final class SyncClassTeachersCommand extends Command
{
    protected $signature = 'neo:sync-class-teachers {--class= : ID de clase específica}';

    protected $description = 'Sincroniza docentes por clase desde NEO LMS API a neo_class_teachers';

    public function handle(SyncClassTeachersUseCase $useCase): int
    {
        $this->info('Sincronizando docentes por clase...');

        $classIds = $this->option('class') !== null ? [(int) $this->option('class')] : null;

        $result = $useCase->execute(new SyncClassTeachersInput(
            classIds: $classIds,
            triggeredBy: 'artisan',
        ));

        $this->table(
            ['Sincronizados', 'Errores'],
            [[$result->synced, count($result->errors)]],
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
