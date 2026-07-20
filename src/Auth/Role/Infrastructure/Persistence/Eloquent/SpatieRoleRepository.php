<?php

declare(strict_types=1);

namespace Auth\Role\Infrastructure\Persistence\Eloquent;

use App\Models\User;
use Auth\Role\Domain\Entities\Role;
use Auth\Role\Domain\Ports\RoleRepositoryContract;
use Auth\Role\Domain\ValueObjects\HierarchyLevel;
use Auth\Role\Domain\ValueObjects\RoleName;
use Illuminate\Support\Facades\DB;
use Shared\Domain\ValueObjects\Uuid;

final class SpatieRoleRepository implements RoleRepositoryContract
{
    private const string MODEL_TYPE = User::class;

    public function findById(Uuid $id): ?Role
    {
        $model = EloquentRoleModel::query()->find($id->toString());

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByName(string $name): ?Role
    {
        $model = EloquentRoleModel::query()->where('name', $name)->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function getByUser(Uuid $userId): array
    {
        $models = EloquentRoleModel::query()
            ->join('model_has_roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $userId->toString())
            ->where('model_has_roles.model_type', self::MODEL_TYPE)
            ->select('roles.*')
            ->get();

        return $models->map(fn (EloquentRoleModel $model): Role => $this->toDomain($model))->all();
    }

    public function getExclusions(Uuid $roleId): array
    {
        $rows = DB::table('role_exclusions')
            ->where('role_a_id', $roleId->toString())
            ->orWhere('role_b_id', $roleId->toString())
            ->get(['role_a_id', 'role_b_id']);

        return $rows
            ->map(function (object $row) use ($roleId): Uuid {
                $otherId = $row->role_a_id === $roleId->toString() ? $row->role_b_id : $row->role_a_id;

                return Uuid::fromString($otherId);
            })
            ->all();
    }

    public function assignToUser(Uuid $userId, Uuid $roleId, Uuid $assignedBy): void
    {
        DB::table('model_has_roles')->insert([
            'role_id' => $roleId->toString(),
            'model_type' => self::MODEL_TYPE,
            'model_id' => $userId->toString(),
            'assigned_by' => $assignedBy->toString(),
            'assigned_at' => now(),
        ]);
    }

    public function revokeFromUser(Uuid $userId, Uuid $roleId): void
    {
        DB::table('model_has_roles')
            ->where('role_id', $roleId->toString())
            ->where('model_id', $userId->toString())
            ->where('model_type', self::MODEL_TYPE)
            ->delete();
    }

    public function getPermissionsForRole(Uuid $roleId): array
    {
        return DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('role_has_permissions.role_id', $roleId->toString())
            ->pluck('permissions.name')
            ->all();
    }

    public function all(): array
    {
        return EloquentRoleModel::query()->get()
            ->map(fn (EloquentRoleModel $model): Role => $this->toDomain($model))
            ->all();
    }

    private function toDomain(EloquentRoleModel $model): Role
    {
        return new Role(
            id: Uuid::fromString($model->id),
            name: new RoleName($model->name),
            displayName: $model->display_name,
            hierarchyLevel: new HierarchyLevel((int) $model->hierarchy_level),
            isSystem: (bool) $model->is_system,
            twoFactorRequired: (bool) $model->two_factor_required,
        );
    }
}
