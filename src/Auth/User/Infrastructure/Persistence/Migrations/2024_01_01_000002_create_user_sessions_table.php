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
        Schema::create('user_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('client_type', 10)->default('WEB');
            $table->string('access_token_hash', 255)->unique();
            $table->string('refresh_token_hash', 255)->unique();
            $table->timestampTz('access_expires_at');
            $table->timestampTz('refresh_expires_at');
            $table->uuid('active_role_id')->nullable();
            $table->timestampTz('role_activated_at')->nullable();
            $table->timestampTz('last_activity_at');
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('user_id');
            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
