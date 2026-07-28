<?php

declare(strict_types=1);

namespace Grades\Grade\Application\GetAnalytics;

final readonly class ClassAssignmentsResult
{
    public function __construct(
        public int $classId,
        public string $className,
        public array $totals,
        public array $assignments,
    ) {}
}
