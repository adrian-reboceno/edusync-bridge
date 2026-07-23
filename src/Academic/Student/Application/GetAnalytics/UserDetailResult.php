<?php

declare(strict_types=1);

namespace Academic\Student\Application\GetAnalytics;

final readonly class UserDetailResult
{
    public function __construct(
        public array $user,
        public array $sessionsSummary,
        public array $dailyActivity,
        public array $sessions,
    ) {}
}
