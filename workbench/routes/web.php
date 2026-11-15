<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use App\Support\ThemeDemoData;
use App\Support\WorkbenchCustomizer;
use Illuminate\Http\Request;
use Webuild\ThemeKit\Workbench\DynamicContent;

Route::get('/theme-assets/{themeKey}/{asset}', function (string $themeKey, string $asset) {
    abort_unless(preg_match('#^dist/[a-zA-Z0-9_./-]+\.(css|js|mjs|png|jpg|jpeg|webp|gif|svg|woff2?|ttf|ico|avif)$#i', $asset) === 1 && ! str_contains($asset, '..'), 404);
    abort_unless(preg_match('/^[a-z0-9_-]+$/', $themeKey) === 1, 404);
    $root = realpath(dirname(__DIR__, 3).'/themes/'.$themeKey.'/dist');
    $path = realpath(dirname(__DIR__, 3).'/themes/'.$themeKey.'/'.$asset);
    abort_unless($root && $path && str_starts_with($path, $root.DIRECTORY_SEPARATOR) && is_file($path), 404);
    $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
        'css' => 'text/css; charset=UTF-8',
        'js', 'mjs' => 'text/javascript; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        default => mime_content_type($path) ?: 'application/octet-stream',
    };

    return response()->file($path, ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff']);
})->where('asset', '.*');

// Shared Mock Context for Public Themes
$buildContext = function (string $title = 'Portal Demo') {
    $activeThemeKey = config('theme.active', 'modern');
    $demo = app(ThemeDemoData::class);

    $app = new \Webuild\ThemeKit\Workbench\WorkbenchApplication([
        'id' => '00000000-0000-0000-0000-000000000000',
        'name' => 'Dinas Komunikasi, Informatika dan Statistik',
        'hostname' => 'localhost',
        'activeTheme' => (object)[
            'key' => $activeThemeKey,
            // Warna dan pengaturan tema lain dari drawer customizer (default: theme.json → settings_schema).
            'configuration' => app(WorkbenchCustomizer::class)->themeConfiguration(),
        ],
        'organizationProfile' => (object)[
            'website_name' => 'Diskominfo Portal Resmi',
            'header_logo_mode' => 'logo_name',
            'logo_url' => null, // tema menampilkan inisial nama situs
            'favicon_url' => null,
            'email' => 'diskominfo@example.go.id',
            'phone' => '(0435) 821123',
            'address' => 'Jl. Sapta Marga No. 12, Kompleks Perkantoran Pemerintah Daerah',
            'accessibility_mode' => 'standard',
            'rating_enabled' => false,
        ],
    ]);

    $profile = (object)[
        'website_name' => 'Diskominfo Portal Resmi',
        'header_logo_mode' => 'logo_name',
        'logo_url' => null, // tema menampilkan inisial nama situs
        'favicon_url' => null,
        'email' => 'diskominfo@example.go.id',
        'phone' => '(0435) 821123',
        'address' => 'Jl. Sapta Marga No. 12, Kompleks Perkantoran Pemerintah Daerah',
    ];
    // Akun media sosial demo untuk platform yang dibaca dari profil ({platform}_url).
    foreach ($demo->collection('social_media') as $social) {
        if (in_array($social->platform, ['instagram', 'facebook', 'twitter', 'youtube', 'website'], true)) {
            $profile->{"{$social->platform}_url"} = $social->url;
            $app->organizationProfile->{"{$social->platform}_url"} = $social->url;
        }
    }

    $seo = [
        'title' => "{$title} - Diskominfo Portal Resmi",
        'description' => 'Portal resmi layanan informasi dan tata kelola pemerintahan daerah terpadu.',
        'keywords' => 'pemerintah, portal daerah, informasi publik, layanan',
        'url' => url()->current(),
    ];

    $layoutSections = app(WorkbenchCustomizer::class)->layoutSections();

    return array_merge(compact('app', 'profile', 'seo', 'layoutSections'), [
        // Sama dengan PublicLayoutService::getSharedPublicViewData(): layout membaca warna tema dari sini.
        'application' => $app,
        'themeConfig' => app(WorkbenchCustomizer::class)->themeConfiguration(),
        'menus' => app(WorkbenchCustomizer::class)->menus(),
        // Sama dengan PublicLayoutService::getSharedPublicViewData() di webuild-app.
        'websiteName' => $profile->website_name ?? $app->name ?? 'Portal Demo',
        'allContents' => $demo->collection('contents'),
        'newsContents' => $demo->collection('news'),
        'articleContents' => $demo->collection('articles'),
        'mediaItems' => $demo->collection('gallery'),
        'agendas' => $demo->paginate('agenda', 8),
        'todayAgendas' => $demo->collection('agenda'),
        'documents' => $demo->collection('documents'),
        'faqs' => $demo->collection('faqs'),
        'sliders' => $demo->collection('sliders'),
        'links' => $demo->collection('links'),
        'socialMedia' => $demo->collection('social_media'),
        'organizationStructures' => $demo->organizationStructures(),
    ]);
};

