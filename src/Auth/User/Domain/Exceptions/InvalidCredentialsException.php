<?php

declare(strict_types=1);

namespace Auth\User\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'The provided credentials are invalid.',
            errorCode: 'INVALID_CREDENTIALS',
        );
    }
}
