<?php

declare(strict_types=1);

namespace Academic\Student\Application\GetAnalytics;

use Academic\Student\Domain\Ports\NeoUserAnalyticsRepositoryContract;

final readonly class GetUsersSummaryUseCase
{
    public function __construct(
        private NeoUserAnalyticsRepositoryContract $repository,
    ) {}

    public function execute(): UsersSummaryResult
    {
        return $this->repository->getSummary();
    }
}
