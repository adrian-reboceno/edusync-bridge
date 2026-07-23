<?php

declare(strict_types=1);

namespace Academic\Student\Application\GetAnalytics;

use Academic\Student\Domain\Ports\NeoUserAnalyticsRepositoryContract;

final readonly class GetUserDetailUseCase
{
    public function __construct(
        private NeoUserAnalyticsRepositoryContract $repository,
    ) {}

    public function execute(int $neoId): ?UserDetailResult
    {
        $user = $this->repository->getUserDetail($neoId);

        if ($user === null) {
            return null;
        }

        return new UserDetailResult(
            user: $user,
            sessionsSummary: $this->repository->getUserSessionsSummary($neoId),
            dailyActivity: $this->repository->getUserDailyActivity($neoId),
            sessions: $this->repository->getUserSessions($neoId),
        );
    }
}
