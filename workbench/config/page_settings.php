<?php

// Salinan config/page_settings.php dari webuild-app untuk workbench Theme Kit.
// 'routes' memetakan nama rute workbench ke grup pengaturan halaman (dipakai drawer customizer).

return [
    'dokumen' => [
        'label' => 'Dokumen',
        'routes' => ['public.documents.index'],
        'feature' => 'feature.dokumen',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Dokumen Publik'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Manajemen unduhan file publik, dokumen PDF, arsip peraturan, dan laporan.'],
            'default_variant' => ['type' => 'select', 'label' => 'Varian Tampilan Default', 'default' => 'foldering', 'options' => ['foldering' => 'Folder', 'grid' => 'Grid', 'list' => 'List']],
            'show_variant_switcher' => ['type' => 'boolean', 'label' => 'Tampilkan Pilihan Varian Lainnya', 'default' => true],
            'show_category_filter' => ['type' => 'boolean', 'label' => 'Tampilkan Filter Kategori', 'default' => true],
            'show_year_filter' => ['type' => 'boolean', 'label' => 'Tampilkan Filter Tahun', 'default' => true],
        ],
    ],
    'berita' => [
        'label' => 'Berita',
        'routes' => ['public.news.index'],
        'feature' => 'content.news',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Berita'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Manajemen siaran berita resmi, warta terkini, dan liputan kegiatan.'],
            'default_variant' => ['type' => 'select', 'label' => 'Varian Tampilan Default', 'default' => 'grid', 'options' => ['grid' => 'Grid', 'list' => 'List']],
            'show_variant_switcher' => ['type' => 'boolean', 'label' => 'Tampilkan Pilihan Varian Lainnya', 'default' => true],
            'show_popular' => ['type' => 'boolean', 'label' => 'Tampilkan Berita Terpopuler', 'default' => true],
            'show_category_filter' => ['type' => 'boolean', 'label' => 'Tampilkan Filter Kategori', 'default' => true],
        ],
    ],
    'artikel' => [
        'label' => 'Artikel',
        'routes' => ['public.articles.index'],
        'feature' => 'content.article',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Artikel'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Manajemen publikasi artikel, opini, esai, dan analisis mendalam.'],
            'default_variant' => ['type' => 'select', 'label' => 'Varian Tampilan Default', 'default' => 'grid', 'options' => ['grid' => 'Grid', 'list' => 'List']],
            'show_variant_switcher' => ['type' => 'boolean', 'label' => 'Tampilkan Pilihan Varian Lainnya', 'default' => true],
            'show_popular' => ['type' => 'boolean', 'label' => 'Tampilkan Artikel Terpopuler', 'default' => true],
            'show_category_filter' => ['type' => 'boolean', 'label' => 'Tampilkan Filter Kategori', 'default' => true],
        ],
    ],
    'galeri' => [
        'label' => 'Galeri',
        'routes' => ['public.media.index', 'public.media.category'],
        'feature' => 'feature.media',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Galeri Media'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Dokumentasi foto dan video kegiatan.'],
            'show_category_filter' => ['type' => 'boolean', 'label' => 'Tampilkan Filter Kategori', 'default' => true],
            'show_type_filter' => ['type' => 'boolean', 'label' => 'Tampilkan Filter Jenis Media', 'default' => true],
        ],
    ],
    'katalog' => [
        'label' => 'Katalog Konten Dinamis',
        'routes' => ['public.dynamic-content.index'],
        'feature' => 'feature.dynamic_content',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Katalog Informasi'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Jelajahi seluruh data, layanan, dan informasi publik yang terstruktur.'],
            'show_description' => ['type' => 'boolean', 'label' => 'Tampilkan Deskripsi', 'default' => true],
            'default_variant' => ['type' => 'select', 'label' => 'Varian Tampilan Default', 'default' => 'grid', 'options' => ['grid' => 'Grid', 'list' => 'List']],
            'show_variant_switcher' => ['type' => 'boolean', 'label' => 'Tampilkan Pilihan Varian Lainnya', 'default' => true],
            'show_images' => ['type' => 'boolean', 'label' => 'Tampilkan Gambar Konten', 'default' => true],
            'show_categories' => ['type' => 'boolean', 'label' => 'Tampilkan Navigasi Kategori', 'default' => true],
        ],
    ],
    'booking' => [
        'label' => 'Reservasi dan Booking',
        'routes' => ['public.booking.index'],
        'feature' => 'feature.jadwal_reservasi',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Formulir Booking Kunjungan'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Pendaftaran reservasi kunjungan daring resmi. Lengkapi 3 langkah mudah berikut ini.'],
            'show_tracking_link' => ['type' => 'boolean', 'label' => 'Tampilkan Tautan Pelacakan Reservasi', 'default' => true],
        ],
    ],
    'pelacakan_booking' => [
        'label' => 'Pelacakan Reservasi',
        'routes' => ['public.booking.track'],
        'feature' => 'feature.jadwal_reservasi',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Lacak Status Reservasi'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Masukkan kode booking unik Anda untuk memeriksa status verifikasi dan persetujuan permohonan.'],
            'show_statistics' => ['type' => 'boolean', 'label' => 'Tampilkan Statistik Reservasi', 'default' => true],
            'show_reservation_list' => ['type' => 'boolean', 'label' => 'Tampilkan Daftar Reservasi', 'default' => true],
        ],
    ],
    'pemetaan' => [
        'label' => 'Pemetaan',
        'routes' => [],
        'feature' => 'feature.pemetaan',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Peta Digital & Geospasial'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Jelajahi peta tematik resmi, data spasial interaktif, batas wilayah, dan persebaran fasilitas di wilayah {website_name}.'],
            'show_type_filter' => ['type' => 'boolean', 'label' => 'Tampilkan Filter Jenis Peta', 'default' => true],
        ],
    ],
    'agenda' => [
        'label' => 'Agenda Kegiatan',
        'routes' => ['public.agenda.index'],
        'feature' => 'feature.agenda',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Agenda Kegiatan Pejabat'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Kalender dan jadwal agenda kegiatan resmi pimpinan instansi {website_name}.'],
            'show_calendar' => ['type' => 'boolean', 'label' => 'Tampilkan Kalender', 'default' => true],
            'show_agenda_list' => ['type' => 'boolean', 'label' => 'Tampilkan Daftar Agenda', 'default' => true],
        ],
    ],
    'faq' => [
        'label' => 'FAQ',
        'routes' => ['public.faqs.index'],
        'feature' => 'feature.faq',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Tanya Jawab (FAQ)'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Kumpulan pertanyaan yang sering diajukan seputar layanan, operasional, dan informasi resmi portal kami.'],
            'show_category_filter' => ['type' => 'boolean', 'label' => 'Tampilkan Filter Kategori', 'default' => true],
            'show_toggle_all' => ['type' => 'boolean', 'label' => 'Tampilkan Tombol Buka/Tutup Semua', 'default' => true],
        ],
    ],
    'pranala' => [
        'label' => 'Pranala',
        'routes' => ['public.pranala.index', 'public.links.index'],
        'feature' => 'feature.pranala',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Pranala & Tautan Terkait'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Kumpulan tautan eksternal, portal aplikasi terintegrasi, dan direktori layanan resmi yang dikelola atau terafiliasi dengan {website_name}.'],
            'show_logos' => ['type' => 'boolean', 'label' => 'Tampilkan Logo Pranala', 'default' => true],
        ],
    ],
    'buku_tamu' => [
        'label' => 'Buku Tamu',
        'routes' => ['public.guestbook.index'],
        'feature' => 'feature.buku_tamu',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Buku Tamu Digital'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Sampaikan kesan, pesan, dan data kunjungan Anda.'],
            'show_info_panel' => ['type' => 'boolean', 'label' => 'Tampilkan Panel Informasi', 'default' => true],
        ],
    ],
    'struktur_organisasi' => [
        'label' => 'Struktur Organisasi',
        'routes' => ['public.organization-structures.index'],
        'feature' => 'feature.organization_structure',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Struktur Organisasi'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Susunan hierarki kedudukan, pembagian tugas unit kerja, dan pimpinan instansi kami.'],
            'default_variant' => ['type' => 'select', 'label' => 'Varian Tampilan Default', 'default' => 'tree', 'options' => ['tree' => 'Bagan Hirarki', 'horizontal' => 'Bagan Horizontal', 'grid' => 'Kartu Grid', 'list' => 'Daftar Unit', 'accordion' => 'Akordion']],
            'show_variant_switcher' => ['type' => 'boolean', 'label' => 'Tampilkan Pilihan Varian Lainnya', 'default' => true],
        ],
    ],
    'direktori_aplikasi' => [
        'label' => 'Direktori Aplikasi',
        'routes' => [],
        'feature' => 'feature.application_directory',
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Direktori Aplikasi & Portal Resmi'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Daftar lengkap seluruh portal web, sistem administrasi, dan layanan digital Organisasi Perangkat Daerah yang terhubung dan aktif di platform Webuild.'],
            'default_variant' => ['type' => 'select', 'label' => 'Varian Tampilan Default', 'default' => 'slider', 'options' => ['slider' => 'Slider', 'grid' => 'Grid']],
            'show_variant_switcher' => ['type' => 'boolean', 'label' => 'Tampilkan Pilihan Varian Lainnya', 'default' => true],
            'show_total' => ['type' => 'boolean', 'label' => 'Tampilkan Jumlah Aplikasi', 'default' => true],
        ],
    ],
    'pencarian' => [
        'label' => 'Pencarian Publik',
        'routes' => ['public.search'],
        'feature' => null,
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Pencarian Portal Publik'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Ketik kata kunci untuk mencari berita, artikel, halaman, dokumen, atau topik kategori.'],
            'results_title' => ['type' => 'string', 'label' => 'Judul Hasil Pencarian', 'default' => 'Hasil Pencarian: "{keyword}"'],
            'results_subtitle' => ['type' => 'text', 'label' => 'Subjudul Hasil Pencarian', 'default' => 'Ditemukan total {count} hasil yang cocok pada portal resmi {website_name}.'],
            'show_type_filter' => ['type' => 'boolean', 'label' => 'Tampilkan Filter Jenis Konten', 'default' => true],
            'show_matched_categories' => ['type' => 'boolean', 'label' => 'Tampilkan Kategori yang Cocok', 'default' => true],
        ],
    ],
    'direktori_kategori' => [
        'label' => 'Direktori Kategori',
        'routes' => [],
        'feature' => null,
        'fields' => [
            'title' => ['type' => 'string', 'label' => 'Judul Halaman', 'default' => 'Kategori Informasi & Publikasi'],
            'subtitle' => ['type' => 'text', 'label' => 'Subjudul Halaman', 'default' => 'Jelajahi berbagai topik berita, artikel, pengumuman, dan publikasi resmi yang tersedia.'],
            'show_content_count' => ['type' => 'boolean', 'label' => 'Tampilkan Jumlah Konten', 'default' => true],
        ],
    ],
];
