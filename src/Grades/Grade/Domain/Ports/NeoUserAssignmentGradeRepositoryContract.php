<?php

declare(strict_types=1);

namespace Grades\Grade\Domain\Ports;

use Grades\Grade\Application\DTOs\NeoUserAssignmentGradeDTO;

interface NeoUserAssignmentGradeRepositoryContract
{
    /**
     * IDs de alumnos activos (role=Student, archived=false).
     *
     * @return int[]
     */
    public function getActiveStudentIds(): array;

    public function getSisIdForUser(int $neoUserId): ?string;

    /**
     * Actualiza los timestamps detallados en neo_assignment_grades.
     */
    public function updateGradeTimestamps(NeoUserAssignmentGradeDTO $dto): void;

    /**
     * Inserta o actualiza métricas en neo_assignment_grade_metrics.
     * Conflict key: neo_grade_id.
     */
    public function upsertMetrics(NeoUserAssignmentGradeDTO $dto): void;

    /**
     * Métricas de un usuario por clase para el endpoint de analítica.
     */
    public function getMetricsByUserAndClass(int $neoUserId, int $neoClassId): array;
}
