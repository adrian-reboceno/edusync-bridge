<?php

declare(strict_types=1);

namespace Auth\Role\Application\SwitchRole;

final readonly class SwitchRoleResult
{
    public function __construct(
        public string $accessToken,
        public array $activeRole,
    ) {}
}
