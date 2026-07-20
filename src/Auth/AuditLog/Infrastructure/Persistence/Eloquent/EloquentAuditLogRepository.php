<?php

declare(strict_types=1);

namespace Auth\AuditLog\Infrastructure\Persistence\Eloquent;

use Auth\AuditLog\Domain\Entities\AuditLog;
use Auth\AuditLog\Domain\Ports\AuditLogRepositoryContract;
use Auth\AuditLog\Domain\ValueObjects\AuditLogFilter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Csv\Writer;
use Shared\Domain\ValueObjects\Uuid;

final class EloquentAuditLogRepository implements AuditLogRepositoryContract
{
    public function append(AuditLog $log): void
    {
        DB::table('audit_logs')->insert([
            'id' => $log->getId()->toString(),
            'user_id' => $log->getUserId()->toString(),
            'user_email' => $log->getUserEmail(),
            'user_role' => $log->getUserRole(),
            'module' => $log->getModule(),
            'action' => $log->getAction(),
            'entity_type' => $log->getEntityType(),
            'entity_id' => $log->getEntityId()?->toString(),
            'old_values' => $log->getOldValues() !== null ? json_encode($log->getOldValues()) : null,
            'new_values' => $log->getNewValues() !== null ? json_encode($log->getNewValues()) : null,
            'metadata' => $log->getMetadata() !== null ? json_encode($log->getMetadata()) : null,
            'ip_address' => $log->getIpAddress(),
            'user_agent' => $log->getUserAgent(),
            'status' => $log->getStatus(),
            'timestamp' => $log->getTimestamp(),
        ]);
    }

    public function query(AuditLogFilter $filter): array
    {
        return $this->buildQuery($filter)
            ->forPage($filter->page, $filter->perPage)
            ->get()
            ->map(fn (EloquentAuditLogModel $model): AuditLog => $this->toDomain($model))
            ->all();
    }

    public function export(AuditLogFilter $filter): string
    {
        $models = $this->buildQuery($filter)->get();

        $csv = Writer::createFromString('');
        $csv->insertOne(['id', 'user_id', 'user_email', 'user_role', 'module', 'action', 'entity_type', 'entity_id', 'ip_address', 'status', 'timestamp']);

        foreach ($models as $model) {
            $csv->insertOne([
                $model->id,
                $model->user_id,
                $model->user_email,
                $model->user_role,
                $model->module,
                $model->action,
                $model->entity_type,
                $model->entity_id,
                $model->ip_address,
                $model->status,
                $model->timestamp->toAtomString(),
            ]);
        }

        return $csv->toString();
    }

    public function countTodayByUserAndAction(Uuid $userId, string $action): int
    {
        return EloquentAuditLogModel::query()
            ->where('user_id', $userId->toString())
            ->where('action', $action)
            ->whereDate('timestamp', now()->toDateString())
            ->count();
    }

    private function buildQuery(AuditLogFilter $filter): \Illuminate\Database\Eloquent\Builder
    {
        $query = EloquentAuditLogModel::query();

        if ($filter->userId !== null) {
            $query->where('user_id', $filter->userId->toString());
        }

        if ($filter->module !== null) {
            $query->where('module', $filter->module);
        }

        if ($filter->action !== null) {
            $query->where('action', $filter->action);
        }

        if ($filter->status !== null) {
            $query->where('status', $filter->status);
        }

        if ($filter->from !== null) {
            $query->where('timestamp', '>=', $filter->from);
        }

        if ($filter->to !== null) {
            $query->where('timestamp', '<=', $filter->to);
        }

        return $query->orderByDesc('timestamp');
    }

    private function toDomain(EloquentAuditLogModel $model): AuditLog
    {
        return AuditLog::reconstitute(
            id: Uuid::fromString($model->id),
            userId: Uuid::fromString($model->user_id),
            userEmail: $model->user_email,
            userRole: $model->user_role,
            module: $model->module,
            action: $model->action,
            entityType: $model->entity_type,
            entityId: $model->entity_id !== null ? Uuid::fromString($model->entity_id) : null,
            oldValues: $model->old_values,
            newValues: $model->new_values,
            metadata: $model->metadata,
            ipAddress: $model->ip_address,
            userAgent: $model->user_agent,
            status: $model->status,
            timestamp: $model->timestamp->toDateTimeImmutable(),
        );
    }
}
