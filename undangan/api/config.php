<?php
/* ============================================================
   KONFIGURASI DATABASE — UNDANGAN HAWA & ADAM
   -------------------------------------------------------------
   EDIT HANYA BAGIAN INI. File ini dipakai oleh:
     - api/rsvp.php    (API RSVP & Ucapan)
     - admin/index.php (halaman admin)
   ============================================================ */

/* ---------- 1) Koneksi database (samakan dengan phpMyAdmin) ---------- */
/* XAMPP/Laragon default: host=localhost, user=root, password kosong.
   Jika di hosting cPanel: host biasanya "localhost", user & password
   diisi sesuai yang Anda buat di menu "MySQL Databases". */
define('DB_HOST', 'localhost');
define('DB_NAME', 'undangan_db');   // nama database (lihat database.sql)
define('DB_USER', 'root');          // user MySQL
define('DB_PASS', '');              // password MySQL

/* ---------- 2) Password halaman admin — WAJIB DIGANTI! ---------- */
/* Halaman admin: buka /admin/ lewat browser, login dengan password ini. */
define('ADMIN_PASS', 'admin123');

/* ---------- 3) Pengaturan API (boleh dibiarkan default) ---------- */
define('PER_PAGE',     12);   // jumlah ucapan per halaman di "Untaian Doa"
define('MAX_NAME',    100);   // maks. karakter nama
define('MAX_MESSAGE', 500);   // maks. karakter ucapan
define('MAX_GUESTS',   10);   // maks. jumlah tamu per konfirmasi
define('THROTTLE_SEC', 60);   // jeda monitoring spam (detik)
define('THROTTLE_MAX',  3);   // maks. kiriman per IP dalam THROTTLE_SEC detik

/* ---------- 4) Mode debugging (false untuk produksi) ---------- */
/* true  = pesan error database ditampilkan (mudah saat setup)
   false = pesan error disembunyikan (aman untuk tamu) */
define('DEBUG_MODE', true);
