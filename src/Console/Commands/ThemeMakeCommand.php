<?php

namespace Webuild\ThemeKit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Webuild\ThemeKit\Contract\DefaultThemeContract;
use Webuild\ThemeKit\Contract\ThemeContract;

class ThemeMakeCommand extends Command
{
    protected $signature = 'theme:make {name : Nama atau slug tema baru} {--path= : Lokasi folder output (default: themes/{slug})}';

    protected $description = 'Scaffold direktori tema baru yang lengkap dengan manifest dan struktur folder standar';

    public function handle(): int
    {
        $name = $this->argument('name');
        $slug = Str::slug(str_replace('_', '-', $name));
        $title = Str::title(str_replace(['-', '_'], ' ', $name));

        $targetPath = $this->option('path') ?: getcwd() . "/themes/{$slug}";

        if (File::exists($targetPath)) {
            $this->error("Folder tujuan sudah ada: {$targetPath}");

            return Command::FAILURE;
        }

        $this->info("Men-generate kerangka tema '{$title}' pada: {$targetPath}...");

        // 1. Create directory structure
        File::makeDirectory($targetPath . '/views/layouts', 0755, true);
        File::makeDirectory($targetPath . '/views/pages', 0755, true);
        File::makeDirectory($targetPath . '/views/sections/header', 0755, true);
        File::makeDirectory($targetPath . '/views/sections/main', 0755, true);
        File::makeDirectory($targetPath . '/views/sections/footer', 0755, true);
        File::makeDirectory($targetPath . '/views/components', 0755, true);
        File::makeDirectory($targetPath . '/assets/css', 0755, true);
        File::makeDirectory($targetPath . '/assets/js', 0755, true);
        File::makeDirectory($targetPath . '/upgrades', 0755, true);
        File::makeDirectory($targetPath . '/demo', 0755, true);

        // 2. Write theme.json
        $manifest = [
            'key' => $slug,
            'name' => $title,
            'version' => '1.0.0',
            'api_version' => ThemeContract::API_VERSION,
            'description' => "Tema portal publik {$title} untuk Webuild",
            'authors' => [
                ['name' => 'Theme Developer', 'email' => 'dev@example.com'],
            ],
            'settings_schema' => [
                'primary_color' => ['type' => 'color', 'default' => '#059669', 'label' => 'Warna Utama'],
                'secondary_color' => ['type' => 'color', 'default' => '#0d9488', 'label' => 'Warna Sekunder'],
                'accent_color' => ['type' => 'color', 'default' => '#10b981', 'label' => 'Warna Aksen'],
            ],
            'sections' => DefaultThemeContract::getRequiredSections(),
            'component_variants' => DefaultThemeContract::getBaseVariants(),
            'component_settings' => [],
            // CSS Tailwind dibangun lokal (npm run build), bukan dari CDN, agar lolos Content Security Policy.
            'assets' => [
                'styles' => ['dist/theme.css'],
            ],
        ];
        File::put($targetPath . '/theme.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 3. Write basic public.blade.php layout
        $layoutContent = <<<'BLADE'
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo['title'] ?? config('app.name', 'Webuild') }}</title>
    @themeAssets('{$themeKey}')
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col antialiased">
    @includeFirst(["themes.{$themeKey}.layouts.header", 'theme::layouts.header'])

    <main class="flex-grow">
        @yield('content')
    </main>

    @includeFirst(["themes.{$themeKey}.layouts.footer", 'theme::layouts.footer'])
</body>
</html>
BLADE;
        $layoutContent = str_replace('{$themeKey}', $slug, $layoutContent);
        File::put($targetPath . '/views/layouts/public.blade.php', $layoutContent);

        // 4. Scaffold required pages
        foreach (DefaultThemeContract::getRequiredPages() as $pageKey => $domain) {
            $pageDir = $targetPath . "/views/pages/{$domain}";
            if (! File::isDirectory($pageDir)) {
                File::makeDirectory($pageDir, 0755, true);
            }
            $pageFile = "{$pageDir}/{$pageKey}.blade.php";
            $pageStub = <<<BLADE
@extends('themes.{$slug}.layouts.public')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Halaman: {$pageKey}</h1>
        <p class="text-slate-600">Domain: {$domain}. Silakan kustomisasi tampilan ini sesuai kebutuhan.</p>
    </div>
</div>
@endsection
BLADE;
            File::put($pageFile, $pageStub);
        }

