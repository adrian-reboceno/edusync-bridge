<?php

declare(strict_types=1);

namespace Curricular\CurriculumMap\Application\GetAnalytics;

final readonly class ClassLessonsResult
{
    public function __construct(
        public int $classId,
        public string $className,
        public int $totalLessons,
        public int $totalSections,
        public array $lessons,
    ) {}
}
