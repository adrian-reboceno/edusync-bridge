<?php

declare(strict_types=1);

namespace Auth\Role\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class HierarchyLevel
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 1 || $value > 10) {
            throw new InvalidArgumentException("Hierarchy level must be between 1 and 10, got {$value}");
        }

        $this->value = $value;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->value > $other->value;
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
