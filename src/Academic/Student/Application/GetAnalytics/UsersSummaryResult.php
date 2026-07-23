<?php

declare(strict_types=1);

namespace Academic\Student\Application\GetAnalytics;

final readonly class UsersSummaryResult
{
    public function __construct(
        public int $total,
        public int $activated,
        public int $neverLoggedIn,
        public int $archived,
        public float $activationRate,
        public int $students,
        public int $teachers,
        public int $administrators,
        public int $others,
        public ?int $organizationId,
        public ?string $organizationName,
        public int $totalSessions,
        public int $usersWithSessions,
        public float $avgSessionsPerUser,
        public ?string $lastSyncedAt,
    ) {}
}
