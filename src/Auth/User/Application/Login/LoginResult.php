<?php

declare(strict_types=1);

namespace Auth\User\Application\Login;

final readonly class LoginResult
{
    public function __construct(
        public ?string $accessToken,
        public ?string $refreshToken,
        public array $user,
        public ?array $activeRole,
        public bool $requiresRoleSelection,
        public array $availableRoles,
        public bool $requiresTwoFactor,
        public bool $requiresTwoFactorSetup,
        public bool $mustChangePassword,
    ) {}
}
