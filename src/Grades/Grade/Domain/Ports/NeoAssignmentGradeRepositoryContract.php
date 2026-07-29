<?php

declare(strict_types=1);

namespace Grades\Grade\Domain\Ports;

use Grades\Grade\Application\DTOs\NeoAssignmentGradeDTO;

interface NeoAssignmentGradeRepositoryContract
{
    public function upsert(NeoAssignmentGradeDTO $dto): void;

    public function hasChanged(int $neoGradeId, string $checksum): bool;

    /**
     * Mapa neo_user_id → sis_id desde neo_users, para desnormalizar sin N+1.
     *
     * @return array<int, string|null>
     */
    public function getUserSisIdMap(): array;
}
