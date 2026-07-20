<?php

declare(strict_types=1);

namespace Auth\Role\Application\RevokeRole;

final readonly class RevokeRoleCommand
{
    public function __construct(
        public string $targetUserId,
        public string $targetRoleId,
        public string $revokedBy,
        public string $actorRoleId,
        public string $ipAddress,
    ) {}
}
