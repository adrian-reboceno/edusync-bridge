<?php

declare(strict_types=1);

namespace Auth\AuditLog\Application\Query;

final readonly class GetAuditLogQuery
{
    public function __construct(
        public ?string $userId = null,
        public ?string $module = null,
        public ?string $action = null,
        public ?string $status = null,
        public ?string $from = null,
        public ?string $to = null,
        public int $page = 1,
        public int $perPage = 25,
    ) {}
}
