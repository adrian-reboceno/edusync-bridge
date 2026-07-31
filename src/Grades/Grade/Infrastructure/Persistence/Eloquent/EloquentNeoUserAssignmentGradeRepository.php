<?php

declare(strict_types=1);

namespace Grades\Grade\Infrastructure\Persistence\Eloquent;

use Grades\Grade\Application\DTOs\NeoUserAssignmentGradeDTO;
use Grades\Grade\Domain\Ports\NeoUserAssignmentGradeRepositoryContract;
use Illuminate\Support\Facades\DB;

final class EloquentNeoUserAssignmentGradeRepository implements NeoUserAssignmentGradeRepositoryContract
{
    public function getActiveStudentIds(): array
    {
        return DB::table('neo_users')
            ->whereRaw("'Student' = ANY(ARRAY(SELECT jsonb_array_elements_text(roles)))")
            ->where('archived', false)
            ->pluck('neo_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function getSisIdForUser(int $neoUserId): ?string
    {
        return DB::table('neo_users')
            ->where('neo_id', $neoUserId)
            ->value('sis_id');
    }

    public function updateGradeTimestamps(NeoUserAssignmentGradeDTO $dto): void
    {
        DB::table('neo_assignment_grades')
            ->where('neo_grade_id', $dto->neoGradeId)
            ->update([
                'grader_id' => $dto->graderId,
                'lesson_id' => $dto->lessonId,
                'lesson_name' => $dto->lessonName,
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
                'synced_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function upsertMetrics(NeoUserAssignmentGradeDTO $dto): void
    {
        $data = [
            'neo_grade_id' => $dto->neoGradeId,
            'neo_user_id' => $dto->neoUserId,
            'neo_class_id' => $dto->neoClassId,
            'neo_assignment_id' => $dto->neoAssignmentId,
            'sis_id' => $dto->sisId,
            'duration_minutes' => $dto->durationMinutes(),
            'feedback_minutes' => $dto->feedbackMinutes(),
            'time_to_start_minutes' => $dto->timeToStartMinutes(),
            'submitted_on_time' => $dto->submittedOnTime(),
            'minutes_before_deadline' => $dto->minutesBeforeDeadline(),
            'score' => $dto->score,
            'percent' => $dto->percent,
            'grade' => $dto->grade,
            'assignment_type' => $dto->assignmentType,
            'assignment_grading' => $dto->assignmentGrading,
            'calculated_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('neo_assignment_grade_metrics')->upsert(
            array_merge($data, ['created_at' => now()]),
            ['neo_grade_id'],
            array_keys($data),
        );
    }

    public function getMetricsByUserAndClass(int $neoUserId, int $neoClassId): array
    {
        return DB::table('neo_assignment_grade_metrics as m')
            ->join('neo_assignments as a', 'a.neo_assignment_id', '=', 'm.neo_assignment_id')
            ->where('m.neo_user_id', $neoUserId)
            ->where('m.neo_class_id', $neoClassId)
            ->select([
                'm.neo_grade_id',
                'm.neo_assignment_id',
                'a.name as assignment_name',
                'a.type as assignment_type',
                'a.end_at as deadline',
                'm.score', 'm.percent', 'm.grade',
                'm.duration_minutes',
                'm.feedback_minutes',
                'm.time_to_start_minutes',
                'm.submitted_on_time',
                'm.minutes_before_deadline',
                'm.calculated_at',
            ])
            ->orderBy('m.neo_assignment_id')
            ->get()
            ->map(static fn ($r): array => (array) $r)
            ->all();
    }
}
