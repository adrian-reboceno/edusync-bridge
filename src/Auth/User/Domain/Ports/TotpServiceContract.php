<?php

declare(strict_types=1);

namespace Auth\User\Domain\Ports;

interface TotpServiceContract
{
    public function generateSecret(): string;

    public function getQrCodeUrl(string $secret, string $email, string $issuer = 'EduSync Bridge'): string;

    public function verify(string $secret, string $code): bool;
}
