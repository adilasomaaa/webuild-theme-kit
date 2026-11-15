<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Webuild\ThemeKit\ThemeRegistry;

/**
 * Pengaturan tampilan workbench per tema (warna, section beranda, header/footer, menu, pengaturan halaman).
 * Section beranda mengikuti page builder webuild-app: section → kolom (col_span dari 12) → satu komponen per kolom.
 * Disimpan di storage/customizer/{themeKey}.json dan hanya dipakai saat pengembangan tema.
 */
class WorkbenchCustomizer
{
    public const HEADER_AREAS = ['header_col_1', 'header_col_2', 'header_col_3'];

    public const FOOTER_AREAS = ['footer_col_1', 'footer_col_2', 'footer_col_3'];

    // Kunci konfigurasi varian bila theme.json tidak menyebut configuration_key.
    private const VARIANT_KEYS = [
        'header_logo' => 'mode',
        'header_button' => 'button_variant',
        'agenda' => 'timeframe',
        'faq' => 'layout_orientation',
        'slider' => 'aspect_ratio',
    ];

    public const MENU_TYPES = [
        'custom_url' => 'URL Kustom / Anchor (#)',
        'page' => 'Halaman',
        'content' => 'Konten (Berita / Artikel)',
        'feature' => 'Fitur Aplikasi',
        'category' => 'Kategori Konten (Berita / Artikel / Halaman)',
        'document_category' => 'Kategori Dokumen',
        'media_category' => 'Kategori Media / Galeri',
        'dynamic_category' => 'Kategori Katalog',
        'external_link' => 'Tautan Eksternal (Website Lain)',
    ];

    // Preset tata letak kolom untuk tombol "Tambah Section" (jumlah col_span per kolom = 12).
    public const COLUMN_PRESETS = [
        '12' => [12],
        '6-6' => [6, 6],
        '8-4' => [8, 4],
        '4-8' => [4, 8],
        '4-4-4' => [4, 4, 4],
        '3-3-3-3' => [3, 3, 3, 3],
    ];

    private ?array $state = null;

    public function themeKey(): string
    {
        return (string) config('theme.active', 'modern');
    }

    public function manifest(): array
    {
        return app(ThemeRegistry::class)->get($this->themeKey())['manifest'] ?? [];
    }

    /** Field pengaturan komponen: bawaan workbench (config/component_fields.php) ditimpa component_settings tema. */
    public function componentFields(string $type): array
    {
        static $fields = null;
        $fields ??= require dirname(__DIR__, 2) . '/config/component_fields.php';

        $merged = array_merge($fields[$type] ?? [], $this->manifest()['component_settings'][$type] ?? []);
        if ($type === 'social_media' && isset($merged['links'])) {
            // Akun di luar platform profil (tiktok, linkedin, dst.) dari data demo menjadi tautan tambahan bawaan.
            $merged['links']['default'] = app(ThemeDemoData::class)->collection('social_media')
                ->reject(fn ($social) => in_array($social->platform, ['instagram', 'facebook', 'twitter', 'youtube', 'website'], true))
                ->map(fn ($social) => ['platform' => $social->platform, 'label' => $social->label, 'url' => $social->url])
                ->values()
                ->all();
        }

        return $merged;
    }

    /** @return array<string, array<string, mixed>> */
    public function pageRegistry(): array
    {
        return require dirname(__DIR__, 2) . '/config/page_settings.php';
    }

    public function state(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

        $saved = is_file($this->path()) ? json_decode((string) file_get_contents($this->path()), true) : null;
        $defaults = $this->defaults();

        return $this->state = is_array($saved) ? $this->sanitize($saved + $defaults) : $defaults;
    }

