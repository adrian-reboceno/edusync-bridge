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
        Schema::create('neo_class_templates', function (Blueprint $table) {
            $table->integer('neo_id')->primary();
            $table->string('sis_id', 100)->nullable();
            $table->string('sis_pid', 100)->nullable();
            $table->string('name', 300);
            $table->string('style', 50)->nullable();
            $table->string('course_code', 100)->nullable();
            $table->string('section_code', 100)->nullable();
            $table->integer('organization_id')->nullable();
            $table->string('organization_name', 200)->nullable();
            $table->boolean('archived')->default(false);
            $table->jsonb('tags')->default(DB::raw("'[]'::jsonb"));
            $table->jsonb('metadata')->nullable();
            $table->string('checksum', 32)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->timestampsTz();

            $table->index('sis_id');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_class_templates');
    }
};
