<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Role\Domain;

use Auth\AuditLog\Domain\Ports\AuditLogRepositoryContract;
use Auth\Role\Application\AssignRole\AssignRoleUseCase;
use Auth\Role\Domain\Entities\Role;
use Auth\Role\Domain\Exceptions\HierarchyViolationException;
use Auth\Role\Domain\Exceptions\MaxRolesExceededException;
use Auth\Role\Domain\Exceptions\SoDViolationException;
use Auth\Role\Domain\Exceptions\SuperAdminExclusiveException;
use Auth\Role\Domain\Ports\RoleRepositoryContract;
use Auth\Role\Domain\ValueObjects\HierarchyLevel;
use Auth\Role\Domain\ValueObjects\RoleName;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Shared\Domain\Contracts\EventBusContract;
use Shared\Domain\ValueObjects\Uuid;

final class AssignRoleRulesTest extends TestCase
{
    private AssignRoleUseCase $useCase;

    protected function setUp(): void
    {
        $this->useCase = new AssignRoleUseCase(
            $this->createStub(RoleRepositoryContract::class),
            $this->createStub(AuditLogRepositoryContract::class),
            $this->createStub(EventBusContract::class),
        );
    }

    private function role(string $name, int $hierarchyLevel, bool $isSystem = true, bool $twoFactorRequired = false): Role
    {
        return new Role(
            id: Uuid::generate(),
            name: new RoleName($name),
            displayName: ucfirst($name),
            hierarchyLevel: new HierarchyLevel($hierarchyLevel),
            isSystem: $isSystem,
            twoFactorRequired: $twoFactorRequired,
        );
    }

    private function invoke(string $method, array $args): void
    {
        $reflection = new ReflectionMethod($this->useCase, $method);
        $reflection->invokeArgs($this->useCase, $args);
    }

    public function test_hierarchy_validation_allows_assignment_when_actor_outranks_target(): void
    {
        $target = $this->role('operador-academico', 6);
        $actor = $this->role('admin-ti', 8);

        $this->invoke('assertHierarchy', [$target, $actor]);

        self::assertTrue(true);
    }

    public function test_hierarchy_validation_blocks_assignment_when_actor_does_not_outrank_target(): void
    {
        $target = $this->role('admin-ti', 8);
        $actor = $this->role('operador-academico', 6);

        $this->expectException(HierarchyViolationException::class);

        $this->invoke('assertHierarchy', [$target, $actor]);
    }

    public function test_sod_validation_blocks_when_target_role_conflicts_with_current_roles(): void
    {
        $auditor = $this->role('auditor', 2);
        $adminTi = $this->role('admin-ti', 8);

        $roles = $this->createMock(RoleRepositoryContract::class);
        $roles->method('getExclusions')->with($auditor->getId())->willReturn([$adminTi->getId()]);

        $useCase = new AssignRoleUseCase(
            $roles,
            $this->createStub(AuditLogRepositoryContract::class),
            $this->createStub(EventBusContract::class),
        );

        $this->expectException(SoDViolationException::class);

        (new ReflectionMethod($useCase, 'assertNoSoDConflict'))->invokeArgs($useCase, [$auditor, [$adminTi]]);
    }

    public function test_sod_validation_allows_when_no_conflicting_roles_are_currently_assigned(): void
    {
        $auditor = $this->role('auditor', 2);

        $roles = $this->createMock(RoleRepositoryContract::class);
        $roles->method('getExclusions')->willReturn([]);

        $useCase = new AssignRoleUseCase(
            $roles,
            $this->createStub(AuditLogRepositoryContract::class),
            $this->createStub(EventBusContract::class),
        );

        (new ReflectionMethod($useCase, 'assertNoSoDConflict'))->invokeArgs($useCase, [$auditor, []]);

        self::assertTrue(true);
    }

    public function test_cardinality_validation_blocks_when_user_already_has_max_active_roles(): void
    {
        $currentRole = $this->role('operador-academico', 6);

        $this->expectException(MaxRolesExceededException::class);

        $this->invoke('assertMaxRoles', [[$currentRole]]);
    }

    public function test_cardinality_validation_allows_when_user_has_no_active_roles(): void
    {
        $this->invoke('assertMaxRoles', [[]]);

        self::assertTrue(true);
    }

    public function test_super_admin_exclusive_blocks_assigning_super_admin_alongside_other_roles(): void
    {
        $superAdmin = $this->role('super-admin', 10);
        $existingRole = $this->role('auditor', 2);

        $this->expectException(SuperAdminExclusiveException::class);

        $this->invoke('assertSuperAdminExclusive', [$superAdmin, [$existingRole]]);
    }

    public function test_super_admin_exclusive_blocks_assigning_any_role_when_user_already_has_super_admin(): void
    {
        $target = $this->role('auditor', 2);
        $superAdmin = $this->role('super-admin', 10);

        $this->expectException(SuperAdminExclusiveException::class);

        $this->invoke('assertSuperAdminExclusive', [$target, [$superAdmin]]);
    }

    public function test_super_admin_exclusive_allows_assignment_when_no_super_admin_is_involved(): void
    {
        $target = $this->role('operador-academico', 6);
        $existingRole = $this->role('auditor', 2);

        $this->invoke('assertSuperAdminExclusive', [$target, [$existingRole]]);

        self::assertTrue(true);
    }
}