    public function save(array $input): array
    {
        $state = $this->sanitize($input + $this->defaults());
        @mkdir(dirname($this->path()), 0755, true);
        file_put_contents($this->path(), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $this->state = $state;
    }

    public function reset(): void
    {
        @unlink($this->path());
        $this->state = null;
    }

    /** Bentuk sama dengan $layoutSections aplikasi: area => collection objek section. Area main diratakan dari kolom section. */
    public function layoutSections(): Collection
    {
        $main = collect($this->state()['sections'])
            ->filter(fn (array $section) => $section['is_enabled'])
            ->flatMap(fn (array $section) => $section['columns'])
            ->filter(fn (array $column) => $column['section_type'] !== null)
            ->map(fn (array $column) => ['section_type' => $column['section_type'], 'is_enabled' => true, 'configuration' => $column['configuration']])
            ->values()
            ->all();

        return collect(['main' => $main] + $this->state()['layout'])
            ->map(fn (array $items) => collect($items)->map(fn (array $item) => (object) $item));
    }

    /** Nilai pengaturan tema (theme.json → settings_schema, mis. warna) untuk $application->activeTheme->configuration. */
    public function themeConfiguration(): array
    {
        return $this->state()['theme'];
    }

    /** Section beranda untuk page builder tema ($page->sections dengan kolom). */
    public function pageSections(): array
    {
        return $this->state()['sections'];
    }

    /** Menu yang ditandai tampil, dalam bentuk yang dibaca layouts/header. */
    public function menus(): Collection
    {
        $visible = function (array $items) use (&$visible): array {
            return collect($items)
                ->filter(fn (array $item) => $item['is_visible'])
                ->map(fn (array $item) => [
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'target' => $item['target'],
                    'children' => $visible($item['children']),
                ])
                ->values()
                ->all();
        };

        return collect($visible($this->state()['menus']))->map(fn (array $item) => \Webuild\ThemeKit\Workbench\MenuItem::fromArray($item));
    }

    public function pageSettings(string $page): array
    {
        return $this->state()['pages'][$page] ?? [];
    }

    /** Meniru PublicController webuild-app: ?layout= hanya berlaku bila pilihan varian ditampilkan. */
    public function layoutVariant(string $page): ?string
    {
        $options = array_keys($this->pageRegistry()[$page]['fields']['default_variant']['options'] ?? []);
        $settings = $this->pageSettings($page);
        $requested = (string) request('layout', '');

        if (($settings['show_variant_switcher'] ?? true) && in_array($requested, $options, true)) {
            return $requested;
        }

        return $settings['default_variant'] ?? ($options[0] ?? null);
    }

    public function pageKeyForRoute(?string $routeName): ?string
    {
        foreach ($this->pageRegistry() as $key => $page) {
            if ($routeName && in_array($routeName, $page['routes'] ?? [], true)) {
                return $key;
            }
        }

        return null;
    }

    /** Metadata untuk drawer: komponen per area, varian, field pengaturan, dan registry halaman. */
    public function schema(): array
    {
        $manifest = $this->manifest();
        $components = [];

        foreach (['header', 'main', 'footer'] as $area) {
            foreach ($this->allowedSections($area) as $type) {
                $variants = $manifest['component_variants'][$type] ?? [];
                $components[$type] = [
                    'label' => ucwords(str_replace('_', ' ', $type)),
                    'variant_key' => $this->variantKey($type),
                    'variants' => collect($variants['options'] ?? [])->map(fn ($option, $key) => $option['label'] ?? $key)->all(),
                    'settings' => $this->componentFields($type),
                    'default_configuration' => (object) $this->defaultConfiguration($type),
                ];
            }
        }

        return [
            'theme' => ['key' => $this->themeKey(), 'name' => $manifest['name'] ?? $this->themeKey()],
            'themeSettings' => $this->themeSettingsSchema(),
            'areas' => [
                'header' => self::HEADER_AREAS,
                'footer' => self::FOOTER_AREAS,
            ],
            'sections' => [
                'header' => $this->allowedSections('header'),
                'main' => $this->allowedSections('main'),
                'footer' => $this->allowedSections('footer'),
            ],
            'components' => $components,
            'columnPresets' => self::COLUMN_PRESETS,
            'menuTypes' => self::MENU_TYPES,
            'menuSources' => $this->menuSources(),
            'pages' => collect($this->pageRegistry())->map(fn (array $page) => [
                'label' => $page['label'],
                'fields' => $page['fields'],
            ])->all(),
        ];
    }

    // Nama folder ikut dipakai karena salinan starter yang belum diganti key-nya masih memakai key yang sama.
    private function path(): string
    {
        $folder = basename((string) (app(ThemeRegistry::class)->get($this->themeKey())['base_path'] ?? ''));
        $name = preg_replace('/[^a-z0-9_-]/i', '', $this->themeKey() . '-' . $folder);

        return storage_path("customizer/{$name}.json");
    }

    private function defaults(): array
    {
        $item = fn (string $type, bool $enabled = true, array $configuration = []) => [
            'section_type' => $type,
            'is_enabled' => $enabled,
            'configuration' => $this->defaultConfiguration($type) + $configuration,
        ];

        $column = fn (string $type, int $span = 12, array $configuration = []) => [
            'col_span' => $span, 'section_type' => $type, 'configuration' => $this->defaultConfiguration($type) + $configuration,
        ];
        $sections = [
            [$column('slider')],
            [$column('hero')],
            [$column('card_grid')],
            [$column('card_info', 7), $column('profile_video', 5, ['layout_orientation' => 'vertical'])],
            [$column('contents', 12, ['limit' => 6])],
            [$column('media_gallery', 12, ['limit' => 6])],
            [$column('agenda', 6, ['limit' => 4]), $column('documents', 6, ['limit' => 4])],
            [$column('faq')],
        ];
        // Komponen yang tidak didaftarkan tema di sections.main dilewati.
        $allowedMain = $this->allowedSections('main');
        $sections = array_values(array_filter(array_map(
            fn (array $columns) => array_values(array_filter($columns, fn (array $col) => $allowedMain === [] || in_array($col['section_type'], $allowedMain, true))),
            $sections,
        )));

        $pages = collect($this->pageRegistry())
            ->map(fn (array $page) => collect($page['fields'])->map(fn (array $field) => $field['default'] ?? null)->all())
            ->all();

        return [
            'theme' => collect($this->themeSettingsSchema())->map(fn (array $field) => $field['default'] ?? null)->all(),
            'layout' => [
                'header_col_1' => [$item('header_logo')],
                'header_col_2' => [$item('header_menu')],
                'header_col_3' => [$item('header_search', true, ['placeholder' => 'Cari informasi...'])],
                'footer_col_1' => [$item('footer')],
                'footer_col_2' => [$item('quick_links')],
                'footer_col_3' => [$item('contact_info')],
            ],
            'sections' => $this->sanitizeSections(array_map(fn (array $columns) => ['is_enabled' => true, 'columns' => $columns], $sections)),
            'menus' => $this->defaultMenus(),
            'pages' => $pages,
        ];
    }

    private function defaultMenus(): array
    {
        $menu = fn (string $label, string $type, ?string $reference, string $url, array $children = []) => [
            'label' => $label, 'type' => $type, 'reference' => $reference, 'url' => $url,
            'target' => '_self', 'is_visible' => true, 'children' => $children,
        ];

        return [
            $menu('Beranda', 'custom_url', null, '/'),
            $menu('Berita', 'feature', 'berita', '/news'),
            $menu('Agenda', 'feature', 'agenda', '/f/agenda'),
            $menu('Dokumen', 'feature', 'dokumen', '/document'),
            $menu('Informasi', 'custom_url', null, '#', [
                $menu('Galeri', 'feature', 'galeri', '/media'),
                $menu('FAQ', 'feature', 'faq', '/f/faqs'),
                $menu('Buku Tamu', 'feature', 'buku_tamu', '/f/guestbook'),
            ]),
        ];
    }

    /** Pilihan referensi menu per tipe, diambil dari data demo tema: [{value, label, url}]. */
    private function menuSources(): array
    {
        $demo = app(ThemeDemoData::class);
        $option = fn (string $value, string $label, string $url) => compact('value', 'label', 'url');
        $categories = fn (Collection $items, string $prefix) => $items
            ->pluck('category')
            ->filter(fn ($category) => is_object($category))
            ->unique(fn ($category) => data_get($category, 'id'))
            ->map(function ($category) use ($option, $prefix) {
                $slug = data_get($category, 'slug') ?: \Illuminate\Support\Str::slug((string) data_get($category, 'name'));

                return $option($slug, (string) data_get($category, 'name'), $prefix . $slug);
            })
            ->values()
            ->all();

        return [
            'page' => $demo->collection('pages')->map(fn ($page) => $option($page->slug, $page->title, '/p/' . $page->slug))->all(),
            'content' => [
                ...$demo->collection('news')->map(fn ($item) => $option('news:' . $item->slug, '[BERITA] ' . $item->title, '/news/' . $item->slug))->all(),
                ...$demo->collection('articles')->map(fn ($item) => $option('article:' . $item->slug, '[ARTIKEL] ' . $item->title, '/article/' . $item->slug))->all(),
            ],
            'feature' => [
                $option('berita', 'Berita', '/news'),
                $option('artikel', 'Artikel', '/article'),
                $option('agenda', 'Agenda Kegiatan', '/f/agenda'),
                $option('dokumen', 'Dokumen', '/document'),
                $option('galeri', 'Galeri Media', '/media'),
                $option('faq', 'FAQ', '/f/faqs'),
                $option('buku_tamu', 'Buku Tamu', '/f/guestbook'),
                $option('booking', 'Booking Kunjungan', '/f/booking'),
                $option('pelacakan_booking', 'Lacak Status Reservasi', '/f/booking/track'),
                $option('katalog', 'Katalog Informasi', '/f/catalog'),
                $option('struktur_organisasi', 'Struktur Organisasi', '/f/organisasi'),
                $option('pencarian', 'Pencarian', '/search'),
            ],
            'category' => $categories($demo->collection('contents'), '/category/'),
            'document_category' => $categories($demo->collection('documents'), '/document/category/'),
            'media_category' => $categories($demo->collection('gallery'), '/media/category/'),
            'dynamic_category' => [],
        ];
    }

    private function defaultConfiguration(string $type): array
    {
        $manifest = $this->manifest();
        $configuration = collect($this->componentFields($type))
            ->map(fn (array $field) => $field['default'] ?? null)
            ->filter(fn ($value) => $value !== null)
            ->all();

        if (isset($manifest['component_variants'][$type]['default'])) {
            $configuration[$this->variantKey($type)] = $manifest['component_variants'][$type]['default'];
        }

        return $configuration;
    }

    /** @return array<string, array<string, mixed>> */
    private function themeSettingsSchema(): array
    {
        return array_filter((array) ($this->manifest()['settings_schema'] ?? []), 'is_array');
    }

    private function variantKey(string $type): string
    {
        return $this->manifest()['component_variants'][$type]['configuration_key'] ?? self::VARIANT_KEYS[$type] ?? 'layout_variant';
    }

    /**
     * Main sections webuild-app also allows in footer_col_1..3 (`allowed_areas` in webuild-app config/layout_sections.php).
     *
     * @var list<string>
     */
    private const MAIN_SECTIONS_ALLOWED_IN_FOOTER = [
        'contents', 'card_grid', 'audio', 'documents', 'contact', 'maps_embed',
        'booking_tracking', 'guestbook_widget', 'agenda', 'pranala', 'social_media',
    ];

    /** @return list<string> */
    private function allowedSections(string $area): array
    {
        $declared = array_values(array_filter((array) ($this->manifest()['sections'][$area] ?? []), 'is_string'));

        if ($area !== 'footer') {
            return $declared;
        }

        // Same as webuild-app: footer columns accept the theme's footer sections plus these main sections.
        $main = array_values(array_filter((array) ($this->manifest()['sections']['main'] ?? []), 'is_string'));

        return array_values(array_unique([...$declared, ...array_intersect(self::MAIN_SECTIONS_ALLOWED_IN_FOOTER, $main)]));
    }

    private function sanitize(array $state): array
    {
        $layout = [];
        foreach ([...self::HEADER_AREAS, ...self::FOOTER_AREAS] as $area) {
            $allowed = $this->allowedSections(str_starts_with($area, 'header') ? 'header' : 'footer');
            $layout[$area] = collect((array) ($state['layout'][$area] ?? []))
                ->filter(fn ($item) => is_array($item) && is_string($item['section_type'] ?? null))
                ->filter(fn (array $item) => $allowed === [] || in_array($item['section_type'], $allowed, true))
                ->map(fn (array $item) => [
                    'section_type' => $item['section_type'],
                    'is_enabled' => (bool) ($item['is_enabled'] ?? true),
                    'configuration' => $this->sanitizeConfiguration($item['section_type'], (array) ($item['configuration'] ?? [])),
                ])
                ->values()
                ->all();
        }

        $registry = $this->pageRegistry();
        $pages = [];
        foreach ($registry as $key => $page) {
            foreach ($page['fields'] as $field => $definition) {
                $pages[$key][$field] = $this->castField($definition, $state['pages'][$key][$field] ?? $definition['default'] ?? null);
            }
        }

        $theme = [];
        foreach ($this->themeSettingsSchema() as $key => $definition) {
            $value = $state['theme'][$key] ?? $definition['default'] ?? null;
            $theme[$key] = ($definition['type'] ?? null) === 'color'
                ? (is_string($value) && preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : ($definition['default'] ?? null))
                : $this->castField($definition, $value);
        }

        return [
            'theme' => $theme,
            'layout' => $layout,
            'sections' => $this->sanitizeSections((array) ($state['sections'] ?? [])),
            'menus' => $this->sanitizeMenus((array) ($state['menus'] ?? [])),
            'pages' => $pages,
        ];
    }

    private function sanitizeSections(array $sections): array
    {
        $allowed = $this->allowedSections('main');
        $id = fn ($value, string $prefix) => is_string($value) && preg_match('/^[a-z0-9_-]{1,40}$/i', $value) ? $value : $prefix . bin2hex(random_bytes(4));

        return collect($sections)
            ->filter(fn ($section) => is_array($section) && is_array($section['columns'] ?? null))
            ->map(fn (array $section) => [
                'id' => $id($section['id'] ?? null, 'sec-'),
                'is_enabled' => (bool) ($section['is_enabled'] ?? true),
                'columns' => collect($section['columns'])
                    ->filter(fn ($column) => is_array($column))
                    ->take(4)
                    ->map(function (array $column) use ($allowed, $id) {
                        $type = is_string($column['section_type'] ?? null) && ($allowed === [] || in_array($column['section_type'], $allowed, true))
                            ? $column['section_type']
                            : null;

                        return [
                            'id' => $id($column['id'] ?? null, 'col-'),
                            'col_span' => max(1, min(12, (int) ($column['col_span'] ?? 12))),
                            'section_type' => $type,
                            'configuration' => $type ? $this->sanitizeConfiguration($type, (array) ($column['configuration'] ?? [])) : [],
                        ];
                    })
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $section) => $section['columns'] !== [])
            ->values()
            ->all();
    }

    private function sanitizeConfiguration(string $type, array $configuration): array
    {
        $variantKey = $this->variantKey($type);
        $options = array_keys($this->manifest()['component_variants'][$type]['options'] ?? []);

        if (isset($configuration[$variantKey]) && $options !== [] && ! in_array($configuration[$variantKey], $options, true)) {
            unset($configuration[$variantKey]);
        }

        return $configuration;
    }

    private function sanitizeMenus(array $items, int $depth = 0): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['label'] ?? '')) !== '')
            ->map(fn (array $item) => [
                'label' => trim((string) $item['label']),
                'type' => array_key_exists($item['type'] ?? '', self::MENU_TYPES) ? $item['type'] : 'custom_url',
                'reference' => isset($item['reference']) && is_scalar($item['reference']) ? (string) $item['reference'] : null,
                'url' => trim((string) ($item['url'] ?? '')) ?: '#',
                'target' => ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                'is_visible' => (bool) ($item['is_visible'] ?? true),
                'children' => $depth < 1 ? $this->sanitizeMenus((array) ($item['children'] ?? []), $depth + 1) : [],
            ])
            ->values()
            ->all();
    }

    private function castField(array $definition, mixed $value): mixed
    {
        return match ($definition['type'] ?? 'string') {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'number', 'integer' => is_numeric($value) ? $value + 0 : ($definition['default'] ?? 0),
            'select' => array_key_exists((string) $value, $definition['options'] ?? []) ? (string) $value : ($definition['default'] ?? null),
            default => is_scalar($value) ? (string) $value : ($definition['default'] ?? ''),
        };
    }
}
