<?php

declare(strict_types=1);

namespace Auth\Role\Domain\Events;

use DateTimeImmutable;
use Shared\Domain\Contracts\DomainEvent;
use Shared\Domain\ValueObjects\Uuid;

final readonly class RoleSwitched implements DomainEvent
{
    private DateTimeImmutable $occurredAt;

    public function __construct(
        public Uuid $userId,
        public Uuid $sessionId,
        public Uuid $fromRoleId,
        public Uuid $toRoleId,
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
        return 'auth.role.switched';
    }

    public function payload(): array
    {
        return [
            'user_id' => $this->userId->toString(),
            'session_id' => $this->sessionId->toString(),
            'from_role_id' => $this->fromRoleId->toString(),
            'to_role_id' => $this->toRoleId->toString(),
        ];
    }
}
