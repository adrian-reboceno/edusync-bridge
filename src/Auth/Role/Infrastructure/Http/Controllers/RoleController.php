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
use OpenApi\Annotations as OA;

final class RoleController extends Controller
{
    public function __construct(
        private readonly AssignRoleUseCase $assignRoleUseCase,
        private readonly RevokeRoleUseCase $revokeRoleUseCase,
        private readonly RoleRepositoryContract $roles,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/roles",
     *     tags={"Role"},
     *     summary="Listar roles",
     *     description="Retorna todos los roles RBAC2 disponibles en el sistema.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Lista de roles",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(type="object",
     *
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string", example="super-admin"),
     *                     @OA\Property(property="display_name", type="string"),
     *                     @OA\Property(property="hierarchy_level", type="integer"),
     *                     @OA\Property(property="is_system", type="boolean"),
     *                     @OA\Property(property="two_factor_required", type="boolean")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso auth.roles.view")
     * )
     */
    public function index(): JsonResponse
    {
        $roles = array_map($this->roleToArray(...), $this->roles->all());

        return response()->json([
            'data' => $roles,
            'meta' => ['timestamp' => now()->toAtomString()],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/{id}/roles",
     *     tags={"Role"},
     *     summary="Asignar rol a un usuario",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\RequestBody(required=true,
     *
     *         @OA\JsonContent(required={"role_id"},
     *
     *             @OA\Property(property="role_id", type="string", format="uuid")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Rol asignado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="string", format="uuid"),
     *                 @OA\Property(property="role_id", type="string", format="uuid")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso auth.roles.assign")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/v1/users/{id}/roles/{roleId}",
     *     tags={"Role"},
     *     summary="Revocar rol de un usuario",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Parameter(name="roleId", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\Response(response=200, description="Rol revocado",
     *
     *         @OA\JsonContent(@OA\Property(property="data", type="object",
     *
     *             @OA\Property(property="message", type="string", example="Role revoked successfully.")
     *         ))
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso auth.roles.revoke")
     * )
     */
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
