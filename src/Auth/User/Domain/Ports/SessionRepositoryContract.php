<?php

declare(strict_types=1);

namespace Auth\User\Domain\Ports;

use Auth\User\Domain\Entities\UserSession;
use Shared\Domain\ValueObjects\Uuid;

interface SessionRepositoryContract
{
    public function findById(Uuid $id): ?UserSession;

    public function findActiveByUser(Uuid $userId, string $clientType): ?UserSession;

    public function findByRefreshTokenHash(string $refreshTokenHash): ?UserSession;

    public function save(UserSession $session): void;

    public function revoke(Uuid $sessionId): void;
}
