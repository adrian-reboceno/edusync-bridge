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
        Schema::create('neo_users', function (Blueprint $table) {
            $table->integer('neo_id')->primary();
            $table->string('sis_id', 100)->nullable();
            $table->string('sis_pid', 100)->nullable();
            $table->string('userid', 150)->nullable();
            $table->string('studentid', 100)->nullable();
            $table->string('teacherid', 100)->nullable();
            $table->string('first_name', 150);
            $table->string('last_name', 150);
            $table->string('nick_name', 150)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('birthdate')->nullable();
            $table->integer('year_of_graduation')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile_phone', 50)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip', 20)->nullable();
            $table->jsonb('roles')->default(DB::raw("'[]'::jsonb"));
            $table->integer('organization_id')->nullable();
            $table->string('organization_name', 255)->nullable();
            $table->integer('job_title_id')->nullable();
            $table->string('job_title_name', 255)->nullable();
            $table->integer('manager_id')->nullable();
            $table->string('manager_name', 255)->nullable();
            $table->integer('added_by_id')->nullable();
            $table->string('language', 50)->nullable();
            $table->string('time_zone', 100)->nullable();
            $table->string('email_sync', 20)->nullable();
            $table->string('sms_sync', 20)->nullable();
            $table->jsonb('tags')->default(DB::raw("'[]'::jsonb"));
            $table->jsonb('custom_fields')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('joined_at')->nullable();
            $table->timestampTz('first_login_at')->nullable();
            $table->timestampTz('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->boolean('archived')->default(false);
            $table->timestampTz('archived_at')->nullable();
            $table->integer('archiver_id')->nullable();
            $table->jsonb('organization_data')->nullable();
            $table->string('checksum', 32)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->timestampsTz();

            $table->index('sis_id');
            $table->index('email');
            $table->index('organization_id');
            $table->index('archived');
            $table->index('joined_at');
            $table->index('last_login_at');
            $table->index('first_login_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neo_users');
    }
};
