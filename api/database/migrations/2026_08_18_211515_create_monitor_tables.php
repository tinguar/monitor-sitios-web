<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sites')) {
            Schema::create('sites', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 160);
                $table->string('url', 500)->unique();
                $table->integer('timeout_seconds')->default(15);
                $table->integer('consecutive_failures')->default(0);
                $table->string('last_status', 20)->default('unknown');
                $table->string('last_checked_at', 40)->nullable();
                $table->integer('last_response_ms')->nullable();
                $table->integer('last_http_code')->nullable();
                $table->text('last_error')->nullable();
                $table->integer('ssl_days_left')->nullable();
                $table->string('created_at', 40);
            });
        }

        if (! Schema::hasTable('checks')) {
            Schema::create('checks', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('site_id');
                $table->boolean('ok');
                $table->integer('http_code')->nullable();
                $table->integer('response_ms')->nullable();
                $table->text('error')->nullable();
                $table->integer('ssl_days_left')->nullable();
                $table->string('checked_at', 40);
                $table->index(['site_id', 'id']);
                $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->string('setting_key', 64)->primary();
                $table->text('setting_value');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('checks');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('settings');
    }
};
