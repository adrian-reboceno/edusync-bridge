<?php

declare(strict_types=1);

namespace Grades\Grade\Infrastructure\Persistence\Eloquent;

use Grades\Grade\Application\DTOs\NeoAssignmentDTO;
use Grades\Grade\Domain\Ports\NeoAssignmentRepositoryContract;
use Illuminate\Support\Facades\DB;

final class EloquentNeoAssignmentRepository implements NeoAssignmentRepositoryContract
{
    public function upsertAssignment(NeoAssignmentDTO $dto): void
    {
        $data = [
            'neo_assignment_id' => $dto->neoAssignmentId,
            'neo_class_id' => $dto->neoClassId,
            'neo_lesson_id' => $dto->neoLessonId,
            'lesson_name' => $dto->lessonName,
            'creator_id' => $dto->creatorId,
            'type' => $dto->type,
            'name' => $dto->name,
            'points' => $dto->points,
            'grading' => $dto->grading,
            'use_results' => $dto->useResults,
            'category' => $dto->category,
            'begin_at' => $dto->beginAt,
            'end_at' => $dto->endAt,
            'given' => $dto->given,
            'given_at' => $dto->givenAt,
            'checksum' => $dto->checksum(),
            'synced_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('neo_assignments')->upsert(
            array_merge($data, ['created_at' => now()]),
            ['neo_assignment_id'],
            array_keys($data),
        );
    }

    public function hasChanged(int $assignmentId, string $checksum): bool
    {
        $storedChecksum = DB::table('neo_assignments')
            ->where('neo_assignment_id', $assignmentId)
            ->value('checksum');

        return $storedChecksum !== $checksum;
    }
}
