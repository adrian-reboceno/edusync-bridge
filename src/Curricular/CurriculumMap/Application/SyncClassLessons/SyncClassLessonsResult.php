<?php

declare(strict_types=1);

namespace Curricular\CurriculumMap\Application\SyncClassLessons;

final readonly class SyncClassLessonsResult
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
