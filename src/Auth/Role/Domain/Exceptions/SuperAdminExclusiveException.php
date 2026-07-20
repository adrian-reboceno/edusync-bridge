<?php

declare(strict_types=1);

namespace Auth\Role\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class SuperAdminExclusiveException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'The super-admin role cannot be combined with any other role.',
            errorCode: 'SUPER_ADMIN_EXCLUSIVE',
        );
    }
}
