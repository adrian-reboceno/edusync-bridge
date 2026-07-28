<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Infrastructure\Persistence\Eloquent;

use Curricular\StudyProgram\Application\GetAnalytics\ClassesSummaryResult;
use Curricular\StudyProgram\Domain\Ports\NeoClassAnalyticsRepositoryContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EloquentNeoClassAnalyticsRepository implements NeoClassAnalyticsRepositoryContract
{
    public function getSummary(): ClassesSummaryResult
    {
        $totals = DB::table('neo_classes')
            ->selectRaw('
                COUNT(*) AS total_classes,
                COUNT(*) FILTER (WHERE archived = false) AS active,
                COUNT(*) FILTER (WHERE archived = true) AS archived,
                MAX(synced_at) AS last_synced_at
            ')
            ->first();

        $totalEnrollments = (int) DB::table('neo_enrollments')->where('unenrolled', false)->count();
        $totalTeachers = (int) DB::table('neo_class_teachers')->count();

        $byOrganization = DB::table('neo_classes as c')
            ->leftJoin('neo_enrollments as e', 'e.neo_class_id', '=', 'c.neo_id')
            ->where('c.archived', false)
            ->selectRaw('
                c.organization_id AS id,
                c.organization_name AS name,
                COUNT(DISTINCT c.neo_id) AS total_classes,
                COUNT(e.neo_user_id) FILTER (WHERE e.unenrolled = false) AS total_enrolled
            ')
            ->groupBy('c.organization_id', 'c.organization_name')
            ->orderByDesc('total_classes')
            ->get()
            ->map(static function (object $row): array {
                $totalClasses = (int) $row->total_classes;
                $totalEnrolled = (int) $row->total_enrolled;

                return [
                    'id' => $row->id !== null ? (int) $row->id : null,
                    'name' => $row->name,
                    'total_classes' => $totalClasses,
                    'total_enrolled' => $totalEnrolled,
                    'avg_students_per_class' => $totalClasses > 0 ? round($totalEnrolled / $totalClasses, 1) : 0.0,
                ];
            })
            ->all();

        $byStyle = [];
        foreach (DB::table('neo_classes')->where('archived', false)->selectRaw('style, COUNT(*) AS total')->groupBy('style')->get() as $row) {
            $byStyle[(string) ($row->style ?? 'Unknown')] = (int) $row->total;
        }

        return new ClassesSummaryResult(
            totalClasses: (int) $totals->total_classes,
            active: (int) $totals->active,
            archived: (int) $totals->archived,
            totalEnrollments: $totalEnrollments,
            totalTeachers: $totalTeachers,
            byOrganization: $byOrganization,
            byStyle: $byStyle,
            lastSyncedAt: $this->toIso($totals->last_synced_at),
        );
    }

    public function getClassesList(int $page, int $perPage): array
    {
        $total = (int) DB::table('neo_classes')->where('archived', false)->count();

        $rows = DB::select(<<<'SQL'
            SELECT
                c.neo_id, c.sis_id, c.name, c.style,
                c.organization_name, c.start_at, c.finish_at,
                c.archived, c.used_seats, c.synced_at,
                COUNT(DISTINCT e.neo_user_id) FILTER (WHERE e.unenrolled = false) AS total_students,
                COUNT(DISTINCT t.neo_user_id) AS total_teachers,
                COUNT(*) FILTER (WHERE e.completed = true) AS completed_count,
                ROUND(
                    COUNT(*) FILTER (WHERE e.completed = true)::numeric /
                    NULLIF(COUNT(DISTINCT e.neo_user_id) FILTER (WHERE e.unenrolled = false), 0) * 100,
                    1
                ) AS completion_rate,
                ROUND(AVG(e.percent), 1) AS avg_percent
            FROM neo_classes c
            LEFT JOIN neo_enrollments e ON e.neo_class_id = c.neo_id
            LEFT JOIN neo_class_teachers t ON t.neo_class_id = c.neo_id
            WHERE c.archived = false
            GROUP BY c.neo_id
            ORDER BY c.synced_at DESC
            LIMIT ? OFFSET ?
        SQL, [$perPage, ($page - 1) * $perPage]);

        $data = array_map(function (object $row): array {
            return [
                'neo_id' => (int) $row->neo_id,
                'sis_id' => $row->sis_id,
                'name' => $row->name,
                'style' => $row->style,
                'organization_name' => $row->organization_name,
                'start_at' => $row->start_at,
                'finish_at' => $row->finish_at,
                'archived' => (bool) $row->archived,
                'used_seats' => (int) $row->used_seats,
                'total_students' => (int) $row->total_students,
                'total_teachers' => (int) $row->total_teachers,
                'completed_count' => (int) $row->completed_count,
                'completion_rate' => $row->completion_rate !== null ? (float) $row->completion_rate : 0.0,
                'avg_percent' => $row->avg_percent !== null ? (float) $row->avg_percent : null,
            ];
        }, $rows);

        return ['data' => $data, 'total' => $total];
    }

    public function getClassDetail(int $neoId): ?array
    {
        $class = DB::table('neo_classes')
            ->where('neo_id', $neoId)
            ->selectRaw('neo_id, name, style, organization_name, start_at, finish_at, used_seats, metadata')
            ->first();

        if ($class === null) {
            return null;
        }

        $summary = DB::selectOne(<<<'SQL'
            SELECT
                COUNT(DISTINCT e.neo_user_id) FILTER (WHERE e.unenrolled = false) AS total_students,
                (SELECT COUNT(*) FROM neo_class_teachers t WHERE t.neo_class_id = ?) AS total_teachers,
                COUNT(*) FILTER (WHERE e.started = true) AS started,
                COUNT(*) FILTER (WHERE e.completed = true) AS completed,
                COUNT(*) FILTER (WHERE e.unenrolled = true) AS unenrolled,
                ROUND(AVG(e.percent), 1) AS avg_percent,
                ROUND(AVG(e.time_spent_seconds) / 3600.0, 1) AS avg_time_spent_hours
            FROM neo_enrollments e
            WHERE e.neo_class_id = ?
        SQL, [$neoId, $neoId]);

        $totalStudents = (int) $summary->total_students;
        $completed = (int) $summary->completed;

        return [
            'class' => [
                'neo_id' => (int) $class->neo_id,
                'name' => $class->name,
                'style' => $class->style,
                'organization_name' => $class->organization_name,
                'start_at' => $class->start_at,
                'finish_at' => $class->finish_at,
                'used_seats' => (int) $class->used_seats,
                'metadata' => $class->metadata !== null ? json_decode((string) $class->metadata, true) : null,
            ],
            'summary' => [
                'total_students' => $totalStudents,
                'total_teachers' => (int) $summary->total_teachers,
                'started' => (int) $summary->started,
                'completed' => $completed,
                'unenrolled' => (int) $summary->unenrolled,
                'completion_rate' => $totalStudents > 0 ? round($completed / $totalStudents * 100, 1) : 0.0,
                'avg_percent' => $summary->avg_percent !== null ? (float) $summary->avg_percent : null,
                'avg_time_spent_hours' => $summary->avg_time_spent_hours !== null ? (float) $summary->avg_time_spent_hours : null,
            ],
        ];
    }

    public function getClassTeachers(int $neoId): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT t.neo_user_id, u.first_name, u.last_name, u.email, t.coteacher, t.last_visited_at
            FROM neo_class_teachers t
            JOIN neo_users u ON u.neo_id = t.neo_user_id
            WHERE t.neo_class_id = ?
            ORDER BY t.coteacher ASC, u.last_name ASC
        SQL, [$neoId]);

        return array_map(function (object $row): array {
            return [
                'neo_user_id' => (int) $row->neo_user_id,
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'email' => $row->email,
                'coteacher' => (bool) $row->coteacher,
                'last_visited_at' => $this->toIso($row->last_visited_at),
            ];
        }, $rows);
    }

    public function getClassStudents(int $neoId): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT
                e.neo_user_id, e.sis_id, u.first_name, u.last_name,
                e.enrolled_at, e.started, e.completed, e.percent, e.grade,
                e.last_visited_at, e.time_spent_seconds
            FROM neo_enrollments e
            JOIN neo_users u ON u.neo_id = e.neo_user_id
            WHERE e.neo_class_id = ? AND e.unenrolled = false
            ORDER BY u.last_name ASC
        SQL, [$neoId]);

        return array_map(function (object $row): array {
            return [
                'neo_user_id' => (int) $row->neo_user_id,
                'sis_id' => $row->sis_id,
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'enrolled_at' => $this->toIso($row->enrolled_at),
                'started' => (bool) $row->started,
                'completed' => (bool) $row->completed,
                'percent' => $row->percent !== null ? (float) $row->percent : null,
                'grade' => $row->grade,
                'last_visited_at' => $this->toIso($row->last_visited_at),
                'time_spent_hours' => $row->time_spent_seconds > 0 ? round((int) $row->time_spent_seconds / 3600.0, 1) : null,
            ];
        }, $rows);
    }

    private function toIso(?string $value): ?string
    {
        return $value !== null ? Carbon::parse($value)->toIso8601String() : null;
    }
}
