<?php

namespace Database\Seeders;

use App\Models\User;
use Auth\Role\Infrastructure\Persistence\Eloquent\EloquentPermissionModel;
use Auth\Role\Infrastructure\Persistence\Eloquent\EloquentRoleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthSeeder extends Seeder
{
    private const array ROLES = [
        ['name' => 'super-admin',        'display_name' => 'Super Administrador', 'hierarchy_level' => 10, 'is_system' => true, 'two_factor_required' => true],
        ['name' => 'admin-ti',           'display_name' => 'Administrador TI',    'hierarchy_level' => 8,  'is_system' => true, 'two_factor_required' => true],
        ['name' => 'operador-academico', 'display_name' => 'Operador Académico',  'hierarchy_level' => 6,  'is_system' => true, 'two_factor_required' => false],
        ['name' => 'auditor',            'display_name' => 'Auditor',             'hierarchy_level' => 2,  'is_system' => true, 'two_factor_required' => true],
    ];

    private const array PERMISSIONS = [
        // auth
        'auth.users.create'         => 'Crear usuarios',
        'auth.users.view'           => 'Ver usuarios',
        'auth.users.edit'           => 'Editar usuarios',
        'auth.users.deactivate'     => 'Desactivar usuarios',
        'auth.users.unlock'         => 'Desbloquear cuentas',
        'auth.users.force-password' => 'Forzar cambio de contraseña',
        'auth.roles.view'           => 'Ver roles y permisos',
        'auth.roles.assign'         => 'Asignar roles',
        'auth.roles.revoke'         => 'Revocar roles',
        'auth.sessions.view'        => 'Ver sesiones activas',
        'auth.sessions.revoke'      => 'Revocar sesiones',
        'auth.audit.view'           => 'Ver audit log',
        'auth.audit.export'         => 'Exportar audit log',
        // sync
        'sync.students.view'        => 'Ver sync de alumnos',
        'sync.students.trigger'     => 'Disparar sync de alumnos',
        'sync.teachers.view'        => 'Ver sync de docentes',
        'sync.teachers.trigger'     => 'Disparar sync de docentes',
        'sync.enrollments.view'     => 'Ver sync de inscripciones',
        'sync.enrollments.trigger'  => 'Disparar sync de inscripciones',
        'sync.programs.view'        => 'Ver sync de programas de estudio',
        'sync.programs.trigger'     => 'Disparar sync de programas',
        'sync.grades.view'          => 'Ver sync de calificaciones',
        'sync.grades.trigger'       => 'Disparar sync de calificaciones',
        // csv
        'csv.upload'                => 'Subir archivos CSV',
        'csv.preview'               => 'Previsualizar CSV',
        'csv.validate'              => 'Validar CSV',
        'csv.process'               => 'Procesar CSV a NEO LMS',
        // scheduler
        'scheduler.view'            => 'Ver configuración del scheduler',
        'scheduler.edit'            => 'Editar horarios de Jobs',
        'scheduler.toggle'          => 'Activar/desactivar Jobs',
        // adapter
        'adapter.view'              => 'Ver configuración de adaptadores',
        'adapter.switch'            => 'Cambiar adaptador CE (DB o API)',
        'adapter.credentials'       => 'Editar credenciales de adaptadores',
        'adapter.health-check'      => 'Ejecutar health-check de adaptadores',
        // horizon
        'horizon.view'              => 'Ver dashboard de Horizon',
        'horizon.manage'            => 'Pausar/reanudar Horizon',
        // reports
        'reports.sync.view'         => 'Ver reportes de sincronización',
        'reports.sync.export'       => 'Exportar reportes de sync',
        'reports.errors.view'       => 'Ver errores de sync',
    ];

    private const array ROLE_PERMISSIONS = [
        'super-admin' => '*', // todos los permisos

        'admin-ti' => [
            'auth.users.view',
            'auth.roles.view',
            'auth.audit.view',
            'auth.audit.export',
            'sync.students.view',    'sync.students.trigger',
            'sync.teachers.view',    'sync.teachers.trigger',
            'sync.enrollments.view', 'sync.enrollments.trigger',
            'sync.programs.view',    'sync.programs.trigger',
            'sync.grades.view',      'sync.grades.trigger',
            'csv.upload', 'csv.preview', 'csv.validate', 'csv.process',
            'scheduler.view', 'scheduler.edit', 'scheduler.toggle',
            'adapter.view', 'adapter.switch', 'adapter.credentials', 'adapter.health-check',
            'horizon.view', 'horizon.manage',
            'reports.sync.view', 'reports.sync.export', 'reports.errors.view',
        ],

        'operador-academico' => [
            'sync.students.view',    'sync.students.trigger',
            'sync.teachers.view',    'sync.teachers.trigger',
            'sync.enrollments.view', 'sync.enrollments.trigger',
            'sync.programs.view',    'sync.programs.trigger',
            'sync.grades.view',      'sync.grades.trigger',
            'csv.upload', 'csv.preview', 'csv.validate', 'csv.process',
            'scheduler.view',
            'adapter.view', 'adapter.health-check',
            'horizon.view',
            'reports.sync.view', 'reports.errors.view',
        ],

        'auditor' => [
            'auth.users.view',
            'auth.audit.view', 'auth.audit.export',
            'sync.students.view',
            'sync.teachers.view',
            'sync.enrollments.view',
            'sync.programs.view',
            'sync.grades.view',
            'scheduler.view',
            'adapter.view',
            'horizon.view',
            'reports.sync.view', 'reports.sync.export', 'reports.errors.view',
        ],
    ];

    public function run(): void
    {
        // ── 1. Roles ──────────────────────────────────────────────
        $roles = [];
        foreach (self::ROLES as $role) {
            $roles[$role['name']] = EloquentRoleModel::query()->updateOrCreate(
                ['name' => $role['name'], 'guard_name' => 'api'],
                [
                    'display_name'       => $role['display_name'],
                    'hierarchy_level'    => $role['hierarchy_level'],
                    'is_system'          => $role['is_system'],
                    'two_factor_required'=> $role['two_factor_required'],
                ],
            );
        }

        // ── 2. Permisos ───────────────────────────────────────────
        $permissions = [];
        foreach (self::PERMISSIONS as $name => $displayName) {
            $permissions[$name] = EloquentPermissionModel::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'api'],
                ['display_name' => $displayName],
            );
        }

        // ── 3. Asignación rol → permisos ──────────────────────────
        foreach (self::ROLE_PERMISSIONS as $roleName => $permNames) {
            $role = $roles[$roleName];

            DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->delete();

            $permList = $permNames === '*'
                ? array_keys(self::PERMISSIONS)
                : $permNames;

            $inserts = array_map(
                fn(string $p) => ['role_id' => $role->id, 'permission_id' => $permissions[$p]->id],
                $permList,
            );

            DB::table('role_has_permissions')->insert($inserts);
        }

        // ── 4. SSoD — Exclusiones ─────────────────────────────────
        $exclusions = [
            ['super-admin', 'admin-ti',           'ABSOLUTE',     'super-admin es exclusivo'],
            ['super-admin', 'operador-academico',  'ABSOLUTE',     'super-admin es exclusivo'],
            ['super-admin', 'auditor',             'ABSOLUTE',     'super-admin es exclusivo'],
            ['auditor',     'operador-academico',  'ABSOLUTE',     'El auditor no puede operar lo que audita'],
            ['auditor',     'admin-ti',            'RECOMMENDED',  'El auditor no debería auditar su propia configuración'],
        ];

        foreach ($exclusions as [$a, $b, $level, $reason]) {
            DB::table('role_exclusions')->updateOrInsert(
                ['role_a_id' => $roles[$a]->id, 'role_b_id' => $roles[$b]->id],
                [
                    'id'         => (string) Str::uuid(),
                    'level'      => $level,
                    'reason'     => $reason,
                    'is_system'  => true,
                    'created_at' => now(),
                ],
            );
        }

        // ── 5. Usuario super-admin inicial ────────────────────────
        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'admin@edusync.edu'],
            [
                'first_name'          => 'Super',
                'last_name'           => 'Admin',
                'password_hash'       => Hash::make('Admin@2024!'),
                'status'              => 'ACTIVE',
                'must_change_password'=> true,
                'email_verified_at'   => now(),
            ],
        );

        DB::table('model_has_roles')->updateOrInsert(
            [
                'role_id'    => $roles['super-admin']->id,
                'model_id'   => $superAdmin->id,
                'model_type' => User::class,
            ],
            ['assigned_by' => $superAdmin->id, 'assigned_at' => now()],
        );

        $this->command->info('✓ Roles: ' . count(self::ROLES));
        $this->command->info('✓ Permisos: ' . count(self::PERMISSIONS));
        $this->command->info('✓ Super Admin: admin@edusync.edu / Admin@2024!');
    }
}