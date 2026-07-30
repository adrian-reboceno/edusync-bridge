<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neo_assignment_results', function (Blueprint $table) {
            // bigInteger: el id de NEO ya alcanza 2723986614 en sandbox
            $table->bigInteger('neo_result_id')->primary();

            $table->bigInteger('neo_grade_id');
            $table->integer('neo_user_id');
            $table->integer('neo_class_id');
            $table->integer('neo_assignment_id');
            $table->string('sis_id', 100)->nullable();

            $table->integer('question_id')->nullable();
            $table->integer('position')->nullable();

            $table->text('response')->nullable();
            $table->decimal('points', 8, 2)->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->string('grade', 20)->nullable();

            $table->string('checksum', 32)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->timestampsTz();

            $table->foreign('neo_grade_id')
                ->references('neo_grade_id')->on('neo_assignment_grades')->cascadeOnDelete();
            $table->foreign('neo_user_id')
                ->references('neo_id')->on('neo_users')->cascadeOnDelete();
            $table->foreign('neo_class_id')
                ->references('neo_id')->on('neo_classes')->cascadeOnDelete();
            $table->foreign('neo_assignment_id')
                ->references('neo_assignment_id')->on('neo_assignments')->cascadeOnDelete();

            // Un resultado único por grade + pregunta (o por grade si question_id es null)
            $table->unique(['neo_grade_id', 'question_id']);

            $table->index(['neo_assignment_id', 'neo_user_id']);
            $table->index('neo_class_id');
            $table->index('sis_id');
            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_assignment_results');
    }
};
