<?php

namespace Webuild\ThemeKit\Console\Commands;

use Illuminate\Console\Command;
use Webuild\ThemeKit\Services\ThemeManagerService;

class ThemeInstallCommand extends Command
{
    protected $signature = 'theme:install {path? : Path direktori tema (default: direktori aktif)}';

    protected $description = 'Daftarkan dan pasang tema ke katalog database';

    public function handle(ThemeManagerService $service): int
    {
        $path = $this->argument('path') ?: getcwd();

        $this->info("Memasang tema dari path: {$path}");

        $result = $service->install($path, 'CLI (theme:install)');

        if (! $result['success']) {
            $this->error($result['message']);

            return Command::FAILURE;
        }

        $this->info($result['message']);

        return Command::SUCCESS;
    }
}

