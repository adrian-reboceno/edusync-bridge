<?php

declare(strict_types=1);

namespace Auth\Role\Application\SwitchRole;

use Auth\AuditLog\Domain\Entities\AuditLog;
use Auth\AuditLog\Domain\Ports\AuditLogRepositoryContract;
use Auth\Role\Domain\Entities\Role;
use Auth\Role\Domain\Events\RoleSwitched;
use Auth\Role\Domain\Ports\RoleRepositoryContract;
use Auth\User\Domain\Ports\SessionRepositoryContract;
use Auth\User\Domain\Ports\TokenServiceContract;
use Auth\User\Domain\Ports\UserRepositoryContract;
use DateTimeImmutable;
use Shared\Domain\Contracts\EventBusContract;
use Shared\Domain\Exceptions\DomainException;
use Shared\Domain\ValueObjects\Uuid;

final readonly class SwitchRoleUseCase
{
    private const int ACCESS_TOKEN_TTL_MINUTES = 15;

    public function __construct(
        private SessionRepositoryContract $sessions,
        private TokenServiceContract $tokens,
        private RoleRepositoryContract $roles,
        private AuditLogRepositoryContract $auditLogs,
        private EventBusContract $events,
        private UserRepositoryContract $users,
    ) {}

    public function execute(SwitchRoleCommand $command): SwitchRoleResult
    {
        $userId = Uuid::fromString($command->userId);
        $sessionId = Uuid::fromString($command->sessionId);
        $targetRoleId = Uuid::fromString($command->targetRoleId);

        $userRoles = $this->roles->getByUser($userId);
        $targetRole = $this->findAssignedRole($userRoles, $targetRoleId);

        if ($targetRole === null) {
            throw new DomainException('The user does not have the requested role assigned.', 'ROLE_NOT_ASSIGNED');
        }

        $session = $this->sessions->findById($sessionId);

        if ($session === null || $session->isRevoked()) {
            throw new DomainException('Session not found or revoked.', 'SESSION_NOT_FOUND');
        }

        $fromRoleId = $session->getActiveRoleId();

        $this->tokens->blacklist($command->currentAccessToken, self::ACCESS_TOKEN_TTL_MINUTES * 60);

        $permissions = $this->roles->getPermissionsForRole($targetRoleId);
        $newAccessToken = $this->tokens->issueAccessToken($userId, $targetRoleId, $permissions, $sessionId);

        $session->activateRole($targetRoleId);
        $session->rotateTokens(
            accessTokenHash: $this->tokens->hash($newAccessToken),
            refreshTokenHash: $session->getRefreshTokenHash(),
            accessExpiresAt: (new DateTimeImmutable())->modify('+'.self::ACCESS_TOKEN_TTL_MINUTES.' minutes'),
            refreshExpiresAt: $session->getRefreshExpiresAt(),
        );
        $this->sessions->save($session);

        $user = $this->users->findById($userId);
        $userEmail = $user?->getEmail()->toString() ?? '';

        $this->auditLogs->append(AuditLog::record(
            userId: $userId,
            userEmail: $userEmail,
            userRole: $targetRole->getName()->toString(),
            module: 'AUTH',
            action: 'ROLE_SWITCHED',
            ipAddress: $command->ipAddress,
            metadata: [
                'from_role_id' => $fromRoleId?->toString(),
                'to_role_id' => $targetRoleId->toString(),
            ],
        ));

        if ($fromRoleId !== null) {
            $this->events->dispatch(new RoleSwitched($userId, $sessionId, $fromRoleId, $targetRoleId));
        }

        return new SwitchRoleResult(
            accessToken: $newAccessToken,
            activeRole: [
                'id' => $targetRole->getId()->toString(),
                'name' => $targetRole->getName()->toString(),
                'display_name' => $targetRole->getDisplayName(),
                'hierarchy_level' => $targetRole->getHierarchyLevel()->toInt(),
            ],
        );
    }

    /**
     * @param  Role[]  $roles
     */
    private function findAssignedRole(array $roles, Uuid $targetRoleId): ?Role
    {
        foreach ($roles as $role) {
            if ($role->getId()->equals($targetRoleId)) {
                return $role;
            }
        }

        return null;
    }
}
