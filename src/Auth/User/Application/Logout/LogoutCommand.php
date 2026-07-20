<?php

declare(strict_types=1);

namespace Auth\User\Application\Logout;

final readonly class LogoutCommand
{
    public function __construct(
        public string $sessionId,
        public string $accessToken,
        public string $userId,
        public string $ipAddress,
        public string $userAgent,
    ) {}
}
