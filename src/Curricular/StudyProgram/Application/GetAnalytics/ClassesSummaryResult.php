<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Application\GetAnalytics;

final readonly class ClassesSummaryResult
{
    public function __construct(
        public int $totalClasses,
        public int $active,
        public int $archived,
        public int $totalEnrollments,
        public int $totalTeachers,
        public array $byOrganization,
        public array $byStyle,
        public ?string $lastSyncedAt,
    ) {}
}
