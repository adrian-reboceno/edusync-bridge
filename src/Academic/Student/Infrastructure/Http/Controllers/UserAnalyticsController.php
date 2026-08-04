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
use OpenApi\Annotations as OA;

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
     * @OA\Get(
     *     path="/api/v1/analytics/users/summary",
     *     tags={"Analytics - Usuarios"},
     *     summary="Resumen general de usuarios",
     *     description="Retorna totales, distribución por rol, tasa de activación y estadísticas de sesiones de la organización.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Distribución y totales",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="totals", type="object",
     *                     @OA\Property(property="total", type="integer", example=32),
     *                     @OA\Property(property="activated", type="integer", example=9),
     *                     @OA\Property(property="never_logged_in", type="integer", example=23),
     *                     @OA\Property(property="activation_rate", type="number", example=28.1),
     *                     @OA\Property(property="archived", type="integer", example=0)
     *                 ),
     *                 @OA\Property(property="by_role", type="object",
     *                     @OA\Property(property="students", type="integer", example=16),
     *                     @OA\Property(property="teachers", type="integer", example=11),
     *                     @OA\Property(property="administrators", type="integer", example=2),
     *                     @OA\Property(property="others", type="integer", example=3)
     *                 ),
     *                 @OA\Property(property="sessions", type="object",
     *                     @OA\Property(property="total_sessions", type="integer"),
     *                     @OA\Property(property="users_with_sessions", type="integer"),
     *                     @OA\Property(property="avg_sessions_per_user", type="number")
     *                 ),
     *                 @OA\Property(property="organizations", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="id", type="integer", example=228280),
     *                         @OA\Property(property="name", type="string", example="Tec de Monterrey Sandbox"),
     *                         @OA\Property(property="totals", type="object"),
     *                         @OA\Property(property="by_role", type="object")
     *                     )
     *                 ),
     *                 @OA\Property(property="last_synced_at", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso reports.sync.view")
     * )
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
     * @OA\Get(
     *     path="/api/v1/analytics/users",
     *     tags={"Analytics - Usuarios"},
     *     summary="Lista paginada de usuarios",
     *     description="Retorna todos los usuarios con datos de actividad y sesiones. Soporta filtrado por rol, estado de activación y búsqueda.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20, maximum=100)),
     *     @OA\Parameter(name="role", in="query", @OA\Schema(type="string", enum={"Student","Teacher","Administrator","Mentor","Monitor","TA"})),
     *     @OA\Parameter(name="activated", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string", maxLength=100)),
     *     @OA\Parameter(name="order_by", in="query", @OA\Schema(type="string", enum={"last_login_at","first_login_at","joined_at","first_name","last_name"})),
     *     @OA\Parameter(name="order_dir", in="query", @OA\Schema(type="string", enum={"asc","desc"})),
     *
     *     @OA\Response(response=200, description="Lista de usuarios",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(type="object",
     *
     *                     @OA\Property(property="neo_id", type="integer", example=13380636),
     *                     @OA\Property(property="sis_id", type="string", nullable=true),
     *                     @OA\Property(property="first_name", type="string"),
     *                     @OA\Property(property="last_name", type="string"),
     *                     @OA\Property(property="email", type="string", nullable=true),
     *                     @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="organization_name", type="string"),
     *                     @OA\Property(property="joined_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="first_login_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="activated", type="boolean"),
     *                     @OA\Property(property="days_since_login", type="integer", nullable=true),
     *                     @OA\Property(property="total_sessions", type="integer")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="total_pages", type="integer"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/analytics/users/daily-access",
     *     tags={"Analytics - Usuarios"},
     *     summary="Accesos diarios para gráfica de barras",
     *     description="Retorna sesiones agregadas por día para el período indicado. Incluye días sin actividad (valor 0) para series de tiempo continuas.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="days", in="query", @OA\Schema(type="integer", default=30, maximum=365)),
     *     @OA\Parameter(name="user_id", in="query", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Serie de tiempo de accesos",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="period", type="object",
     *                     @OA\Property(property="from", type="string", format="date"),
     *                     @OA\Property(property="to", type="string", format="date"),
     *                     @OA\Property(property="days", type="integer")
     *                 ),
     *                 @OA\Property(property="daily", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="date", type="string", format="date"),
     *                         @OA\Property(property="total_sessions", type="integer"),
     *                         @OA\Property(property="unique_users", type="integer"),
     *                         @OA\Property(property="total_minutes", type="number", nullable=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="totals", type="object",
     *                     @OA\Property(property="total_sessions", type="integer"),
     *                     @OA\Property(property="unique_users", type="integer"),
     *                     @OA\Property(property="total_minutes", type="number", nullable=true),
     *                     @OA\Property(property="avg_sessions_per_day", type="number", nullable=true)
     *                 )
     *             )
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/analytics/users/{neoId}",
     *     tags={"Analytics - Usuarios"},
     *     summary="Detalle completo de un usuario",
     *     description="Retorna perfil, sesiones, clases inscritas con resultados de assignments (grade_detail, metrics, graded_by) y daily streak con racha activa.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="neoId", in="path", required=true, @OA\Schema(type="integer"), example=13380644),
     *
     *     @OA\Response(response=200, description="Detalle del usuario",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="neo_id", type="integer", example=13380644),
     *                     @OA\Property(property="sis_id", type="string", nullable=true),
     *                     @OA\Property(property="first_name", type="string", example="Damian"),
     *                     @OA\Property(property="last_name", type="string", example="Blyne"),
     *                     @OA\Property(property="email", type="string", nullable=true),
     *                     @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="organization_name", type="string"),
     *                     @OA\Property(property="joined_at", type="string", format="date-time"),
     *                     @OA\Property(property="first_login_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="activated", type="boolean"),
     *                     @OA\Property(property="days_since_last_login", type="integer", nullable=true)
     *                 ),
     *                 @OA\Property(property="sessions_summary", type="object",
     *                     @OA\Property(property="total_sessions", type="integer", example=4),
     *                     @OA\Property(property="total_hours", type="number", example=0.1),
     *                     @OA\Property(property="avg_duration_minutes", type="number", nullable=true),
     *                     @OA\Property(property="first_session_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="last_session_at", type="string", format="date-time", nullable=true)
     *                 ),
     *                 @OA\Property(property="daily_activity", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="date", type="string", format="date"),
     *                         @OA\Property(property="sessions", type="integer"),
     *                         @OA\Property(property="total_minutes", type="number", nullable=true),
     *                         @OA\Property(property="avg_minutes", type="number", nullable=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="sessions", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="login_at", type="string", format="date-time"),
     *                         @OA\Property(property="logout_at", type="string", format="date-time", nullable=true),
     *                         @OA\Property(property="duration_minutes", type="number", nullable=true),
     *                         @OA\Property(property="ip_address", type="string", nullable=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="classes", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="neo_class_id", type="integer", example=5454236),
     *                         @OA\Property(property="class_name", type="string"),
     *                         @OA\Property(property="organization_name", type="string"),
     *                         @OA\Property(property="start_at", type="string", format="date", nullable=true),
     *                         @OA\Property(property="finish_at", type="string", format="date", nullable=true),
     *                         @OA\Property(property="enrolled_at", type="string", format="date-time"),
     *                         @OA\Property(property="enroll_type", type="string"),
     *                         @OA\Property(property="started", type="boolean"),
     *                         @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
     *                         @OA\Property(property="completed", type="boolean"),
     *                         @OA\Property(property="completed_at", type="string", format="date-time", nullable=true),
     *                         @OA\Property(property="unenrolled", type="boolean"),
     *                         @OA\Property(property="last_visited_at", type="string", format="date-time", nullable=true),
     *                         @OA\Property(property="time_spent_seconds", type="integer"),
     *                         @OA\Property(property="time_spent_hours", type="number"),
     *                         @OA\Property(property="percent", type="number", nullable=true),
     *                         @OA\Property(property="grade", type="string", nullable=true),
     *                         @OA\Property(property="result", type="array",
     *
     *                             @OA\Items(type="object",
     *
     *                                 @OA\Property(property="neo_result_id", type="integer"),
     *                                 @OA\Property(property="neo_grade_id", type="integer"),
     *                                 @OA\Property(property="neo_assignment_id", type="integer"),
     *                                 @OA\Property(property="neo_lesson_id", type="integer", nullable=true),
     *                                 @OA\Property(property="lesson_name", type="string", nullable=true),
     *                                 @OA\Property(property="assignment_name", type="string"),
     *                                 @OA\Property(property="assignment_type", type="string"),
     *                                 @OA\Property(property="question_id", type="integer", nullable=true),
     *                                 @OA\Property(property="response", type="string", nullable=true),
     *                                 @OA\Property(property="points", type="number", nullable=true),
     *                                 @OA\Property(property="score", type="number", nullable=true),
     *                                 @OA\Property(property="grade", type="string", nullable=true),
     *                                 @OA\Property(property="grade_detail", type="object",
     *                                     @OA\Property(property="started", type="boolean"),
     *                                     @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
     *                                     @OA\Property(property="finished", type="boolean"),
     *                                     @OA\Property(property="finished_at", type="string", format="date-time", nullable=true),
     *                                     @OA\Property(property="graded", type="integer"),
     *                                     @OA\Property(property="graded_at", type="string", format="date-time", nullable=true),
     *                                     @OA\Property(property="fully_graded", type="boolean"),
     *                                     @OA\Property(property="percent", type="number", nullable=true),
     *                                     @OA\Property(property="graded_by", type="object", nullable=true,
     *                                         @OA\Property(property="neo_id", type="integer"),
     *                                         @OA\Property(property="first_name", type="string"),
     *                                         @OA\Property(property="last_name", type="string"),
     *                                         @OA\Property(property="email", type="string", nullable=true)
     *                                     )
     *                                 ),
     *                                 @OA\Property(property="metrics", type="object",
     *                                     @OA\Property(property="duration_minutes", type="number", nullable=true),
     *                                     @OA\Property(property="feedback_minutes", type="number", nullable=true),
     *                                     @OA\Property(property="time_to_start_minutes", type="number", nullable=true),
     *                                     @OA\Property(property="submitted_on_time", type="boolean", nullable=true),
     *                                     @OA\Property(property="minutes_before_deadline", type="number", nullable=true)
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="daily_streak", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="neo_class_id", type="integer"),
     *                         @OA\Property(property="class_name", type="string"),
     *                         @OA\Property(property="history", type="array",
     *
     *                             @OA\Items(type="object",
     *
     *                                 @OA\Property(property="recorded_at", type="string", format="date-time"),
     *                                 @OA\Property(property="percent", type="number", nullable=true),
     *                                 @OA\Property(property="grade", type="string", nullable=true),
     *                                 @OA\Property(property="prev_percent", type="number", nullable=true),
     *                                 @OA\Property(property="prev_grade", type="string", nullable=true),
     *                                 @OA\Property(property="delta_percent", type="number", nullable=true),
     *                                 @OA\Property(property="time_spent_seconds", type="integer", nullable=true),
     *                                 @OA\Property(property="last_visited_at", type="string", format="date-time", nullable=true)
     *                             )
     *                         ),
     *                         @OA\Property(property="total_daily_streak", type="integer", example=2)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Usuario no encontrado")
     * )
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
     * @OA\Get(
     *     path="/api/v1/analytics/users/{neoId}/grades",
     *     tags={"Analytics - Usuarios"},
     *     summary="Calificaciones de un usuario",
     *     description="Retorna el perfil resumido del usuario, un resumen de desempeño (promedios, assignments completados) y el detalle de sus calificaciones en todas sus clases.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="neoId", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Calificaciones del usuario",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="summary", type="object"),
     *                 @OA\Property(property="grades", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Usuario no encontrado")
     * )
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
