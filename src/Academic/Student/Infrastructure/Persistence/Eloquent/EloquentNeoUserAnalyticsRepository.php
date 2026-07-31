<?php

declare(strict_types=1);

namespace Academic\Student\Infrastructure\Persistence\Eloquent;

use Academic\Student\Application\GetAnalytics\UsersSummaryResult;
use Academic\Student\Domain\Ports\NeoUserAnalyticsRepositoryContract;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EloquentNeoUserAnalyticsRepository implements NeoUserAnalyticsRepositoryContract
{
    private const ORDERABLE_COLUMNS = [
        'last_login_at' => 'u.last_login_at',
        'first_login_at' => 'u.first_login_at',
        'joined_at' => 'u.joined_at',
        'first_name' => 'u.first_name',
        'last_name' => 'u.last_name',
        'email' => 'u.email',
        'total_sessions' => 'total_sessions',
    ];

    private const SESSION_TIMEZONE = 'America/Monterrey';

    public function getSummary(): UsersSummaryResult
    {
        // Query global (ya existe)
        $totals = DB::table('neo_users')
            ->selectRaw("
                COUNT(*)                                                                               AS total,
                COUNT(*) FILTER (WHERE first_login_at IS NOT NULL)                                    AS activated,
                COUNT(*) FILTER (WHERE first_login_at IS NULL)                                        AS never_logged_in,
                COUNT(*) FILTER (WHERE archived = true)                                               AS archived,
                COUNT(*) FILTER (WHERE 'Student'       = ANY(ARRAY(SELECT jsonb_array_elements_text(roles)))) AS students,
                COUNT(*) FILTER (WHERE 'Teacher'       = ANY(ARRAY(SELECT jsonb_array_elements_text(roles)))) AS teachers,
                COUNT(*) FILTER (WHERE 'Administrator' = ANY(ARRAY(SELECT jsonb_array_elements_text(roles)))) AS administrators,
                MAX(synced_at)                                                                         AS last_synced_at,
                MAX(organization_name)                                                                 AS organization_name,
                MAX(organization_id)                                                                   AS organization_id
            ")
            ->first();

        // Query de sesiones (ya existe)
        $sessions = DB::table('neo_user_sessions')
            ->selectRaw('
                COUNT(*)                    AS total_sessions,
                COUNT(DISTINCT neo_user_id) AS users_with_sessions
            ')
            ->first();

        // ← NUEVA query por organización
        $orgs = DB::table('neo_users')
            ->selectRaw("
                organization_id,
                organization_name,
                COUNT(*)                                                                               AS total,
                COUNT(*) FILTER (WHERE first_login_at IS NOT NULL)                                    AS activated,
                COUNT(*) FILTER (WHERE first_login_at IS NULL)                                        AS never_logged_in,
                COUNT(*) FILTER (WHERE 'Student'       = ANY(ARRAY(SELECT jsonb_array_elements_text(roles)))) AS students,
                COUNT(*) FILTER (WHERE 'Teacher'       = ANY(ARRAY(SELECT jsonb_array_elements_text(roles)))) AS teachers,
                COUNT(*) FILTER (WHERE 'Administrator' = ANY(ARRAY(SELECT jsonb_array_elements_text(roles)))) AS administrators
            ")
            ->groupBy('organization_id', 'organization_name')
            ->orderByDesc('total')
            ->get();

        $total = (int) $totals->total;
        $activated = (int) $totals->activated;
        $totalSessions = (int) ($sessions->total_sessions ?? 0);
        $usersWithSess = (int) ($sessions->users_with_sessions ?? 0);

        return new UsersSummaryResult(
            total: $total,
            activated: $activated,
            neverLoggedIn: (int) $totals->never_logged_in,
            archived: (int) $totals->archived,
            activationRate: $total > 0 ? round($activated / $total * 100, 1) : 0.0,
            students: (int) $totals->students,
            teachers: (int) $totals->teachers,
            administrators: (int) $totals->administrators,
            others: $total - (int) $totals->students - (int) $totals->teachers - (int) $totals->administrators,
            totalSessions: $totalSessions,
            usersWithSessions: $usersWithSess,
            avgSessionsPerUser: $usersWithSess > 0 ? round($totalSessions / $usersWithSess, 1) : 0.0,
            lastSyncedAt: $totals->last_synced_at,
            organizations: $orgs->map(fn ($org) => [
                'id' => $org->organization_id,
                'name' => $org->organization_name,
                'totals' => [
                    'total' => (int) $org->total,
                    'activated' => (int) $org->activated,
                    'never_logged_in' => (int) $org->never_logged_in,
                    'activation_rate' => (int) $org->total > 0
                        ? round((int) $org->activated / (int) $org->total * 100, 1)
                        : 0.0,
                ],
                'by_role' => [
                    'students' => (int) $org->students,
                    'teachers' => (int) $org->teachers,
                    'administrators' => (int) $org->administrators,
                    'others' => (int) $org->total - (int) $org->students - (int) $org->teachers - (int) $org->administrators,
                ],
            ])->toArray(),
        );
    }

    public function getUsersList(
        int $page,
        int $perPage,
        ?string $role,
        ?bool $activated,
        ?string $search,
        string $orderBy,
        string $orderDir,
    ): array {
        $orderColumn = self::ORDERABLE_COLUMNS[$orderBy] ?? self::ORDERABLE_COLUMNS['last_login_at'];
        $orderDir = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';

        $countQuery = DB::table('neo_users as u')->where('u.archived', false);
        $this->applyUserFilters($countQuery, $role, $activated, $search);
        $total = $countQuery->count();

        $query = DB::table('neo_users as u')
            ->leftJoin('neo_user_sessions as s', 's.neo_user_id', '=', 'u.neo_id')
            ->where('u.archived', false)
            ->groupBy(
                'u.neo_id',
                'u.sis_id',
                'u.userid',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.roles',
                'u.organization_name',
                'u.language',
                'u.time_zone',
                'u.joined_at',
                'u.first_login_at',
                'u.last_login_at',
            )
            ->select([
                'u.neo_id', 'u.sis_id', 'u.userid', 'u.first_name', 'u.last_name', 'u.email',
                'u.roles', 'u.organization_name', 'u.language', 'u.time_zone',
                'u.joined_at', 'u.first_login_at', 'u.last_login_at',
                DB::raw('(u.first_login_at IS NOT NULL) AS activated'),
                DB::raw('EXTRACT(DAY FROM NOW() - u.last_login_at)::int AS days_since_login'),
                DB::raw('COUNT(s.neo_session_id) AS total_sessions'),
            ]);

        $this->applyUserFilters($query, $role, $activated, $search);

        $rows = $query
            ->orderByRaw("{$orderColumn} {$orderDir} NULLS LAST")
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $data = $rows->map(function (object $row): array {
            return [
                'neo_id' => (int) $row->neo_id,
                'sis_id' => $row->sis_id,
                'userid' => $row->userid,
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'email' => $row->email,
                'roles' => json_decode((string) $row->roles, true) ?? [],
                'organization_name' => $row->organization_name,
                'language' => $row->language,
                'time_zone' => $row->time_zone,
                'joined_at' => $this->toIso($row->joined_at),
                'first_login_at' => $this->toIso($row->first_login_at),
                'last_login_at' => $this->toIso($row->last_login_at),
                'activated' => (bool) $row->activated,
                'days_since_login' => $row->days_since_login !== null ? (int) $row->days_since_login : null,
                'total_sessions' => (int) $row->total_sessions,
            ];
        })->all();

        return ['data' => $data, 'total' => $total];
    }

    public function getDailyAccess(int $days, ?int $userId = null): array
    {
        $query = DB::table('neo_user_sessions')
            ->selectRaw("DATE(login_at AT TIME ZONE '" . self::SESSION_TIMEZONE . "') AS date")
            ->selectRaw('COUNT(*) AS total_sessions')
            ->selectRaw('COUNT(DISTINCT neo_user_id) AS unique_users')
            ->selectRaw('ROUND(SUM(duration_seconds) / 60.0, 1) AS total_minutes')
            ->where('login_at', '>=', Carbon::now()->subDays($days))
            ->groupBy('date')
            ->orderByDesc('date');

        if ($userId !== null) {
            $query->where('neo_user_id', $userId);
        }

        $rows = $query->get()->map(function (object $row): array {
            return [
                'date' => substr((string) $row->date, 0, 10),
                'total_sessions' => (int) $row->total_sessions,
                'unique_users' => (int) $row->unique_users,
                'total_minutes' => $row->total_minutes !== null ? (float) $row->total_minutes : null,
            ];
        })->all();

        return $this->fillMissingDays($rows, $days);
    }

    public function getDailyAccessTotals(int $days, ?int $userId = null): array
    {
        $query = DB::table('neo_user_sessions')
            ->selectRaw('COUNT(*) AS total_sessions')
            ->selectRaw('COUNT(DISTINCT neo_user_id) AS unique_users')
            ->selectRaw('SUM(duration_seconds) AS total_seconds')
            ->where('login_at', '>=', Carbon::now()->subDays($days));

        if ($userId !== null) {
            $query->where('neo_user_id', $userId);
        }

        $row = $query->first();

        return [
            'total_sessions' => (int) $row->total_sessions,
            'unique_users' => (int) $row->unique_users,
            'total_minutes' => $row->total_seconds !== null ? round(((int) $row->total_seconds) / 60.0, 1) : null,
        ];
    }

    public function getUserDetail(int $neoId): ?array
    {
        $row = DB::selectOne(<<<'SQL'
            SELECT
              neo_id, sis_id, userid, first_name, last_name, email, roles,
              organization_id, organization_name, language, time_zone,
              joined_at, first_login_at, last_login_at,
              (first_login_at IS NOT NULL) AS activated,
              EXTRACT(DAY FROM NOW() - last_login_at)::int AS days_since_last_login
            FROM neo_users
            WHERE neo_id = ?
        SQL, [$neoId]);

        if ($row === null) {
            return null;
        }

        return [
            'neo_id' => (int) $row->neo_id,
            'sis_id' => $row->sis_id,
            'userid' => $row->userid,
            'first_name' => $row->first_name,
            'last_name' => $row->last_name,
            'email' => $row->email,
            'roles' => json_decode((string) $row->roles, true) ?? [],
            'organization_id' => $row->organization_id !== null ? (int) $row->organization_id : null,
            'organization_name' => $row->organization_name,
            'language' => $row->language,
            'time_zone' => $row->time_zone,
            'joined_at' => $this->toIso($row->joined_at),
            'first_login_at' => $this->toIso($row->first_login_at),
            'last_login_at' => $this->toIso($row->last_login_at),
            'activated' => (bool) $row->activated,
            'days_since_last_login' => $row->days_since_last_login !== null ? (int) $row->days_since_last_login : null,
        ];
    }

    public function getUserSessionsSummary(int $neoId): array
    {
        $row = DB::selectOne(<<<'SQL'
            SELECT
              COUNT(*)                        AS total_sessions,
              SUM(duration_seconds) / 3600.0  AS total_hours,
              AVG(duration_seconds) / 60.0    AS avg_duration_minutes,
              MIN(login_at)                    AS first_session_at,
              MAX(login_at)                    AS last_session_at
            FROM neo_user_sessions
            WHERE neo_user_id = ?
        SQL, [$neoId]);

        return [
            'total_sessions' => (int) $row->total_sessions,
            'total_hours' => $row->total_hours !== null ? round((float) $row->total_hours, 1) : 0.0,
            'avg_duration_minutes' => $row->avg_duration_minutes !== null ? round((float) $row->avg_duration_minutes, 1) : null,
            'first_session_at' => $this->toIso($row->first_session_at),
            'last_session_at' => $this->toIso($row->last_session_at),
        ];
    }

    public function getUserDailyActivity(int $neoId): array
    {
        $rows = DB::select(<<<SQL
            SELECT
              DATE(login_at AT TIME ZONE '{$this->sessionTimezone()}') AS date,
              COUNT(*)                                     AS sessions,
              ROUND(SUM(duration_seconds) / 60.0, 1)       AS total_minutes,
              ROUND(AVG(duration_seconds) / 60.0, 1)       AS avg_minutes
            FROM neo_user_sessions
            WHERE neo_user_id = ?
            GROUP BY date
            ORDER BY date DESC
        SQL, [$neoId]);

        return array_map(function (object $row): array {
            return [
                'date' => substr((string) $row->date, 0, 10),
                'sessions' => (int) $row->sessions,
                'total_minutes' => $row->total_minutes !== null ? (float) $row->total_minutes : null,
                'avg_minutes' => $row->avg_minutes !== null ? (float) $row->avg_minutes : null,
            ];
        }, $rows);
    }

    public function getUserSessions(int $neoId): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT
              neo_session_id AS id,
              login_at, logout_at,
              ROUND(duration_seconds / 60.0, 1) AS duration_minutes,
              ip_address
            FROM neo_user_sessions
            WHERE neo_user_id = ?
            ORDER BY login_at DESC
        SQL, [$neoId]);

        return array_map(function (object $row): array {
            return [
                'id' => (int) $row->id,
                'login_at' => $this->toIso($row->login_at),
                'logout_at' => $this->toIso($row->logout_at),
                'duration_minutes' => $row->duration_minutes !== null ? (float) $row->duration_minutes : null,
                'ip_address' => $row->ip_address,
            ];
        }, $rows);
    }

    public function getUserClasses(int $neoId): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT
                e.neo_class_id,
                c.name                                  AS class_name,
                c.organization_name,
                c.start_at,
                c.finish_at,
                e.enrolled_at,
                e.enroll_type,
                e.started,
                e.started_at,
                e.completed,
                e.completed_at,
                e.unenrolled,
                e.last_visited_at,
                e.time_spent_seconds,
                ROUND(e.time_spent_seconds / 3600.0, 2)  AS time_spent_hours,
                e.percent,
                e.grade
            FROM neo_enrollments e
            JOIN neo_classes c ON c.neo_id = e.neo_class_id
            WHERE e.neo_user_id = ?
              AND e.unenrolled = false
            ORDER BY e.last_visited_at DESC NULLS LAST, c.name ASC
        SQL, [$neoId]);

        return array_map(function (object $row): array {
            return [
                'neo_class_id' => (int) $row->neo_class_id,
                'class_name' => $row->class_name,
                'organization_name' => $row->organization_name,
                'start_at' => $row->start_at,
                'finish_at' => $row->finish_at,
                'enrolled_at' => $this->toIso($row->enrolled_at),
                'enroll_type' => $row->enroll_type,
                'started' => (bool) $row->started,
                'started_at' => $this->toIso($row->started_at),
                'completed' => (bool) $row->completed,
                'completed_at' => $this->toIso($row->completed_at),
                'unenrolled' => (bool) $row->unenrolled,
                'last_visited_at' => $this->toIso($row->last_visited_at),
                'time_spent_seconds' => (int) ($row->time_spent_seconds ?? 0),
                'time_spent_hours' => (float) ($row->time_spent_hours ?? 0),
                'percent' => $row->percent !== null ? (float) $row->percent : null,
                'grade' => $row->grade,
            ];
        }, $rows);
    }

    public function getUserDailyStreak(int $neoId): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT
                h.neo_class_id,
                c.name                                                       AS class_name,
                h.recorded_at,
                h.percent,
                h.grade,
                h.prev_percent,
                h.prev_grade,
                ROUND((h.percent - COALESCE(h.prev_percent, 0))::numeric, 2) AS delta_percent,
                h.time_spent_seconds,
                h.last_visited_at
            FROM neo_enrollment_progress_history h
            JOIN neo_classes c ON c.neo_id = h.neo_class_id
            WHERE h.neo_user_id = ?
            ORDER BY h.neo_class_id, h.recorded_at ASC
        SQL, [$neoId]);

        $grouped = [];
        foreach ($rows as $row) {
            $classId = (int) $row->neo_class_id;

            if (! isset($grouped[$classId])) {
                $grouped[$classId] = [
                    'neo_class_id' => $classId,
                    'class_name' => $row->class_name,
                    'history' => [],
                ];
            }

            $grouped[$classId]['history'][] = [
                'recorded_at' => $this->toIso($row->recorded_at),
                'percent' => $row->percent !== null ? (float) $row->percent : null,
                'grade' => $row->grade,
                'prev_percent' => $row->prev_percent !== null ? (float) $row->prev_percent : null,
                'prev_grade' => $row->prev_grade,
                'delta_percent' => $row->delta_percent !== null ? (float) $row->delta_percent : null,
                'time_spent_seconds' => $row->time_spent_seconds !== null ? (int) $row->time_spent_seconds : null,
                'last_visited_at' => $this->toIso($row->last_visited_at),
            ];
        }

        $result = [];
        foreach ($grouped as $classData) {
            $result[] = [
                'neo_class_id' => $classData['neo_class_id'],
                'class_name' => $classData['class_name'],
                'history' => $classData['history'],
                'total_daily_streak' => $this->calculateStreak($classData['history']),
            ];
        }

        return $result;
    }

    public function getUserResultsByClass(int $neoId): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT
                r.neo_class_id,
                r.neo_result_id,
                r.neo_grade_id,
                r.neo_assignment_id,
                a.neo_lesson_id,
                a.lesson_name,
                a.name AS assignment_name,
                a.type AS assignment_type,
                r.question_id,
                r.response,
                r.points,
                r.score,
                r.grade,
                g.started,
                g.started_at,
                g.finished,
                g.finished_at,
                g.graded,
                g.graded_at,
                g.fully_graded,
                g.percent AS grade_percent,
                m.duration_minutes,
                m.feedback_minutes,
                m.time_to_start_minutes,
                m.submitted_on_time,
                m.minutes_before_deadline
            FROM neo_assignment_results r
            JOIN neo_assignments a ON a.neo_assignment_id = r.neo_assignment_id
            LEFT JOIN neo_assignment_grades g ON g.neo_grade_id = r.neo_grade_id
            LEFT JOIN neo_assignment_grade_metrics m ON m.neo_grade_id = r.neo_grade_id
            WHERE r.neo_user_id = ?
            ORDER BY r.neo_class_id, r.neo_assignment_id, r.question_id NULLS LAST
        SQL, [$neoId]);

        $grouped = [];
        foreach ($rows as $row) {
            $classId = (int) $row->neo_class_id;

            $grouped[$classId][] = [
                'neo_result_id' => (int) $row->neo_result_id,
                'neo_grade_id' => (int) $row->neo_grade_id,
                'neo_assignment_id' => (int) $row->neo_assignment_id,
                'neo_lesson_id' => $row->neo_lesson_id !== null ? (int) $row->neo_lesson_id : null,
                'lesson_name' => $row->lesson_name,
                'assignment_name' => $row->assignment_name,
                'assignment_type' => $row->assignment_type,
                'question_id' => $row->question_id !== null ? (int) $row->question_id : null,
                'response' => $row->response,
                'points' => $row->points !== null ? (float) $row->points : null,
                'score' => $row->score !== null ? (float) $row->score : null,
                'grade' => $row->grade,
                'grade_detail' => [
                    'started' => (bool) $row->started,
                    'started_at' => $this->toIso($row->started_at),
                    'finished' => (bool) $row->finished,
                    'finished_at' => $this->toIso($row->finished_at),
                    'graded' => (int) ($row->graded ?? 0),
                    'graded_at' => $this->toIso($row->graded_at),
                    'fully_graded' => (bool) $row->fully_graded,
                    'percent' => $row->grade_percent !== null ? (float) $row->grade_percent : null,
                ],
                'metrics' => [
                    'duration_minutes' => $row->duration_minutes !== null ? (float) $row->duration_minutes : null,
                    'feedback_minutes' => $row->feedback_minutes !== null ? (float) $row->feedback_minutes : null,
                    'time_to_start_minutes' => $row->time_to_start_minutes !== null ? (float) $row->time_to_start_minutes : null,
                    'submitted_on_time' => $row->submitted_on_time !== null ? (bool) $row->submitted_on_time : null,
                    'minutes_before_deadline' => $row->minutes_before_deadline !== null ? (float) $row->minutes_before_deadline : null,
                ],
            ];
        }

        return $grouped;
    }

    private function applyUserFilters(Builder $query, ?string $role, ?bool $activated, ?string $search): void
    {
        if ($role !== null) {
            $query->whereRaw('u.roles @> ?::jsonb', [json_encode([$role], JSON_THROW_ON_ERROR)]);
        }

        if ($activated === true) {
            $query->whereNotNull('u.first_login_at');
        } elseif ($activated === false) {
            $query->whereNull('u.first_login_at');
        }

        if ($search !== null && $search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('u.first_name', 'ilike', $like)
                    ->orWhere('u.last_name', 'ilike', $like)
                    ->orWhere('u.email', 'ilike', $like)
                    ->orWhere('u.sis_id', 'ilike', $like);
            });
        }
    }

    /**
     * Genera la serie completa de días del período y la combina con los resultados
     * de la BD, rellenando con ceros los días sin sesiones registradas.
     */
    private function fillMissingDays(array $dbRows, int $days): array
    {
        $period = new DatePeriod(
            new DateTimeImmutable("-{$days} days"),
            new DateInterval('P1D'),
            new DateTimeImmutable('tomorrow'),
        );

        $indexed = array_column($dbRows, null, 'date');

        $result = [];
        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $result[] = $indexed[$key] ?? [
                'date' => $key,
                'total_sessions' => 0,
                'unique_users' => 0,
                'total_minutes' => 0,
            ];
        }

        return array_reverse($result);
    }

    private function sessionTimezone(): string
    {
        return self::SESSION_TIMEZONE;
    }

    private function toIso(?string $value): ?string
    {
        return $value !== null ? Carbon::parse($value)->toIso8601String() : null;
    }

    private function calculateStreak(array $history): int
    {
        if (empty($history)) {
            return 0;
        }

        // Filtrar registros con progreso real Y con last_visited_at
        $progressDays = [];
        foreach ($history as $record) {
            $hasRealProgress =
                ($record['delta_percent'] !== null && $record['delta_percent'] > 0) ||
                ($record['grade'] !== null && $record['grade'] !== ($record['prev_grade'] ?? null));

            // Usar last_visited_at — cuándo el alumno realmente visitó la clase
            if ($hasRealProgress && $record['last_visited_at'] !== null) {
                $day = substr($record['last_visited_at'], 0, 10); // "2026-07-28"
                $progressDays[$day] = true;
            }
        }

        if (empty($progressDays)) {
            return 0;
        }

        $days = array_keys($progressDays);
        sort($days);

        // Verificar si el último día está dentro de las últimas 24h
        $lastDay = end($days);
        $lastDate = new DateTimeImmutable($lastDay . ' 23:59:59', new DateTimeZone('UTC'));
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $hoursSinceLast = ($now->getTimestamp() - $lastDate->getTimestamp()) / 3600;

        if ($hoursSinceLast > 24) {
            return 0;
        }

        // Contar días consecutivos hacia atrás
        $streak = 1;
        $current = new DateTimeImmutable($lastDay);

        for ($i = count($days) - 2; $i >= 0; $i--) {
            $prev = new DateTimeImmutable($days[$i]);
            $diffDays = (int) $current->diff($prev)->days;

            if ($diffDays === 1) {
                $streak++;
                $current = $prev;
            } else {
                break;
            }
        }

        return $streak;
    }
}
