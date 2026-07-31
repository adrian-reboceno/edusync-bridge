<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neo_assignment_grade_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('neo_grade_id');
            $table->integer('neo_user_id');
            $table->integer('neo_class_id');
            $table->integer('neo_assignment_id');
            $table->string('sis_id', 100)->nullable();

            $table->decimal('duration_minutes', 8, 2)->nullable();
            $table->decimal('feedback_minutes', 8, 2)->nullable();
            $table->decimal('time_to_start_minutes', 8, 2)->nullable();

            $table->boolean('submitted_on_time')->nullable();
            $table->decimal('minutes_before_deadline', 8, 2)->nullable();

            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('percent', 6, 2)->nullable();
            $table->string('grade', 20)->nullable();
            $table->string('assignment_type', 100)->nullable();
            $table->string('assignment_grading', 50)->nullable();

            $table->timestampTz('calculated_at');
            $table->timestampsTz();

            $table->foreign('neo_grade_id')
                ->references('neo_grade_id')->on('neo_assignment_grades')->cascadeOnDelete();
            $table->foreign('neo_user_id')
                ->references('neo_id')->on('neo_users')->cascadeOnDelete();

            $table->unique('neo_grade_id');
            $table->index(['neo_class_id', 'neo_assignment_id']);
            $table->index('neo_user_id');
            $table->index('sis_id');
            $table->index('submitted_on_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_assignment_grade_metrics');
    }
};
