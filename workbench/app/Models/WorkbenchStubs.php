<?php

namespace Webuild\ThemeKit\Workbench;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Webuild\ThemeKit\ThemeRegistry;

// Stub Models with Eloquent
class Agenda extends Model
{
    protected $guarded = [];
}

class Content extends Model
{
    protected $guarded = [];

    public function user()
    {
        return (object)['name' => 'Admin Redaksi'];
    }

    public function category()
    {
        return (object)['id' => 1, 'name' => 'Berita Pemerintahan', 'slug' => 'pemerintahan'];
    }

    public function comments()
    {
        return collect();
    }
}

class Document extends Model
{
    protected $guarded = [];

    public function category()
    {
        return (object)['id' => 1, 'name' => 'Peraturan', 'slug' => 'peraturan'];
    }
}

class DynamicContent extends Model
{
    protected $guarded = [];

    public function category()
    {
        return (object)['id' => 1, 'name' => 'Layanan Publik', 'slug' => 'layanan-publik'];
    }
}

class Media extends Model
{
    protected $guarded = [];
}

class Page extends Model
{
    protected $guarded = [];

    public function user()
    {
        return (object)['name' => 'Administrator'];
    }
}

class Application extends Model
{
    protected $guarded = [];

    public function organizationProfile()
    {
        return (object)[
            'website_name' => 'Diskominfo Portal Resmi',
            'header_logo_mode' => 'logo_name',
            'logo_url' => 'https://raw.githubusercontent.com/lucide-icons/lucide/main/icons/building-2.svg',
            'favicon_url' => null,
            'email' => 'diskominfo@example.go.id',
            'phone' => '(0435) 821123',
            'address' => 'Jl. Sapta Marga No. 12, Kompleks Perkantoran',
            'accessibility_mode' => 'standard',
            'rating_enabled' => false,
        ];
    }

    public function applicationWidgets()
    {
        return new class {
            public function where(...$args) { return $this; }
            public function orderBy(...$args) { return $this; }
            public function with(...$args) { return $this; }
            public function get() { return collect(); }
        };
    }
}

/** Application object for the preview: plain properties plus webuild-app's feature check (all features on). */
#[\AllowDynamicProperties]
class WorkbenchApplication
{
    /** @param  array<string, mixed>  $attributes */
    public function __construct(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function hasFeature(?string $feature = null): bool
    {
        return true;
    }

    public function hasFeatureForPublicLink(?string $feature = null): bool
    {
        return true;
    }
}

/** Booking schedule with the surface booking/step1_schedule reads from webuild-app's Schedule model. */
class ScheduleStub
{
    public Collection $exceptions;

    /** @param  list<int>  $operating_days */
    public function __construct(
        public int $id,
        public string $name,
        public string $start_time,
        public string $end_time,
        public array $operating_days = [1, 2, 3, 4, 5],
        public string $operating_days_label = 'Senin – Jumat',
        public int $default_capacity = 50,
    ) {
        $this->exceptions = collect();
    }

    /** @return array<string, int> */
    public function getAllCategoryCapacities(): array
    {
        return ['Pelajar' => 30, 'Umum' => $this->default_capacity];
    }
}

/** Menu item with the same surface the theme header reads from webuild-app's Menu model. */
class MenuItem
{
    public Collection $children;

    /** The footer reads `title`, the header reads `label`. */
    public string $title;

    /** @param  list<array<string, mixed>>  $children */
    public function __construct(
        public string $label,
        public ?string $url = null,
        public string $target = '_self',
        array $children = [],
    ) {
        $this->title = $label;
        $this->children = collect($children)->map(fn (array $child) => self::fromArray($child));
    }

    /** @param  array<string, mixed>  $item */
    public static function fromArray(array $item): self
    {
        return new self((string) ($item['label'] ?? ''), $item['url'] ?? null, (string) ($item['target'] ?? '_self'), (array) ($item['children'] ?? []));
    }

    public function getResolvedUrl(): string
    {
        return $this->url ?: '#';
    }
}

class ApplicationServiceStub
{
    public function getActiveRegisteredApplications(int $limit = 8): Collection
    {
        return collect([
            (object)[
                'id' => 1,
                'name' => 'Portal Layanan Terpadu',
                'url' => 'https://layanan.example.go.id',
                'description' => 'Layanan administrasi online terintegrasi untuk masyarakat.',
                'icon' => null,
            ],
            (object)[
                'id' => 2,
                'name' => 'Sistem Informasi Presensi',
                'url' => 'https://presensi.example.go.id',
                'description' => 'Monitoring absensi dan kinerja pegawai instansi.',
                'icon' => null,
            ],
            (object)[
                'id' => 3,
                'name' => 'JDIH Dokumentasi Hukum',
                'url' => 'https://jdih.example.go.id',
                'description' => 'Jaringan Dokumentasi dan Informasi Hukum Pemerintah Daerah.',
                'icon' => null,
            ],
        ])->take($limit);
    }
}

// Global class aliases for theme compatibility
class_alias(Agenda::class, 'App\Models\Agenda\Agenda');
class_alias(Content::class, 'App\Models\Content\Content');
class_alias(Document::class, 'App\Models\Document\Document');
class_alias(DynamicContent::class, 'App\Models\DynamicContent\DynamicContent');
class_alias(Media::class, 'App\Models\Media\Media');
class_alias(Page::class, 'App\Models\Page\Page');
class_alias(Application::class, 'App\Models\Application\Application');
class_alias(ApplicationServiceStub::class, 'App\Services\Application\ApplicationService');

// Trait compatibility for ResolvesThemeViews
if (! trait_exists('App\Traits\ResolvesThemeViews')) {
    trait ResolvesThemeViewsCompat {
        public static function resolveSection(string $theme, string $sectionType, ?string $preferredArea = null): ?string
        {
            return ThemeRegistry::resolveSection($theme, $sectionType, $preferredArea);
        }
    }
    class_alias(ResolvesThemeViewsCompat::class, 'App\Traits\ResolvesThemeViews');
}
