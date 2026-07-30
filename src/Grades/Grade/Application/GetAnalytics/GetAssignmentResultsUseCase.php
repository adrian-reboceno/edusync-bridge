<?php

declare(strict_types=1);

namespace Grades\Grade\Application\GetAnalytics;

use Grades\Grade\Domain\Ports\NeoAssignmentResultAnalyticsRepositoryContract;

final readonly class GetAssignmentResultsUseCase
{
    public function __construct(
        private NeoAssignmentResultAnalyticsRepositoryContract $repository,
    ) {}

    public function execute(int $classId, int $assignmentId): ?AssignmentResultsResult
    {
        $data = $this->repository->getAssignmentResults($classId, $assignmentId);

        if ($data === null) {
            return null;
        }

        return new AssignmentResultsResult(
            assignment: $data['assignment'],
            totalResults: $data['total_results'],
            results: $data['results'],
        );
    }
}
