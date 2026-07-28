<?php

declare(strict_types=1);

namespace Grades\Grade\Application\GetAnalytics;

use Grades\Grade\Domain\Ports\NeoAssignmentAnalyticsRepositoryContract;

final readonly class GetClassAssignmentsUseCase
{
    public function __construct(
        private NeoAssignmentAnalyticsRepositoryContract $repository,
    ) {}

    public function execute(int $classId): ?ClassAssignmentsResult
    {
        $data = $this->repository->getClassAssignments($classId);

        if ($data === null) {
            return null;
        }

        return new ClassAssignmentsResult(
            classId: $data['class_id'],
            className: $data['class_name'],
            totals: $data['totals'],
            assignments: $data['assignments'],
        );
    }
}
