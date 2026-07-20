<?php

declare(strict_types=1);

namespace Auth\Role\Infrastructure\Http\Controllers;

use Auth\Role\Application\AssignRole\AssignRoleCommand;
use Auth\Role\Application\AssignRole\AssignRoleUseCase;
use Auth\Role\Application\RevokeRole\RevokeRoleCommand;
use Auth\Role\Application\RevokeRole\RevokeRoleUseCase;
use Auth\Role\Domain\Entities\Role;
use Auth\Role\Domain\Ports\RoleRepositoryContract;
use Auth\Role\Infrastructure\Http\Requests\AssignRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class RoleController extends Controller
{
    public function __construct(
        private readonly AssignRoleUseCase $assignRoleUseCase,
        private readonly RevokeRoleUseCase $revokeRoleUseCase,
        private readonly RoleRepositoryContract $roles,
    ) {}

    public function index(): JsonResponse
    {
        $roles = array_map($this->roleToArray(...), $this->roles->all());

        return response()->json([
            'data' => $roles,
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    public function assign(AssignRoleRequest $request, string $id): JsonResponse
    {
        $this->assignRoleUseCase->execute(new AssignRoleCommand(
            targetUserId: $id,
            targetRoleId: (string) $request->input('role_id'),
            actorRoleId: (string) $request->attributes->get('auth_role_id'),
            assignedBy: (string) $request->attributes->get('auth_user_id'),
            ipAddress: (string) $request->ip(),
        ));

        return response()->json([
            'data' => [
                'user_id' => $id,
                'role_id' => (string) $request->input('role_id'),
            ],
            'meta' => ['timestamp' => now()->toAtomString()],
        ], 201);
    }

    public function revoke(Request $request, string $id, string $roleId): JsonResponse
    {
        $this->revokeRoleUseCase->execute(new RevokeRoleCommand(
            targetUserId: $id,
            targetRoleId: $roleId,
            revokedBy: (string) $request->attributes->get('auth_user_id'),
            actorRoleId: (string) $request->attributes->get('auth_role_id'),
            ipAddress: (string) $request->ip(),
        ));

        return response()->json([
            'data' => ['message' => 'Role revoked successfully.'],
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    private function roleToArray(Role $role): array
    {
        return [
            'id' => $role->getId()->toString(),
            'name' => $role->getName()->toString(),
            'display_name' => $role->getDisplayName(),
            'hierarchy_level' => $role->getHierarchyLevel()->toInt(),
            'is_system' => $role->isSystem(),
            'two_factor_required' => $role->isTwoFactorRequired(),
        ];
    }
}
