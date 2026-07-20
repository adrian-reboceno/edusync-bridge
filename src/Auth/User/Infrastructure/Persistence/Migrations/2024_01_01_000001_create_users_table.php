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
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');

        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('status', 30)->default('PENDING_VERIFICATION');
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->boolean('must_change_password')->default(true);
            $table->timestampTz('password_changed_at')->nullable();
            $table->smallInteger('failed_login_attempts')->default(0);
            $table->timestampTz('locked_until')->nullable();
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampTz('last_activity_at')->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestampTz('deleted_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('status');
            $table->index('email');
        });

        // Added in a separate statement: Postgres compiles ADD PRIMARY KEY after
        // ADD FOREIGN KEY within the same create(), which breaks this self-reference.
        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
