<?php

declare(strict_types=1);

namespace Auth\User\Application\RefreshToken;

final readonly class RefreshTokenCommand
{
    public function __construct(
        public string $refreshToken,
    ) {}
}
