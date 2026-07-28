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
        Schema::create('neo_classes', function (Blueprint $table) {
            $table->integer('neo_id')->primary();
            $table->integer('parent_id')->nullable();
            $table->string('sis_id', 100)->nullable();
            $table->string('sis_pid', 100)->nullable();
            $table->string('name', 255);
            $table->string('style', 50)->nullable();
            $table->string('course_code', 100)->nullable();
            $table->string('section_code', 100)->nullable();
            $table->integer('organization_id')->nullable();
            $table->string('organization_name', 255)->nullable();
            $table->date('start_at')->nullable();
            $table->date('finish_at')->nullable();
            $table->string('time_zone', 100)->nullable();
            $table->boolean('archived')->default(false);
            $table->timestampTz('archived_at')->nullable();
            $table->integer('archiver_id')->nullable();
            $table->boolean('private')->default(false);
            $table->string('access_code', 50)->nullable();
            $table->boolean('enrollment_open')->default(true);
            $table->boolean('allow_reenrollment')->default(false);
            $table->boolean('allow_unenrollment')->default(true);
            $table->integer('used_seats')->default(0);
            $table->integer('max_seats')->nullable();
            $table->integer('max_students')->nullable();
            $table->jsonb('tags')->default(DB::raw("'[]'::jsonb"));
            $table->jsonb('metadata')->nullable();
            $table->jsonb('catalog_categories')->default(DB::raw("'[]'::jsonb"));
            $table->string('checksum', 32)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->timestampsTz();

            $table->index('sis_id');
            $table->index('parent_id');
            $table->index('organization_id');
            $table->index('archived');
            $table->index('synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_classes');
    }
};
