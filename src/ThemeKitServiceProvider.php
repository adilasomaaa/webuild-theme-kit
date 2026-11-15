<?php

namespace Webuild\ThemeKit;

use Illuminate\Support\ServiceProvider;
use Webuild\ThemeKit\Console\Commands\ThemeCheckCommand;
use Webuild\ThemeKit\Console\Commands\ThemeHistoryCommand;
use Webuild\ThemeKit\Console\Commands\ThemeInstallCommand;
use Webuild\ThemeKit\Console\Commands\ThemeListCommand;
use Webuild\ThemeKit\Console\Commands\ThemeMakeCommand;
use Webuild\ThemeKit\Console\Commands\ThemeRollbackCommand;
use Webuild\ThemeKit\Console\Commands\ThemeUpdateCommand;
use Webuild\ThemeKit\Services\ThemeManagerService;

class ThemeKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeRegistry::class, function () {
            return new ThemeRegistry();
        });

        $this->app->singleton(ThemeManagerService::class, function ($app) {
            return new ThemeManagerService($app->make(ThemeRegistry::class));
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $workbenchViews = dirname(__DIR__) . '/workbench/views';
        if (is_dir($workbenchViews)) {
            \Illuminate\Support\Facades\View::addLocation($workbenchViews);
        }

        $registry = $this->app->make(ThemeRegistry::class);

        // Auto-detect theme from THEME_PATH environment variable, active_theme file, or current working directory
        $activeThemeFile = dirname(__DIR__) . '/workbench/storage/active_theme.txt';
        $themePath = getenv('THEME_PATH');
        if (! $themePath && file_exists($activeThemeFile)) {
            $saved = trim((string) file_get_contents($activeThemeFile));
            if ($saved && is_dir($saved)) {
                $themePath = $saved;
            }
        }
        if (! $themePath) {
            $themePath = getcwd();
        }

        $manifestPath = $themePath . '/theme.json';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
            $themeKey = $manifest['key'] ?? basename($themePath);
            config(['theme.active' => $themeKey]);
            $registry->register($themeKey, $themePath, $manifest);
        }

        // Also register sibling themes if present in themes directory
        $themesDir = dirname(__DIR__, 2) . '/themes';
        if (is_dir($themesDir)) {
            foreach (scandir($themesDir) as $item) {
                if ($item !== '.' && $item !== '..' && file_exists("{$themesDir}/{$item}/theme.json")) {
                    $m = json_decode(file_get_contents("{$themesDir}/{$item}/theme.json"), true) ?: [];
                    $key = $m['key'] ?? $item;
                    if (! $registry->has($key)) {
                        $registry->register($key, "{$themesDir}/{$item}", $m);
                    }
                }
            }
        }

        // Ensure built-in default theme fallback is always registered even if GitLab/external package fails
        if (function_exists('resource_path')) {
            $builtinDefaultPath = resource_path('views/themes/default');
            if (is_dir($builtinDefaultPath)) {
                \Illuminate\Support\Facades\View::addNamespace('themes.default', $builtinDefaultPath);
                \Illuminate\Support\Facades\View::addNamespace('theme-default', $builtinDefaultPath);

                if (! $registry->has(ThemeRegistry::FALLBACK_THEME)) {
                    $registry->register(ThemeRegistry::FALLBACK_THEME, $builtinDefaultPath, [
                        'key' => ThemeRegistry::FALLBACK_THEME,
                        'name' => 'Default Webuild (Fallback)',
                        'version' => '1.0.0',
                        'api_version' => '1.0',
                    ]);
                }
            }
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                ThemeCheckCommand::class,
                ThemeListCommand::class,
                ThemeInstallCommand::class,
                ThemeUpdateCommand::class,
                ThemeRollbackCommand::class,
                ThemeHistoryCommand::class,
                ThemeMakeCommand::class,
            ]);
        }
    }
}
