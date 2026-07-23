<?php

declare(strict_types=1);

namespace Academic\Student\Application\GetAnalytics;

use Academic\Student\Domain\Ports\NeoUserAnalyticsRepositoryContract;

final readonly class GetUsersListUseCase
{
    public function __construct(
        private NeoUserAnalyticsRepositoryContract $repository,
    ) {}

    public function execute(GetUsersListQuery $query): GetUsersListResult
    {
        $result = $this->repository->getUsersList(
            page: $query->page,
            perPage: $query->perPage,
            role: $query->role,
            activated: $query->activated,
            search: $query->search,
            orderBy: $query->orderBy,
            orderDir: $query->orderDir,
        );

        return new GetUsersListResult(
            data: $result['data'],
            total: $result['total'],
            page: $query->page,
            perPage: $query->perPage,
        );
    }
}
