<?php

declare(strict_types=1);

namespace Auth\User\Application\Enable2fa;

use Auth\AuditLog\Domain\Entities\AuditLog;
use Auth\AuditLog\Domain\Ports\AuditLogRepositoryContract;
use Auth\User\Domain\Exceptions\InvalidCredentialsException;
use Auth\User\Domain\Ports\TotpServiceContract;
use Auth\User\Domain\Ports\UserRepositoryContract;
use Auth\User\Domain\ValueObjects\Email;

final readonly class Enable2faUseCase
{
    public function __construct(
        private UserRepositoryContract $users,
        private TotpServiceContract $totp,
        private AuditLogRepositoryContract $auditLogs,
    ) {}

    public function execute(Enable2faCommand $command): void
    {
        $user = $this->users->findByEmail(new Email($command->email));

        if ($user === null) {
            throw new \RuntimeException('User not found.');
        }

        if (! $this->totp->verify($command->secret, $command->totpCode)) {
            throw new InvalidCredentialsException('Invalid TOTP code.');
        }

        $user->enableTwoFactor($command->secret);
        $this->users->save($user);

        $this->auditLogs->append(AuditLog::record(
            userId: $user->getId(),
            userEmail: $user->getEmail()->toString(),
            userRole: '',
            module: 'AUTH',
            action: 'TWO_FACTOR_ENABLED',
            ipAddress: $command->ipAddress,
            userAgent: $command->userAgent,
        ));
    }
}
