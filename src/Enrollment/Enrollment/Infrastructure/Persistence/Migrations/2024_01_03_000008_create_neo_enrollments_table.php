<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neo_enrollments', function (Blueprint $table) {
            $table->integer('neo_enrollment_id')->primary();
            $table->integer('neo_user_id');
            $table->integer('neo_class_id');
            $table->string('sis_id', 100)->nullable();
            $table->string('enroll_type', 50)->nullable();
            $table->timestampTz('enrolled_at')->nullable();
            $table->integer('enrolled_by_id')->nullable();
            $table->boolean('started')->default(false);
            $table->timestampTz('started_at')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestampTz('completed_at')->nullable();
            $table->integer('completed_by_id')->nullable();
            $table->boolean('unenrolled')->default(false);
            $table->timestampTz('unenrolled_at')->nullable();
            $table->integer('unenrolled_by_id')->nullable();
            $table->boolean('deactivated')->default(false);
            $table->timestampTz('deactivated_at')->nullable();
            $table->timestampTz('reactivated_at')->nullable();
            $table->boolean('transferred')->default(false);
            $table->timestampTz('transferred_at')->nullable();
            $table->integer('transferred_from_id')->nullable();
            $table->integer('transferred_to_id')->nullable();
            $table->timestampTz('last_visited_at')->nullable();
            $table->integer('time_spent_seconds')->default(0);
            $table->string('grade', 20)->nullable();
            $table->decimal('percent', 6, 2)->nullable();
            $table->decimal('override_percent', 6, 2)->nullable();
            $table->text('override_comment')->nullable();
            $table->integer('override_by_id')->nullable();
            $table->timestampTz('override_at')->nullable();
            $table->boolean('class_archived')->default(false);
            $table->boolean('user_archived')->default(false);
            $table->string('checksum', 32)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->date('snapshot_date')->nullable();
            $table->timestampsTz();

            $table->foreign('neo_user_id')
                ->references('neo_id')->on('neo_users')->cascadeOnDelete();
            $table->foreign('neo_class_id')
                ->references('neo_id')->on('neo_classes')->cascadeOnDelete();

            $table->unique(['neo_user_id', 'neo_class_id']);
            $table->index('neo_class_id');
            $table->index('neo_user_id');
            $table->index('unenrolled');
            $table->index('completed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_enrollments');
    }
};
