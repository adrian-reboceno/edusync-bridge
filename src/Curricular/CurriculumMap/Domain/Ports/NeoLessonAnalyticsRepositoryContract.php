<?php

declare(strict_types=1);

namespace Curricular\CurriculumMap\Domain\Ports;

interface NeoLessonAnalyticsRepositoryContract
{
    /**
     * @return array{class_id: int, class_name: string, total_lessons: int, total_sections: int, lessons: array[]}|null
     */
    public function getClassLessons(int $classId): ?array;
}
