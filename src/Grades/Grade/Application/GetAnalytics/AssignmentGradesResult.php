<?php

declare(strict_types=1);

namespace Grades\Grade\Application\GetAnalytics;

final readonly class AssignmentGradesResult
{
    public function __construct(
        public array $assignment,
        public array $stats,
        public array $grades,
    ) {}
}