// 1. Home
Route::get('/', function () use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Beranda');
    $contents = $ctx['allContents']->take(6);
    $agendas = $ctx['agendas'];

    return view("themes.{$theme}.pages.home.home", array_merge($ctx, [
        'application' => $ctx['app'],
        'theme' => $theme,
        'sections' => $ctx['layoutSections']->get('main'),
        // Beranda disusun lewat page builder (section → kolom → komponen) dari drawer customizer.
        // Sama seperti webuild-app: Collection berisi objek section ($section->is_enabled, $section->columns).
        'page' => (object) ['is_homepage' => true, 'title' => 'Beranda', 'excerpt' => null, 'settings' => [], 'comments_enabled' => false, 'featured_image_url' => null, 'image_url' => null, 'sections' => collect(app(WorkbenchCustomizer::class)->pageSections())->map(fn (array $section) => (object) $section)],
        'contents' => $contents,
        'agendas' => $agendas,
    ]));
})->name('home');

// 2. News and article routes mirror Webuild's public routes.
$renderContentIndex = function (string $type, string $title) use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $demo = app(ThemeDemoData::class);
    $ctx = $buildContext($title);
    $pageTitle = $type === 'news' ? 'Berita' : 'Artikel';
    $category = (object) ['id' => 1, 'name' => 'Pemerintahan', 'slug' => 'pemerintahan'];
    $feature = (object) ['name' => $pageTitle, 'description' => "Informasi {$pageTitle} terbaru."];
    $customizer = app(WorkbenchCustomizer::class);
    $pageKey = $type === 'news' ? 'berita' : 'artikel';

    return view("themes.{$theme}.features.content.index", array_merge($ctx, [
        'application' => $ctx['app'],
        'feature' => $feature,
        'contents' => $demo->paginate($type, 9),
        'featuredContents' => $demo->collection($type),
        'availableCategories' => collect([$category]),
        'pageSettings' => $customizer->pageSettings($pageKey),
        'layoutVariant' => $customizer->layoutVariant($pageKey),
    ]));
};

$renderContentDetail = function (string $slug, string $type) use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext($type === 'news' ? 'Detail Berita' : 'Detail Artikel');
    $content = app(ThemeDemoData::class)->collection($type)->firstWhere('slug', $slug);

    abort_unless($content, 404);

    $content->type = (object) ['value' => $type === 'news' ? 'news' : 'article'];
    $content->categories = collect($content->category ? [$content->category] : []);
    $content->tags = collect();
    $content->created_at = $content->created_at ?? $content->published_at ?? now();
    $content->author_display_name = $content->author_display_name ?? 'Tim Redaksi';
    $content->featured_image_url = $content->featured_image_url ?? $content->thumbnail ?? null;
    $content->show_thumbnail_on_detail = true;

    $relatedContents = app(ThemeDemoData::class)->collection($type)
        ->reject(fn ($item) => $item->slug === $slug)
        ->take(5)
        ->values();

    return view("themes.{$theme}.pages.content.content", array_merge($ctx, [
        'application' => $ctx['app'],
        'content' => $content,
        'page' => $content,
        'pageSettings' => [
            'berita' => app(WorkbenchCustomizer::class)->pageSettings('berita'),
            'artikel' => app(WorkbenchCustomizer::class)->pageSettings('artikel'),
        ],
        'comments' => collect(),
        'relatedContents' => $relatedContents,
        'relatedArticles' => $relatedContents,
        'popularContents' => $relatedContents->sortByDesc(fn ($item) => (int) ($item->views_count ?? 0))->values(),
    ]));
};

