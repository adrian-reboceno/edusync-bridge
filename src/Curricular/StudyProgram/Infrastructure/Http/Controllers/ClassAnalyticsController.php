<?php

declare(strict_types=1);

namespace Curricular\StudyProgram\Infrastructure\Http\Controllers;

use Curricular\StudyProgram\Application\GetAnalytics\GetClassDetailUseCase;
use Curricular\StudyProgram\Application\GetAnalytics\GetClassesListQuery;
use Curricular\StudyProgram\Application\GetAnalytics\GetClassesListUseCase;
use Curricular\StudyProgram\Application\GetAnalytics\GetClassesSummaryUseCase;
use Curricular\StudyProgram\Infrastructure\Http\Requests\GetClassesListRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * @tags Analytics - Clases
 */
final class ClassAnalyticsController extends Controller
{
    public function __construct(
        private readonly GetClassesSummaryUseCase $summaryUseCase,
        private readonly GetClassesListUseCase $listUseCase,
        private readonly GetClassDetailUseCase $detailUseCase,
    ) {}

    /**
     * Resumen general de clases
     *
     * Retorna totales, distribución por organización y por estilo de clase.
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
     * Lista paginada de clases
     *
     * Retorna clases activas con métricas agregadas de inscripciones y docentes.
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
     * Detalle de una clase
     *
     * Retorna la clase, un resumen de inscripciones, docentes y alumnos.
     * Responde 404 si la clase no existe.
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
}
