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
        Schema::create('neo_user_sessions', function (Blueprint $table) {
            $table->bigInteger('neo_session_id')->primary();
            $table->integer('neo_user_id');
            $table->string('sis_id', 100)->nullable();

            $table->timestampTz('login_at');
            $table->timestampTz('logout_at')->nullable();

            $table->integer('duration_seconds')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestampTz('synced_at');
            $table->timestampTz('created_at')->default(DB::raw('now()'));

            $table->foreign('neo_user_id')
                ->references('neo_id')
                ->on('neo_users')
                ->cascadeOnDelete();

            $table->index('neo_user_id');
            $table->index('sis_id');
            $table->index('login_at');
            $table->index(['neo_user_id', 'login_at']);
            $table->index(['login_at', 'logout_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_user_sessions');
    }
};