Route::get('/news', fn () => $renderContentIndex('news', 'Berita'))->name('public.news.index');
Route::get('/news/{slug}', fn (string $slug) => $renderContentDetail($slug, 'news'))->name('public.news.show');
Route::get('/article', fn () => $renderContentIndex('articles', 'Artikel'))->name('public.articles.index');
Route::get('/article/{slug}', fn (string $slug) => $renderContentDetail($slug, 'articles'))->name('public.articles.show');

// Keep old demo links working while generated links use Webuild's canonical paths.
Route::redirect('/berita', '/news');
Route::get('/berita/{slug}', fn (string $slug) => redirect()->route('public.news.show', ['slug' => $slug], 301));

// 3. Agenda
Route::get('/f/agenda', function () use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Agenda Kegiatan');
    $agendas = app(ThemeDemoData::class)->paginate('agenda', 8);
    $selectedMonth = (int) request('month', now()->month);
    $selectedYear = (int) request('year', now()->year);

    return view("themes.{$theme}.pages.agenda.agenda", array_merge($ctx, [
        'application' => $ctx['app'],
        'agendas' => $agendas,
        'selectedMonth' => $selectedMonth,
        'selectedYear' => $selectedYear,
        'search' => request('search', ''),
        'officialName' => request('official_name', ''),
        'websiteName' => $ctx['profile']->website_name,
        'pageSettings' => app(WorkbenchCustomizer::class)->pageSettings('agenda'),
    ]));
})->name('public.agenda.index');

Route::get('/agenda/{slug}', function ($slug) use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Detail Agenda');
    $agenda = app(ThemeDemoData::class)->collection('agenda')->firstWhere('slug', $slug) ?? (object)[
        'id' => 0,
        'slug' => $slug,
        'title' => 'Rakor Pembangunan Daerah 2026',
        'description' => 'Koordinasi program prioritas tahun anggaran berjalan.',
        'location' => 'Kantor Bappeda',
        'start_at' => now()->addDays(3),
        'end_at' => now()->addDays(3)->addHour(),
        'organizer' => 'Bappeda',
        'official_name' => 'Sekretariat Daerah',
    ];

    return view("themes.{$theme}.pages.agenda.agenda-show", array_merge($ctx, [
        'application' => $ctx['app'],
        'agenda' => $agenda,
    ]));
})->name('public.agenda.show');
Route::redirect('/agenda', '/f/agenda');

// 4. Documents mirror Webuild's /document and /f/document routes.
$renderDocumentIndex = function () use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Dokumen & Regulasi');
    $feature = (object) ['name' => 'Dokumen & Regulasi', 'description' => 'Dokumen publik dan regulasi.'];
    $categories = collect([
        (object) ['id' => 1, 'name' => 'Peraturan Daerah', 'slug' => 'perda', 'parent_id' => null],
        (object) ['id' => 2, 'name' => 'Perda 2026', 'slug' => 'perda-2026', 'parent_id' => 1],
    ]);
    // Sama dengan PublicController webuild-app: filter + folder aktif + subfolder.
    $filters = [
        'search' => (string) request('search', request('q', '')),
        'category_id' => (string) request('category_id', request('kategori', request('category', ''))),
        'year' => (string) request('year', request('tahun', '')),
    ];
    $currentDocumentCategory = $filters['category_id'] === '' ? null
        : $categories->first(fn ($c) => (string) $c->id === $filters['category_id'] || $c->slug === $filters['category_id']);

    return view("themes.{$theme}.features.document.index", array_merge($ctx, [
        'application' => $ctx['app'],
        'feature' => $feature,
        'contents' => app(ThemeDemoData::class)->paginate('documents', 10),
        'featuredContents' => collect(),
        'categories' => $categories,
        'availableCategories' => $categories,
        'filters' => $filters,
        'currentDocumentCategory' => $currentDocumentCategory,
        'childDocumentCategories' => $categories->where('parent_id', $currentDocumentCategory?->id)->values(),
        'availableYears' => collect([now()->year]),
        'pageSettings' => app(WorkbenchCustomizer::class)->pageSettings('dokumen'),
        'layoutVariant' => app(WorkbenchCustomizer::class)->layoutVariant('dokumen'),
    ]));
};

