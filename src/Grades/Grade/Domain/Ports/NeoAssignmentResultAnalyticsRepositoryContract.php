<?php

declare(strict_types=1);

namespace Grades\Grade\Domain\Ports;

interface NeoAssignmentResultAnalyticsRepositoryContract
{
    /**
     * @return array{assignment: array, total_results: int, results: array[]}|null
     */
    public function getAssignmentResults(int $classId, int $assignmentId): ?array;
}
