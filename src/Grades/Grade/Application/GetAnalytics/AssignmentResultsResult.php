<?php

declare(strict_types=1);

namespace Grades\Grade\Application\GetAnalytics;

final readonly class AssignmentResultsResult
{
    public function __construct(
        public array $assignment,
        public int $totalResults,
        public array $results,
    ) {}
}