Route::get('/document', $renderDocumentIndex)->name('public.documents.index');
Route::get('/f/document', $renderDocumentIndex);

Route::get('/document/category/{categorySlug}', fn () => redirect()->route('public.documents.index'))->name('public.documents.category');
Route::get('/document/{document}/download', fn ($document) => response('Demo PDF Document'))->name('public.documents.download');
Route::get('/document/{document}/preview', fn ($document) => response('Preview PDF Document'))->name('public.documents.preview');
Route::get('/document/{slug}', function (string $slug) use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Detail Dokumen');
    $document = app(ThemeDemoData::class)->collection('documents')->firstWhere('slug', $slug);

    abort_unless($document, 404);

    return view("themes.{$theme}.pages.document.document", array_merge($ctx, [
        'application' => $ctx['app'],
        'document' => $document,
        'previewFileSize' => 1800000,
        'relatedDocuments' => collect(),
        'pageSettings' => ['dokumen' => app(WorkbenchCustomizer::class)->pageSettings('dokumen')],
    ]));
})->name('public.documents.show');

Route::redirect('/dokumen', '/document');
Route::get('/dokumen/{slug}', fn (string $slug) => redirect()->route('public.documents.show', ['slug' => $slug], 301));

// 5. Booking
Route::get('/f/booking', function () use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Reservasi & Kunjungan');

    return view("themes.{$theme}.pages.booking.booking", array_merge($ctx, [
        'application' => $ctx['app'],
        'services' => collect(),
        // Sama dengan PublicBookingController::index() di webuild-app.
        'schedules' => collect([
            new \Webuild\ThemeKit\Workbench\ScheduleStub(1, 'Sesi Pagi', '08:00', '11:30'),
            new \Webuild\ThemeKit\Workbench\ScheduleStub(2, 'Sesi Siang', '13:00', '15:30', [1, 2, 3, 4], 'Senin – Kamis', 40),
        ]),
        'pageSettings' => app(WorkbenchCustomizer::class)->pageSettings('booking'),
    ]));
})->name('public.booking.index');
Route::post('/f/booking', fn () => back())->name('public.booking.store');
Route::get('/f/booking/slots', fn () => response()->json([]))->name('public.booking.slots');
// Sama dengan PublicBookingController::track() di webuild-app (tanpa data reservasi).
Route::get('/f/booking/track', fn () => view('themes.' . config('theme.active', 'modern') . '.pages.booking.booking_track', array_merge($buildContext('Lacak Booking'), [
    'booking' => null,
    'code' => (string) request('code', ''),
    'searched' => request()->filled('code'),
    'schedules' => collect(),
    'filterDate' => null,
    'filterScheduleId' => null,
    'bookingList' => new LengthAwarePaginator([], 0, 20),
    'bookingListLoaded' => false,
    'bookingStats' => null,
    'pageSettings' => app(WorkbenchCustomizer::class)->pageSettings('pelacakan_booking'),
    'bookingPageSettings' => app(WorkbenchCustomizer::class)->pageSettings('booking'),
])))->name('public.booking.track');
Route::get('/f/booking/{code}/print', fn ($code) => 'Print Booking ' . $code)->name('public.booking.print');
Route::post('/admin/bookings/validate', fn () => response()->json(['valid' => true]))->name('admin.bookings.validate');

// 6. Dynamic Content
Route::get('/f/catalog/{categorySlug?}', function () use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Katalog Informasi');
    $contents = DynamicContent::paginate(10);
    $categories = collect();

    return view("themes.{$theme}.pages.dynamic_content.dynamic-content", array_merge($ctx, [
        'application' => $ctx['app'],
        'contents' => $contents,
        'categories' => $categories,
        'selectedCategory' => null,
        'pageSettings' => app(WorkbenchCustomizer::class)->pageSettings('katalog'),
        'layoutVariant' => app(WorkbenchCustomizer::class)->layoutVariant('katalog'),
    ]));
})->name('public.dynamic-content.index');
Route::get('/f/catalog/{categorySlug}/{slug}', fn () => redirect()->route('public.dynamic-content.index'))->name('public.dynamic-content.show');
Route::get('/f/catalog-category/{categorySlug}', fn () => redirect()->route('public.dynamic-content.index'))->name('public.dynamic-category.show');

