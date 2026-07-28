<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Curricular\StudyProgram\Application\SyncNeoClasses\SyncNeoClassesCommand as SyncNeoClassesInput;
use Curricular\StudyProgram\Application\SyncNeoClasses\SyncNeoClassesUseCase;
use Illuminate\Console\Command;

final class SyncNeoClassesCommand extends Command
{
    protected $signature = 'neo:sync-classes {--org= : ID de organización} {--archived : incluir archivadas}';

    protected $description = 'Sincroniza clases desde NEO LMS API a neo_classes';

    public function handle(SyncNeoClassesUseCase $useCase): int
    {
        $this->info('Sincronizando clases desde NEO LMS...');

        $result = $useCase->execute(new SyncNeoClassesInput(
            organizationId: $this->option('org') !== null ? (int) $this->option('org') : null,
            includeArchived: (bool) $this->option('archived'),
            triggeredBy: 'artisan',
        ));

        $this->table(
            ['Total', 'Sincronizadas', 'Sin cambios', 'Errores'],
            [[$result->total, $result->synced, $result->skipped, count($result->errors)]],
        );

        if ($result->hasErrors()) {
            foreach ($result->errors as $err) {
                $this->error("  neo_id={$err['neo_id']}: {$err['message']}");
            }

            return self::FAILURE;
        }

        $this->info('Clases sincronizadas correctamente.');

        return self::SUCCESS;
    }
}
