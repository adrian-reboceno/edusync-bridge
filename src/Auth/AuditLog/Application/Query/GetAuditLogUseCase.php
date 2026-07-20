<?php

declare(strict_types=1);

namespace Auth\AuditLog\Application\Query;

use Auth\AuditLog\Domain\Entities\AuditLog;
use Auth\AuditLog\Domain\Ports\AuditLogRepositoryContract;
use Auth\AuditLog\Domain\ValueObjects\AuditLogFilter;
use DateTimeImmutable;
use Shared\Domain\ValueObjects\Uuid;

final readonly class GetAuditLogUseCase
{
    public function __construct(
        private AuditLogRepositoryContract $auditLogs,
    ) {}

    /**
     * @return AuditLog[]
     */
    public function execute(GetAuditLogQuery $query): array
    {
        return $this->auditLogs->query($this->toFilter($query));
    }

    public function export(GetAuditLogQuery $query): string
    {
        return $this->auditLogs->export($this->toFilter($query));
    }

    private function toFilter(GetAuditLogQuery $query): AuditLogFilter
    {
        return new AuditLogFilter(
            userId: $query->userId !== null ? Uuid::fromString($query->userId) : null,
            module: $query->module,
            action: $query->action,
            status: $query->status,
            from: $query->from !== null ? new DateTimeImmutable($query->from) : null,
            to: $query->to !== null ? new DateTimeImmutable($query->to) : null,
            page: $query->page,
            perPage: $query->perPage,
        );
    }
}
