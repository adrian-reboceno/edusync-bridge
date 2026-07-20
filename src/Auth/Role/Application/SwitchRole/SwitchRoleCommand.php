<?php

declare(strict_types=1);

namespace Auth\Role\Application\SwitchRole;

final readonly class SwitchRoleCommand
{
    public function __construct(
        public string $sessionId,
        public string $currentAccessToken,
        public string $userId,
        public string $targetRoleId,
        public string $ipAddress,
    ) {}
}
