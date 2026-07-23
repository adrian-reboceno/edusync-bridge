<?php

declare(strict_types=1);

namespace Academic\Student\Application\GetAnalytics;

final readonly class DailyAccessResult
{
    public function __construct(
        public string $from,
        public string $to,
        public int $days,
        public array $daily,
        public int $totalSessions,
        public int $uniqueUsers,
        public ?float $totalMinutes,
        public float $avgSessionsPerDay,
    ) {}
}
