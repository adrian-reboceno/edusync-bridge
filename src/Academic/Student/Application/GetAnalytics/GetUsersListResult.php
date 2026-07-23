<?php

declare(strict_types=1);

namespace Academic\Student\Application\GetAnalytics;

final readonly class GetUsersListResult
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
