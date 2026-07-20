<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Uuid
{
    private const string PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    private function __construct(
        private string $value,
    ) {
        if (! preg_match(self::PATTERN, $value)) {
            throw new InvalidArgumentException("Invalid UUID format: {$value}");
        }
    }

    public static function fromString(string $value): self
    {
        return new self(strtolower($value));
    }

    public static function generate(): self
    {
        return new self(self::uuidV4());
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

    private static function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
