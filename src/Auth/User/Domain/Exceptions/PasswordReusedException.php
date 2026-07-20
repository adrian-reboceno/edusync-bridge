<?php

declare(strict_types=1);

namespace Auth\User\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class PasswordReusedException extends DomainException
{
    public function __construct(int $historyLimit)
    {
        parent::__construct(
            message: "The new password must not match any of the last {$historyLimit} passwords used.",
            errorCode: 'PASSWORD_REUSED',
            context: ['history_limit' => $historyLimit],
        );
    }
}
