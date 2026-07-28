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
}
