<?php

declare(strict_types=1);

namespace Grades\Grade\Application\SyncClassAssignments;

final readonly class SyncClassAssignmentsCommand
{
    /**
     * @param  int[]|null  $classIds
     */
    public function __construct(
        public ?array $classIds = null,
        public string $triggeredBy = 'scheduler',
    ) {}
}
