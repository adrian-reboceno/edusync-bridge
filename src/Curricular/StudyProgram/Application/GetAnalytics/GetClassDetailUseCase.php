<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Application\GetAnalytics;

use Curricular\StudyProgram\Domain\Ports\NeoClassAnalyticsRepositoryContract;

final readonly class GetClassDetailUseCase
{
    public function __construct(
        private NeoClassAnalyticsRepositoryContract $repository,
    ) {}

    public function execute(int $neoId): ?ClassDetailResult
    {
        $class = $this->repository->getClassDetail($neoId);

        if ($class === null) {
            return null;
        }

        return new ClassDetailResult(
            class: $class['class'],
            summary: $class['summary'],
            teachers: $this->repository->getClassTeachers($neoId),
            students: $this->repository->getClassStudents($neoId),
        );
    }
}
