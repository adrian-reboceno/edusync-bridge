<?php

declare(strict_types=1);

namespace Auth\AuditLog\Infrastructure\Http\Controllers;

use Auth\AuditLog\Application\Query\GetAuditLogQuery;
use Auth\AuditLog\Application\Query\GetAuditLogUseCase;
use Auth\AuditLog\Domain\Entities\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use OpenApi\Annotations as OA;

final class AuditLogController extends Controller
{
    public function __construct(
        private readonly GetAuditLogUseCase $getAuditLogUseCase,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/audit-logs",
     *     tags={"AuditLog"},
     *     summary="Listar registros de auditoría",
     *     description="Retorna los registros de auditoría paginados, con filtros opcionales por usuario, módulo, acción, estado y rango de fechas.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="user_id", in="query", @OA\Schema(type="string", format="uuid")),
     *     @OA\Parameter(name="module", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="action", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=25)),
     *
     *     @OA\Response(response=200, description="Lista de registros de auditoría",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(type="object",
     *
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="user_id", type="string", format="uuid"),
     *                     @OA\Property(property="user_email", type="string", nullable=true),
     *                     @OA\Property(property="user_role", type="string", nullable=true),
     *                     @OA\Property(property="module", type="string"),
     *                     @OA\Property(property="action", type="string"),
     *                     @OA\Property(property="entity_type", type="string", nullable=true),
     *                     @OA\Property(property="entity_id", type="string", format="uuid", nullable=true),
     *                     @OA\Property(property="old_values", type="object", nullable=true),
     *                     @OA\Property(property="new_values", type="object", nullable=true),
     *                     @OA\Property(property="metadata", type="object", nullable=true),
     *                     @OA\Property(property="ip_address", type="string", nullable=true),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="timestamp", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="timestamp", type="string", format="date-time"),
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso auth.audit.view")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->queryFromRequest($request);
        $logs = $this->getAuditLogUseCase->execute($query);

        return response()->json([
            'data' => array_map($this->logToArray(...), $logs),
            'meta' => [
                'timestamp' => now()->toAtomString(),
                'page' => $query->page,
                'per_page' => $query->perPage,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/audit-logs/export",
     *     tags={"AuditLog"},
     *     summary="Exportar registros de auditoría a CSV",
     *     description="Exporta los registros de auditoría filtrados como archivo CSV descargable.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="user_id", in="query", @OA\Schema(type="string", format="uuid")),
     *     @OA\Parameter(name="module", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="action", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", @OA\Schema(type="string", format="date")),
     *
     *     @OA\Response(response=200, description="Archivo CSV de auditoría",
     *
     *         @OA\MediaType(mediaType="text/csv")
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso auth.audit.export")
     * )
     */
    public function export(Request $request): Response
    {
        $csv = $this->getAuditLogUseCase->export($this->queryFromRequest($request));

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit-logs.csv"',
        ]);
    }

    private function queryFromRequest(Request $request): GetAuditLogQuery
    {
        return new GetAuditLogQuery(
            userId: $request->input('user_id'),
            module: $request->input('module'),
            action: $request->input('action'),
            status: $request->input('status'),
            from: $request->input('from'),
            to: $request->input('to'),
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 25),
        );
    }

    private function logToArray(AuditLog $log): array
    {
        return [
            'id' => $log->getId()->toString(),
            'user_id' => $log->getUserId()->toString(),
            'user_email' => $log->getUserEmail(),
            'user_role' => $log->getUserRole(),
            'module' => $log->getModule(),
            'action' => $log->getAction(),
            'entity_type' => $log->getEntityType(),
            'entity_id' => $log->getEntityId()?->toString(),
            'old_values' => $log->getOldValues(),
            'new_values' => $log->getNewValues(),
            'metadata' => $log->getMetadata(),
            'ip_address' => $log->getIpAddress(),
            'status' => $log->getStatus(),
            'timestamp' => $log->getTimestamp()->format(DATE_ATOM),
        ];
    }
}
