<?php

declare(strict_types=1);

namespace Grades\Grade\Infrastructure\Persistence\Eloquent;

use Grades\Grade\Domain\Ports\NeoAssignmentAnalyticsRepositoryContract;
use Illuminate\Support\Facades\DB;

final class EloquentNeoAssignmentAnalyticsRepository implements NeoAssignmentAnalyticsRepositoryContract
{
    public function getClassAssignments(int $classId): ?array
    {
        $class = DB::table('neo_classes')->where('neo_id', $classId)->first(['neo_id', 'name']);

        if ($class === null) {
            return null;
        }

        $rows = DB::select(<<<'SQL'
            SELECT
                neo_assignment_id AS neo_id,
                lesson_name,
                type,
                name,
                points,
                grading,
                category,
                end_at,
                given
            FROM neo_assignments
            WHERE neo_class_id = ?
            ORDER BY end_at NULLS LAST, neo_assignment_id
        SQL, [$classId]);

        $given = 0;
        $totalPoints = 0.0;
        $byType = [];
        $byGrading = [];
        $assignments = [];

        foreach ($rows as $row) {
            $isGiven = (bool) $row->given;

            if ($isGiven) {
                $given++;
            }

            $totalPoints += $row->points !== null ? (float) $row->points : 0.0;

            $type = $row->type ?? 'Unknown';
            $byType[$type] = ($byType[$type] ?? 0) + 1;

            $grading = $row->grading ?? 'Unknown';
            $byGrading[$grading] = ($byGrading[$grading] ?? 0) + 1;

            $assignments[] = [
                'neo_id' => (int) $row->neo_id,
                'lesson_name' => $row->lesson_name,
                'type' => $row->type,
                'name' => $row->name,
                'points' => $row->points !== null ? (float) $row->points : null,
                'grading' => $row->grading,
                'end_at' => $row->end_at,
                'given' => $isGiven,
                'category' => $row->category,
            ];
        }

        $total = count($rows);

        return [
            'class_id' => (int) $class->neo_id,
            'class_name' => $class->name,
            'totals' => [
                'total' => $total,
                'given' => $given,
                'pending' => $total - $given,
                'total_points' => $totalPoints,
                'by_type' => $byType,
                'by_grading' => $byGrading,
            ],
            'assignments' => $assignments,
        ];
    }
}
