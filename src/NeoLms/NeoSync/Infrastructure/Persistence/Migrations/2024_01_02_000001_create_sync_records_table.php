<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_records', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('entity_type', 50);
            $table->string('local_id', 100);
            $table->string('neo_id', 100)->nullable();
            $table->string('direction', 10)->default('push');
            $table->string('status', 10)->default('pending');
            $table->text('error_message')->nullable();
            $table->smallInteger('retry_count')->default(0);
            $table->string('checksum', 32)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->timestampsTz();

            $table->unique(['entity_type', 'local_id']);
            $table->index(['entity_type', 'status']);
            $table->index('synced_at');
        });

        Schema::create('sync_audit_log', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('entity_type', 50);
            $table->string('local_id', 100)->nullable();
            $table->string('neo_id', 100)->nullable();
            $table->string('direction', 10);
            $table->string('operation', 10);
            $table->string('job_class', 200)->nullable();
            $table->string('triggered_by', 100)->default('scheduler');
            $table->text('payload_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestampsTz();

            $table->index(['entity_type', 'created_at']);
            $table->index('triggered_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_audit_log');
        Schema::dropIfExists('sync_records');
    }
};
