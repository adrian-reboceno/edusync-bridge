<?php

declare(strict_types=1);

namespace Academic\Student\Infrastructure\Http\Controllers;

use Academic\Student\Application\GetAnalytics\GetDailyAccessQuery;
use Academic\Student\Application\GetAnalytics\GetDailyAccessUseCase;
use Academic\Student\Application\GetAnalytics\GetUserDetailUseCase;
use Academic\Student\Application\GetAnalytics\GetUsersListQuery;
use Academic\Student\Application\GetAnalytics\GetUsersListUseCase;
use Academic\Student\Application\GetAnalytics\GetUsersSummaryUseCase;
use Academic\Student\Application\GetAnalytics\UsersSummaryResult;
use Academic\Student\Infrastructure\Http\Requests\GetDailyAccessRequest;
use Academic\Student\Infrastructure\Http\Requests\GetUsersListRequest;
use Grades\Grade\Application\GetAnalytics\GetUserGradesUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * @tags Analytics - Usuarios
 */
final class UserAnalyticsController extends Controller
{
    public function __construct(
        private readonly GetUsersSummaryUseCase $summaryUseCase,
        private readonly GetUsersListUseCase $listUseCase,
        private readonly GetDailyAccessUseCase $dailyAccessUseCase,
        private readonly GetUserDetailUseCase $detailUseCase,
        private readonly GetUserGradesUseCase $gradesUseCase,
    ) {}

    /**
     * Resumen general de usuarios
     *
     * Retorna totales, distribución por rol, tasa de activación
     * y estadísticas de sesiones de la organización.
     */
    public function summary(): JsonResponse
    {
        $result = $this->summaryUseCase->execute();

        return response()->json([
            'data' => [
                'totals' => [
                    'total' => $result->total,
                    'activated' => $result->activated,
                    'never_logged_in' => $result->neverLoggedIn,
                    'activation_rate' => $result->activationRate,
                    'archived' => $result->archived,
                ],
                'by_role' => [
                    'students' => $result->students,
                    'teachers' => $result->teachers,
                    'administrators' => $result->administrators,
                    'others' => $result->others,
                ],
                'sessions' => [
                    'total_sessions' => $result->totalSessions,
                    'users_with_sessions' => $result->usersWithSessions,
                    'avg_sessions_per_user' => $result->avgSessionsPerUser,
                ],
                'organizations' => $result->organizations,   // ← nuevo
                'last_synced_at' => $result->lastSyncedAt,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * Lista paginada de usuarios
     *
     * Retorna todos los usuarios con datos de actividad y sesiones.
     * Soporta filtrado por rol, estado de activación y búsqueda.
     */
    public function index(GetUsersListRequest $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $page = max((int) $request->input('page', 1), 1);
        $activated = $request->has('activated')
            ? filter_var($request->input('activated'), FILTER_VALIDATE_BOOLEAN)
            : null;

        $query = new GetUsersListQuery(
            page: $page,
            perPage: $perPage,
            role: $request->input('role'),
            activated: $activated,
            search: $request->input('search'),
            orderBy: (string) $request->input('order_by', 'last_login_at'),
            orderDir: (string) $request->input('order_dir', 'desc'),
        );

        $result = $this->listUseCase->execute($query);

        return response()->json([
            'data' => $result->data,
            'meta' => [
                'timestamp' => now()->toAtomString(),
                'page' => $result->page,
                'per_page' => $result->perPage,
                'total' => $result->total,
                'total_pages' => $result->totalPages(),
            ],
        ]);
    }

    /**
     * Accesos diarios
     *
     * Retorna sesiones agregadas por día para el período indicado.
     * Incluye días sin actividad (valor 0) para series de tiempo continuas.
     * Útil para gráficas de barras de actividad.
     */
    public function dailyAccess(GetDailyAccessRequest $request): JsonResponse
    {
        $days = min((int) $request->input('days', 30), 365);
        $userId = $request->filled('user_id') ? (int) $request->input('user_id') : null;

        $result = $this->dailyAccessUseCase->execute(new GetDailyAccessQuery(days: $days, userId: $userId));

        return response()->json([
            'data' => [
                'period' => [
                    'from' => $result->from,
                    'to' => $result->to,
                    'days' => $result->days,
                ],
                'daily' => $result->daily,
                'totals' => [
                    'total_sessions' => $result->totalSessions,
                    'unique_users' => $result->uniqueUsers,
                    'total_minutes' => $result->totalMinutes,
                    'avg_sessions_per_day' => $result->avgSessionsPerDay,
                ],
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * Detalle de un usuario
     *
     * Retorna perfil completo, resumen de sesiones y actividad diaria
     * del usuario indicado por su ID de NEO LMS. Responde 404 si el usuario no existe.
     */
    public function show(Request $request, int $neoId): JsonResponse
    {
        $result = $this->detailUseCase->execute($neoId);

        if ($result === null) {
            return response()->json([
                'errors' => [[
                    'code' => 'NOT_FOUND',
                    'message' => "User with neo_id {$neoId} not found.",
                    'field' => null,
                ]],
            ], 404);
        }

        $classes = array_map(function (array $class) use ($result): array {
            $class['result'] = $result->resultsByClass[$class['neo_class_id']] ?? [];

            return $class;
        }, $result->classes);

        return response()->json([
            'data' => [
                'user' => $result->user,
                'sessions_summary' => $result->sessionsSummary,
                'daily_activity' => $result->dailyActivity,
                'sessions' => $result->sessions,
                'classes' => $classes,
                'daily_streak' => $result->dailyStreak,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * Calificaciones de un usuario
     *
     * Retorna el perfil resumido del usuario, un resumen de desempeño
     * (promedios, assignments completados) y el detalle de sus calificaciones
     * en todas sus clases. Responde 404 si el usuario no existe.
     */
    public function grades(int $neoId): JsonResponse
    {
        $result = $this->gradesUseCase->execute($neoId);

        if ($result === null) {
            return response()->json([
                'errors' => [[
                    'code' => 'NOT_FOUND',
                    'message' => "User with neo_id {$neoId} not found.",
                    'field' => null,
                ]],
            ], 404);
        }

        return response()->json([
            'data' => [
                'user' => $result->user,
                'summary' => $result->summary,
                'grades' => $result->grades,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    private function summaryToArray(UsersSummaryResult $result): array
    {
        return [
            'organization' => [
                'id' => $result->organizationId,
                'name' => $result->organizationName,
            ],
            'totals' => [
                'total' => $result->total,
                'activated' => $result->activated,
                'never_logged_in' => $result->neverLoggedIn,
                'activation_rate' => $result->activationRate,
                'archived' => $result->archived,
            ],
            'by_role' => [
                'students' => $result->students,
                'teachers' => $result->teachers,
                'administrators' => $result->administrators,
                'others' => $result->others,
            ],
            'sessions' => [
                'total_sessions' => $result->totalSessions,
                'users_with_sessions' => $result->usersWithSessions,
                'avg_sessions_per_user' => $result->avgSessionsPerUser,
            ],
            'last_synced_at' => $result->lastSyncedAt,
        ];
    }
}
