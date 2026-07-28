<?php

declare(strict_types=1);

namespace Enrollment\Enrollment\Domain\Ports;

use Enrollment\Enrollment\Application\DTOs\NeoClassTeacherDTO;

interface NeoClassTeacherRepositoryContract
{
    /**
     * Inserta o actualiza un docente de clase en neo_class_teachers.
     * Usa INSERT ... ON CONFLICT (neo_teacher_record_id) DO UPDATE para idempotencia.
     */
    public function upsertTeacher(NeoClassTeacherDTO $dto): void;
}
