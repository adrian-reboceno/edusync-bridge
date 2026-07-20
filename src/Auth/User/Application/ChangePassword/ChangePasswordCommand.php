<?php

declare(strict_types=1);

namespace Auth\User\Application\ChangePassword;

final readonly class ChangePasswordCommand
{
    public function __construct(
        public string $userId,
        public string $currentPassword,
        public string $newPassword,
    ) {}
}
