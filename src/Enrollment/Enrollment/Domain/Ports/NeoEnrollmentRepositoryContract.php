<?php

declare(strict_types=1);

namespace Enrollment\Enrollment\Domain\Ports;

use Enrollment\Enrollment\Application\DTOs\NeoEnrollmentDTO;

interface NeoEnrollmentRepositoryContract
{
    /**
     * Inserta o actualiza una inscripción en neo_enrollments.
     * Usa INSERT ... ON CONFLICT (neo_enrollment_id) DO UPDATE para idempotencia.
     */
    public function upsertEnrollment(NeoEnrollmentDTO $dto): void;

    /**
     * Verifica si la inscripción cambió comparando el checksum almacenado.
     */
    public function hasChanged(int $enrollmentId, string $checksum): bool;

    /**
     * Retorna el mapa neo_user_id => sis_id desde neo_users, para desnormalizar
     * la matrícula CE en neo_enrollments sin ida y vuelta a la API.
     *
     * @return array<int, string|null>
     */
    public function getSisIdMap(): array;

    /**
     * Retorna el estado actual de una inscripción en neo_enrollments ANTES del upsert.
     * Usado para comparar y detectar cambios de progreso y de estado. Null si es la
     * primera vez que se ve esta inscripción.
     */
    public function findCurrentState(int $neoUserId, int $neoClassId): ?object;

    /**
     * Inserta un registro de historial de progreso (append-only).
     * Solo se llama cuando se detectó un cambio de progreso.
     */
    public function insertProgressHistory(NeoEnrollmentDTO $current, ?object $previous): void;

    /**
     * Inserta un registro de historial de estado (append-only).
     * Un registro por evento detectado.
     */
    public function insertStatusHistory(NeoEnrollmentDTO $current, string $event): void;
}
