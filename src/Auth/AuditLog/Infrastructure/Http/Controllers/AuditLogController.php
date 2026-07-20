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

final class AuditLogController extends Controller
{
    public function __construct(
        private readonly GetAuditLogUseCase $getAuditLogUseCase,
    ) {}

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
