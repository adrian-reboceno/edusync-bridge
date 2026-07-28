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
        Schema::create('neo_lessons', function (Blueprint $table) {
            $table->integer('neo_id')->primary();
            $table->integer('neo_class_id');

            $table->string('name', 300);
            $table->text('description')->nullable();
            $table->string('picture', 500)->nullable();
            $table->text('notes')->nullable();
            $table->integer('position')->default(0);

            $table->date('start_at')->nullable();
            $table->timestampTz('released_at')->nullable();
            $table->timestampTz('begin_at')->nullable();
            $table->timestampTz('end_at')->nullable();
            $table->boolean('all_day')->default(true);
            $table->string('location', 300)->nullable();

            $table->string('tile_color', 20)->nullable();

            $table->boolean('personalized')->default(false);
            $table->boolean('optional_for_completion')->default(false);

            $table->jsonb('tags')->default(DB::raw("'[]'::jsonb"));
            $table->timestampTz('neo_updated_at')->nullable();

            $table->string('checksum', 32)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->timestampsTz();

            $table->foreign('neo_class_id')
                ->references('neo_id')->on('neo_classes')->cascadeOnDelete();

            $table->index('neo_class_id');
            $table->index(['neo_class_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_lessons');
    }
};
