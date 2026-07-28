<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Application\SyncNeoClasses;

final readonly class SyncNeoClassesCommand
{
    public function __construct(
        public ?int $organizationId = null,
        public bool $includeArchived = false,
        public string $triggeredBy = 'scheduler',
    ) {}
}
