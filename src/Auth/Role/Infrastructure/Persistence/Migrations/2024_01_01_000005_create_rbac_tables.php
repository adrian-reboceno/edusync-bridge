<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 125);
            $table->string('display_name', 200)->nullable();
            $table->string('guard_name', 125)->default('api');
            $table->smallInteger('hierarchy_level')->default(1);
            $table->boolean('is_system')->default(false);
            $table->boolean('two_factor_required')->default(false);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 125);
            $table->string('display_name', 200)->nullable();
            $table->string('guard_name', 125)->default('api');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->foreignUuid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->string('model_type', 125);
            $table->uuid('model_id');

            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('model_type', 125);
            $table->uuid('model_id');
            $table->uuid('assigned_by')->nullable();
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestampTz('expires_at')->nullable();

            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->foreignUuid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();

            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_exclusions', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('role_a_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUuid('role_b_id')->constrained('roles')->cascadeOnDelete();
            $table->string('level', 20);
            $table->text('reason');
            $table->boolean('is_system')->default(false);
            $table->uuid('created_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['role_a_id', 'role_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_exclusions');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
