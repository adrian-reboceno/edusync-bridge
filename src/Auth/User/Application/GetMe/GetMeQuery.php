<?php

declare(strict_types=1);

namespace Auth\User\Application\GetMe;

final readonly class GetMeQuery
{
    public function __construct(
        public string $userId,
        public string $roleId,
        public array $permissions,
        public string $sessionId,
    ) {}
}
