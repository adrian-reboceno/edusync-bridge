<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Application\GetAnalytics;

final readonly class GetClassesListResult
{
    public function __construct(
        public array $data,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function totalPages(): int
    {
        return $this->perPage > 0 ? (int) ceil($this->total / $this->perPage) : 0;
    }
}
