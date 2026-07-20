<?php

declare(strict_types=1);

namespace Auth\User\Domain\Events;

use DateTimeImmutable;
use Shared\Domain\Contracts\DomainEvent;
use Shared\Domain\ValueObjects\Uuid;

final readonly class LoginFailed implements DomainEvent
{
    private DateTimeImmutable $occurredAt;

    public function __construct(
        public Uuid $userId,
        public int $failedAttempts,
        public string $ipAddress,
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
        return 'auth.user.login_failed';
    }

    public function payload(): array
    {
        return [
            'user_id' => $this->userId->toString(),
            'failed_attempts' => $this->failedAttempts,
            'ip_address' => $this->ipAddress,
        ];
    }
}
