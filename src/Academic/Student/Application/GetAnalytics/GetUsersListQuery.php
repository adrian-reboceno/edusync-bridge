<?php

declare(strict_types=1);

namespace Academic\Student\Application\GetAnalytics;

final readonly class GetUsersListQuery
{
    public function __construct(
        public int $page,
        public int $perPage,
        public ?string $role,
        public ?bool $activated,
        public ?string $search,
        public string $orderBy,
        public string $orderDir,
    ) {}
}
