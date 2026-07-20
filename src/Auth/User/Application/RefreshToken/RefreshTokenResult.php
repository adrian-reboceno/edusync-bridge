<?php

declare(strict_types=1);

namespace Auth\User\Application\RefreshToken;

final readonly class RefreshTokenResult
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
    ) {}
}
