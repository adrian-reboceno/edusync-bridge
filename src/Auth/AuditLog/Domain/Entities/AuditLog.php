<?php

declare(strict_types=1);

namespace Auth\AuditLog\Domain\Entities;

use DateTimeImmutable;
use Shared\Domain\ValueObjects\Uuid;

final readonly class AuditLog
{
    private function __construct(
        private Uuid $id,
        private Uuid $userId,
        private string $userEmail,
        private string $userRole,
        private string $module,
        private string $action,
        private ?string $entityType,
        private ?Uuid $entityId,
        private ?array $oldValues,
        private ?array $newValues,
        private ?array $metadata,
        private string $ipAddress,
        private ?string $userAgent,
        private string $status,
        private DateTimeImmutable $timestamp,
    ) {}

    public static function record(
        Uuid $userId,
        string $userEmail,
        string $userRole,
        string $module,
        string $action,
        string $ipAddress,
        ?string $userAgent = null,
        ?string $entityType = null,
        ?Uuid $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        string $status = 'SUCCESS',
    ): self {
        return new self(
            id: Uuid::generate(),
            userId: $userId,
            userEmail: $userEmail,
            userRole: $userRole,
            module: $module,
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: $metadata,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            status: $status,
            timestamp: new DateTimeImmutable(),
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $userId,
        string $userEmail,
        string $userRole,
        string $module,
        string $action,
        ?string $entityType,
        ?Uuid $entityId,
        ?array $oldValues,
        ?array $newValues,
        ?array $metadata,
        string $ipAddress,
        ?string $userAgent,
        string $status,
        DateTimeImmutable $timestamp,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            userEmail: $userEmail,
            userRole: $userRole,
            module: $module,
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: $metadata,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            status: $status,
            timestamp: $timestamp,
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

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function getUserRole(): string
    {
        return $this->userRole;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function getEntityId(): ?Uuid
    {
        return $this->entityId;
    }

    public function getOldValues(): ?array
    {
        return $this->oldValues;
    }

    public function getNewValues(): ?array
    {
        return $this->newValues;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }
}
