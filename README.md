# Kopsis POS — Laravel 11

Sistem POS Koperasi Sekolah (Laboratorium Kewirausahaan MTSN 8 Banyuwangi). Migrasi dari **PHP Native** ke **Laravel 11**.

## Stack

- **Backend:** Laravel 11 (PHP 8.2+)
- **Database:** MySQL 8 (`kopsis_pos`)
- **Auth:** Session-based (Laravel auth bawaan) + role middleware (`admin`, `kasir`)
- **PDF:** `barryvdh/laravel-dompdf` (struk transaksi)
- **Frontend:** Blade + vanilla CSS/JS (di `public/assets/`) + Bootstrap 5 + Chart.js + JsBarcode + AOS

## Struktur

```
app/
├── Http/
│   ├── Controllers/    # 11 controllers (Auth, Dashboard, Profil, Kategori, Barang,
│   │                   #  Supplier, Restock, User, Transaksi, Riwayat, Laporan)
│   └── Middleware/     # EnsureUserHasRole (alias 'role')
├── Models/             # 7 Eloquent models
├── Providers/          # AppServiceProvider
└── helpers.php         # app_e, app_url, app_rupiah, dll

database/
├── migrations/         # 8 migration (users, kategori, barang, supplier,
│                       #  restock, transaksi, detail_transaksi, cache+jobs)
├── seeders/            # DatabaseSeeder (admin + kasir1 default)
├── kopsis_pos.sql      # SQL native lama (referensi)
└── migration_revisi.sql

public/
├── assets/             # CSS, JS, vendor libs, images
├── index.php
└── .htaccess

resources/views/
├── layouts/            # app.blade.php (master), sidebar, navbar
├── components/         # flash, toast, confirm-modal, empty-state, pagination
├── auth/               # login
├── admin/              # dashboard, kategori/, barang/, supplier/, restock/,
│                       #  user/, transaksi/, riwayat/, laporan/
├── kasir/              # dashboard, profil, transaksi/
└── shared/             # pos, struk, struk-content, struk-pdf

routes/
└── web.php             # Mirror semua URL native (/login, /admin/*, /kasir/*, dll)
```

## Cara Setup di Local

### 1. Clone & install dependencies

```bash
git clone https://github.com/wiistore/fix-project.git kopsis-pos
cd kopsis-pos
composer install
```

### 2. Setup `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuaikan DB:

```env
DB_DATABASE=kopsis_pos
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Bikin database

```bash
mysql -uroot -e "CREATE DATABASE kopsis_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Migrasi + seed

```bash
php artisan migrate --seed
```

Akan dibuat:
- Admin default: `admin` / `admin123` (protected)
- Kasir sample: `kasir1` / `kasir123`
- 2 kategori sample (ATK, Sembako)

### 5. Jalanin server lokal

```bash
php artisan serve
```

Buka `http://localhost:8000` → login pakai `admin` / `admin123`.

## Setup Production (Shared Hosting / VPS)

1. Upload semua file ke server
2. Set document root ke folder `public/`
3. Set permission writable: `storage/`, `bootstrap/cache/`
4. Buat `.env` dari `.env.example`, set `APP_ENV=production`, `APP_DEBUG=false`
5. `composer install --optimize-autoloader --no-dev`
6. `php artisan key:generate`
7. `php artisan migrate --seed --force`
8. `php artisan config:cache && php artisan route:cache && php artisan view:cache`

## Akun Default

| Role  | Username | Password   |
|-------|----------|------------|
| Admin | admin    | admin123   |
| Kasir | kasir1   | kasir123   |

> ⚠️ **Wajib ganti password admin** setelah setup di production. Lewat: login admin → menu User Kasir → reset password (atau langsung lewat DB).

## Modul

| # | Modul | Path | Keterangan |
|---|-------|------|------------|
| 1 | Auth | `/login`, `/logout` | Session-based, role admin & kasir |
| 2 | Dashboard | `/admin/dashboard`, `/kasir/dashboard` | Chart penjualan 7 hari, top barang, stok |
| 3 | Profil Kasir | `/kasir/profil` | Update profil + password |
| 4 | Kategori | `/admin/kategori` | CRUD kategori |
| 5 | Barang | `/admin/barang` | CRUD + barcode generator + cetak label single/bulk |
| 6 | Supplier | `/admin/supplier` | CRUD supplier |
| 7 | Restock | `/admin/restock` | Stok masuk/keluar dengan transaction lock |
| 8 | User Kasir | `/admin/user` | CRUD kasir + reset password (admin protected) |
| 9 | Transaksi | `/admin/transaksi`, `/kasir/transaksi` | POS, struk HTML, struk PDF |
| 10 | Riwayat | `/admin/riwayat-transaksi` | Detail, edit (recalculate stok), cancel |
| 11 | Laporan | `/admin/laporan` | Ringkasan, penjualan, laba, top barang, restock + 5 export Excel |

## Folder Penting

- `dokumen dan diagram/` — Dokumentasi SRS, ERD, DFD, Use Case, Sequence Diagram, Activity Diagram, Class Diagram, Flowchart, Dokumen Pengujian
- `database/kopsis_pos.sql` — Backup SQL dari versi PHP Native (referensi schema awal)

## Migrasi dari PHP Native

Project ini awalnya dibangun dengan PHP Native (custom MVC). Versi native tetap ada di branch `main` (sebelum merge PR). Branch ini (`feat/migrate-to-laravel`) adalah Laravel 11 setara fitur:

- ✅ Schema database identik (FK, index, enum)
- ✅ Semua URL & flow business logic sama
- ✅ Tampilan dipertahankan (CSS/JS native dipakai apa adanya)
- ✅ Auth pakai Laravel `auth()->user()` (replace `Session::user()`)
- ✅ Eloquent ORM (replace custom `Model` class)
- ✅ DB transaction untuk integritas stok (restock, transaksi, riwayat edit/cancel)
- ✅ CSRF protection di semua form POST (`@csrf`)

## Lisensi

MIT