        // 5. Scaffold required sections
        foreach (DefaultThemeContract::getRequiredSections() as $area => $sections) {
            foreach ($sections as $sectionKey) {
                $sectionDir = $targetPath . "/views/sections/{$area}/{$sectionKey}";
                File::makeDirectory($sectionDir, 0755, true);
                $sectionFile = "{$sectionDir}/{$sectionKey}.blade.php";
                $sectionStub = <<<BLADE
<section class="theme-section theme-section-{$sectionKey} py-6" data-section-type="{$sectionKey}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section: {$sectionKey} ({$area}) -->
    </div>
</section>
BLADE;
                File::put($sectionFile, $sectionStub);
            }
        }

        // 6. Header and Footer layouts
        File::put($targetPath . '/views/layouts/header.blade.php', <<<BLADE
<header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
        <a href="{{ route('home') }}" class="font-bold text-lg text-emerald-700">{$title}</a>
        <nav class="hidden md:flex gap-6 text-sm font-medium text-slate-700">
            <a href="{{ route('home') }}" class="hover:text-emerald-600">Beranda</a>
            <a href="{{ route('public.articles.index') }}" class="hover:text-emerald-600">Berita</a>
            <a href="{{ route('public.documents.index') }}" class="hover:text-emerald-600">Dokumen</a>
            <a href="{{ route('public.agenda.index') }}" class="hover:text-emerald-600">Agenda</a>
        </nav>
    </div>
</header>
BLADE
        );

        File::put($targetPath . '/views/layouts/footer.blade.php', <<<BLADE
<footer class="bg-slate-900 text-slate-400 py-8 border-t border-slate-800 text-sm mt-auto">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <p>&copy; {{ date('Y') }} {$title}. Hak cipta dilindungi undang-undang.</p>
    </div>
</footer>
BLADE
        );

        // 6b. Toolchain build CSS (Tailwind v4 via Vite), sama dengan theme-starter
        File::makeDirectory($targetPath . '/resources', 0755, true);
        File::put($targetPath . '/resources/theme.css', "@import \"tailwindcss\";\n@source \"../views\";\n");
        File::put($targetPath . '/package.json', json_encode([
            'name' => "@webuild/theme-{$slug}-assets",
            'private' => true,
            'type' => 'module',
            'scripts' => [
                'dev' => 'vite build --watch',
                'build' => 'vite build',
            ],
            'devDependencies' => [
                '@tailwindcss/vite' => '^4.3.3',
                'tailwindcss' => '^4.3.3',
                'vite' => '^8.3.5',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        File::put($targetPath . '/vite.config.js', <<<'JS'
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [tailwindcss()],
    build: {
        cssMinify: true,
        emptyOutDir: true,
        outDir: 'dist',
        rollupOptions: {
            input: { styles: 'resources/theme.css' },
            output: {
                assetFileNames: (asset) => asset.name?.endsWith('.css') ? 'theme.css' : '[name][extname]',
            },
        },
    },
});

JS);
        File::put($targetPath . '/.gitignore', "/vendor/\n/node_modules/\n.env\n.DS_Store\n");

        // 7. composer.json
        $composerJson = [
            'name' => "webuild/theme-{$slug}",
            'description' => "Tema portal publik {$title} untuk Webuild",
            'type' => 'library',
            'license' => 'proprietary',
            'require' => [
                'php' => '^8.2',
                'webuild/theme-kit' => '^1.0',
            ],
            'autoload' => [
                'psr-4' => [
                    'Webuild\\Themes\\' . Str::studly($slug) . '\\' => 'src/',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        'Webuild\\Themes\\' . Str::studly($slug) . '\\ThemeServiceProvider',
                    ],
                ],
            ],
        ];
        File::put($targetPath . '/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 8. ServiceProvider
        File::makeDirectory($targetPath . '/src', 0755, true);
        $studlyName = Str::studly($slug);
        $providerContent = <<<PHP
<?php

namespace Webuild\Themes\\{$studlyName};

use Illuminate\Support\ServiceProvider;
use Webuild\ThemeKit\ThemeRegistry;

class ThemeServiceProvider extends ServiceProvider
{
    public function boot(ThemeRegistry \$registry): void
    {
        \$manifest = json_decode(file_get_contents(__DIR__ . '/../theme.json'), true) ?: [];
        \$registry->register('{$slug}', dirname(__DIR__), \$manifest);
    }
}
PHP;
        File::put($targetPath . '/src/ThemeServiceProvider.php', $providerContent);

        // 9. Root artisan executable
        $artisanContent = <<<'PHP'
#!/usr/bin/env php
<?php

define('LARAVEL_START', microtime(true));

// Locate vendor autoload
$autoloadFiles = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../theme-kit/vendor/autoload.php',
    __DIR__ . '/../../theme-kit/vendor/autoload.php',
    __DIR__ . '/../webuild-app/vendor/autoload.php',
    __DIR__ . '/../../webuild-app/vendor/autoload.php',
    dirname(__DIR__, 2) . '/webuild-app/vendor/autoload.php',
];

$autoloadLoaded = false;
foreach ($autoloadFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        if (class_exists(Illuminate\Foundation\Application::class)) {
            $autoloadLoaded = true;
            break;
        }
    }
}

if (! $autoloadLoaded && ! class_exists(Illuminate\Foundation\Application::class)) {
    fwrite(STDERR, "Laravel Application class tidak ditemukan. Jalankan 'composer install' terlebih dahulu.\n");
    exit(1);
}

$possiblePaths = [
    __DIR__ . '/vendor/webuild/theme-kit/workbench/bootstrap/app.php',
    __DIR__ . '/../theme-kit/workbench/bootstrap/app.php',
    __DIR__ . '/../../theme-kit/workbench/bootstrap/app.php',
    dirname(__DIR__, 2) . '/theme-kit/workbench/bootstrap/app.php',
];

$workbenchBootstrap = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $workbenchBootstrap = $path;
        break;
    }
}

