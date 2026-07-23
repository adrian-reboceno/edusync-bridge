<?php

declare(strict_types=1);

namespace Academic\Student\Domain\Ports;

use Academic\Student\Application\GetAnalytics\UsersSummaryResult;

interface NeoUserAnalyticsRepositoryContract
{
    public function getSummary(): UsersSummaryResult;

    /**
     * @return array{data: array[], total: int}
     */
    public function getUsersList(
        int $page,
        int $perPage,
        ?string $role,
        ?bool $activated,
        ?string $search,
        string $orderBy,
        string $orderDir,
    ): array;

    /**
     * Sesiones diarias agregadas de los últimos $days días, incluye días sin sesiones.
     *
     * @return array[]
     */
    public function getDailyAccess(int $days, ?int $userId = null): array;

    /**
     * Totales agregados del mismo período que getDailyAccess().
     *
     * @return array{total_sessions: int, unique_users: int, total_minutes: ?float}
     */
    public function getDailyAccessTotals(int $days, ?int $userId = null): array;

    public function getUserDetail(int $neoId): ?array;

    /**
     * @return array{total_sessions: int, total_hours: float, avg_duration_minutes: ?float, first_session_at: ?string, last_session_at: ?string}
     */
    public function getUserSessionsSummary(int $neoId): array;

    /**
     * @return array[]
     */
    public function getUserDailyActivity(int $neoId): array;

    /**
     * @return array[]
     */
    public function getUserSessions(int $neoId): array;
}
