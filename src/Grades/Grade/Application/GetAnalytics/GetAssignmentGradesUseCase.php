<?php

declare(strict_types=1);

namespace Grades\Grade\Application\GetAnalytics;

use Grades\Grade\Domain\Ports\NeoAssignmentGradeAnalyticsRepositoryContract;

final readonly class GetAssignmentGradesUseCase
{
    public function __construct(
        private NeoAssignmentGradeAnalyticsRepositoryContract $repository,
    ) {}

    public function execute(int $classId, int $assignmentId): ?AssignmentGradesResult
    {
        $data = $this->repository->getAssignmentGrades($classId, $assignmentId);

        if ($data === null) {
            return null;
        }

        return new AssignmentGradesResult(
            assignment: $data['assignment'],
            stats: $data['stats'],
            grades: $data['grades'],
        );
    }
}
