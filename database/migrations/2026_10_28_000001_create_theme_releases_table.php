<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('theme_releases')) {
            Schema::create('theme_releases', function (Blueprint $table) {
                $table->id();
                $table->string('theme_key', 100)->index();
                $table->string('version', 50);
                $table->string('channel', 50)->default('main');
                $table->string('commit_sha', 100)->nullable();
                $table->text('changelog')->nullable();
                $table->json('manifest_snapshot')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->timestamps();

                $table->unique(['theme_key', 'version']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_releases');
    }
};

