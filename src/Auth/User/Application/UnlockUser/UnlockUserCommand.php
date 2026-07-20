<?php

declare(strict_types=1);

namespace Auth\User\Application\UnlockUser;

final readonly class UnlockUserCommand
{
    public function __construct(
        public string $targetUserId,
        public string $unlockedBy,
        public string $actorRoleId,
        public string $ipAddress,
    ) {}
}
