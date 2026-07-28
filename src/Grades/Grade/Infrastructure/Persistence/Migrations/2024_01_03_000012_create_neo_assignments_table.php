<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neo_assignments', function (Blueprint $table) {
            $table->integer('neo_assignment_id')->primary();
            $table->integer('neo_class_id');
            $table->integer('neo_lesson_id')->nullable();
            $table->string('lesson_name', 300)->nullable();

            $table->integer('creator_id')->nullable();
            $table->string('type', 100)->nullable();
            $table->string('name', 300)->nullable();
            $table->decimal('points', 8, 2)->nullable();
            $table->string('grading', 50)->nullable();
            $table->string('use_results', 50)->nullable();
            $table->string('category', 100)->nullable();

            $table->timestampTz('begin_at')->nullable();
            $table->timestampTz('end_at')->nullable();
            $table->boolean('given')->default(false);
            $table->timestampTz('given_at')->nullable();

            $table->string('checksum', 32)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->timestampsTz();

            $table->foreign('neo_class_id')
                ->references('neo_id')->on('neo_classes')->cascadeOnDelete();

            $table->index('neo_class_id');
            $table->index('neo_lesson_id');
            $table->index('type');
            $table->index('end_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_assignments');
    }
};