// 7. Organization Structure, FAQ, Guestbook, Search, Categories, Links
Route::get('/f/organisasi', function () use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Struktur Organisasi');

    return view("themes.{$theme}.pages.organization_structure.organization-structure", array_merge($ctx, [
        'application' => $ctx['app'],
        'structures' => $ctx['organizationStructures'],
        'tree' => $ctx['organizationStructures']->whereNull('parent_id')->values(),
        'search' => request('search', ''),
        'pageSettings' => app(WorkbenchCustomizer::class)->pageSettings('struktur_organisasi'),
        'layoutVariant' => app(WorkbenchCustomizer::class)->layoutVariant('struktur_organisasi'),
    ]));
})->name('public.organization-structures.index');
Route::get('/f/organization-structure/{slug}', function (string $slug) use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Detail Jabatan');
    $structure = $ctx['organizationStructures']->firstWhere('slug', $slug);
    abort_unless($structure, 404);

    return view("themes.{$theme}.pages.organization_structure.organization-structure-detail", array_merge($ctx, [
        'application' => $ctx['app'],
        'structure' => $structure,
        'pageSettings' => app(WorkbenchCustomizer::class)->pageSettings('struktur_organisasi'),
    ]));
})->name('public.organization-structures.show');
Route::get('/f/organization-structure', fn () => redirect()->route('public.organization-structures.index'))->name('public.organization-structure.index');

Route::get('/f/faqs', function () use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('FAQ');
    $allFaqs = app(ThemeDemoData::class)->collection('faqs');
    $search = trim((string) request('search', ''));
    $category = (string) request('category', '');
    $categories = $allFaqs->pluck('category')->filter()->unique()->values();
    $faqs = $allFaqs
        ->when($category !== '', fn ($items) => $items->where('category', $category))
        ->when($search !== '', fn ($items) => $items->filter(fn ($faq) =>
            str_contains(mb_strtolower($faq->question), mb_strtolower($search))
            || str_contains(mb_strtolower($faq->answer), mb_strtolower($search))
        ))
        ->values();

    return view("themes.{$theme}.pages.faq.faq", array_merge($ctx, [
        'bladePath' => "themes.{$theme}",
        'application' => $ctx['app'],
        'faqs' => $faqs,
        'categories' => $categories,
        'category' => $category,
        'search' => $search,
        'pageSettings' => app(WorkbenchCustomizer::class)->pageSettings('faq'),
    ]));
})->name('public.faqs.index');

Route::get('/f/guestbook', function () use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Buku Tamu');

    return view("themes.{$theme}.pages.guestbook.guestbook", array_merge($ctx, [
        'application' => $ctx['app'],
        'guestbooks' => new LengthAwarePaginator([], 0, 10),
        // Sama dengan PublicGuestbookController::index() di webuild-app.
        'categories' => collect([
            (object) ['id' => 1, 'name' => 'Kunjungan Dinas'],
            (object) ['id' => 2, 'name' => 'Konsultasi Layanan'],
        ]),
        'pageSettings' => app(WorkbenchCustomizer::class)->pageSettings('buku_tamu'),
    ]));
})->name('public.guestbook.index');
Route::post('/f/guestbook', fn () => back())->name('public.guestbook.store');

Route::get('/search', function () use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Hasil Pencarian');

    return view("themes.{$theme}.pages.search.search", array_merge($ctx, [
        'application' => $ctx['app'],
        // Sama dengan SearchService::search() di webuild-app.
        'query' => request('q', ''),
        'keyword' => (string) request('q', request('search', '')),
        'type' => (string) request('type', 'all'),
        'counts' => ['all' => 0],
        'results' => new LengthAwarePaginator([], 0, 12),
        'categories' => collect(),
        'pageSettings' => app(WorkbenchCustomizer::class)->pageSettings('pencarian'),
    ]));
})->name('public.search');

