<?php

declare(strict_types=1);

namespace Curricular\CurriculumMap\Application\GetAnalytics;

use Curricular\CurriculumMap\Domain\Ports\NeoLessonAnalyticsRepositoryContract;

final readonly class GetClassLessonsUseCase
{
    public function __construct(
        private NeoLessonAnalyticsRepositoryContract $repository,
    ) {}

    public function execute(int $classId): ?ClassLessonsResult
    {
        $data = $this->repository->getClassLessons($classId);

        if ($data === null) {
            return null;
        }

        return new ClassLessonsResult(
            classId: $data['class_id'],
            className: $data['class_name'],
            totalLessons: $data['total_lessons'],
            totalSections: $data['total_sections'],
            lessons: $data['lessons'],
        );
    }
}
