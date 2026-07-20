<?php

declare(strict_types=1);

namespace Auth\User\Application\UnlockUser;

use Auth\AuditLog\Domain\Entities\AuditLog;
use Auth\AuditLog\Domain\Ports\AuditLogRepositoryContract;
use Auth\Role\Domain\Ports\RoleRepositoryContract;
use Auth\User\Domain\Ports\UserRepositoryContract;
use Shared\Domain\Exceptions\DomainException;
use Shared\Domain\ValueObjects\Uuid;

final readonly class UnlockUserUseCase
{
    public function __construct(
        private UserRepositoryContract $users,
        private AuditLogRepositoryContract $auditLogs,
        private RoleRepositoryContract $roles,
    ) {}

    public function execute(UnlockUserCommand $command): void
    {
        $targetUserId = Uuid::fromString($command->targetUserId);
        $user = $this->users->findById($targetUserId);

        if ($user === null) {
            throw new DomainException('User not found.', 'USER_NOT_FOUND');
        }

        $user->unlock();
        $this->users->save($user);

        $unlockedBy = Uuid::fromString($command->unlockedBy);
        $actor = $this->users->findById($unlockedBy);
        $actorRole = $this->roles->findById(Uuid::fromString($command->actorRoleId));

        $this->auditLogs->append(AuditLog::record(
            userId: $unlockedBy,
            userEmail: $actor?->getEmail()->toString() ?? '',
            userRole: $actorRole?->getName()->toString() ?? '',
            module: 'AUTH',
            action: 'USER_UNLOCKED',
            ipAddress: $command->ipAddress,
            entityType: 'user',
            entityId: $targetUserId,
        ));
    }
}
