<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Domain\Ports;

use Curricular\StudyProgram\Application\DTOs\NeoClassDTO;

interface NeoClassRepositoryContract
{
    /**
     * Inserta o actualiza una clase en neo_classes.
     * Usa INSERT ... ON CONFLICT (neo_id) DO UPDATE para idempotencia.
     */
    public function upsertClass(NeoClassDTO $dto): void;

    /**
     * Verifica si la clase cambió comparando el checksum almacenado.
     */
    public function hasChanged(int $neoId, string $checksum): bool;

    /**
     * Retorna los neo_id de todas las clases no archivadas.
     *
     * @return int[]
     */
    public function getAllActiveClassIds(): array;

    public function findById(int $neoId): ?object;
}
