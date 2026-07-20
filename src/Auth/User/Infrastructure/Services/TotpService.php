<?php

declare(strict_types=1);

namespace Auth\User\Infrastructure\Services;

use Auth\User\Domain\Ports\TotpServiceContract;
use PragmaRX\Google2FA\Google2FA;

final class TotpService implements TotpServiceContract
{
    private const int CODE_LENGTH = 6;

    private const int PERIOD_SECONDS = 30;

    private readonly Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA();
        $this->engine->setKeyRegeneration(self::PERIOD_SECONDS);
        $this->engine->setOneTimePasswordLength(self::CODE_LENGTH);
    }

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey(32);
    }

    public function getQrCodeUrl(string $secret, string $email, string $issuer = 'EduSync Bridge'): string
    {
        return $this->engine->getQRCodeUrl($issuer, $email, $secret);
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->engine->verifyKey($secret, $code) !== false;
    }
}
