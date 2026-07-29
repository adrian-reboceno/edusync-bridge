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
        Schema::create('neo_enrollment_status_history', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('neo_enrollment_id');
            $table->integer('neo_user_id');
            $table->integer('neo_class_id');
            $table->string('sis_id', 100)->nullable();

            // enrolled|started|completed|unenrolled|deactivated|transferred|reactivated
            $table->string('event', 30);

            $table->string('enroll_type', 50)->nullable();
            $table->integer('enrolled_by_id')->nullable();
            $table->timestampTz('enrolled_at')->nullable();

            $table->boolean('unenrolled')->default(false);
            $table->boolean('deactivated')->default(false);
            $table->boolean('transferred')->default(false);
            $table->boolean('completed')->default(false);
            $table->boolean('started')->default(false);

            $table->timestampTz('recorded_at')->default(DB::raw('now()'));

            $table->foreign('neo_user_id')
                ->references('neo_id')->on('neo_users')->cascadeOnDelete();
            $table->foreign('neo_class_id')
                ->references('neo_id')->on('neo_classes')->cascadeOnDelete();

            $table->index(['neo_user_id', 'neo_class_id']);
            $table->index(['neo_class_id', 'event']);
            $table->index('event');
            $table->index('recorded_at');
            $table->index('sis_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_enrollment_status_history');
    }
};
