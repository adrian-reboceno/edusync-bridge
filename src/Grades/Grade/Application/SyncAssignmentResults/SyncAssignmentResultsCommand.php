<?php

declare(strict_types=1);

namespace Grades\Grade\Application\SyncAssignmentResults;

final readonly class SyncAssignmentResultsCommand
{
    public function __construct(
        public ?int $classId = null,
        public ?int $assignmentId = null,
        public string $triggeredBy = 'scheduler',
    ) {}
}
