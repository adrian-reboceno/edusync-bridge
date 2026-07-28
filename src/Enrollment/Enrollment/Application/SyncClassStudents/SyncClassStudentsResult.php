<?php

declare(strict_types=1);

namespace Enrollment\Enrollment\Application\SyncClassStudents;

final readonly class SyncClassStudentsResult
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
