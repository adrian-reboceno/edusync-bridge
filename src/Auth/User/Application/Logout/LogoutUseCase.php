<?php

declare(strict_types=1);

namespace Auth\User\Application\Logout;

use Auth\AuditLog\Domain\Entities\AuditLog;
use Auth\AuditLog\Domain\Ports\AuditLogRepositoryContract;
use Auth\Role\Domain\Ports\RoleRepositoryContract;
use Auth\User\Domain\Ports\SessionRepositoryContract;
use Auth\User\Domain\Ports\TokenServiceContract;
use Auth\User\Domain\Ports\UserRepositoryContract;
use Shared\Domain\ValueObjects\Uuid;

final readonly class LogoutUseCase
{
    private const int ACCESS_TOKEN_TTL_MINUTES = 15;

    public function __construct(
        private SessionRepositoryContract $sessions,
        private TokenServiceContract $tokens,
        private AuditLogRepositoryContract $auditLogs,
        private UserRepositoryContract $users,
        private RoleRepositoryContract $roles,
    ) {}

    public function execute(LogoutCommand $command): void
    {
        $sessionId = Uuid::fromString($command->sessionId);
        $userId = Uuid::fromString($command->userId);

        $session = $this->sessions->findById($sessionId);

        $this->sessions->revoke($sessionId);

        $this->tokens->blacklist($command->accessToken, self::ACCESS_TOKEN_TTL_MINUTES * 60);

        $user = $this->users->findById($userId);
        $userEmail = $user?->getEmail()->toString() ?? '';

        $roleId = $session?->getActiveRoleId();
        $role = $roleId !== null ? $this->roles->findById($roleId) : null;
        $userRole = $role?->getName()->toString() ?? '';

        $this->auditLogs->append(AuditLog::record(
            userId: $userId,
            userEmail: $userEmail,
            userRole: $userRole,
            module: 'AUTH',
            action: 'LOGOUT',
            ipAddress: $command->ipAddress,
            userAgent: $command->userAgent,
        ));
    }
}
