<?php

declare(strict_types=1);

namespace Auth\User\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Password
{
    public function __construct(
        private string $plainText,
    ) {
        $this->validate();
    }

    public function hash(): HashedPassword
    {
        return HashedPassword::fromPlain($this);
    }

    public function toString(): string
    {
        return $this->plainText;
    }

    private function validate(): void
    {
        $errors = [];

        if (mb_strlen($this->plainText) < 8) {
            $errors[] = 'must be at least 8 characters long';
        }

        if (! preg_match('/[A-Z]/', $this->plainText)) {
            $errors[] = 'must contain at least one uppercase letter';
        }

        if (! preg_match('/[0-9]/', $this->plainText)) {
            $errors[] = 'must contain at least one number';
        }

        if (! preg_match('/[^a-zA-Z0-9]/', $this->plainText)) {
            $errors[] = 'must contain at least one special character';
        }

        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid password: '.implode(', ', $errors));
        }
    }
}
