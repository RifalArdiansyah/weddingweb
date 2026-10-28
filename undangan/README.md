# Undangan Digital — Leony & Fikhri (Terhubung Database)

Undangan pernikahan digital dengan **RSVP & Ucapan yang tersimpan ke database
MySQL/MariaDB** (dikelola lewat phpMyAdmin), lengkap dengan halaman admin.

## Struktur Folder

```
undangan/
├── undangan.html      ← halaman undangan (RSVP & Ucapan → database)
├── images/            ← foto galeri & mempelai
├── api/
│   ├── config.php     ← ★ EDIT INI: koneksi database & password admin
│   ├── db.php         ← koneksi PDO ke MySQL
│   └── rsvp.php       ← API (GET: daftar ucapan, POST: kirim RSVP)
├── admin/
│   └── index.php      ← halaman admin (statistik, filter, hapus, unduh CSV)
├── database.sql       ← ★ IMPORT ke phpMyAdmin (buat database + tabel)
├── README.md          ← file ini
└── TUTORIAL.md        ← panduan lengkap langkah demi langkah (XAMPP & hosting)
```

## Mulai Cepat (XAMPP)

1. Install & jalankan **XAMPP** → nyalakan **Apache** dan **MySQL**.
2. Salin folder `undangan` ini ke `C:\xampp\htdocs\` (Windows)
   atau `/Applications/XAMPP/htdocs/` (Mac).
3. Buka **http://localhost/phpmyadmin** → tab **Import** → pilih `database.sql` → **Kirim**.
4. Sesuaikan `api/config.php` jika perlu (default XAMPP: user `root`, password kosong).
5. Buka **http://localhost/undangan/undangan.html** → coba kirim RSVP.
6. Lihat datanya di **http://localhost/phpmyadmin** (database `undangan_db`, tabel `rsvp`)
   atau di **http://localhost/undangan/admin/** (password default: `admin123` — segera ganti!).

Panduan lengkap (termasuk deploy ke hosting): baca **TUTORIAL.md**.

## Ganti Password Admin

Edit `api/config.php`, baris:

```php
define('ADMIN_PASS', 'admin123');   // ganti 'admin123' menjadi password Anda
```