if (! $workbenchBootstrap) {
    fwrite(STDERR, "Theme Kit workbench not found. Jalankan 'composer install' terlebih dahulu.\n");
    exit(1);
}

putenv('THEME_PATH=' . __DIR__);
$_ENV['THEME_PATH'] = __DIR__;
@file_put_contents(dirname($workbenchBootstrap, 2) . '/storage/active_theme.txt', __DIR__);

$app = require_once $workbenchBootstrap;
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->handle(
    $input = new Symfony\Component\Console\Input\ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
);
$kernel->terminate($input, $status);
exit($status);
PHP;
        File::put($targetPath . '/artisan', $artisanContent);
        chmod($targetPath . '/artisan', 0755);

        // 10. CHANGELOG.md and README.md
        File::put($targetPath . '/CHANGELOG.md', "# Changelog - Tema {$title}\n\n## [1.0.0] - " . date('Y-m-d') . "\n\n- Rilis inisial tema {$title}.\n");
        File::put($targetPath . '/README.md', "# Tema {$title} (`theme-{$slug}`)\n\nTema portal Webuild mandiri.\n\n## Menjalankan di Lingkungan Lokal\n```bash\ncomposer install\nnpm install && npm run build   # CSS Tailwind ke dist/theme.css (tanpa CDN)\nphp artisan migrate --seed\nphp artisan serve\n```\n\nBuka `http://localhost:8000` di peramban Anda.\n\n## Komponen, Variant, dan Data Demo\n\nDaftarkan komponen di `sections`, pilihan variant di `component_variants`, dan field konfigurasi di `component_settings`. Implementasi tampilan tetap berada di Blade. Ubah dummy data workbench pada `demo/data.json` (berita, artikel, galeri, agenda, dokumen, FAQ, dan slider).\n\n## Memvalidasi Integritas Tema\n```bash\nphp artisan theme:check --render\n```\n\nOpsi `--render` merender halaman utama aplikasi aktif. Jalankan dari workbench dan aplikasi Webuild untuk memeriksa kedua konteks tersebut.\n");

        $demoDataTemplate = dirname(__DIR__, 3) . '/workbench/demo/data.json';
        if (File::exists($demoDataTemplate)) {
            File::copy($demoDataTemplate, $targetPath . '/demo/data.json');
        }

        $this->info("✔ Scaffold tema '{$slug}' berhasil dibuat.");
        $this->line('  Bangun CSS tema (wajib, hasil dist/ ikut di-commit): npm install && npm run build');
        $this->line("  Uji integritas dengan: php artisan theme:check {$targetPath}");

        return Command::SUCCESS;
    }
}
