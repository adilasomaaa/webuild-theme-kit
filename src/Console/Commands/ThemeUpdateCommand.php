<?php

namespace Webuild\ThemeKit\Console\Commands;

use Illuminate\Console\Command;
use Webuild\ThemeKit\Services\ThemeManagerService;

class ThemeUpdateCommand extends Command
{
    protected $signature = 'theme:update {key : Key tema yang akan diperbarui} {path? : Path direktori tema (default: direktori aktif)} {--channel=main : Channel pembaruan (main atau dev)}';

    protected $description = 'Perbarui tema ke rilis terbaru, jalankan skrip upgrade, dan catat riwayat';

    public function handle(ThemeManagerService $service): int
    {
        $key = $this->argument('key');
        $path = $this->argument('path') ?: getcwd();
        $channel = $this->option('channel') ?: 'main';

        $this->info("Memperbarui tema '{$key}' (Channel: {$channel}) dari path: {$path}...");

        $result = $service->update($key, $path, $channel, 'CLI (theme:update)');

        if (! $result['success']) {
            $this->error($result['message']);

            return Command::FAILURE;
        }

        $this->info($result['message']);

        return Command::SUCCESS;
    }
}

