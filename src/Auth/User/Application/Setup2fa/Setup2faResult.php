<?php

declare(strict_types=1);

namespace Auth\User\Application\Setup2fa;

final readonly class Setup2faResult
{
    public function __construct(
        public string $secret,
        public string $qrCodeUrl,
        public string $qrSvg,
    ) {}
}
