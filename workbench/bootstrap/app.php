<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Blade;
use Webuild\ThemeKit\ThemeKitServiceProvider;

// Register ThemeKit and Themes PSR-4 autoload fallback
spl_autoload_register(function ($class) {
    $themeKitPrefix = 'Webuild\\ThemeKit\\';
    if (str_starts_with($class, $themeKitPrefix)) {
        $relativeClass = substr($class, strlen($themeKitPrefix));
        $file = dirname(__DIR__, 2) . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    $modernPrefix = 'Webuild\\Themes\\Modern\\';
    if (str_starts_with($class, $modernPrefix)) {
        $relativeClass = substr($class, strlen($modernPrefix));
        $file = dirname(__DIR__, 3) . '/themes/modern/src/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    $defaultPrefix = 'Webuild\\Themes\\Default\\';
    if (str_starts_with($class, $defaultPrefix)) {
        $relativeClass = substr($class, strlen($defaultPrefix));
        $file = dirname(__DIR__, 3) . '/themes/default/src/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load stubs and aliases
require_once __DIR__ . '/../app/Models/WorkbenchStubs.php';
require_once __DIR__ . '/../app/Support/ThemeDemoData.php';
require_once __DIR__ . '/../app/Support/WorkbenchCustomizer.php';
require_once __DIR__ . '/../app/Http/Middleware/InjectCustomizerDrawer.php';
require_once __DIR__ . '/../database/seeders/ThemeDemoSeeder.php';

$basePath = dirname(__DIR__);

// Ensure workbench sqlite database exists
$dbPath = $basePath . '/database/database.sqlite';
if (! file_exists($dbPath)) {
    @mkdir(dirname($dbPath), 0755, true);
    touch($dbPath);
}

// Set default environment configurations
putenv('DB_CONNECTION=sqlite');
putenv("DB_DATABASE={$dbPath}");
putenv('SESSION_DRIVER=file');
putenv('APP_ENV=local');
putenv('APP_DEBUG=true');
putenv('APP_KEY=base64:uQvN0tJqg7+oN4xHj1pZf9w8e7d6c5b4a3Z2Y1X0w9v=');

$app = Application::configure(basePath: $basePath)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        // Drawer customizer hanya ada di workbench, tidak ikut ke aplikasi Webuild.
        $middleware->web(append: \App\Http\Middleware\InjectCustomizerDrawer::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })
    ->create();

// Register ThemeKitServiceProvider
$app->register(ThemeKitServiceProvider::class);
$app->singleton(\App\Support\WorkbenchCustomizer::class);

$app->booted(static function (): void {
    Blade::directive('themeAssets', static fn (string $expression): string => "<?php echo \\Webuild\\ThemeKit\\ThemeAssetRenderer::render({$expression}); ?>");
});

return $app;
