<?php

declare(strict_types=1);

namespace Auth\Role\Domain\Ports;

use Auth\Role\Domain\Entities\Role;
use Shared\Domain\ValueObjects\Uuid;

interface RoleRepositoryContract
{
    public function findById(Uuid $id): ?Role;

    public function findByName(string $name): ?Role;

    /**
     * @return Role[]
     */
    public function getByUser(Uuid $userId): array;

    /**
     * @return Uuid[]
     */
    public function getExclusions(Uuid $roleId): array;

    public function assignToUser(Uuid $userId, Uuid $roleId, Uuid $assignedBy): void;

    public function revokeFromUser(Uuid $userId, Uuid $roleId): void;

    /**
     * @return string[]
     */
    public function getPermissionsForRole(Uuid $roleId): array;

    /**
     * @return Role[]
     */
    public function all(): array;
}
