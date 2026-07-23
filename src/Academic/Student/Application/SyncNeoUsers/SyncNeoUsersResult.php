<?php

declare(strict_types=1);

namespace Academic\Student\Application\SyncNeoUsers;

final readonly class SyncNeoUsersResult
{
    public function __construct(
        public int   $synced,
        public int   $skipped,
        public array $errors,
        public int   $total,
    ) {}

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}
