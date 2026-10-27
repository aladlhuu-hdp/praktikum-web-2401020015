# Sistem Inventaris Laboratorium (Tipe B)

Studi kasus Praktikum Pemrograman Web Modul 1–7 (INF11197).
Aplikasi CRUD inventaris laboratorium dengan Laravel, PDO, MariaDB/MySQL, dan Blade.

- Nama: `Al Adlhu Sodri Niwrad`
- NIM: 2401020015

## Fitur
- Read, Create, Update, Delete data inventaris
- Pencarian pada `kode_barang`, `nama_barang`, dan `nomor_aset`
- Filter berdasarkan ruang
- Validasi server-side, pencegahan duplikat, dan error handling yang aman
- Fungsi `statusUsiaAset()` (Baru jika tahun ≥ 2023, selain itu Lama)

## Struktur Data
- `ruang` (master): `id`, `nama_ruang`
- `inventaris`: `id`, `kode_barang`, `nama_barang`, `nomor_aset`, `tahun_perolehan`, `ruang_id`, `created_at`, `updated_at`

Skema dan data awal ada di `database/inventaris_lab_2401020015.sql`.

## Route
| Method | URI | Fungsi |
|---|---|---|
| GET | `/inventaris` | Indeks, pencarian, filter |
| POST | `/inventaris` | Create |
| GET | `/inventaris/{id}/edit` | Form edit |
| PUT | `/inventaris/{id}` | Update |
| DELETE | `/inventaris/{id}` | Delete |

## Cara Menjalankan
1. Instal dependensi: `composer install`
2. Salin konfigurasi: `cp .env.example .env`, lalu isi `DB_USERNAME` dan `DB_PASSWORD` sesuai MariaDB Anda.
3. Buat key: `php artisan key:generate`
4. Impor database: `mysql -u root -p < database/inventaris_lab_2401020015.sql`
5. Jalankan server: `php artisan serve`, lalu buka http://127.0.0.1:8000/inventaris

## Pengujian
```bash
php artisan test --filter=InventarisCrudTest
```

## Keamanan
- Semua query memakai PDO prepared statement.
- Kesalahan dicatat dengan `report()`, pengguna hanya melihat pesan singkat.
- `.env` tidak disertakan di repository.

