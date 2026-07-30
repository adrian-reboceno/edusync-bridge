<?php

declare(strict_types=1);

namespace Grades\Grade\Infrastructure\Persistence\Eloquent;

use Grades\Grade\Domain\Ports\NeoAssignmentResultAnalyticsRepositoryContract;
use Illuminate\Support\Facades\DB;

final class EloquentNeoAssignmentResultAnalyticsRepository implements NeoAssignmentResultAnalyticsRepositoryContract
{
    public function getAssignmentResults(int $classId, int $assignmentId): ?array
    {
        $assignment = DB::table('neo_assignments')
            ->where('neo_assignment_id', $assignmentId)
            ->where('neo_class_id', $classId)
            ->first(['neo_assignment_id', 'name', 'type', 'points', 'lesson_name']);

        if ($assignment === null) {
            return null;
        }

        $results = DB::table('neo_assignment_results as r')
            ->join('neo_users as u', 'u.neo_id', '=', 'r.neo_user_id')
            ->where('r.neo_class_id', $classId)
            ->where('r.neo_assignment_id', $assignmentId)
            ->orderBy('r.neo_user_id')
            ->orderBy('r.position')
            ->get([
                'r.neo_result_id', 'r.neo_user_id', 'u.first_name', 'u.last_name',
                'r.sis_id', 'r.question_id', 'r.position', 'r.response',
                'r.points', 'r.score', 'r.grade',
            ]);

        return [
            'assignment' => [
                'neo_id' => (int) $assignment->neo_assignment_id,
                'name' => $assignment->name,
                'type' => $assignment->type,
                'points' => $assignment->points !== null ? (float) $assignment->points : null,
                'lesson_name' => $assignment->lesson_name,
            ],
            'total_results' => $results->count(),
            'results' => $results->map(static fn (object $row): array => [
                'neo_result_id' => (int) $row->neo_result_id,
                'neo_user_id' => (int) $row->neo_user_id,
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'sis_id' => $row->sis_id,
                'question_id' => $row->question_id !== null ? (int) $row->question_id : null,
                'position' => $row->position !== null ? (int) $row->position : null,
                'response' => $row->response,
                'points' => $row->points !== null ? (float) $row->points : null,
                'score' => $row->score !== null ? (float) $row->score : null,
                'grade' => $row->grade,
            ])->all(),
        ];
    }
}
