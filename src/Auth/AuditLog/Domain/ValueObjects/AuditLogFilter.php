<?php

declare(strict_types=1);

namespace Auth\AuditLog\Domain\ValueObjects;

use DateTimeImmutable;
use Shared\Domain\ValueObjects\Uuid;

final readonly class AuditLogFilter
{
    public function __construct(
        public ?Uuid $userId = null,
        public ?string $module = null,
        public ?string $action = null,
        public ?string $status = null,
        public ?DateTimeImmutable $from = null,
        public ?DateTimeImmutable $to = null,
        public int $page = 1,
        public int $perPage = 25,
    ) {}
}
