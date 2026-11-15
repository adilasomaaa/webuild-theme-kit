<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('theme_update_logs')) {
            Schema::create('theme_update_logs', function (Blueprint $table) {
                $table->id();
                $table->string('theme_key', 100)->index();
                $table->string('action', 50)->default('update'); // install, update, rollback
                $table->string('from_version', 50)->nullable();
                $table->string('to_version', 50);
                $table->string('channel', 50)->default('main');
                $table->string('status', 50)->default('pending'); // pending, success, failed, rolled_back
                $table->string('actor_name', 150)->nullable();
                $table->text('log_output')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_update_logs');
    }
};

