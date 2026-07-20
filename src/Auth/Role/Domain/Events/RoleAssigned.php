<?php

declare(strict_types=1);

namespace Auth\Role\Domain\Events;

use DateTimeImmutable;
use Shared\Domain\Contracts\DomainEvent;
use Shared\Domain\ValueObjects\Uuid;

final readonly class RoleAssigned implements DomainEvent
{
    private DateTimeImmutable $occurredAt;

    public function __construct(
        public Uuid $userId,
        public Uuid $roleId,
        public Uuid $assignedBy,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function name(): string
    {
        return 'auth.role.assigned';
    }

    public function payload(): array
    {
        return [
            'user_id' => $this->userId->toString(),
            'role_id' => $this->roleId->toString(),
            'assigned_by' => $this->assignedBy->toString(),
        ];
    }
}
