# Webuild Theme Kit

[![Packagist](https://img.shields.io/packagist/v/webuild/theme-kit)](https://packagist.org/packages/webuild/theme-kit)
[![License](https://img.shields.io/packagist/l/webuild/theme-kit)](LICENSE)

Paket Laravel untuk membuat dan mengelola tema portal publik **Webuild**. Isinya:

- **Kontrak tema**: daftar halaman, section, route, dan variant dasar yang wajib dipenuhi setiap tema.
- **Theme registry**: mendaftarkan view namespace tema, me-resolve section, dan menyediakan fallback ke tema default.
- **Validator** `theme:check`: memeriksa manifest, kelengkapan halaman/section, variant, aset, dan data demo.
- **Manajemen rilis**: `theme:install`, `theme:update`, `theme:rollback`, dan `theme:history`, beserta riwayat di database.
- **Workbench**: aplikasi Laravel mini dengan data contoh dan customizer untuk mengembangkan tema tanpa memasang webuild-app.

## Kebutuhan

| | Versi |
| --- | --- |
| PHP | 8.2, 8.3, atau 8.4 |
| Laravel | 11, 12, atau 13 |
| Versi API tema | `1.1` |

## Instalasi

### Untuk developer tema

Cara termudah adalah membuat repo dari
[webuild-theme-starter](https://github.com/adilasomaaa/webuild-theme-starter) (**Use this template**).
Theme Kit sudah menjadi dependensinya:

```bash
composer install
npm install && npm run build
php artisan serve        # workbench di http://localhost:8000
```

Untuk tema yang tidak dibuat dari starter:

```bash
composer require webuild/theme-kit
```

Lalu daftarkan tema lewat service provider milik tema:

```php
use Webuild\ThemeKit\ThemeRegistry;

public function boot(ThemeRegistry $registry): void
{
    $manifest = json_decode(file_get_contents(__DIR__.'/../theme.json'), true) ?: [];
    $registry->register('kabupaten-hijau', dirname(__DIR__), $manifest);
}
```

### Untuk aplikasi Webuild

```bash
composer require webuild/theme-kit
php artisan migrate      # tabel theme_releases dan theme_update_logs
```

Service provider (`Webuild\ThemeKit\ThemeKitServiceProvider`) terdaftar otomatis lewat package discovery Laravel.

### Memperbarui

```bash
composer update webuild/theme-kit
```

Versi mengikuti semver. Versi minor bisa menambah isi kontrak (mis. section baru), jadi jalankan
`php artisan theme:check` setelah memperbarui.

## Perintah Artisan

| Perintah | Fungsi |
| --- | --- |
| `theme:check {path?} {--render}` | Validasi struktur tema, `theme.json`, kelengkapan halaman/section, variant, dan aset. `--render` juga merender halaman utama untuk mendeteksi error Blade |
| `theme:make {name} {--path=}` | Scaffold tema baru lengkap dengan manifest dan struktur folder |
| `theme:install {path?}` | Daftarkan dan pasang tema ke katalog database |
| `theme:update {key} {path?} {--channel=main}` | Perbarui tema ke rilis terbaru, jalankan skrip upgrade, dan catat riwayat |
| `theme:rollback {key}` | Kembalikan tema ke rilis sebelumnya yang berhasil |
| `theme:history {key}` | Tampilkan riwayat rilis dan catatan pembaruan |
| `theme:list` | Daftar tema terpasang beserta status dan versinya |

## Kontrak tema (ringkas)

Setiap tema wajib memiliki:

- `theme.json` dengan `key`, `name`, `version`, `api_version`, `sections` (header/main/footer), dan `assets`,
- semua halaman di `views/pages/{grup}/{nama}.blade.php`,
- semua section di `views/sections/{area}/{tipe}/{tipe}.blade.php`,
- semua pilihan variant dasar. Tema boleh menambah pilihan, tetapi tidak boleh menghapusnya.

Theme Kit menolak pembaruan tema yang **menghapus** section atau pilihan variant yang sudah dirilis.

Daftar lengkap komponen, variant, dan halaman ada di
[inventaris komponen](https://github.com/adilasomaaa/webuild-theme-starter/blob/main/docs/guide/pembuatan-tema/02-inventaris-komponen.md).
Sumber kebenarannya adalah `src/Contract/DefaultThemeContract.php`.

## Workbench

Saat `php artisan serve` dijalankan dari repo tema, workbench memuat tema tersebut dengan data dari
`demo/data.json`. Customizer (tombol gerigi di kanan bawah) meniru layout builder dan page builder
webuild-app: susunan section, pembagian kolom, variant, header/footer, dan menu. Jalankan dengan
`THEME_CUSTOMIZER=false` untuk menyembunyikannya. Customizer tidak ikut terpasang ke webuild-app.

## Panduan lengkap

- [Panduan pembuatan tema](https://github.com/adilasomaaa/webuild-theme-starter/tree/main/docs/guide/pembuatan-tema): desain di Figma, persiapan, pengembangan, dan rilis
- [webuild-skills](https://github.com/adilasomaaa/webuild-skills): skill agen AI untuk pengembangan tema (`npx skills add adilasomaaa/webuild-skills`)

## Lisensi

[MIT](LICENSE)
