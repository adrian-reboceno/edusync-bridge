<?php

declare(strict_types=1);

namespace Auth\User\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class HashedPassword
{
    private function __construct(
        private string $hash,
    ) {
        if ($hash === '') {
            throw new InvalidArgumentException('Hashed password cannot be empty');
        }
    }

    public static function fromPlain(Password $plain): self
    {
        $hash = password_hash($plain->toString(), PASSWORD_BCRYPT, ['cost' => 12]);

        return new self($hash);
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function verify(Password $plain): bool
    {
        return password_verify($plain->toString(), $this->hash);
    }

    public function toString(): string
    {
        return $this->hash;
    }
}
