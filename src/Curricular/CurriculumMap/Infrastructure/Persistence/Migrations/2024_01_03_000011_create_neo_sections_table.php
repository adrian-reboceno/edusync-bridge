<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neo_sections', function (Blueprint $table) {
            $table->integer('neo_id')->primary();
            $table->integer('neo_lesson_id');
            $table->integer('neo_class_id');

            $table->string('name', 300);
            $table->string('type', 100)->nullable();
            $table->text('instructions')->nullable();
            $table->integer('position')->default(0);
            $table->integer('level')->default(0);
            $table->boolean('personalized')->default(false);
            $table->boolean('optional_for_completion')->default(false);
            $table->integer('referenced_class_id')->nullable();

            $table->string('checksum', 32)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->timestampsTz();

            $table->foreign('neo_lesson_id')
                ->references('neo_id')->on('neo_lessons')->cascadeOnDelete();

            $table->index('neo_lesson_id');
            $table->index('neo_class_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_sections');
    }
};
