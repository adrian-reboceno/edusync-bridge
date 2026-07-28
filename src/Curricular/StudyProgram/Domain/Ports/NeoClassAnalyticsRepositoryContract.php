<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Domain\Ports;

use Curricular\StudyProgram\Application\GetAnalytics\ClassesSummaryResult;

interface NeoClassAnalyticsRepositoryContract
{
    public function getSummary(): ClassesSummaryResult;

    /**
     * @return array{data: array[], total: int}
     */
    public function getClassesList(int $page, int $perPage): array;

    /**
     * @return array{class: array, summary: array}|null
     */
    public function getClassDetail(int $neoId): ?array;

    /**
     * @return array[]
     */
    public function getClassTeachers(int $neoId): array;

    /**
     * @return array[]
     */
    public function getClassStudents(int $neoId): array;
}
