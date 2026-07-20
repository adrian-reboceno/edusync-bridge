<?php

declare(strict_types=1);

namespace Auth\User\Application\Login;

final readonly class LoginCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public string $ipAddress,
        public string $userAgent,
        public string $clientType = 'WEB',
        public ?string $totpCode = null,
        public ?string $selectedRoleId = null,
    ) {}
}
