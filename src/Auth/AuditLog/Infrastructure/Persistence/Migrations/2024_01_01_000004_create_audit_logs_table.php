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
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('user_id');
            $table->string('user_email', 255);
            $table->string('user_role', 100);
            $table->string('module', 50);
            $table->string('action', 100);
            $table->string('entity_type', 100)->nullable();
            $table->uuid('entity_id')->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->string('status', 10)->default('SUCCESS');
            $table->timestampTz('timestamp')->useCurrent();

            $table->index('user_id');
            $table->index('module');
            $table->index('action');
            $table->index('timestamp');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
