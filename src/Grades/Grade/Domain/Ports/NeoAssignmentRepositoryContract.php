<?php

declare(strict_types=1);

namespace Grades\Grade\Domain\Ports;

use Grades\Grade\Application\DTOs\NeoAssignmentDTO;

interface NeoAssignmentRepositoryContract
{
    public function upsertAssignment(NeoAssignmentDTO $dto): void;

    public function hasChanged(int $assignmentId, string $checksum): bool;
}
