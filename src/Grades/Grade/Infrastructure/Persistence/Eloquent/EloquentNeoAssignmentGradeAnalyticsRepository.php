<?php

declare(strict_types=1);

namespace Grades\Grade\Infrastructure\Persistence\Eloquent;

use Grades\Grade\Domain\Ports\NeoAssignmentGradeAnalyticsRepositoryContract;
use Illuminate\Support\Facades\DB;

final class EloquentNeoAssignmentGradeAnalyticsRepository implements NeoAssignmentGradeAnalyticsRepositoryContract
{
    public function getAssignmentGrades(int $classId, int $assignmentId): ?array
    {
        $assignment = DB::table('neo_assignments')
            ->where('neo_assignment_id', $assignmentId)
            ->where('neo_class_id', $classId)
            ->first(['neo_assignment_id', 'name', 'type', 'points', 'grading', 'end_at']);

        if ($assignment === null) {
            return null;
        }

        $stats = DB::table('neo_assignment_grades')
            ->where('neo_assignment_id', $assignmentId)
            ->selectRaw(<<<'SQL'
                COUNT(*) AS total_students,
                COUNT(*) FILTER (WHERE finished = true) AS finished_count,
                COUNT(*) FILTER (WHERE fully_graded = true) AS graded_count,
                ROUND(AVG(score)::numeric, 2) AS avg_score,
                ROUND(AVG(percent)::numeric, 2) AS avg_percent,
                MIN(score) AS min_score,
                MAX(score) AS max_score,
                COUNT(*) FILTER (WHERE absent = true) AS absent_count,
                COUNT(*) FILTER (WHERE excused = true) AS excused_count,
                COUNT(*) FILTER (WHERE missing = true) AS missing_count
            SQL)
            ->first();

        $distribution = DB::table('neo_assignment_grades')
            ->where('neo_assignment_id', $assignmentId)
            ->where('finished', true)
            ->selectRaw(<<<'SQL'
                COUNT(*) FILTER (WHERE percent >= 90) AS range_90_100,
                COUNT(*) FILTER (WHERE percent >= 80 AND percent < 90) AS range_80_89,
                COUNT(*) FILTER (WHERE percent >= 70 AND percent < 80) AS range_70_79,
                COUNT(*) FILTER (WHERE percent >= 60 AND percent < 70) AS range_60_69,
                COUNT(*) FILTER (WHERE percent < 60) AS range_0_59
            SQL)
            ->first();

        $grades = DB::table('neo_assignment_grades')
            ->where('neo_assignment_id', $assignmentId)
            ->orderByRaw('graded_at DESC NULLS LAST')
            ->get([
                'neo_grade_id', 'neo_user_id', 'sis_id', 'score', 'percent', 'grade',
                'finished', 'fully_graded', 'started_at', 'finished_at', 'graded_at',
                'teacher_comment', 'absent', 'excused', 'missing',
            ]);

        return [
            'assignment' => [
                'neo_id' => (int) $assignment->neo_assignment_id,
                'name' => $assignment->name,
                'type' => $assignment->type,
                'points' => $assignment->points !== null ? (float) $assignment->points : null,
                'grading' => $assignment->grading,
                'end_at' => $assignment->end_at,
            ],
            'stats' => [
                'total_students' => (int) $stats->total_students,
                'finished_count' => (int) $stats->finished_count,
                'graded_count' => (int) $stats->graded_count,
                'avg_score' => (float) ($stats->avg_score ?? 0),
                'avg_percent' => (float) ($stats->avg_percent ?? 0),
                'min_score' => $stats->min_score !== null ? (float) $stats->min_score : null,
                'max_score' => $stats->max_score !== null ? (float) $stats->max_score : null,
                'absent_count' => (int) $stats->absent_count,
                'excused_count' => (int) $stats->excused_count,
                'missing_count' => (int) $stats->missing_count,
                'score_distribution' => [
                    '90_100' => (int) $distribution->range_90_100,
                    '80_89' => (int) $distribution->range_80_89,
                    '70_79' => (int) $distribution->range_70_79,
                    '60_69' => (int) $distribution->range_60_69,
                    '0_59' => (int) $distribution->range_0_59,
                ],
            ],
            'grades' => $grades->map(static fn (object $row): array => [
                'neo_grade_id' => (int) $row->neo_grade_id,
                'neo_user_id' => (int) $row->neo_user_id,
                'sis_id' => $row->sis_id,
                'score' => $row->score !== null ? (float) $row->score : null,
                'percent' => $row->percent !== null ? (float) $row->percent : null,
                'grade' => $row->grade,
                'finished' => (bool) $row->finished,
                'fully_graded' => (bool) $row->fully_graded,
                'started_at' => $row->started_at,
                'finished_at' => $row->finished_at,
                'graded_at' => $row->graded_at,
                'teacher_comment' => $row->teacher_comment,
                'absent' => (bool) $row->absent,
                'excused' => (bool) $row->excused,
                'missing' => (bool) $row->missing,
            ])->all(),
        ];
    }

