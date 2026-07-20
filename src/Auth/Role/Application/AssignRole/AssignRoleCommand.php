<?php

declare(strict_types=1);

namespace Auth\Role\Application\AssignRole;

final readonly class AssignRoleCommand
{
    public function __construct(
        public string $targetUserId,
        public string $targetRoleId,
        public string $actorRoleId,
        public string $assignedBy,
        public string $ipAddress,
    ) {}
}
