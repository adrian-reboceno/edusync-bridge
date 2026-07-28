<?php

declare(strict_types=1);

namespace Enrollment\Enrollment\Application\SyncClassTeachers;

final readonly class SyncClassTeachersResult
{
    public function __construct(
        public int $synced,
        public array $errors,
    ) {}

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}
