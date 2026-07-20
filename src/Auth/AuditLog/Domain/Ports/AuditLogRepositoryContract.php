<?php

declare(strict_types=1);

namespace Auth\AuditLog\Domain\Ports;

use Auth\AuditLog\Domain\Entities\AuditLog;
use Auth\AuditLog\Domain\ValueObjects\AuditLogFilter;
use Shared\Domain\ValueObjects\Uuid;

interface AuditLogRepositoryContract
{
    public function append(AuditLog $log): void;

    /**
     * @return AuditLog[]
     */
    public function query(AuditLogFilter $filter): array;

    public function export(AuditLogFilter $filter): string;

    public function countTodayByUserAndAction(Uuid $userId, string $action): int;
}
