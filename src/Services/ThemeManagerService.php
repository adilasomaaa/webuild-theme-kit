<?php

namespace Webuild\ThemeKit\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Webuild\ThemeKit\Contract\DefaultThemeContract;
use Webuild\ThemeKit\Contract\ThemeContract;
use Webuild\ThemeKit\Models\ThemeRelease;
use Webuild\ThemeKit\Models\ThemeUpdateLog;
use Webuild\ThemeKit\ThemeRegistry;

class ThemeManagerService
{
    public function __construct(
        protected ThemeRegistry $registry
    ) {}

    /**
     * Validate theme manifest and structural completeness.
     *
     * @return array{valid: bool, errors: list<string>, warnings: list<string>, manifest: array<string, mixed>}
     */
    public function validateTheme(string $themePath): array
    {
        $errors = [];
        $warnings = [];
        $manifestPath = rtrim($themePath, '/').'/theme.json';

        if (! File::exists($manifestPath)) {
            return [
                'valid' => false,
                'errors' => ["File 'theme.json' tidak ditemukan di path: {$themePath}"],
                'warnings' => [],
                'manifest' => [],
            ];
        }

        $content = File::get($manifestPath);
        $manifest = json_decode($content, true);

        if (! is_array($manifest)) {
            return [
                'valid' => false,
                'errors' => ['File theme.json tidak berformat JSON yang valid.'],
                'warnings' => [],
                'manifest' => [],
            ];
        }

        $errors = [...$errors, ...$this->validateManifest($manifest)['errors']];

        // Check required view directories
        $viewsPath = is_dir($themePath.'/views') ? $themePath.'/views' : $themePath;
        if (! is_dir($viewsPath.'/layouts')) {
            $errors[] = "Folder 'layouts/' tidak ditemukan di dalam views tema.";
        }
        if (! is_dir($viewsPath.'/pages')) {
            $errors[] = "Folder 'pages/' tidak ditemukan di dalam views tema.";
        }
        if (! is_dir($viewsPath.'/sections')) {
            $errors[] = "Folder 'sections/' tidak ditemukan di dalam views tema.";
        }

        $componentVariants = $manifest['component_variants'] ?? [];
        if (! is_array($componentVariants)) {
            $errors[] = "Field 'component_variants' pada theme.json harus berupa object.";
            $componentVariants = [];
        }

        foreach ($componentVariants as $component => $definition) {
            if (! is_array($definition) || ! is_array($definition['options'] ?? null) || $definition['options'] === []) {
                $errors[] = "Daftar variant untuk komponen '{$component}' harus berisi 'options' non-kosong.";

                continue;
            }

            $default = $definition['default'] ?? null;
            if (! is_string($default) || ! array_key_exists($default, $definition['options'])) {
                $errors[] = "Variant default untuk komponen '{$component}' harus cocok dengan salah satu key 'options'.";
            }

            foreach ($definition['options'] as $variant => $option) {
                if (! is_array($option) || empty($option['label'])) {
                    $errors[] = "Variant '{$component}.{$variant}' harus memiliki 'label'.";

                    continue;
                }

                $view = $option['view'] ?? null;
                if ($view !== null && (! is_string($view) || str_starts_with($view, '/') || str_contains($view, '..') || ! File::exists($viewsPath.'/'.$view))) {
                    $errors[] = "View variant '{$component}.{$variant}' tidak valid atau tidak ditemukan.";
                }
            }
        }

        $componentSettings = $manifest['component_settings'] ?? [];
        if (! is_array($componentSettings)) {
            $errors[] = "Field 'component_settings' pada theme.json harus berupa object.";
            $componentSettings = [];
        }

        $declaredComponents = [];
        $manifestSections = $manifest['sections'] ?? [];
        if (! is_array($manifestSections)) {
            $errors[] = "Field 'sections' pada theme.json harus berupa object.";
            $manifestSections = [];
        }

        foreach ($manifestSections as $area => $sectionTypes) {
            if (! is_array($sectionTypes)) {
                $errors[] = "Daftar section '{$area}' pada theme.json harus berupa array.";

                continue;
            }
            $declaredComponents = [...$declaredComponents, ...$sectionTypes];
        }

        $sectionAreas = ['header' => 'header', 'main' => 'main', 'footer' => 'footer'];
        foreach (DefaultThemeContract::getRequiredSections() as $area => $sectionTypes) {
            foreach ($sectionTypes as $section) {
                $view = $viewsPath.'/sections/'.$sectionAreas[$area].'/'.$section.'/'.$section.'.blade.php';
                if (! File::exists($view)) {
                    $errors[] = "Section wajib '{$section}' tidak tersedia pada {$view}.";
                }
            }
        }

        foreach (array_unique([...array_keys($componentVariants), ...array_keys($componentSettings)]) as $component) {
            if ($declaredComponents !== [] && ! in_array($component, $declaredComponents, true)) {
                $errors[] = "Komponen '{$component}' pada metadata tidak terdaftar di 'sections'.";
            }
        }

        foreach ($componentSettings as $component => $fields) {
            if (! is_array($fields)) {
                $errors[] = "Pengaturan untuk komponen '{$component}' harus berupa object.";

                continue;
            }

            foreach ($fields as $field => $definition) {
                if (! is_array($definition) || empty($definition['type']) || empty($definition['label'])) {
                    $errors[] = "Pengaturan '{$component}.{$field}' harus memiliki 'type' dan 'label'.";

                    continue;
                }

                if (($definition['type'] ?? null) === 'select' && (! is_array($definition['options'] ?? null) || $definition['options'] === [])) {
                    $errors[] = "Pengaturan select '{$component}.{$field}' harus memiliki 'options'.";
                }
            }
        }

        $componentDefinitions = $manifest['component_definitions'] ?? [];
        if (! is_array($componentDefinitions)) {
            $errors[] = "Field 'component_definitions' pada theme.json harus berupa object.";
            $componentDefinitions = [];
        }
        $supportedSources = ['static', 'news', 'articles', 'gallery', 'agenda', 'documents', 'faqs', 'sliders'];
        foreach ($componentDefinitions as $component => $definition) {
            if (! is_array($definition) || empty($definition['label']) || empty($definition['category']) || empty($definition['icon'])
                || ! is_array($definition['default_configuration'] ?? null)) {
                $errors[] = "Definisi komponen '{$component}' harus memiliki label, kategori, ikon, dan default_configuration.";

                continue;
            }
            $sources = $definition['data_source'] ?? [];
            $sources = is_string($sources) ? [$sources] : $sources;
            if (! is_array($sources) || array_diff($sources, $supportedSources) !== []) {
                $errors[] = "Sumber data komponen '{$component}' tidak didukung.";
            }
            if ($declaredComponents !== [] && ! in_array($component, $declaredComponents, true)) {
                $errors[] = "Definisi komponen '{$component}' tidak terdaftar di 'sections'.";
            }
        }

        $demoDataPath = rtrim($themePath, '/').'/demo/data.json';
        if (File::exists($demoDataPath)) {
            $demoData = json_decode(File::get($demoDataPath), true);
            $dataTypes = ['news', 'articles', 'gallery', 'agenda', 'documents', 'faqs', 'sliders'];

            if (! is_array($demoData) || array_is_list($demoData)) {
                $errors[] = "File 'demo/data.json' harus berisi object JSON.";
            } else {
                foreach ($dataTypes as $type) {
                    if (! isset($demoData[$type]) || ! is_array($demoData[$type]) || ! array_is_list($demoData[$type])) {
                        $errors[] = "Field '{$type}' pada 'demo/data.json' harus berupa array.";

                        continue;
                    }

                    foreach ($demoData[$type] as $index => $record) {
                        if (! is_array($record) || array_is_list($record)) {
                            $errors[] = "Data '{$type}[{$index}]' harus berupa object JSON.";

                            continue;
                        }

                        $requiredFields = match ($type) {
                            'news', 'articles' => ['title', 'slug'],
                            'gallery', 'sliders' => ['title', 'image_url'],
                            'agenda' => ['title', 'start_at'],
                            'documents' => ['title'],
                            'faqs' => ['question', 'answer'],
                        };

                        foreach ($requiredFields as $field) {
                            if (! isset($record[$field]) || ! is_string($record[$field]) || $record[$field] === '') {
                                $errors[] = "Field '{$field}' wajib berupa string pada '{$type}[{$index}]'.";
                            }
                        }
                    }
                }
            }
        }

        // Check required pages completeness
        $requiredPages = DefaultThemeContract::getRequiredPages();
        foreach ($requiredPages as $pageKey => $domain) {
            $pagePath = $viewsPath."/pages/{$domain}/{$pageKey}.blade.php";
            $fallbackPath = $viewsPath."/pages/{$pageKey}.blade.php";
            if (! File::exists($pagePath) && ! File::exists($fallbackPath)) {
                $warnings[] = "Halaman publik 'pages/{$domain}/{$pageKey}.blade.php' belum tersedia.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'manifest' => $manifest,
        ];
    }

    /**
     * Compares a theme's previous manifest with the next one before it replaces it.
     * Runtime sections and variant options may only grow; dropping a non-catalog key is just reported.
     *
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $next
     * @return array{errors: list<string>, warnings: list<string>}
     */
    public function compareManifests(array $previous, array $next): array
    {
        $aliases = DefaultThemeContract::getLegacyAliases();
        $canonical = fn (string $key): string => $aliases[$key] ?? $key;
        $catalog = array_merge(...array_values(DefaultThemeContract::getRequiredSections()));
        $sectionsOf = function (array $manifest) use ($canonical): array {
            $keys = [];
            foreach (is_array($manifest['sections'] ?? null) ? $manifest['sections'] : [] as $components) {
                foreach (is_array($components) ? $components : [] as $component) {
                    $keys[] = $canonical((string) $component);
                }
            }

            return array_values(array_unique($keys));
        };
        $optionsOf = function (array $manifest) use ($canonical): array {
            $options = [];
            foreach (is_array($manifest['component_variants'] ?? null) ? $manifest['component_variants'] : [] as $component => $variant) {
                $options[$canonical((string) $component)] = array_keys(is_array($variant['options'] ?? null) ? $variant['options'] : []);
            }

            return $options;
        };

        $errors = [];
        $warnings = [];
        foreach (array_diff($sectionsOf($previous), $sectionsOf($next)) as $removed) {
            if (in_array($removed, $catalog, true)) {
                $errors[] = "Section '{$removed}' dihapus pada versi baru, padahal termasuk katalog widget Webuild.";
            } else {
                $warnings[] = "Section '{$removed}' tidak lagi disediakan (bukan bagian katalog widget Webuild).";
            }
        }
        $nextOptions = $optionsOf($next);
        foreach ($optionsOf($previous) as $component => $options) {
            if (! in_array($component, $catalog, true)) {
                continue;
            }
            $available = $nextOptions[$component] ?? array_keys(DefaultThemeContract::getBaseVariants()[$component]['options'] ?? []);
            foreach (array_diff($options, $available) as $removed) {
                $errors[] = "Pilihan variant '{$removed}' pada '{$component}' dihapus; tema boleh menambah variant, tidak boleh mengurangi.";
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validate manifest metadata without requiring a local copy of theme files.
     *
     * @param  array<string, mixed>  $manifest
     * @return array{valid: bool, errors: list<string>}
     */
    public function validateManifest(array $manifest): array
    {
        $errors = [];
        foreach (['key', 'name', 'version', 'api_version'] as $field) {
            if (! is_string($manifest[$field] ?? null) || trim($manifest[$field]) === '') {
                $errors[] = "Field wajib '{$field}' belum didefinisikan pada theme.json.";
            }
        }

        if (is_string($manifest['key'] ?? null) && ! preg_match('/^[a-z][a-z0-9_-]{0,99}$/', $manifest['key'])) {
            $errors[] = 'Key tema harus memakai huruf kecil, angka, garis bawah, atau tanda hubung.';
        }
        if (is_string($manifest['api_version'] ?? null) && version_compare($manifest['api_version'], ThemeContract::API_VERSION, '>')) {
            $errors[] = "Versi API tema ({$manifest['api_version']}) lebih baru dari Core Webuild (".ThemeContract::API_VERSION.'). Harap perbarui Core.';
        }

        $requiredCapabilities = $manifest['required_capabilities'] ?? [];
        if (! is_array($requiredCapabilities) || array_filter($requiredCapabilities, fn ($capability): bool => ! is_string($capability)) !== []) {
            $errors[] = "Field 'required_capabilities' pada theme.json harus berupa array string.";
        } else {
            foreach (array_diff($requiredCapabilities, ['document.flipbook', 'organization.structure']) as $capability) {
                $errors[] = "Kapabilitas core '{$capability}' belum didukung oleh Webuild.";
            }
        }

        $sections = $manifest['sections'] ?? null;
        if (! is_array($sections) || $sections === []) {
            $errors[] = "Field 'sections' pada theme.json harus berisi deklarasi section.";
        } else {
            foreach ($sections as $area => $components) {
                if (! in_array($area, ['header', 'main', 'footer'], true) || ! is_array($components)) {
                    $errors[] = "Deklarasi area section '{$area}' tidak valid.";

                    continue;
                }
                foreach ($components as $component) {
                    if (! is_string($component) || ! preg_match('/^[a-z][a-z0-9_]*$/', $component)) {
                        $errors[] = "Daftar section '{$area}' memuat key komponen yang tidak valid.";
                    }
                }
            }
        }

        $catalogEnforced = is_string($manifest['api_version'] ?? null) && version_compare($manifest['api_version'], '1.1', '>=');
        if ($catalogEnforced) {
            // Widget catalog is shared with Webuild: every runtime section is required and nothing else is allowed
            $aliases = DefaultThemeContract::getLegacyAliases();
            foreach (DefaultThemeContract::getRequiredSections() as $area => $requiredSections) {
                $declared = is_array($sections[$area] ?? null) ? $sections[$area] : [];
                foreach (array_diff($requiredSections, $declared) as $missing) {
                    $errors[] = "Section '{$missing}' wajib dicantumkan pada area {$area}.";
                }
                foreach (array_diff($declared, $requiredSections) as $unknown) {
                    $errors[] = isset($aliases[$unknown])
                        ? "Section '{$unknown}' pada area {$area} sudah diganti; gunakan '{$aliases[$unknown]}'."
                        : "Section '{$unknown}' pada area {$area} tidak termasuk katalog widget Webuild.";
                }
            }
        }

        $variants = $manifest['component_variants'] ?? [];
        if (! is_array($variants)) {
            $errors[] = "Field 'component_variants' pada theme.json harus berupa object.";
            $variants = [];
        }
        if ($catalogEnforced) {
            foreach (DefaultThemeContract::getBaseVariants() as $component => $base) {
                $variant = $variants[$component] ?? null;
                if (! is_array($variant)) {
                    continue;
                }
                $options = is_array($variant['options'] ?? null) ? $variant['options'] : [];
                foreach (array_diff(array_keys($base['options']), array_keys($options)) as $missing) {
                    $errors[] = "Variant '{$component}' wajib menyediakan pilihan dasar '{$missing}'; tema boleh menambah pilihan, tidak boleh mengurangi.";
                }
                if (isset($variant['configuration_key']) && $variant['configuration_key'] !== $base['configuration_key']) {
                    $errors[] = "Variant '{$component}' harus memakai configuration_key '{$base['configuration_key']}'.";
                }
            }
        }
        foreach ($variants as $component => $variant) {
            if (! is_array($variant) || ! is_array($variant['options'] ?? null) || $variant['options'] === []) {
                $errors[] = "Variant '{$component}' harus memiliki options.";

                continue;
            }
            if (! is_string($variant['default'] ?? null) || ! array_key_exists($variant['default'], $variant['options'])) {
                $errors[] = "Variant default '{$component}' harus tersedia pada options.";
            }
            foreach ($variant['options'] as $option) {
                if (! is_array($option) || empty($option['label'])) {
                    $errors[] = "Setiap pilihan variant '{$component}' harus memiliki label.";
                }
            }
        }

        $settings = $manifest['component_settings'] ?? [];
        if (! is_array($settings)) {
            $errors[] = "Field 'component_settings' pada theme.json harus berupa object.";
        } else {
            foreach ($settings as $component => $fields) {
                if (! is_array($fields)) {
                    $errors[] = "Pengaturan '{$component}' harus berupa object.";

                    continue;
                }
                foreach ($fields as $field => $definition) {
                    if (! is_array($definition) || empty($definition['type']) || empty($definition['label'])) {
                        $errors[] = "Pengaturan '{$component}.{$field}' harus memiliki type dan label.";
                    } elseif ($definition['type'] === 'select'
                        && (! is_array($definition['options'] ?? null) || $definition['options'] === [])) {
                        $errors[] = "Pengaturan select '{$component}.{$field}' harus memiliki options.";
                    }
                }
            }
        }

        $definitions = $manifest['component_definitions'] ?? [];
        if (! is_array($definitions)) {
            $errors[] = "Field 'component_definitions' pada theme.json harus berupa object.";
        } else {
            $supportedSources = ['static', 'news', 'articles', 'gallery', 'agenda', 'documents', 'faqs', 'sliders'];
            foreach ($definitions as $component => $definition) {
                if (! is_array($definition) || empty($definition['label']) || empty($definition['category'])
                    || empty($definition['icon']) || ! is_array($definition['default_configuration'] ?? null)) {
                    $errors[] = "Definisi komponen '{$component}' tidak lengkap.";

                    continue;
                }
                $sources = $definition['data_source'] ?? [];
                $sources = is_string($sources) ? [$sources] : $sources;
                if (! is_array($sources) || array_diff($sources, $supportedSources) !== []) {
                    $errors[] = "Sumber data komponen '{$component}' tidak didukung.";
                }
            }
        }

        $declaredComponents = [];
        foreach (is_array($sections) ? $sections : [] as $components) {
            if (is_array($components)) {
                $declaredComponents = [...$declaredComponents, ...$components];
            }
        }
        foreach (array_unique([...array_keys($variants), ...array_keys(is_array($settings) ? $settings : []), ...array_keys(is_array($definitions) ? $definitions : [])]) as $component) {
            if ($declaredComponents !== [] && ! in_array($component, $declaredComponents, true)) {
                $errors[] = "Metadata komponen '{$component}' tidak tercantum pada sections.";
            }
        }

        $assets = $manifest['assets'] ?? [];
        if (! is_array($assets) || ! is_array($assets['styles'] ?? []) || ! is_array($assets['scripts'] ?? [])) {
            $errors[] = 'Deklarasi assets.styles dan assets.scripts harus berupa array.';
        } else {
            foreach ($assets['styles'] ?? [] as $path) {
                if (! is_string($path) || ! preg_match('#^dist/[a-zA-Z0-9_./-]+\.css$#', $path) || str_contains($path, '..')) {
                    $errors[] = 'Path assets.styles harus berupa file CSS aman di dalam dist/.';
                }
            }
            foreach ($assets['scripts'] ?? [] as $asset) {
                $path = is_array($asset) ? ($asset['path'] ?? null) : $asset;
                if (! is_string($path) || ! preg_match('#^dist/[a-zA-Z0-9_./-]+\.(js|mjs)$#', $path) || str_contains($path, '..')
                    || (is_array($asset) && ! in_array($asset['type'] ?? null, ['classic', 'module'], true))) {
                    $errors[] = 'Path assets.scripts harus berupa file JavaScript aman di dalam dist/.';
                }
            }
        }

        return ['valid' => $errors === [], 'errors' => array_values(array_unique($errors))];
    }

    /**
     * Install a theme into the database catalog.
     *
     * @return array{success: bool, message: string}
     */
    public function install(string $themePath, ?string $actorName = null): array
    {
        $validation = $this->validateTheme($themePath);
        if (! $validation['valid']) {
            return [
                'success' => false,
                'message' => 'Validasi tema gagal: '.implode('; ', $validation['errors']),
            ];
        }

        $manifest = $validation['manifest'];
        $themeKey = $manifest['key'];
        $version = $manifest['version'];

        $log = ThemeUpdateLog::create([
            'theme_key' => $themeKey,
            'action' => 'install',
            'from_version' => null,
            'to_version' => $version,
            'channel' => 'main',
            'status' => 'pending',
            'actor_name' => $actorName ?? 'System',
            'started_at' => now(),
        ]);

        try {
            DB::transaction(function () use ($themeKey, $manifest, $version, $log) {
                // Upsert into themes table if available
                if (DB::getSchemaBuilder()->hasTable('themes')) {
                    DB::table('themes')->updateOrInsert(
                        ['key' => $themeKey],
                        [
                            'id' => DB::raw('COALESCE(id, '.(DB::getDriverName() === 'pgsql' ? 'gen_random_uuid()' : "'{$themeKey}'").')'),
                            'name' => $manifest['name'],
                            'description' => $manifest['description'] ?? null,
                            'blade_path' => "themes.{$themeKey}",
                            'manifest' => json_encode($manifest),
                            'is_active' => true,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }

                // Record release
                ThemeRelease::updateOrCreate(
                    ['theme_key' => $themeKey, 'version' => $version],
                    [
                        'channel' => 'main',
                        'changelog' => $manifest['changelog'] ?? 'Initial release',
                        'manifest_snapshot' => $manifest,
                        'released_at' => now(),
                    ]
                );

                $log->update([
                    'status' => 'success',
                    'log_output' => "Tema '{$manifest['name']}' ({$themeKey} v{$version}) berhasil dipasang.",
                    'finished_at' => now(),
                ]);
            });

            return [
                'success' => true,
                'message' => "Tema '{$manifest['name']}' berhasil dipasang.",
            ];
        } catch (Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal memasang tema: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Update an installed theme to a new release or channel.
     *
     * @return array{success: bool, message: string}
     */
    public function update(string $themeKey, string $themePath, string $channel = 'main', ?string $actorName = null): array
    {
        $currentRelease = ThemeRelease::where('theme_key', $themeKey)->latest('id')->first();
        $fromVersion = $currentRelease?->version ?? '0.0.0';

        $validation = $this->validateTheme($themePath);
        if (! $validation['valid']) {
            $errorMsg = 'Validasi tema gagal: '.implode('; ', $validation['errors']);

            ThemeUpdateLog::create([
                'theme_key' => $themeKey,
                'action' => 'update',
                'from_version' => $fromVersion,
                'to_version' => $fromVersion,
                'channel' => $channel,
                'status' => 'failed',
                'actor_name' => $actorName ?? 'System',
                'error_message' => $errorMsg." (Fallback aktif: '".ThemeRegistry::FALLBACK_THEME."')",
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => $errorMsg,
                'fallback_theme' => ThemeRegistry::FALLBACK_THEME,
            ];
        }

        $manifest = $validation['manifest'];
        $toVersion = $manifest['version'];

        $log = ThemeUpdateLog::create([
            'theme_key' => $themeKey,
            'action' => 'update',
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'channel' => $channel,
            'status' => 'pending',
            'actor_name' => $actorName ?? 'System',
            'started_at' => now(),
        ]);

        try {
            DB::transaction(function () use ($themeKey, $manifest, $fromVersion, $toVersion, $channel, $themePath, $log) {
                // Execute migration/upgrade script if present in upgrades/{version}.php
                $upgradeScript = rtrim($themePath, '/')."/upgrades/{$toVersion}.php";
                if (File::exists($upgradeScript)) {
                    $runner = require $upgradeScript;
                    if (is_callable($runner)) {
                        $runner();
                    }
                }

                // Update themes table
                if (DB::getSchemaBuilder()->hasTable('themes')) {
                    DB::table('themes')->where('key', $themeKey)->update([
                        'name' => $manifest['name'],
                        'description' => $manifest['description'] ?? null,
                        'manifest' => json_encode($manifest),
                        'updated_at' => now(),
                    ]);
                }

                // Record release
                ThemeRelease::updateOrCreate(
                    ['theme_key' => $themeKey, 'version' => $toVersion],
                    [
                        'channel' => $channel,
                        'changelog' => $manifest['changelog'] ?? 'Pembaruan ke v'.$toVersion,
                        'manifest_snapshot' => $manifest,
                        'released_at' => now(),
                    ]
                );

                $log->update([
                    'status' => 'success',
                    'log_output' => "Tema berhasil diperbarui dari v{$fromVersion} ke v{$toVersion}.",
                    'finished_at' => now(),
                ]);
            });

            return [
                'success' => true,
                'message' => "Tema '{$themeKey}' berhasil diperbarui ke v{$toVersion}.",
            ];
        } catch (Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal memperbarui tema: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Rollback a theme to its previous successful version.
     *
     * @return array{success: bool, message: string}
     */
    public function rollback(string $themeKey, ?string $actorName = null): array
    {
        $lastSuccessfulUpdate = ThemeUpdateLog::where('theme_key', $themeKey)
            ->where('status', 'success')
            ->whereNotNull('from_version')
            ->latest('id')
            ->first();

        if (! $lastSuccessfulUpdate || ! $lastSuccessfulUpdate->from_version) {
            return [
                'success' => false,
                'message' => "Tidak ditemukan riwayat versi sebelumnya untuk tema '{$themeKey}'.",
            ];
        }

        $targetVersion = $lastSuccessfulUpdate->from_version;
        $currentVersion = $lastSuccessfulUpdate->to_version;

        $targetRelease = ThemeRelease::where('theme_key', $themeKey)
            ->where('version', $targetVersion)
            ->first();

        $log = ThemeUpdateLog::create([
            'theme_key' => $themeKey,
            'action' => 'rollback',
            'from_version' => $currentVersion,
            'to_version' => $targetVersion,
            'channel' => 'main',
            'status' => 'pending',
            'actor_name' => $actorName ?? 'System',
            'started_at' => now(),
        ]);

        try {
            DB::transaction(function () use ($themeKey, $targetRelease, $targetVersion, $currentVersion, $log) {
                if ($targetRelease && DB::getSchemaBuilder()->hasTable('themes')) {
                    DB::table('themes')->where('key', $themeKey)->update([
                        'manifest' => json_encode($targetRelease->manifest_snapshot),
                        'updated_at' => now(),
                    ]);
                }

                $log->update([
                    'status' => 'success',
                    'log_output' => "Tema berhasil di-rollback dari v{$currentVersion} ke v{$targetVersion}.",
                    'finished_at' => now(),
                ]);
            });

            return [
                'success' => true,
                'message' => "Tema '{$themeKey}' berhasil di-rollback ke v{$targetVersion}.",
            ];
        } catch (Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal melakukan rollback: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get releases and update logs for a theme.
     *
     * @return array{releases: list<ThemeRelease>, logs: list<ThemeUpdateLog>}
     */
    public function getHistory(string $themeKey): array
    {
        return [
            'releases' => ThemeRelease::where('theme_key', $themeKey)->orderByDesc('id')->get()->all(),
            'logs' => ThemeUpdateLog::where('theme_key', $themeKey)->orderByDesc('id')->get()->all(),
        ];
    }
}
