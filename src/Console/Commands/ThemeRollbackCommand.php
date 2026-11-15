<?php

namespace Webuild\ThemeKit\Console\Commands;

use Illuminate\Console\Command;
use Webuild\ThemeKit\Services\ThemeManagerService;

class ThemeRollbackCommand extends Command
{
    protected $signature = 'theme:rollback {key : Key tema yang akan di-rollback}';

    protected $description = 'Kembalikan tema ke versi rilis sebelumnya yang berhasil';

    public function handle(ThemeManagerService $service): int
    {
        $key = $this->argument('key');

        $this->warn("Melakukan rollback untuk tema '{$key}'...");

        $result = $service->rollback($key, 'CLI (theme:rollback)');

        if (! $result['success']) {
            $this->error($result['message']);

            return Command::FAILURE;
        }

        $this->info($result['message']);

        return Command::SUCCESS;
    }
}

