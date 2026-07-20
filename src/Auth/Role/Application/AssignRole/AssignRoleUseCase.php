<?php

declare(strict_types=1);

namespace Auth\Role\Application\AssignRole;

use Auth\AuditLog\Domain\Entities\AuditLog;
use Auth\AuditLog\Domain\Ports\AuditLogRepositoryContract;
use Auth\Role\Domain\Entities\Role;
use Auth\Role\Domain\Events\RoleAssigned;
use Auth\Role\Domain\Exceptions\MaxRolesExceededException;
use Auth\Role\Domain\Exceptions\SoDViolationException;
use Auth\Role\Domain\Exceptions\SuperAdminExclusiveException;
use Auth\Role\Domain\Ports\RoleRepositoryContract;
use Auth\User\Domain\Ports\UserRepositoryContract;
use Shared\Domain\Contracts\EventBusContract;
use Shared\Domain\Exceptions\DomainException;
use Shared\Domain\ValueObjects\Uuid;

final readonly class AssignRoleUseCase
{
    private const int MAX_ACTIVE_ROLES = 1;

    public function __construct(
        private RoleRepositoryContract $roles,
        private AuditLogRepositoryContract $auditLogs,
        private EventBusContract $events,
        private UserRepositoryContract $users,
    ) {}

    public function execute(AssignRoleCommand $command): void
    {
        $targetUserId = Uuid::fromString($command->targetUserId);
        $targetRoleId = Uuid::fromString($command->targetRoleId);
        $actorRoleId = Uuid::fromString($command->actorRoleId);
        $assignedBy = Uuid::fromString($command->assignedBy);

        $targetRole = $this->roles->findById($targetRoleId);
        $actorRole = $this->roles->findById($actorRoleId);

        if ($targetRole === null || $actorRole === null) {
            throw new DomainException('Role not found.', 'ROLE_NOT_FOUND');
        }

        $currentRoles = $this->roles->getByUser($targetUserId);

        $this->assertHierarchy($targetRole, $actorRole);
        $this->assertNoSoDConflict($targetRole, $currentRoles);
        $this->assertMaxRoles($currentRoles);
        $this->assertSuperAdminExclusive($targetRole, $currentRoles);

        $this->roles->assignToUser($targetUserId, $targetRoleId, $assignedBy);

        $actor = $this->users->findById($assignedBy);

        $this->auditLogs->append(AuditLog::record(
            userId: $assignedBy,
            userEmail: $actor?->getEmail()->toString() ?? '',
            userRole: $actorRole->getName()->toString(),
            module: 'AUTH',
            action: 'ROLE_ASSIGNED',
            ipAddress: $command->ipAddress,
            entityType: 'user',
            entityId: $targetUserId,
            newValues: ['role_id' => $targetRoleId->toString(), 'role_name' => $targetRole->getName()->toString()],
        ));

        $this->events->dispatch(new RoleAssigned($targetUserId, $targetRoleId, $assignedBy));
    }

    private function assertHierarchy(Role $target, Role $actor): void
    {
        $target->assertCanBeAssignedTo($actor);
    }

    /**
     * @param  Role[]  $currentRoles
     */
    private function assertNoSoDConflict(Role $target, array $currentRoles): void
    {
        $exclusions = $this->roles->getExclusions($target->getId());

        if ($exclusions === []) {
            return;
        }

        $conflicting = [];

        foreach ($currentRoles as $currentRole) {
            foreach ($exclusions as $excludedRoleId) {
                if ($currentRole->getId()->equals($excludedRoleId)) {
                    $conflicting[] = $currentRole->getName()->toString();
                }
            }
        }

        if ($conflicting !== []) {
            throw new SoDViolationException(
                $conflicting,
                "Role '{$target->getName()->toString()}' conflicts with currently assigned role(s): ".implode(', ', $conflicting),
            );
        }
    }

    /**
     * @param  Role[]  $currentRoles
     */
    private function assertMaxRoles(array $currentRoles): void
    {
        if (count($currentRoles) >= self::MAX_ACTIVE_ROLES) {
            throw new MaxRolesExceededException(self::MAX_ACTIVE_ROLES, count($currentRoles));
        }
    }

    /**
     * @param  Role[]  $currentRoles
     */
    private function assertSuperAdminExclusive(Role $target, array $currentRoles): void
    {
        if ($target->isExclusive() && $currentRoles !== []) {
            throw new SuperAdminExclusiveException();
        }

        foreach ($currentRoles as $currentRole) {
            if ($currentRole->isExclusive()) {
                throw new SuperAdminExclusiveException();
            }
        }
    }
}
