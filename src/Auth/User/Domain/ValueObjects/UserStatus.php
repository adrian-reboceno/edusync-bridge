<?php

declare(strict_types=1);

namespace Auth\User\Domain\ValueObjects;

enum UserStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case LOCKED = 'LOCKED';
    case PENDING_VERIFICATION = 'PENDING_VERIFICATION';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isLocked(): bool
    {
        return $this === self::LOCKED;
    }

    public function canLogin(): bool
    {
        return match ($this) {
            self::ACTIVE => true,
            self::INACTIVE, self::LOCKED, self::PENDING_VERIFICATION => false,
        };
    }
}
