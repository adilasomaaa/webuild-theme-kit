<?php

namespace Webuild\ThemeKit\Workbench\Database\Seeders;

use App\Support\ThemeDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ThemeDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create SQLite tables for workbench if not exist
        if (! Schema::hasTable('contents')) {
            Schema::create('contents', function ($table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('summary')->nullable();
                $table->longText('content')->nullable();
                $table->string('type')->default('article'); // article, news, announcement
                $table->string('status')->default('published');
                $table->string('thumbnail')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('agendas')) {
            Schema::create('agendas', function ($table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('location')->nullable();
                $table->timestamp('start_date')->nullable();
                $table->timestamp('end_date')->nullable();
                $table->string('organizer')->nullable();
                $table->string('status')->default('published');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('documents')) {
            Schema::create('documents', function ($table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_size')->nullable();
                $table->string('file_type')->default('pdf');
                $table->string('status')->default('published');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('dynamic_contents')) {
            Schema::create('dynamic_contents', function ($table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('summary')->nullable();
                $table->longText('content')->nullable();
                $table->string('status')->default('published');
                $table->timestamps();
            });
        }

        $demoData = app(ThemeDemoData::class)->data();

        // 2. Seed the database-backed demo tables from the active theme fixture.
        if (DB::table('contents')->count() === 0) {
            $contents = [];
            foreach (['news' => 'news', 'articles' => 'article'] as $source => $type) {
                foreach ($demoData[$source] ?? [] as $record) {
                    $contents[] = [
                        'title' => $record['title'],
                        'slug' => $record['slug'],
                        'summary' => $record['summary'] ?? null,
                        'content' => $record['content'] ?? null,
                        'type' => $type,
                        'status' => 'published',
                        'thumbnail' => $record['thumbnail'] ?? $record['image_url'] ?? null,
                        'published_at' => $record['published_at'] ?? now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if ($contents !== []) {
                DB::table('contents')->insert($contents);
            }
        }

        if (DB::table('agendas')->count() === 0) {
            $agendas = array_map(fn (array $record) => [
                'title' => $record['title'],
                'slug' => $record['slug'],
                'description' => $record['description'] ?? null,
                'location' => $record['location'] ?? null,
                'start_date' => $record['start_at'],
                'end_date' => $record['end_at'] ?? null,
                'organizer' => $record['organizer'] ?? null,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ], $demoData['agenda'] ?? []);

            if ($agendas !== []) {
                DB::table('agendas')->insert($agendas);
            }
        }

        if (DB::table('documents')->count() === 0) {
            $documents = array_map(fn (array $record) => [
                'title' => $record['title'],
                'slug' => $record['slug'],
                'description' => $record['description'] ?? null,
                'file_path' => $record['file_path'] ?? null,
                'file_size' => $record['file_size'] ?? null,
                'file_type' => $record['file_type'] ?? 'pdf',
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ], $demoData['documents'] ?? []);

            if ($documents !== []) {
                DB::table('documents')->insert($documents);
            }
        }
    }
}

