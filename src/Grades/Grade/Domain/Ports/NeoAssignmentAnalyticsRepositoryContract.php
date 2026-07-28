<?php

declare(strict_types=1);

namespace Grades\Grade\Domain\Ports;

interface NeoAssignmentAnalyticsRepositoryContract
{
    /**
     * @return array{class_id: int, class_name: string, totals: array, assignments: array[]}|null
     */
    public function getClassAssignments(int $classId): ?array;
}
