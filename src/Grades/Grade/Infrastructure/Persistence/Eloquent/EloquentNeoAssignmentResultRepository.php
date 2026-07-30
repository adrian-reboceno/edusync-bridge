<?php

declare(strict_types=1);

namespace Grades\Grade\Infrastructure\Persistence\Eloquent;

use Grades\Grade\Application\DTOs\NeoAssignmentResultDTO;
use Grades\Grade\Domain\Ports\NeoAssignmentResultRepositoryContract;
use Illuminate\Support\Facades\DB;

final class EloquentNeoAssignmentResultRepository implements NeoAssignmentResultRepositoryContract
{
    public function upsert(NeoAssignmentResultDTO $dto): void
    {
        $data = [
            'neo_result_id' => $dto->neoResultId,
            'neo_grade_id' => $dto->neoGradeId,
            'neo_user_id' => $dto->neoUserId,
            'neo_class_id' => $dto->neoClassId,
            'neo_assignment_id' => $dto->neoAssignmentId,
            'sis_id' => $dto->sisId,
            'question_id' => $dto->questionId,
            'position' => $dto->position,
            'response' => $dto->response,
            'points' => $dto->points,
            'score' => $dto->score,
            'grade' => $dto->grade,
            'checksum' => $dto->checksum(),
            'synced_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('neo_assignment_results')->upsert(
            array_merge($data, ['created_at' => now()]),
            ['neo_result_id'],
            array_keys($data),
        );
    }

    public function hasChanged(int $neoResultId, string $checksum): bool
    {
        $storedChecksum = DB::table('neo_assignment_results')
            ->where('neo_result_id', $neoResultId)
            ->value('checksum');

        return $storedChecksum !== $checksum;
    }

    public function getUserSisIdMap(): array
    {
        return DB::table('neo_users')
            ->whereNotNull('sis_id')
            ->pluck('sis_id', 'neo_id')
            ->all();
    }
}
