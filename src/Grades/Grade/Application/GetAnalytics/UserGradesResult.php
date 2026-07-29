<?php

declare(strict_types=1);

namespace Grades\Grade\Application\GetAnalytics;

final readonly class UserGradesResult
{
    public function __construct(
        public array $user,
        public array $summary,
        public array $grades,
    ) {}
}