    public function getUserGrades(int $neoId): ?array
    {
        $user = DB::table('neo_users')
            ->where('neo_id', $neoId)
            ->first(['neo_id', 'sis_id', 'first_name', 'last_name']);

        if ($user === null) {
            return null;
        }

        $summary = DB::table('neo_assignment_grades')
            ->where('neo_user_id', $neoId)
            ->selectRaw(<<<'SQL'
                COUNT(DISTINCT neo_assignment_id) AS total_assignments,
                COUNT(*) FILTER (WHERE finished = true) AS finished,
                ROUND(AVG(percent)::numeric, 2) AS avg_percent,
                ROUND(AVG(score)::numeric, 2) AS avg_score
            SQL)
            ->first();

        $grades = DB::table('neo_assignment_grades AS g')
            ->join('neo_classes AS c', 'c.neo_id', '=', 'g.neo_class_id')
            ->join('neo_assignments AS a', 'a.neo_assignment_id', '=', 'g.neo_assignment_id')
            ->where('g.neo_user_id', $neoId)
            ->orderByRaw('g.graded_at DESC NULLS LAST')
            ->get([
                'g.neo_grade_id', 'c.name AS class_name', 'a.name AS assignment_name',
                'a.type AS assignment_type', 'g.lesson_name', 'g.score', 'g.percent',
                'g.grade', 'g.points', 'g.finished', 'g.finished_at', 'g.graded_at',
                'g.fully_graded', 'g.absent', 'g.excused', 'g.missing', 'g.teacher_comment',
            ]);

        return [
            'user' => [
                'neo_id' => (int) $user->neo_id,
                'sis_id' => $user->sis_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ],
            'summary' => [
                'total_assignments' => (int) $summary->total_assignments,
                'finished' => (int) $summary->finished,
                'avg_percent' => (float) ($summary->avg_percent ?? 0),
                'avg_score' => (float) ($summary->avg_score ?? 0),
            ],
            'grades' => $grades->map(static fn (object $row): array => [
                'neo_grade_id' => (int) $row->neo_grade_id,
                'class_name' => $row->class_name,
                'assignment_name' => $row->assignment_name,
                'assignment_type' => $row->assignment_type,
                'lesson_name' => $row->lesson_name,
                'score' => $row->score !== null ? (float) $row->score : null,
                'percent' => $row->percent !== null ? (float) $row->percent : null,
                'grade' => $row->grade,
                'points' => $row->points !== null ? (float) $row->points : null,
                'finished' => (bool) $row->finished,
                'finished_at' => $row->finished_at,
                'graded_at' => $row->graded_at,
                'fully_graded' => (bool) $row->fully_graded,
                'absent' => (bool) $row->absent,
                'excused' => (bool) $row->excused,
                'missing' => (bool) $row->missing,
                'teacher_comment' => $row->teacher_comment,
            ])->all(),
        ];
    }
}
