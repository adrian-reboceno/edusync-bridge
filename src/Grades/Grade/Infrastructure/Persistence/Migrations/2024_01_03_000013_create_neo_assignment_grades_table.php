<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neo_assignment_grades', function (Blueprint $table) {
            // bigInteger: el id de NEO ya alcanza 600915859 en sandbox
            $table->bigInteger('neo_grade_id')->primary();
            $table->integer('neo_class_id');
            $table->integer('neo_assignment_id');
            $table->integer('neo_user_id');
            $table->integer('lesson_id')->nullable();
            $table->string('lesson_name', 300)->nullable();
            $table->string('sis_id', 100)->nullable();

            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('percent', 6, 2)->nullable();
            $table->string('grade', 20)->nullable();
            $table->decimal('points', 8, 2)->nullable();
            $table->decimal('min_points', 8, 2)->nullable();

            $table->boolean('started')->default(false);
            $table->timestampTz('started_at')->nullable();
            $table->boolean('finished')->default(false);
            $table->timestampTz('finished_at')->nullable();
            $table->integer('graded')->default(0);
            $table->timestampTz('graded_at')->nullable();
            $table->boolean('fully_graded')->default(false);
            $table->integer('grader_id')->nullable();

            $table->boolean('missing')->default(false);
            $table->boolean('absent')->default(false);
            $table->boolean('excused')->default(false);
            $table->boolean('incomplete')->default(false);

            $table->text('teacher_comment')->nullable();
            $table->text('excused_comment')->nullable();

            $table->string('checksum', 32)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->timestampsTz();

            $table->foreign('neo_class_id')
                ->references('neo_id')->on('neo_classes')->cascadeOnDelete();
            $table->foreign('neo_assignment_id')
                ->references('neo_assignment_id')->on('neo_assignments')->cascadeOnDelete();
            $table->foreign('neo_user_id')
                ->references('neo_id')->on('neo_users')->cascadeOnDelete();

            $table->unique(['neo_assignment_id', 'neo_user_id']);
            $table->index('sis_id');
            $table->index(['neo_class_id', 'neo_assignment_id']);
            $table->index(['neo_user_id', 'finished']);
            $table->index('percent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_assignment_grades');
    }
};
