<?php

declare(strict_types=1);

namespace Grades\Grade\Application\SyncUserAssignmentGrades;

final readonly class SyncUserAssignmentGradesResult
{
    public function __construct(
        public int $synced,
        public int $skipped,
        public array $errors,
    ) {}

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}
