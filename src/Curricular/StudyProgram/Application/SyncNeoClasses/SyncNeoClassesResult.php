<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Application\SyncNeoClasses;

final readonly class SyncNeoClassesResult
{
    public function __construct(
        public int $total,
        public int $synced,
        public int $skipped,
        public array $errors,
    ) {}

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}
