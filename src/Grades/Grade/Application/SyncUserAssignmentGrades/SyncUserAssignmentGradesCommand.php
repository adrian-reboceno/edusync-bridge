<?php

declare(strict_types=1);

namespace Grades\Grade\Application\SyncUserAssignmentGrades;

final readonly class SyncUserAssignmentGradesCommand
{
    public function __construct(
        public ?int $userId = null,
        public string $triggeredBy = 'scheduler',
    ) {}
}
