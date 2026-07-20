<?php

declare(strict_types=1);

namespace Auth\User\Application\Setup2fa;

final readonly class Setup2faCommand
{
    public function __construct(
        public string $email,
    ) {}
}
