<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neo_class_teachers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('neo_teacher_record_id')->unique();
            $table->integer('neo_user_id');
            $table->integer('neo_class_id');
            $table->boolean('coteacher')->default(false);
            $table->timestampTz('last_visited_at')->nullable();
            $table->timestampTz('synced_at');
            $table->timestampsTz();

            $table->foreign('neo_user_id')
                ->references('neo_id')->on('neo_users')->cascadeOnDelete();
            $table->foreign('neo_class_id')
                ->references('neo_id')->on('neo_classes')->cascadeOnDelete();

            $table->unique(['neo_user_id', 'neo_class_id']);
            $table->index('neo_class_id');
            $table->index('neo_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_class_teachers');
    }
};
