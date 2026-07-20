<?php

declare(strict_types=1);

namespace Auth\Role\Application\RevokeRole;

use Auth\AuditLog\Domain\Entities\AuditLog;
use Auth\AuditLog\Domain\Ports\AuditLogRepositoryContract;
use Auth\Role\Domain\Events\RoleRevoked;
use Auth\Role\Domain\Ports\RoleRepositoryContract;
use Auth\User\Domain\Ports\UserRepositoryContract;
use Shared\Domain\Contracts\EventBusContract;
use Shared\Domain\Exceptions\DomainException;
use Shared\Domain\ValueObjects\Uuid;

final readonly class RevokeRoleUseCase
{
    public function __construct(
        private RoleRepositoryContract $roles,
        private AuditLogRepositoryContract $auditLogs,
        private EventBusContract $events,
        private UserRepositoryContract $users,
    ) {}

    public function execute(RevokeRoleCommand $command): void
    {
        $targetUserId = Uuid::fromString($command->targetUserId);
        $targetRoleId = Uuid::fromString($command->targetRoleId);
        $revokedBy = Uuid::fromString($command->revokedBy);

        $targetRole = $this->roles->findById($targetRoleId);

        if ($targetRole === null) {
            throw new DomainException('Role not found.', 'ROLE_NOT_FOUND');
        }

        $this->roles->revokeFromUser($targetUserId, $targetRoleId);

        $actor = $this->users->findById($revokedBy);
        $actorRole = $this->roles->findById(Uuid::fromString($command->actorRoleId));

        $this->auditLogs->append(AuditLog::record(
            userId: $revokedBy,
            userEmail: $actor?->getEmail()->toString() ?? '',
            userRole: $actorRole?->getName()->toString() ?? '',
            module: 'AUTH',
            action: 'ROLE_REVOKED',
            ipAddress: $command->ipAddress,
            entityType: 'user',
            entityId: $targetUserId,
            oldValues: ['role_id' => $targetRoleId->toString(), 'role_name' => $targetRole->getName()->toString()],
        ));

        $this->events->dispatch(new RoleRevoked($targetUserId, $targetRoleId, $revokedBy));
    }
}
