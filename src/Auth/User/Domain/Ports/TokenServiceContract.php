<?php

declare(strict_types=1);

namespace Auth\User\Domain\Ports;

use Shared\Domain\ValueObjects\Uuid;

interface TokenServiceContract
{
    public function issueAccessToken(Uuid $userId, Uuid $roleId, array $permissions, Uuid $sessionId): string;

    public function issueRefreshToken(Uuid $sessionId): string;

    public function verifyAccessToken(string $token): array;

    public function verifyRefreshToken(string $token): array;

    public function hash(string $token): string;

    public function blacklist(string $jti, int $ttlSeconds): void;

    public function isBlacklisted(string $jti): bool;
}
