<?php

declare(strict_types=1);

namespace Auth\User\Application\Enable2fa;

final readonly class Enable2faCommand
{
    public function __construct(
        public string $email,
        public string $secret,
        public string $totpCode,
        public string $ipAddress,
        public string $userAgent,
    ) {}
}
