<?php

// Field pengaturan komponen untuk drawer customizer, mengikuti form section di webuild-app
// (HeroSectionForm.vue, SliderSectionForm.vue). Field di theme.json → component_settings menimpa field di sini.
// 'group' mengelompokkan field, 'show_if' menampilkan field hanya bila field lain bernilai tertentu.

$align = ['left' => 'Kiri', 'center' => 'Tengah', 'right' => 'Kanan'];
$textSize = ['small' => 'Kecil', 'normal' => 'Normal', 'large' => 'Besar'];
$cardIcons = [
    'building' => 'Gedung / Profil', 'calendar' => 'Kalender / Agenda', 'ticket' => 'Tiket / Reservasi', 'location' => 'Lokasi / Alamat',
    'phone' => 'Telepon / Kontak', 'weather' => 'Cuaca / Hidrometeorologi', 'chart' => 'Kinerja / Grafik', 'money' => 'Anggaran / Kas',
    'shopping-cart' => 'Pengadaan', 'service' => 'Layanan / Bantuan', 'info' => 'Informasi / PPID', 'megaphone' => 'Publikasi / Berita',
    'alert' => 'Pengaduan / Lapor', 'contact' => 'Kontak / Surel', 'document' => 'Dokumen / Regulasi', 'users' => 'Organisasi / SDM',
    'shield' => 'Keamanan / Tata Kelola', 'link' => 'Tautan / Pranala',
];
$card = fn (string $icon, string $label, string $description) => ['icon' => $icon, 'label' => $label, 'description' => $description, 'link_url' => '', 'open_in_new_tab' => false];

