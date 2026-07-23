<?php

declare(strict_types=1);

namespace Academic\Student\Application\GetAnalytics;

final readonly class GetDailyAccessQuery
{
    public function __construct(
        public int $days,
        public ?int $userId = null,
    ) {}
}
