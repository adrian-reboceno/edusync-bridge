<?php

declare(strict_types=1);

namespace Auth\Role\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class HierarchyViolationException extends DomainException
{
    public function __construct(
        private readonly string $actorRoleName,
        private readonly string $targetRoleName,
    ) {
        parent::__construct(
            message: "Role '{$actorRoleName}' does not have sufficient hierarchy to assign role '{$targetRoleName}'.",
            errorCode: 'HIERARCHY_VIOLATION',
            context: ['actor_role' => $actorRoleName, 'target_role' => $targetRoleName],
        );
    }

    public function actorRoleName(): string
    {
        return $this->actorRoleName;
    }

    public function targetRoleName(): string
    {
        return $this->targetRoleName;
    }
}