Route::get('/categories', fn () => redirect()->route('public.articles.index'))->name('public.categories.index');
Route::get('/category/{slug}', fn () => redirect()->route('public.articles.index'))->name('public.categories.show');
$renderPranala = function () use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Pranala');
    $search = trim((string) request('search', ''));
    $links = $ctx['links']->when($search !== '', fn ($items) => $items->filter(fn ($link) => str_contains(mb_strtolower($link->title), mb_strtolower($search))))->values();
    $pageSettings = app(WorkbenchCustomizer::class)->pageSettings('pranala');

    return view("themes.{$theme}.pages.pranala.pranala", array_merge($ctx, [
        'application' => $ctx['app'],
        'links' => new LengthAwarePaginator($links->forPage(LengthAwarePaginator::resolveCurrentPage(), 12)->values(), $links->count(), 12, null, ['path' => url()->current(), 'query' => request()->query()]),
        'search' => $search,
        'pageSettings' => $pageSettings,
        'subtitle' => str_replace('{website_name}', $ctx['profile']->website_name, (string) ($pageSettings['subtitle'] ?? '')),
    ]));
};
Route::get('/f/links', $renderPranala)->name('public.links.index');
Route::get('/f/pranala', $renderPranala)->name('public.pranala.index');
Route::get('/f/aplikasi', fn () => redirect('/'))->name('public.applications.index');
// Media and content feature URLs used by Webuild's public templates.
$renderMediaIndex = function (?string $categorySlug = null) use ($buildContext) {
    $theme = config('theme.active', 'modern');
    $ctx = $buildContext('Galeri Media');
    $demo = app(ThemeDemoData::class);
    $mediaItems = $demo->collection('gallery');
    $categories = $mediaItems
        ->pluck('category')
        ->filter()
        ->unique('id')
        ->map(function ($category) {
            $category->slug = data_get($category, 'slug') ?: \Illuminate\Support\Str::slug($category->name);

            return $category;
        })
        ->values();
    $currentCategory = $categorySlug
        ? $categories->first(fn ($category) => $category->slug === $categorySlug || (string) $category->id === $categorySlug)
        : null;
    abort_if($categorySlug && ! $currentCategory, 404);

    if ($currentCategory) {
        $mediaItems = $mediaItems->filter(fn ($item) => (string) ($item->category_id ?? data_get($item, 'category.id')) === (string) $currentCategory->id);
    }

    $perPage = 12;
    $page = LengthAwarePaginator::resolveCurrentPage();
    $paginatedMedia = new LengthAwarePaginator(
        $mediaItems->forPage($page, $perPage)->values(),
        $mediaItems->count(),
        $perPage,
        $page,
        ['path' => url()->current(), 'query' => request()->query()],
    );
    $feature = (object) ['name' => 'Galeri Media', 'description' => 'Dokumentasi kegiatan dan informasi visual.'];

    return view("themes.{$theme}.features.media.index", array_merge($ctx, [
        'application' => $ctx['app'],
        'feature' => $feature,
        'contents' => $paginatedMedia,
        'availableCategories' => $categories,
        'currentCategory' => $currentCategory,
        'filters' => request()->only(['category_id', 'search']),
        'pageSettings' => app(WorkbenchCustomizer::class)->pageSettings('galeri'),
    ]));
};

Route::get('/media', $renderMediaIndex)->name('public.media.index');
Route::get('/f/media', $renderMediaIndex);
Route::get('/media/category/{categorySlug}', $renderMediaIndex)->name('public.media.category');
Route::get('/f/media/category/{categorySlug}', $renderMediaIndex);
Route::get('/f/{feature_key}', function (string $feature_key) use ($renderContentIndex) {
    if ($feature_key === 'content.news') {
        return $renderContentIndex('news', 'Berita');
    }

    if ($feature_key === 'content.article') {
        return $renderContentIndex('articles', 'Artikel');
    }

    return redirect('/');
})->name('public.features.show');
Route::get('/f/map', fn () => redirect('/'))->name('public.geo-maps.index');
Route::get('/f/map/{slug}', fn () => redirect('/'))->name('public.geo-maps.show');
Route::get('/p/{slug}', fn () => redirect('/'))->name('public.pages.show');
Route::post('/f/comments/{type}/{id}', fn () => back())->name('public.comments.store');

// Drawer customizer workbench: simpan dan reset pengaturan tampilan tema aktif.
Route::post('/__workbench/customizer', function (Request $request) {
    $state = app(WorkbenchCustomizer::class)->save((array) $request->input('state', []));

    return response()->json(['state' => $state]);
})->name('workbench.customizer.save');
Route::delete('/__workbench/customizer', function () {
    app(WorkbenchCustomizer::class)->reset();

    return response()->json(['reset' => true]);
})->name('workbench.customizer.reset');
