<?php

namespace Webuild\ThemeKit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Webuild\ThemeKit\Services\ThemeManagerService;
use Throwable;

class ThemeCheckCommand extends Command
{
    protected $signature = 'theme:check {path? : Path ke direktori tema (default: direktori aktif)} {--render : Render halaman utama aplikasi aktif untuk memeriksa kompatibilitas Blade}';

    protected $description = 'Validasi struktur tema, manifest theme.json, dan kelengkapan halaman/section';

    public function handle(ThemeManagerService $service): int
    {
        $path = $this->argument('path') ?: getcwd();

        $this->info("Memeriksa integritas tema pada: {$path}");

        $result = $service->validateTheme($path);

        if (! $result['valid']) {
            $this->error('Validasi tema GAGAL:');
            foreach ($result['errors'] as $error) {
                $this->line("  ❌ <fg=red>{$error}</>");
            }

            return Command::FAILURE;
        }

        $manifest = $result['manifest'];
        $this->info("✔ Manifest valid: {$manifest['name']} ({$manifest['key']} v{$manifest['version']})");
        $this->line("  Versi API Target: {$manifest['api_version']}");

        if (! empty($result['warnings'])) {
            $this->warn('Peringatan kelengkapan:');
            foreach ($result['warnings'] as $warning) {
                $this->line("  ⚠️  <fg=yellow>{$warning}</>");
            }
        } else {
            $this->info('✔ Semua halaman dan section standar lengkap.');
        }

        if ($this->option('render')) {
            try {
                $request = Request::create('/');
                $kernel = app(HttpKernel::class);
                $response = $kernel->handle($request);
                $kernel->terminate($request, $response);

                if ($response->getStatusCode() >= 400) {
                    $this->error("Render halaman utama gagal dengan HTTP {$response->getStatusCode()}.");

                    return Command::FAILURE;
                }

                $this->info("✔ Halaman utama berhasil dirender (HTTP {$response->getStatusCode()}).");
            } catch (Throwable $exception) {
                $this->error('Render halaman utama gagal: ' . $exception->getMessage());

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}

