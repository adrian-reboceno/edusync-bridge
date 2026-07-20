<?php

declare(strict_types=1);

namespace Auth\User\Domain\Events;

use DateTimeImmutable;
use Shared\Domain\Contracts\DomainEvent;
use Shared\Domain\ValueObjects\Uuid;

final readonly class AccountLocked implements DomainEvent
{
    private DateTimeImmutable $occurredAt;

    public function __construct(
        public Uuid $userId,
        public DateTimeImmutable $lockedUntil,
        public int $durationMinutes,
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
        return 'auth.user.account_locked';
    }

    public function payload(): array
    {
        return [
            'user_id' => $this->userId->toString(),
            'locked_until' => $this->lockedUntil->format(DateTimeImmutable::ATOM),
            'duration_minutes' => $this->durationMinutes,
        ];
    }
}
