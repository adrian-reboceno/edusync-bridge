<?php

declare(strict_types=1);

namespace Auth\Role\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class RoleName
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            throw new InvalidArgumentException('Role name cannot be empty');
        }

        if (! preg_match('/^[a-z0-9\-]+$/', $normalized)) {
            throw new InvalidArgumentException("Invalid role name: {$value}");
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
