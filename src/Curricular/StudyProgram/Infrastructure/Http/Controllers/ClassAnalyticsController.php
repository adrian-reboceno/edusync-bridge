<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Infrastructure\Http\Controllers;

use Curricular\CurriculumMap\Application\GetAnalytics\GetClassLessonsUseCase;
use Curricular\StudyProgram\Application\GetAnalytics\GetClassDetailUseCase;
use Curricular\StudyProgram\Application\GetAnalytics\GetClassesListQuery;
use Curricular\StudyProgram\Application\GetAnalytics\GetClassesListUseCase;
use Curricular\StudyProgram\Application\GetAnalytics\GetClassesSummaryUseCase;
use Curricular\StudyProgram\Infrastructure\Http\Requests\GetClassesListRequest;
use Grades\Grade\Application\GetAnalytics\GetAssignmentGradesUseCase;
use Grades\Grade\Application\GetAnalytics\GetAssignmentResultsUseCase;
use Grades\Grade\Application\GetAnalytics\GetClassAssignmentsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OpenApi\Annotations as OA;

final class ClassAnalyticsController extends Controller
{
    public function __construct(
        private readonly GetClassesSummaryUseCase $summaryUseCase,
        private readonly GetClassesListUseCase $listUseCase,
        private readonly GetClassDetailUseCase $detailUseCase,
        private readonly GetClassLessonsUseCase $lessonsUseCase,
        private readonly GetClassAssignmentsUseCase $assignmentsUseCase,
        private readonly GetAssignmentGradesUseCase $assignmentGradesUseCase,
        private readonly GetAssignmentResultsUseCase $assignmentResultsUseCase,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/analytics/classes/summary",
     *     tags={"Analytics - Clases"},
     *     summary="Resumen general de clases",
     *     description="Retorna totales, distribución por organización y por estilo de clase.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Resumen de clases",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="totals", type="object",
     *                     @OA\Property(property="total_classes", type="integer"),
     *                     @OA\Property(property="active", type="integer"),
     *                     @OA\Property(property="archived", type="integer"),
     *                     @OA\Property(property="total_enrollments", type="integer"),
     *                     @OA\Property(property="total_teachers", type="integer")
     *                 ),
     *                 @OA\Property(property="by_organization", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="by_style", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="last_synced_at", type="string", format="date-time", nullable=true)
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
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
                    'total_classes' => $result->totalClasses,
                    'active' => $result->active,
                    'archived' => $result->archived,
                    'total_enrollments' => $result->totalEnrollments,
                    'total_teachers' => $result->totalTeachers,
                ],
                'by_organization' => $result->byOrganization,
                'by_style' => $result->byStyle,
                'last_synced_at' => $result->lastSyncedAt,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/analytics/classes",
     *     tags={"Analytics - Clases"},
     *     summary="Lista paginada de clases",
     *     description="Retorna clases activas con métricas agregadas de inscripciones y docentes.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20, maximum=100)),
     *
     *     @OA\Response(response=200, description="Lista de clases",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="timestamp", type="string", format="date-time"),
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="total_pages", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(GetClassesListRequest $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $page = max((int) $request->input('page', 1), 1);

        $result = $this->listUseCase->execute(new GetClassesListQuery(
            page: $page,
            perPage: $perPage,
        ));

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
     *     path="/api/v1/analytics/classes/{neoId}",
     *     tags={"Analytics - Clases"},
     *     summary="Detalle de una clase",
     *     description="Retorna la clase, un resumen de inscripciones, docentes y alumnos.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="neoId", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Detalle de la clase",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="class", type="object"),
     *                 @OA\Property(property="summary", type="object"),
     *                 @OA\Property(property="teachers", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="students", type="array", @OA\Items(type="object"))
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Clase no encontrada")
     * )
     */
    public function show(int $neoId): JsonResponse
    {
        $result = $this->detailUseCase->execute($neoId);

        if ($result === null) {
            return response()->json([
                'errors' => [[
                    'code' => 'NOT_FOUND',
                    'message' => "Class with neo_id {$neoId} not found.",
                    'field' => null,
                ]],
            ], 404);
        }

        return response()->json([
            'data' => [
                'class' => $result->class,
                'summary' => $result->summary,
                'teachers' => $result->teachers,
                'students' => $result->students,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/analytics/classes/{neoId}/lessons",
     *     tags={"Analytics - Clases"},
     *     summary="Lessons y sections de una clase",
     *     description="Retorna el curriculum (lessons con sus sections anidadas) de una clase.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="neoId", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Curriculum de la clase",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="class_id", type="integer"),
     *                 @OA\Property(property="class_name", type="string"),
     *                 @OA\Property(property="total_lessons", type="integer"),
     *                 @OA\Property(property="total_sections", type="integer"),
     *                 @OA\Property(property="lessons", type="array", @OA\Items(type="object"))
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Clase no encontrada")
     * )
     */
    public function lessons(int $neoId): JsonResponse
    {
        $result = $this->lessonsUseCase->execute($neoId);

        if ($result === null) {
            return response()->json([
                'errors' => [[
                    'code' => 'NOT_FOUND',
                    'message' => "Class with neo_id {$neoId} not found.",
                    'field' => null,
                ]],
            ], 404);
        }

        return response()->json([
            'data' => [
                'class_id' => $result->classId,
                'class_name' => $result->className,
                'total_lessons' => $result->totalLessons,
                'total_sections' => $result->totalSections,
                'lessons' => $result->lessons,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/analytics/classes/{neoId}/assignments",
     *     tags={"Analytics - Clases"},
     *     summary="Assignments de una clase",
     *     description="Retorna los assignments de una clase junto con totales agregados (entregados, pendientes, puntos, distribución por tipo y por modo de calificación).",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="neoId", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Assignments de la clase",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="class_id", type="integer"),
     *                 @OA\Property(property="class_name", type="string"),
     *                 @OA\Property(property="totals", type="object"),
     *                 @OA\Property(property="assignments", type="array", @OA\Items(type="object"))
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Clase no encontrada")
     * )
     */
    public function assignments(int $neoId): JsonResponse
    {
        $result = $this->assignmentsUseCase->execute($neoId);

        if ($result === null) {
            return response()->json([
                'errors' => [[
                    'code' => 'NOT_FOUND',
                    'message' => "Class with neo_id {$neoId} not found.",
                    'field' => null,
                ]],
            ], 404);
        }

        return response()->json([
            'data' => [
                'class_id' => $result->classId,
                'class_name' => $result->className,
                'totals' => $result->totals,
                'assignments' => $result->assignments,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/analytics/classes/{neoId}/assignments/{assignmentId}/grades",
     *     tags={"Analytics - Clases"},
     *     summary="Calificaciones de un assignment",
     *     description="Retorna el assignment, estadísticas agregadas (promedios, distribución de calificaciones) y la lista de calificaciones por alumno.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="neoId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="assignmentId", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Calificaciones del assignment",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="assignment", type="object"),
     *                 @OA\Property(property="stats", type="object"),
     *                 @OA\Property(property="grades", type="array", @OA\Items(type="object"))
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Clase o assignment no encontrados")
     * )
     */
    public function assignmentGrades(int $neoId, int $assignmentId): JsonResponse
    {
        $result = $this->assignmentGradesUseCase->execute($neoId, $assignmentId);

        if ($result === null) {
            return response()->json([
                'errors' => [[
                    'code' => 'NOT_FOUND',
                    'message' => "Assignment {$assignmentId} not found in class {$neoId}.",
                    'field' => null,
                ]],
            ], 404);
        }

        return response()->json([
            'data' => [
                'assignment' => $result->assignment,
                'stats' => $result->stats,
                'grades' => $result->grades,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/analytics/classes/{neoId}/assignments/{assignmentId}/results",
     *     tags={"Analytics - Clases"},
     *     summary="Resultados de un assignment",
     *     description="Retorna las respuestas individuales de los alumnos para un assignment (una por pregunta en quizzes, una por entrega en texto libre).",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="neoId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="assignmentId", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Resultados del assignment",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="assignment", type="object"),
     *                 @OA\Property(property="total_results", type="integer"),
     *                 @OA\Property(property="results", type="array", @OA\Items(type="object"))
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Clase o assignment no encontrados")
     * )
     */
    public function assignmentResults(int $neoId, int $assignmentId): JsonResponse
    {
        $result = $this->assignmentResultsUseCase->execute($neoId, $assignmentId);

        if ($result === null) {
            return response()->json([
                'errors' => [[
                    'code' => 'NOT_FOUND',
                    'message' => "Assignment {$assignmentId} not found in class {$neoId}.",
                    'field' => null,
                ]],
            ], 404);
        }

        return response()->json([
            'data' => [
                'assignment' => $result->assignment,
                'total_results' => $result->totalResults,
                'results' => $result->results,
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }
}
