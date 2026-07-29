<?php

declare(strict_types=1);

namespace Grades\Grade\Domain\Ports;

interface NeoAssignmentGradeAnalyticsRepositoryContract
{
    /**
     * @return array{assignment: array, stats: array, grades: array[]}|null
     */
    public function getAssignmentGrades(int $classId, int $assignmentId): ?array;

    /**
     * @return array{user: array, summary: array, grades: array[]}|null
     */
    public function getUserGrades(int $neoId): ?array;
}
