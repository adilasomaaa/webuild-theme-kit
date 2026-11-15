<?php

namespace Webuild\ThemeKit;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Webuild\ThemeKit\Contract\DefaultThemeContract;

class ThemeRegistry
{
    public const FALLBACK_THEME = 'default';

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $themes = [];

    /**
     * Register a theme into the registry and configure Laravel View namespaces.
     *
     * @param array<string, mixed> $manifest
     */
    public function register(string $key, string $basePath, array $manifest = []): void
    {
        $this->themes[$key] = [
            'key' => $key,
            'base_path' => $basePath,
            'manifest' => $manifest,
            'name' => $manifest['name'] ?? ucfirst($key),
            'version' => $manifest['version'] ?? '1.0.0',
            'api_version' => $manifest['api_version'] ?? '1.0',
        ];

        // Register view namespace if views folder exists
        $viewsPath = is_dir($basePath . '/views') ? $basePath . '/views' : $basePath;

        // Register view path directly under 'themes.{key}' namespace
        View::addNamespace("themes.{$key}", $viewsPath);
        // Also register standard theme alias 'theme-{key}'
        View::addNamespace("theme-{$key}", $viewsPath);

        // Register Blade anonymous component namespace
        if (is_dir($viewsPath . '/components')) {
            Blade::anonymousComponentNamespace("themes.{$key}.components", "theme-{$key}");
        }
    }

    /**
     * Check if a theme is registered.
     */
    public function has(string $key): bool
    {
        return isset($this->themes[$key]);
    }

    /**
     * Get theme details.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        return $this->themes[$key] ?? null;
    }

    /**
     * Get variant metadata declared for a component in a theme manifest.
     *
     * @return array{default?: string, options?: array<string, array<string, mixed>>}
     */
    public function componentVariants(string $themeKey, string $component): array
    {
        return $this->themes[$themeKey]['manifest']['component_variants'][$component] ?? [];
    }

    /**
     * Get configuration field metadata declared for a component in a theme manifest.
     *
     * @return array<string, array<string, mixed>>
     */
    public function componentSettings(string $themeKey, string $component): array
    {
        return $this->themes[$themeKey]['manifest']['component_settings'][$component] ?? [];
    }

    /** @return array<string, array<string, mixed>> */
    public function componentDefinitions(string $themeKey): array
    {
        return $this->themes[$themeKey]['manifest']['component_definitions'] ?? [];
    }

    /**
     * Get all registered themes.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->themes;
    }

    /**
     * Resolve a safe theme key, falling back to 'default' if the requested theme is missing or broken.
     */
    public function resolveSafeThemeKey(string $themeKey): string
    {
        $themeKey = $themeKey ?: self::FALLBACK_THEME;

        if ($themeKey === self::FALLBACK_THEME) {
            return self::FALLBACK_THEME;
        }

        if (View::exists("themes.{$themeKey}.layouts.public")) {
            return $themeKey;
        }

        Log::warning("Tema '{$themeKey}' tidak ditemukan atau rusak. Menggunakan fallback tema '" . self::FALLBACK_THEME . "'.");

        return self::FALLBACK_THEME;
    }

    /**
     * Resolve a public page view path for a given theme, with automatic fallback to 'default'.
     */
    public function resolveView(string $themeKey, string $viewName): string
    {
        $themeKey = $themeKey ?: self::FALLBACK_THEME;
        $domainMap = DefaultThemeContract::getRequiredPages();
        $domain = $domainMap[$viewName] ?? null;

        // 1. Check in themes.{theme}.pages.{domain}.{view}
        if ($domain && View::exists("themes.{$themeKey}.pages.{$domain}.{$viewName}")) {
            return "themes.{$themeKey}.pages.{$domain}.{$viewName}";
        }

        // 2. Check in themes.{theme}.pages.{view}
        if (View::exists("themes.{$themeKey}.pages.{$viewName}")) {
            return "themes.{$themeKey}.pages.{$viewName}";
        }

        // 3. Check in themes.{theme}.{view}
        if (View::exists("themes.{$themeKey}.{$viewName}")) {
            return "themes.{$themeKey}.{$viewName}";
        }

        // 4. Fallback to 'default' theme if current theme is not 'default'
        if ($themeKey !== self::FALLBACK_THEME) {
            $fallback = self::FALLBACK_THEME;

            if ($domain && View::exists("themes.{$fallback}.pages.{$domain}.{$viewName}")) {
                Log::warning("View '{$viewName}' tidak ditemukan pada tema '{$themeKey}', dialihkan ke fallback tema '{$fallback}'.");

                return "themes.{$fallback}.pages.{$domain}.{$viewName}";
            }

            if (View::exists("themes.{$fallback}.pages.{$viewName}")) {
                Log::warning("View '{$viewName}' tidak ditemukan pada tema '{$themeKey}', dialihkan ke fallback tema '{$fallback}'.");

                return "themes.{$fallback}.pages.{$viewName}";
            }

            if (View::exists("themes.{$fallback}.{$viewName}")) {
                Log::warning("View '{$viewName}' tidak ditemukan pada tema '{$themeKey}', dialihkan ke fallback tema '{$fallback}'.");

                return "themes.{$fallback}.{$viewName}";
            }
        }

        // 5. Final fallback string representation (always prefers default if target theme is missing)
        $targetTheme = View::exists("themes.{$themeKey}.layouts.public") ? $themeKey : self::FALLBACK_THEME;

        return $domain
            ? "themes.{$targetTheme}.pages.{$domain}.{$viewName}"
            : "themes.{$targetTheme}.pages.{$viewName}";
    }

    /**
     * Resolve a modular section view across semantic areas (header, main, footer) with fallback to 'default'.
     */
    public static function resolveSection(string $theme, string $sectionType, ?string $preferredArea = null): ?string
    {
        $theme = $theme ?: self::FALLBACK_THEME;
        $sectionType = DefaultThemeContract::getLegacyAliases()[$sectionType] ?? $sectionType;
        $order = match ($preferredArea) {
            'header' => ['header', 'main', 'footer'],
            'footer' => ['footer', 'main', 'header'],
            default => ['main', 'header', 'footer'],
        };

        foreach ($order as $area) {
            $view = "themes.{$theme}.sections.{$area}.{$sectionType}.{$sectionType}";
            if (View::exists($view)) {
                return $view;
            }
        }

        // Fallback to 'default' theme section if not found in requested theme
        if ($theme !== self::FALLBACK_THEME) {
            $fallback = self::FALLBACK_THEME;
            foreach ($order as $area) {
                $fallbackView = "themes.{$fallback}.sections.{$area}.{$sectionType}.{$sectionType}";
                if (View::exists($fallbackView)) {
                    return $fallbackView;
                }
            }
        }

        return null;
    }
}
