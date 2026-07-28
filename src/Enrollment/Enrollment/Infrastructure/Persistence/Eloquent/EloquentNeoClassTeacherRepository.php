<?php

declare(strict_types=1);

namespace Enrollment\Enrollment\Infrastructure\Persistence\Eloquent;

use Enrollment\Enrollment\Application\DTOs\NeoClassTeacherDTO;
use Enrollment\Enrollment\Domain\Ports\NeoClassTeacherRepositoryContract;
use Illuminate\Support\Facades\DB;

final class EloquentNeoClassTeacherRepository implements NeoClassTeacherRepositoryContract
{
    public function upsertTeacher(NeoClassTeacherDTO $dto): void
    {
        $data = [
            'neo_teacher_record_id' => $dto->neoTeacherRecordId,
            'neo_user_id' => $dto->neoUserId,
            'neo_class_id' => $dto->neoClassId,
            'coteacher' => $dto->coteacher,
            'last_visited_at' => $dto->lastVisitedAt,
            'synced_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('neo_class_teachers')->upsert(
            array_merge($data, ['created_at' => now()]),
            ['neo_teacher_record_id'],
            array_keys($data),
        );
    }
}
