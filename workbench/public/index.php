<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Locate vendor autoload. The theme being served comes first: its vendor symlinks theme-kit,
// whereas webuild-app/vendor holds a physical copy whose ThemeAssetRenderer cannot find themes/.
$activeThemeFile = __DIR__ . '/../storage/active_theme.txt';
$activeThemePath = getenv('THEME_PATH') ?: (is_file($activeThemeFile) ? trim((string) file_get_contents($activeThemeFile)) : '');

$autoloadFiles = [
    ...($activeThemePath !== '' ? [$activeThemePath . '/vendor/autoload.php'] : []),
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    dirname(__DIR__, 2) . '/themes/modern/vendor/autoload.php',
    dirname(__DIR__, 2) . '/themes/default/vendor/autoload.php',
    dirname(__DIR__, 2) . '/theme-starter/vendor/autoload.php',
    dirname(__DIR__, 2) . '/webuild-app/vendor/autoload.php',
    __DIR__ . '/../../../webuild-app/vendor/autoload.php',
];

$autoloadLoaded = false;
foreach ($autoloadFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        if (class_exists(Application::class)) {
            $autoloadLoaded = true;
            break;
        }
    }
}

if (! $autoloadLoaded && ! class_exists(Application::class)) {
    http_response_code(500);
    echo "Laravel Application class tidak ditemukan. Jalankan 'composer install' terlebih dahulu.\n";
    exit(1);
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());

