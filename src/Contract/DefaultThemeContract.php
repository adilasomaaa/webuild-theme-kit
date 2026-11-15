<?php

namespace Webuild\ThemeKit\Contract;

class DefaultThemeContract implements ThemeContract
{
    public static function getRequiredPages(): array
    {
        return [
            'home' => 'home',
            'page' => 'page',
            'agenda' => 'agenda',
            'agenda-show' => 'agenda',
            'booking' => 'booking',
            'booking_track' => 'booking',
            'booking_print' => 'booking',
            'catalog' => 'catalog',
            'catalog-show' => 'catalog',
            'catalog-category' => 'catalog',
            'content' => 'content',
            'categories' => 'content',
            'dynamic-content' => 'dynamic_content',
            'dynamic-content-show' => 'dynamic_content',
            'dynamic-category' => 'dynamic_content',
            'organization-structure' => 'organization_structure',
            'organization-structure-detail' => 'organization_structure',
            'document' => 'document',
            'faq' => 'faq',
            'guestbook' => 'guestbook',
            'pranala' => 'pranala',
            'links' => 'pranala',
            'applications' => 'applications',
            'search' => 'search',
            'ar-preview' => 'ar_preview',
            'geo-map' => 'geo_map',
            'geo-map-list' => 'geo_map',
        ];
    }

    public static function getRequiredSections(): array
    {
        return [
            'header' => ['header_logo', 'header_menu', 'header_search', 'header_button', 'empty'],
            'main' => [
                'hero', 'slider', 'rich_text', 'contents', 'single_highlight', 'maklumat_pelayanan',
                'profile_video', 'image', 'html_embed', 'card_info', 'card_grid', 'audio', 'documents',
                'media_gallery', 'contact', 'maps_embed', 'geo_map', 'booking_methods', 'booking_tracking',
                'guestbook_widget', 'agenda', 'pranala', 'faq', 'organization_structure', 'social_media',
                'registered_applications', 'content_reference', 'document_reference',
            ],
            'footer' => ['footer', 'quick_links'],
        ];
    }

    /**
     * Variants every theme must support for a component. Themes may add options, never remove these.
     *
     * @return array<string, array{configuration_key: string, default: string, options: array<string, array{label: string}>}>
     */
    public static function getBaseVariants(): array
    {
        $variant = fn (string $key, array $options): array => [
            'configuration_key' => $key,
            'default' => array_key_first($options),
            'options' => array_map(fn (string $label): array => ['label' => $label], $options),
        ];

        return [
            'header_logo' => $variant('mode', ['logo_name' => 'Logo dan nama', 'logo_only' => 'Logo saja', 'name_only' => 'Nama saja']),
            'header_button' => $variant('button_variant', ['primary' => 'Utama', 'secondary' => 'Sekunder', 'outline' => 'Outline']),
            'contents' => $variant('layout_variant', ['grid' => 'Grid', 'list' => 'Daftar', 'featured_list' => 'Sorotan']),
            'media_gallery' => $variant('layout_variant', ['asymmetric_grid' => 'Grid editorial', 'marquee' => 'Marquee']),
            'agenda' => $variant('timeframe', ['today' => 'Hari ini', 'this_week' => 'Minggu ini', 'this_month' => 'Bulan ini']),
            'documents' => $variant('layout_variant', ['grid' => 'Grid', 'list' => 'Daftar']),
            'faq' => $variant('layout_orientation', ['vertical' => 'Vertikal', 'horizontal' => 'Horizontal']),
            'slider' => $variant('aspect_ratio', ['21:9' => 'Panorama', '16:10' => 'Layar lebar', '4:3' => 'Klasik', '16:9' => 'Video']),
        ];
    }

    /**
     * Old variant values still found in saved configuration, mapped to the base option views render them as.
     *
     * @return array<string, array<string, string>>
     */
    public static function getLegacyVariantValues(): array
    {
        return [
            'media_gallery' => ['editorial_grid' => 'asymmetric_grid'],
            'contents' => ['sorotan' => 'featured_list'],
        ];
    }

    /**
     * Old section keys still found in saved data, mapped to their current name.
     *
     * @return array<string, string>
     */
    public static function getLegacyAliases(): array
    {
        return ['gallery_media' => 'media_gallery', 'links' => 'pranala', 'contact_info' => 'contact'];
    }

    public static function getRequiredRoutes(): array
    {
        return [
            'home',
            'public.agenda.index',
            'public.agenda.show',
            'public.applications.index',
            'public.articles.index',
            'public.articles.show',
            'public.booking.index',
            'public.booking.print',
            'public.booking.slots',
            'public.booking.store',
            'public.booking.track',
            'public.categories.index',
            'public.categories.show',
            'public.comments.store',
            'public.documents.category',
            'public.documents.download',
            'public.documents.index',
            'public.documents.preview',
            'public.documents.show',
            'public.dynamic-category.show',
            'public.dynamic-content.index',
            'public.dynamic-content.show',
            'public.faqs.index',
            'public.features.show',
            'public.geo-maps.index',
            'public.geo-maps.show',
            'public.guestbook.index',
            'public.guestbook.store',
            'public.links.index',
            'public.media.category',
            'public.media.index',
            'public.news.index',
            'public.news.show',
            'public.organization-structure.index',
            'public.organization-structures.index',
            'public.organization-structures.show',
            'public.pages.show',
            'public.pranala.index',
            'public.search',
            'admin.bookings.validate',
        ];
    }

    public static function getStandardViewVariables(): array
    {
        return [
            'application',
            'profile',
            'seo',
            'layoutSections',
            'themeConfig',
            'siteDisplayName',
            'primaryColor',
            'secondaryColor',
            'accentColor',
        ];
    }
}

