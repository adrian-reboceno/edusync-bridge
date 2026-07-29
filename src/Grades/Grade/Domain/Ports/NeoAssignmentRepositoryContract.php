<?php

declare(strict_types=1);

namespace Grades\Grade\Domain\Ports;

use Grades\Grade\Application\DTOs\NeoAssignmentDTO;

interface NeoAssignmentRepositoryContract
{
    public function upsertAssignment(NeoAssignmentDTO $dto): void;

    public function hasChanged(int $assignmentId, string $checksum): bool;

    /**
     * Todos los assignments conocidos, para iterar y extraer sus grades.
     *
     * @return array<int, array{neo_assignment_id: int, neo_class_id: int}>
     */
    public function getAllAssignments(): array;

    /**
     * Assignments de una clase específica.
     *
     * @return array<int, array{neo_assignment_id: int, neo_class_id: int}>
     */
    public function getAssignmentsByClass(int $classId): array;
}
