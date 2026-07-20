<?php

declare(strict_types=1);

namespace Auth\User\Application\GetMe;

final readonly class GetMeResult
{
    public function __construct(
        public array $user,
        public string $activeRoleId,
        public array $permissions,
        public string $sessionId,
    ) {}
}
