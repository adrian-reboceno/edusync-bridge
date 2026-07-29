<?php

declare(strict_types=1);

namespace Grades\Grade\Application\GetAnalytics;

use Grades\Grade\Domain\Ports\NeoAssignmentGradeAnalyticsRepositoryContract;

final readonly class GetUserGradesUseCase
{
    public function __construct(
        private NeoAssignmentGradeAnalyticsRepositoryContract $repository,
    ) {}

    public function execute(int $neoId): ?UserGradesResult
    {
        $data = $this->repository->getUserGrades($neoId);

        if ($data === null) {
            return null;
        }

        return new UserGradesResult(
            user: $data['user'],
            summary: $data['summary'],
            grades: $data['grades'],
        );
    }
}
