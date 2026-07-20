<?php

declare(strict_types=1);

namespace Auth\User\Domain\Entities;

use DateTimeImmutable;
use Shared\Domain\ValueObjects\Uuid;

final class UserSession
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $userId,
        private readonly string $clientType,
        private string $accessTokenHash,
        private string $refreshTokenHash,
        private DateTimeImmutable $accessExpiresAt,
        private DateTimeImmutable $refreshExpiresAt,
        private ?Uuid $activeRoleId,
        private ?DateTimeImmutable $roleActivatedAt,
        private DateTimeImmutable $lastActivityAt,
        private readonly string $ipAddress,
        private readonly ?string $userAgent,
        private ?DateTimeImmutable $revokedAt,
    ) {}

    public static function create(
        Uuid $userId,
        string $clientType,
        string $accessTokenHash,
        string $refreshTokenHash,
        DateTimeImmutable $accessExpiresAt,
        DateTimeImmutable $refreshExpiresAt,
        string $ipAddress,
        ?string $userAgent,
        ?Uuid $activeRoleId = null,
        ?Uuid $id = null,
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            id: $id ?? Uuid::generate(),
            userId: $userId,
            clientType: $clientType,
            accessTokenHash: $accessTokenHash,
            refreshTokenHash: $refreshTokenHash,
            accessExpiresAt: $accessExpiresAt,
            refreshExpiresAt: $refreshExpiresAt,
            activeRoleId: $activeRoleId,
            roleActivatedAt: $activeRoleId !== null ? $now : null,
            lastActivityAt: $now,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            revokedAt: null,
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $userId,
        string $clientType,
        string $accessTokenHash,
        string $refreshTokenHash,
        DateTimeImmutable $accessExpiresAt,
        DateTimeImmutable $refreshExpiresAt,
        ?Uuid $activeRoleId,
        ?DateTimeImmutable $roleActivatedAt,
        DateTimeImmutable $lastActivityAt,
        string $ipAddress,
        ?string $userAgent,
        ?DateTimeImmutable $revokedAt,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            clientType: $clientType,
            accessTokenHash: $accessTokenHash,
            refreshTokenHash: $refreshTokenHash,
            accessExpiresAt: $accessExpiresAt,
            refreshExpiresAt: $refreshExpiresAt,
            activeRoleId: $activeRoleId,
            roleActivatedAt: $roleActivatedAt,
            lastActivityAt: $lastActivityAt,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            revokedAt: $revokedAt,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getClientType(): string
    {
        return $this->clientType;
    }

    public function getAccessTokenHash(): string
    {
        return $this->accessTokenHash;
    }

    public function getRefreshTokenHash(): string
    {
        return $this->refreshTokenHash;
    }

    public function getAccessExpiresAt(): DateTimeImmutable
    {
        return $this->accessExpiresAt;
    }

    public function getRefreshExpiresAt(): DateTimeImmutable
    {
        return $this->refreshExpiresAt;
    }

    public function getActiveRoleId(): ?Uuid
    {
        return $this->activeRoleId;
    }

    public function getLastActivityAt(): DateTimeImmutable
    {
        return $this->lastActivityAt;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function isRefreshExpired(): bool
    {
        return $this->refreshExpiresAt < new DateTimeImmutable();
    }

    public function activateRole(Uuid $roleId): void
    {
        $this->activeRoleId = $roleId;
        $this->roleActivatedAt = new DateTimeImmutable();
    }

    public function rotateTokens(
        string $accessTokenHash,
        string $refreshTokenHash,
        DateTimeImmutable $accessExpiresAt,
        DateTimeImmutable $refreshExpiresAt,
    ): void {
        $this->accessTokenHash = $accessTokenHash;
        $this->refreshTokenHash = $refreshTokenHash;
        $this->accessExpiresAt = $accessExpiresAt;
        $this->refreshExpiresAt = $refreshExpiresAt;
        $this->touch();
    }

    public function touch(): void
    {
        $this->lastActivityAt = new DateTimeImmutable();
    }

    public function revoke(): void
    {
        $this->revokedAt = new DateTimeImmutable();
    }
}
