<?php

declare(strict_types=1);

namespace Curricular\CurriculumMap\Application\SyncClassLessons;

final readonly class SyncClassLessonsCommand
{
    /**
     * @param  int[]|null  $classIds
     */
    public function __construct(
        public ?array $classIds = null,
        public string $triggeredBy = 'scheduler',
    ) {}
}
