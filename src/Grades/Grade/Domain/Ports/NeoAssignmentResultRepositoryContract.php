<?php

declare(strict_types=1);

namespace Grades\Grade\Domain\Ports;

use Grades\Grade\Application\DTOs\NeoAssignmentResultDTO;

interface NeoAssignmentResultRepositoryContract
{
    public function upsert(NeoAssignmentResultDTO $dto): void;

    public function hasChanged(int $neoResultId, string $checksum): bool;

    /**
     * Mapa neo_user_id → sis_id desde neo_users, para desnormalizar sin N+1.
     *
     * @return array<int, string|null>
     */
    public function getUserSisIdMap(): array;
}
