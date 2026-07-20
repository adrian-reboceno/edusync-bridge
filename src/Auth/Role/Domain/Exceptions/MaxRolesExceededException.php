<?php

declare(strict_types=1);

namespace Auth\Role\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class MaxRolesExceededException extends DomainException
{
    public function __construct(
        private readonly int $maxRoles,
        private readonly int $currentCount,
    ) {
        parent::__construct(
            message: "User already has the maximum of {$maxRoles} active role(s) ({$currentCount} assigned).",
            errorCode: 'MAX_ROLES_EXCEEDED',
            context: ['max_roles' => $maxRoles, 'current_count' => $currentCount],
        );
    }

    public function maxRoles(): int
    {
        return $this->maxRoles;
    }
}
