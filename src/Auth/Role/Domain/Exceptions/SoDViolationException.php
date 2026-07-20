<?php

declare(strict_types=1);

namespace Auth\Role\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class SoDViolationException extends DomainException
{
    /**
     * @param  array<int, string>  $conflictingRoles
     */
    public function __construct(
        private readonly array $conflictingRoles,
        private readonly string $reason,
    ) {
        parent::__construct(
            message: "Segregation of Duties violation: {$reason}",
            errorCode: 'SOD_VIOLATION',
            context: ['conflicting_roles' => $conflictingRoles, 'reason' => $reason],
        );
    }

    /**
     * @return array<int, string>
     */
    public function conflictingRoles(): array
    {
        return $this->conflictingRoles;
    }
}
