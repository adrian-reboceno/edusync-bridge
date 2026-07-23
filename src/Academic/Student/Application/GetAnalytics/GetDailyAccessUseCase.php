<?php

declare(strict_types=1);

namespace Academic\Student\Application\GetAnalytics;

use Academic\Student\Domain\Ports\NeoUserAnalyticsRepositoryContract;
use DateTimeImmutable;

final readonly class GetDailyAccessUseCase
{
    public function __construct(
        private NeoUserAnalyticsRepositoryContract $repository,
    ) {}

    public function execute(GetDailyAccessQuery $query): DailyAccessResult
    {
        $daily = $this->repository->getDailyAccess($query->days, $query->userId);
        $totals = $this->repository->getDailyAccessTotals($query->days, $query->userId);

        $to = new DateTimeImmutable('today');
        $from = $to->modify("-{$query->days} days");

        $totalSessions = (int) $totals['total_sessions'];

        return new DailyAccessResult(
            from: $from->format('Y-m-d'),
            to: $to->format('Y-m-d'),
            days: $query->days,
            daily: $daily,
            totalSessions: $totalSessions,
            uniqueUsers: (int) $totals['unique_users'],
            totalMinutes: $totals['total_minutes'],
            avgSessionsPerDay: $query->days > 0 ? round($totalSessions / $query->days, 1) : 0.0,
        );
    }
}