return [
    'hero' => [
        'highlight_text' => ['type' => 'string', 'group' => 'Konten', 'label' => 'Teks Utama Highlight (Baris 1)', 'placeholder' => 'Contoh: Transformasi Digital', 'default' => 'Transformasi Digital'],
        'title' => ['type' => 'string', 'group' => 'Konten', 'label' => 'Judul Utama (Baris 2)', 'placeholder' => 'Layanan Terpadu Menuju Masa Depan', 'default' => 'Layanan Publik Terpadu & Terintegrasi'],
        'subtitle' => ['type' => 'text', 'group' => 'Konten', 'label' => 'Deskripsi / Subjudul', 'placeholder' => 'Deskripsi singkat mengenai layanan dan portal resmi...', 'default' => 'Akses informasi publik, agenda kegiatan, pengumuman resmi, dan layanan permohonan daring secara cepat, transparan, dan terpercaya.'],
        'text_align' => ['type' => 'select', 'group' => 'Konten', 'label' => 'Posisi Teks', 'options' => $align, 'default' => 'left'],
        'text_size' => ['type' => 'select', 'group' => 'Konten', 'label' => 'Ukuran Judul', 'options' => $textSize, 'default' => 'normal'],
        'section_height' => ['type' => 'select', 'group' => 'Konten', 'label' => 'Tinggi Banner', 'options' => ['auto' => 'Otomatis sesuai konten', 'tall' => 'Tinggi proporsional', 'fullscreen' => 'Satu layar penuh'], 'default' => 'auto'],

        'background_type' => ['type' => 'select', 'group' => 'Latar belakang', 'label' => 'Jenis latar', 'options' => ['solid' => 'Warna solid', 'gradient' => 'Gradien', 'image' => 'Gambar'], 'default' => 'gradient'],
        'background_color' => ['type' => 'color', 'group' => 'Latar belakang', 'label' => 'Warna latar', 'default' => '#ffffff', 'show_if' => ['background_type' => 'solid']],
        'background_gradient' => ['type' => 'select', 'group' => 'Latar belakang', 'label' => 'Pilih gradien', 'options' => ['theme' => 'Warna tema', 'ocean' => 'Samudra', 'sunset' => 'Senja', 'forest' => 'Hutan', 'violet' => 'Violet', 'slate' => 'Arang'], 'default' => 'theme', 'help' => 'Warna tema memakai gradien warna utama → sekunder.', 'show_if' => ['background_type' => 'gradient']],
        'bg_image_url' => ['type' => 'image', 'group' => 'Latar belakang', 'label' => 'URL gambar latar', 'placeholder' => 'https://...', 'default' => 'https://picsum.photos/seed/hero/1920/1080', 'show_if' => ['background_type' => 'image']],
        'overlay_opacity' => ['type' => 'range', 'group' => 'Latar belakang', 'label' => 'Kegelapan overlay', 'min' => 0, 'max' => 100, 'step' => 5, 'unit' => '%', 'default' => 40, 'show_if' => ['background_type' => 'image']],
        'hover_effect' => ['type' => 'select', 'group' => 'Latar belakang', 'label' => 'Efek hover', 'options' => ['none' => 'Tidak ada', 'blur' => 'Blur gambar'], 'default' => 'none', 'show_if' => ['background_type' => 'image']],

        'show_search_bar' => ['type' => 'boolean', 'group' => 'Kolom pencarian', 'label' => 'Tampilkan kolom pencarian di hero', 'default' => true],
        'search_placeholder' => ['type' => 'string', 'group' => 'Kolom pencarian', 'label' => 'Teks placeholder pencarian', 'default' => 'Cari berita, artikel, katalog, dokumen...', 'show_if' => ['show_search_bar' => true]],

        'show_buttons' => ['type' => 'boolean', 'group' => 'Tombol aksi', 'label' => 'Tampilkan tombol aksi (call to action)', 'default' => true],
        'primary_button_text' => ['type' => 'string', 'group' => 'Tombol aksi', 'label' => 'Teks tombol utama', 'placeholder' => 'Jelajahi Konten', 'default' => 'Jelajahi Konten', 'show_if' => ['show_buttons' => true]],
        'primary_button_url' => ['type' => 'url', 'group' => 'Tombol aksi', 'label' => 'Tautan tombol utama (URL / anchor)', 'placeholder' => '#konten atau https://...', 'default' => '/news', 'show_if' => ['show_buttons' => true]],
        'secondary_button_text' => ['type' => 'string', 'group' => 'Tombol aksi', 'label' => 'Teks tombol sekunder', 'placeholder' => 'Tentang Kami', 'default' => 'Tentang Kami', 'show_if' => ['show_buttons' => true]],
        'secondary_button_url' => ['type' => 'url', 'group' => 'Tombol aksi', 'label' => 'Tautan tombol sekunder (URL / anchor)', 'placeholder' => '#tentang atau https://...', 'default' => '/f/guestbook', 'show_if' => ['show_buttons' => true]],
    ],

    'slider' => [
        'title' => ['type' => 'string', 'group' => 'Teks & tipografi', 'label' => 'Judul', 'placeholder' => 'Judul banner', 'default' => 'Sorotan & Pengumuman'],
        'subtitle' => ['type' => 'text', 'group' => 'Teks & tipografi', 'label' => 'Subjudul', 'placeholder' => 'Deskripsi singkat banner', 'default' => 'Informasi penting dan banner kegiatan terkini'],
        'text_align' => ['type' => 'select', 'group' => 'Teks & tipografi', 'label' => 'Posisi teks (horizontal)', 'options' => $align, 'default' => 'left'],
        'text_valign' => ['type' => 'select', 'group' => 'Teks & tipografi', 'label' => 'Posisi teks (vertikal)', 'options' => ['bottom' => 'Bawah', 'center' => 'Tengah', 'top' => 'Atas'], 'default' => 'bottom'],
        'text_size' => ['type' => 'select', 'group' => 'Teks & tipografi', 'label' => 'Ukuran judul teks', 'options' => $textSize, 'default' => 'normal'],

        'full_section' => ['type' => 'boolean', 'group' => 'Tampilan slider', 'label' => 'Lebar penuh layar', 'help' => 'Slider membentang sampai tepi layar.', 'default' => true],
        'autoplay' => ['type' => 'boolean', 'group' => 'Tampilan slider', 'label' => 'Putar otomatis', 'default' => true],
        'interval' => ['type' => 'select', 'group' => 'Tampilan slider', 'label' => 'Durasi per slide', 'options' => ['3000' => '3 detik', '5000' => '5 detik', '7000' => '7 detik', '10000' => '10 detik'], 'default' => '5000', 'show_if' => ['autoplay' => true]],
        'overlay_opacity' => ['type' => 'range', 'group' => 'Tampilan slider', 'label' => 'Gelap overlay', 'min' => 0, 'max' => 90, 'step' => 5, 'unit' => '%', 'default' => 60],
    ],

    // ProfileVideoSectionForm.vue
    'profile_video' => [
        'title' => ['type' => 'string', 'label' => 'Judul Section', 'placeholder' => 'Mengenal Lebih Dekat Layanan Kami', 'default' => 'Mengenal Lebih Dekat Layanan Kami'],
        'subtitle' => ['type' => 'text', 'label' => 'Subjudul / Deskripsi', 'placeholder' => 'Tonton video profil dan dokumentasi visual program kerja kami.', 'default' => 'Tonton video profil dan dokumentasi visual program kerja kami.'],
        'video_embed_url' => ['type' => 'url', 'label' => 'URL / Link Video (YouTube, YouTube Shorts, Vimeo, atau Embed)', 'placeholder' => 'https://www.youtube.com/watch?v=... atau https://youtube.com/shorts/...', 'help' => 'Tautan YouTube/Vimeo biasa otomatis diubah menjadi pemutar embed.', 'default' => 'https://www.youtube.com/watch?v=ScMzIvxBSi4'],
        'layout_orientation' => ['type' => 'select', 'label' => 'Orientasi Tata Letak Section', 'options' => ['vertical' => 'Vertikal', 'horizontal' => 'Horizontal'], 'default' => 'vertical'],
        'aspect_ratio' => ['type' => 'select', 'label' => 'Rasio Aspek Pemutar Video', 'options' => ['16:9' => '16:9 (Layar Lebar Standar Landscape)', '4:3' => '4:3 (Klasik / TV)', '21:9' => '21:9 (Sinematik)', '1:1' => '1:1 (Persegi)'], 'default' => '16:9'],
    ],

    // CardGridSectionForm.vue
    'card_grid' => [
        'title' => ['type' => 'string', 'label' => 'Judul Section (Opsional)', 'placeholder' => 'Contoh: Kategori Informasi (bisa dikosongkan)', 'default' => 'Kategori Informasi'],
        'subtitle' => ['type' => 'text', 'label' => 'Subjudul / Deskripsi Pengantar (Opsional)', 'placeholder' => 'Tuliskan petunjuk atau keterangan singkat di bawah judul...', 'default' => 'Akses cepat ke informasi dan layanan publik.'],
        'columns' => ['type' => 'select', 'label' => 'Jumlah Kolom Grid', 'options' => ['2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'], 'default' => '4'],
        'cards' => ['type' => 'repeater', 'label' => 'Daftar Kartu', 'item_label' => 'label', 'add_label' => 'Tambah Kartu', 'fields' => [
            'icon' => ['type' => 'select', 'label' => 'Ikon Kartu', 'options' => $cardIcons, 'default' => 'info'],
            'label' => ['type' => 'string', 'label' => 'Judul Kartu', 'placeholder' => 'Contoh: 1. Profil', 'default' => 'Kartu baru'],
            'description' => ['type' => 'text', 'label' => 'Deskripsi Ringkas', 'placeholder' => 'Contoh: Tentang, Visi Misi, Struktur Organisasi, Profil Pimpinan.', 'default' => ''],
            'link_url' => ['type' => 'url', 'label' => 'Tautan URL (Opsional)', 'placeholder' => 'https://... atau /profil', 'default' => ''],
            'open_in_new_tab' => ['type' => 'boolean', 'label' => 'Buka di tab baru', 'default' => false],
        ], 'default' => [
            $card('building', '1. Profil', 'Tentang, Visi Misi, Struktur Organisasi, Profil Pimpinan.'),
            $card('calendar', '2. Program & Kegiatan', 'Program Prioritas, Agenda, Capaian Program.'),
            $card('chart', '3. Perencanaan & Kinerja', 'Renstra, Renja, IKU, Laporan Kinerja LKjIP.'),
            $card('money', '4. Transparansi Anggaran', 'Dokumen Anggaran, DPA, Realisasi, Laporan Keuangan.'),
            $card('shopping-cart', '5. Pengadaan Barang/Jasa', 'RUP, Paket Pengadaan, Kontrak, Tautan SiRUP/LPSE.'),
            $card('service', '6. Layanan', 'Daftar Layanan, SOP, Layanan Online, Tracking.'),
            $card('info', '7. PPID', 'Struktur, Informasi Berkala/Serta-merta, Permohonan.'),
            $card('megaphone', '8. Publikasi', 'Berita, Pengumuman, Agenda, Galeri Foto/Video.'),
            $card('alert', '9. Pengaduan', 'SP4N-LAPOR!, Whistleblowing System, Tracking.'),
            $card('contact', '10. Kontak', 'Alamat, Telepon, Email, Media Sosial, Lokasi.'),
        ]],
    ],

    // CardInfoSectionForm.vue
    'card_info' => [
        'title' => ['type' => 'string', 'label' => 'Judul Kartu *', 'placeholder' => 'Misal: Portal Pelayanan Terpadu Satu Pintu', 'default' => 'Portal Pelayanan Terpadu Satu Pintu'],
        'subtitle' => ['type' => 'string', 'label' => 'Subjudul (Opsional)', 'placeholder' => 'Misal: Solusi Cepat, Transparan, & Akuntabel', 'default' => 'Solusi Cepat, Transparan, & Akuntabel'],
        'description' => ['type' => 'text', 'label' => 'Deskripsi / Penjelasan (Opsional)', 'placeholder' => 'Tuliskan keterangan detail atau ringkasan informasi...', 'default' => 'Ajukan perizinan dan layanan administrasi secara daring tanpa perlu antre di kantor.'],
        'image_url' => ['type' => 'image', 'label' => 'Gambar / Ilustrasi Kartu (Opsional)', 'placeholder' => 'https://...', 'default' => 'https://picsum.photos/seed/cardinfo/1200/800'],
        'button_text' => ['type' => 'string', 'group' => 'Tombol Aksi (CTA / Tautan)', 'label' => 'Teks Tombol', 'placeholder' => 'Contoh: Kunjungi Sekarang', 'default' => 'Kunjungi Sekarang'],
        'button_url' => ['type' => 'url', 'group' => 'Tombol Aksi (CTA / Tautan)', 'label' => 'URL Tujuan', 'placeholder' => 'https://... atau /halaman', 'default' => '/f/booking'],
        'open_in_new_tab' => ['type' => 'boolean', 'group' => 'Tombol Aksi (CTA / Tautan)', 'label' => 'Buka di tab baru', 'default' => false],
        'hide_border' => ['type' => 'boolean', 'group' => 'Tampilan', 'label' => 'Sembunyikan garis tepi', 'default' => false],
        'hide_shadow' => ['type' => 'boolean', 'group' => 'Tampilan', 'label' => 'Sembunyikan bayangan', 'default' => false],
    ],

    'social_media' => [
        'title' => ['type' => 'string', 'label' => 'Judul', 'default' => 'Ikuti Media Sosial Kami'],
        'subtitle' => ['type' => 'text', 'label' => 'Subjudul', 'default' => 'Dapatkan kabar terbaru melalui kanal resmi instansi.'],
        'include_organization_profile' => ['type' => 'boolean', 'label' => 'Sertakan akun dari profil instansi', 'help' => 'Instagram, Facebook, X, YouTube, dan situs resmi dari profil organisasi.', 'default' => true],
        // Default 'links' diisi workbench dari demo/data.json → social_media.
        'links' => ['type' => 'repeater', 'label' => 'Tautan tambahan', 'item_label' => 'label', 'add_label' => 'Tambah Tautan', 'fields' => [
            'platform' => ['type' => 'select', 'label' => 'Platform', 'options' => ['tiktok' => 'TikTok', 'linkedin' => 'LinkedIn', 'whatsapp' => 'WhatsApp', 'telegram' => 'Telegram', 'email' => 'Email', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'twitter' => 'X (Twitter)', 'youtube' => 'YouTube', 'website' => 'Situs web', 'link' => 'Lainnya'], 'default' => 'link'],
            'label' => ['type' => 'string', 'label' => 'Label', 'default' => 'Tautan'],
            'url' => ['type' => 'url', 'label' => 'URL', 'placeholder' => 'https://...', 'default' => ''],
        ], 'default' => []],
    ],
];
