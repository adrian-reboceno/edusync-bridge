<?php

declare(strict_types=1);

namespace Auth\User\Domain\Exceptions;

use DateTimeImmutable;
use Shared\Domain\Exceptions\DomainException;

final class AccountLockedException extends DomainException
{
    public function __construct(
        private readonly DateTimeImmutable $lockedUntil,
    ) {
        parent::__construct(
            message: 'The account is locked until '.$lockedUntil->format(DateTimeImmutable::ATOM).'.',
            errorCode: 'ACCOUNT_LOCKED',
            context: ['locked_until' => $lockedUntil->format(DateTimeImmutable::ATOM)],
        );
    }

    public function lockedUntil(): DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function retryAfterSeconds(): int
    {
        return max(0, $this->lockedUntil->getTimestamp() - time());
    }
}
