<?php

declare(strict_types=1);

namespace Academic\Student\Infrastructure\Persistence\Eloquent;

use Academic\Student\Application\GetAnalytics\UsersSummaryResult;
use Academic\Student\Domain\Ports\NeoUserAnalyticsRepositoryContract;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
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
        $userRow = DB::selectOne(<<<'SQL'
            SELECT
              COUNT(*) AS total,
              COUNT(*) FILTER (WHERE first_login_at IS NOT NULL) AS activated,
              COUNT(*) FILTER (WHERE first_login_at IS NULL)     AS never_logged_in,
              COUNT(*) FILTER (WHERE archived = true)            AS archived,
              MAX(organization_id)                                AS organization_id,
              MAX(organization_name)                              AS organization_name,
              MAX(synced_at)                                      AS last_synced_at,
              COUNT(*) FILTER (WHERE roles @> '["Student"]')       AS students,
              COUNT(*) FILTER (WHERE roles @> '["Teacher"]')       AS teachers,
              COUNT(*) FILTER (WHERE roles @> '["Administrator"]') AS administrators,
              COUNT(*) FILTER (
                WHERE NOT (
                  roles @> '["Student"]' OR roles @> '["Teacher"]' OR roles @> '["Administrator"]'
                )
              ) AS others
            FROM neo_users
        SQL);

        $sessionRow = DB::selectOne(<<<'SQL'
            SELECT
              COUNT(*)                    AS total_sessions,
              COUNT(DISTINCT neo_user_id) AS users_with_sessions
            FROM neo_user_sessions
        SQL);

        $total = (int) $userRow->total;
        $activated = (int) $userRow->activated;
        $totalSessions = (int) $sessionRow->total_sessions;
        $usersWithSessions = (int) $sessionRow->users_with_sessions;

        return new UsersSummaryResult(
            total: $total,
            activated: $activated,
            neverLoggedIn: (int) $userRow->never_logged_in,
            archived: (int) $userRow->archived,
            activationRate: $total > 0 ? round($activated / $total * 100, 1) : 0.0,
            students: (int) $userRow->students,
            teachers: (int) $userRow->teachers,
            administrators: (int) $userRow->administrators,
            others: (int) $userRow->others,
            organizationId: $userRow->organization_id !== null ? (int) $userRow->organization_id : null,
            organizationName: $userRow->organization_name,
            totalSessions: $totalSessions,
            usersWithSessions: $usersWithSessions,
            avgSessionsPerUser: $usersWithSessions > 0 ? round($totalSessions / $usersWithSessions, 1) : 0.0,
            lastSyncedAt: $this->toIso($userRow->last_synced_at),
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
                DB::raw("EXTRACT(DAY FROM NOW() - u.last_login_at)::int AS days_since_login"),
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
}
