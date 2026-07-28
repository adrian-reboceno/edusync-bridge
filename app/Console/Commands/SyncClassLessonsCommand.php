<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Curricular\CurriculumMap\Application\SyncClassLessons\SyncClassLessonsCommand as SyncClassLessonsInput;
use Curricular\CurriculumMap\Application\SyncClassLessons\SyncClassLessonsUseCase;
use Illuminate\Console\Command;

final class SyncClassLessonsCommand extends Command
{
    protected $signature = 'neo:sync-class-lessons {--class= : ID de clase específica}';

    protected $description = 'Sincroniza lessons y sections por clase desde NEO LMS';

    public function handle(SyncClassLessonsUseCase $useCase): int
    {
        $this->info('Sincronizando lessons y sections...');

        $classIds = $this->option('class') !== null ? [(int) $this->option('class')] : null;

        $result = $useCase->execute(new SyncClassLessonsInput(
            classIds: $classIds,
            triggeredBy: 'artisan',
        ));

        $this->table(
            ['Lessons sincronizadas', 'Sin cambios', 'Errores'],
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
