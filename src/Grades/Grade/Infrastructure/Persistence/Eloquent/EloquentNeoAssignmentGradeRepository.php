<?php

declare(strict_types=1);

namespace Grades\Grade\Infrastructure\Persistence\Eloquent;

use Grades\Grade\Application\DTOs\NeoAssignmentGradeDTO;
use Grades\Grade\Domain\Ports\NeoAssignmentGradeRepositoryContract;
use Illuminate\Support\Facades\DB;

final class EloquentNeoAssignmentGradeRepository implements NeoAssignmentGradeRepositoryContract
{
    public function upsert(NeoAssignmentGradeDTO $dto): void
    {
        $data = [
            'neo_grade_id' => $dto->neoGradeId,
            'neo_class_id' => $dto->neoClassId,
            'neo_assignment_id' => $dto->neoAssignmentId,
            'neo_user_id' => $dto->neoUserId,
            'lesson_id' => $dto->lessonId,
            'lesson_name' => $dto->lessonName,
            'sis_id' => $dto->sisId,
            'grader_id' => $dto->graderId,
            'started' => $dto->started,
            'started_at' => $dto->startedAt,
            'finished' => $dto->finished,
            'finished_at' => $dto->finishedAt,
            'graded' => $dto->graded,
            'graded_at' => $dto->gradedAt,
            'fully_graded' => $dto->fullyGraded,
            'score' => $dto->score,
            'percent' => $dto->percent,
            'grade' => $dto->grade,
            'points' => $dto->points,
            'min_points' => $dto->minPoints,
            'missing' => $dto->missing,
            'absent' => $dto->absent,
            'excused' => $dto->excused,
            'incomplete' => $dto->incomplete,
            'excused_comment' => $dto->excusedComment,
            'teacher_comment' => $dto->teacherComment,
            'checksum' => $dto->checksum(),
            'synced_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('neo_assignment_grades')->upsert(
            array_merge($data, ['created_at' => now()]),
            ['neo_grade_id'],
            array_keys($data),
        );
    }

    public function hasChanged(int $neoGradeId, string $checksum): bool
    {
        $storedChecksum = DB::table('neo_assignment_grades')
            ->where('neo_grade_id', $neoGradeId)
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
