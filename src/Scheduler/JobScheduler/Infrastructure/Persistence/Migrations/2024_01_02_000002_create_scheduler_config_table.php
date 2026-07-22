<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduler_config', function (Blueprint $table) {
            $table->id();
            $table->string('job_key', 100)->unique();
            $table->string('label', 150);
            $table->text('description')->nullable();
            $table->string('job_class', 250);
            $table->string('queue', 50)->default('neo-sync-default');
            $table->string('cron_expression', 100)->default('0 2 * * *');
            $table->string('default_cron', 100);
            $table->boolean('enabled')->default(true);
            $table->smallInteger('overlap_ttl_minutes')->default(10);
            $table->timestampTz('last_run_at')->nullable();
            $table->string('last_run_status', 20)->nullable();
            $table->string('updated_by', 150)->nullable();
            $table->timestampsTz();

            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduler_config');
    }
};
