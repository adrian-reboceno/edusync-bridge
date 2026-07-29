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
        Schema::create('neo_enrollment_progress_history', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('neo_user_id');
            $table->integer('neo_class_id');
            $table->string('sis_id', 100)->nullable();

            $table->decimal('percent', 6, 2)->nullable();
            $table->string('grade', 20)->nullable();
            $table->integer('time_spent_seconds')->nullable();
            $table->timestampTz('last_visited_at')->nullable();

            $table->boolean('percent_changed')->default(false);
            $table->boolean('grade_changed')->default(false);
            $table->boolean('time_spent_changed')->default(false);
            $table->boolean('last_visited_changed')->default(false);

            $table->decimal('prev_percent', 6, 2)->nullable();
            $table->string('prev_grade', 20)->nullable();
            $table->integer('prev_time_spent_seconds')->nullable();

            $table->timestampTz('recorded_at')->default(DB::raw('now()'));

            $table->foreign('neo_user_id')
                ->references('neo_id')->on('neo_users')->cascadeOnDelete();
            $table->foreign('neo_class_id')
                ->references('neo_id')->on('neo_classes')->cascadeOnDelete();

            $table->index(['neo_user_id', 'neo_class_id', 'recorded_at']);
            $table->index(['neo_class_id', 'recorded_at']);
            $table->index('sis_id');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_enrollment_progress_history');
    }
};
