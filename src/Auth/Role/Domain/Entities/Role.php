<?php

declare(strict_types=1);

namespace Auth\Role\Domain\Entities;

use Auth\Role\Domain\Exceptions\HierarchyViolationException;
use Auth\Role\Domain\ValueObjects\HierarchyLevel;
use Auth\Role\Domain\ValueObjects\RoleName;
use Shared\Domain\ValueObjects\Uuid;

final readonly class Role
{
    public function __construct(
        private Uuid $id,
        private RoleName $name,
        private ?string $displayName,
        private HierarchyLevel $hierarchyLevel,
        private bool $isSystem,
        private bool $twoFactorRequired,
    ) {}

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): RoleName
    {
        return $this->name;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getHierarchyLevel(): HierarchyLevel
    {
        return $this->hierarchyLevel;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function isTwoFactorRequired(): bool
    {
        return $this->twoFactorRequired;
    }

    public function isExclusive(): bool
    {
        return $this->name->toString() === 'super-admin';
    }

    public function assertCanBeAssignedTo(Role $actorRole): void
    {
        if ($actorRole->hierarchyLevel->isGreaterThan($this->hierarchyLevel)) {
            return;
        }

        throw new HierarchyViolationException($actorRole->getName()->toString(), $this->name->toString());
    }
}
