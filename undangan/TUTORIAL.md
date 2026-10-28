# TUTORIAL LENGKAP — RSVP & Ucapan Terhubung Database (phpMyAdmin)

Dokumen ini menjelaskan langkah demi langkah menghubungkan undangan ke database,
mulai dari nol di komputer lokal (XAMPP) sampai siap dipasang di hosting.

---

## Bagian 1 — Cara Kerja Singkat

Sebelumnya, RSVP & Ucapan hanya tersimpan di **localStorage** (perangkat masing-masing
pengunjung — tidak bisa dilihat pengunjung lain, tidak bisa dilihat Anda).

Sekarang alurnya menjadi:

```
Pengunjung (browser)  →  api/rsvp.php (PHP)  →  database MySQL (tabel rsvp)
                                                     ↑
Anda (admin)  ←  admin/index.php (statistik, filter, hapus, CSV)  ←┘
```

- Tamu mengisi formulir → JavaScript mengirim data ke `api/rsvp.php` (fetch).
- PHP memvalidasi lalu menyimpannya ke tabel `rsvp` di database.
- Daftar "Untaian Doa" dibaca langsung dari database (terbaru di atas,
  otomatis dibagi per halaman, tombol "Muat ucapan lainnya").
- Jika halaman dibuka tanpa server (misal klik 2x file HTML, `file://`),
  undangan otomatis beralih ke **mode demo** dengan catatan jelas —
  sehingga file tetap bisa dibagikan sebagai pratinjau.

---

## Bagian 2 — Instalasi di Komputer (XAMPP + phpMyAdmin)

### Langkah 1 — Install XAMPP
1. Unduh XAMPP di https://www.apachefriends.org (pilih versi PHP 8.x).
2. Install seperti aplikasi biasa (Windows: ke `C:\xampp`).
3. Buka **XAMPP Control Panel**, klik **Start** pada:
   - **Apache** (server web + PHP)
   - **MySQL** (database server, otomatis ada phpMyAdmin)

### Langkah 2 — Salin Proyek ke htdocs
1. Salin seluruh folder `undangan` ini ke:
   - Windows : `C:\xampp\htdocs\undangan`
   - Mac     : `/Applications/XAMPP/htdocs/undangan`
   - Linux   : `/opt/lampp/htdocs/undangan`
2. Pastikan struktur foldernya persis seperti di README.md.

### Langkah 3 — Buat Database Lewat phpMyAdmin
1. Buka browser → **http://localhost/phpmyadmin**
2. Klik tab **Import** di menu atas.
3. Pada "File to import", klik **Choose File** → pilih `database.sql` dari folder proyek.
4. Biarkan format = SQL, lalu klik **Kirim / Go** (di bagian bawah halaman).
5. Jika berhasil akan muncul tulisan hijau "Import has been successfully finished"
   dan di panel kiri muncul database **undangan_db** berisi tabel **rsvp**.

> Alternatif tanpa import: klik tab **SQL**, salin seluruh isi `database.sql`,
> klik **Kirim / Go**. Hasilnya sama.

Tabel `rsvp` memiliki kolom:

| Kolom       | Tipe                          | Keterangan                        |
|-------------|-------------------------------|-----------------------------------|
| id          | INT AUTO_INCREMENT            | nomor urut otomatis (primary key) |
| nama        | VARCHAR(100)                  | nama tamu                         |
| kehadiran   | ENUM('hadir','ragu','tidak')  | status konfirmasi                 |
| jumlah_tamu | TINYINT                       | jumlah orang yang datang          |
| pesan       | VARCHAR(500)                  | ucapan & doa (boleh kosong)       |
| ip_address  | VARCHAR(45)                   | IP pengirim (untuk anti-spam)     |
| created_at  | TIMESTAMP                     | waktu kirim otomatis              |

### Langkah 4 — Periksa Koneksi di api/config.php
Buka `api/config.php`. Untuk XAMPP standar, nilainya sudah benar:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'undangan_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Jika MySQL Anda memakai password (misal Laragon atau XAMPP yang sudah diubah),
isi `DB_PASS` sesuai password tersebut.

### Langkah 5 — Uji Coba
1. Buka **http://localhost/undangan/undangan.html**
2. Klik "Buka Undangan" → gulir ke bagian **RSVP & Ucapan**.
3. Catatan kecil di bawah tombol kini berbunyi:
   "Konfirmasi & ucapan Anda tersimpan langsung ke database server."
   (Kalau masih tertulis "Mode demo", berarti Apache/MySQL belum jalan atau
   alamatnya salah — lihat Bagian 5 Troubleshooting.)
4. Isi nama, pilih kehadiran, tulis ucapan → **Kirim Ucapan**.
5. Buka **http://localhost/phpmyadmin** → database `undangan_db` → tabel `rsvp`
   → **Browse**. Data yang Anda kirim harus muncul di sana.
6. Buka juga **http://localhost/undangan/undangan.html?to=Bapak Budi**
   — parameter `?to=` tetap berfungsi untuk menampilkan nama tamu di cover.

---

## Bagian 3 — Halaman Admin

Buka **http://localhost/undangan/admin/** lalu login dengan password dari
`api/config.php` (default: `admin123`).

Fitur yang tersedia:
- **Statistik ringkas**: total konfirmasi, jumlah yang hadir, estimasi total tamu
  (penjumlahan `jumlah_tamu` dari yang memilih "Hadir"), masih ragu, berhalangan.
- **Filter & pencarian**: saring berdasarkan kehadiran, cari nama atau isi ucapan.
- **Hapus ucapan** yang tidak pantas (ada tombol per baris, wajib konfirmasi).
- **Unduh CSV**: file siap dibuka di Microsoft Excel / Google Sheets.

> **PENTING — ganti password default!** Edit `api/config.php`:
> ```php
> define('ADMIN_PASS', 'gantiDenganPasswordAnda');
> ```

---

## Bagian 4 — Upload ke Hosting (Online)

Undangan bisa dipasang di hosting cPanel biasa (Hostinger, Niagahoster, dsb.)
yang umumnya sudah menyediakan PHP + MySQL + phpMyAdmin.

### Langkah 1 — Buat Database di Hosting
1. Login ke cPanel → menu **MySQL Databases** (atau **MySQL Database Wizard**).
2. Buat database baru, contoh namanya: `userku_undangan`
   (cPanel otomatis menambah prefiks username, catat nama lengkapnya).
3. Buat **user database** baru + password, lalu **Add User To Database**
   dengan hak **ALL PRIVILEGES**.
4. Catat 3 hal: **nama database**, **username**, **password**.

### Langkah 2 — Import Struktur Tabel
1. cPanel → **phpMyAdmin**.
2. Di panel kiri, klik nama database yang tadi dibuat.
3. Tab **Import** → pilih file `database.sql` → **Go**.
4. Tabel `rsvp` akan dibuat di dalam database tersebut.

### Langkah 3 — Upload File Proyek
1. cPanel → **File Manager** → masuk ke folder `public_html`.
2. Upload seluruh isi folder `undangan` (bisa zip dulu lalu extract di server).
   Struktur akhir: `public_html/undangan/undangan.html`, `public_html/undangan/api/...`, dst.
3. Undangan kini bisa diakses di `https://domainanda.com/undangan/undangan.html`.

### Langkah 4 — Sesuaikan api/config.php
Edit `api/config.php` (lewat File Manager → klik kanan → Edit):

```php
define('DB_HOST', 'localhost');            // di sebagian besar hosting tetap localhost
define('DB_NAME', 'userku_undangan');      // nama lengkap dari Langkah 1
define('DB_USER', 'userku_admin');         // username database
define('DB_PASS', 'passwordDatabaseAnda'); // password database
define('ADMIN_PASS', 'passwordAdminBaru'); // jangan pakai admin123!
```

Selesai. Uji: buka undangan, kirim satu RSVP, cek phpMyAdmin di hosting.

> Tips produksi: di `api/config.php` ubah `DEBUG_MODE` menjadi `false`
> agar detail error database tidak terlihat oleh tamu.

---

## Bagian 5 — Troubleshooting (Masalah Umum)

**1. Catatan formulir masih "Mode demo" padahal sudah lewat localhost**
- Pastikan **Apache** dan **MySQL** statusnya *Running* di XAMPP Control Panel.
- Pastikan Anda membuka lewat `http://localhost/undangan/undangan.html`
  (bukan klik 2x file HTML-nya).
- Buka `http://localhost/undangan/api/rsvp.php` langsung di browser.
  Respons normal: `{"ok":true,"message":"API RSVP berjalan..."}`
  Jika muncul "Koneksi database gagal ..." → lanjut poin 2.

**2. Pesan "Koneksi database gagal (SQLSTATE[HY000] [1049] Unknown database)"**
- Database `undangan_db` belum dibuat → ulangi Langkah 3 Bagian 2 (import `database.sql`).

**3. Pesan "SQLSTATE[HY000] [1045] Access denied for user"**
- User/password MySQL salah. XAMPP default: user `root`, password kosong.
  Isi `DB_USER` dan `DB_PASS` sesuai punya Anda.

**4. Kirim RSVP muncul error "Gagal mengirim" atau respon bukan JSON**
- Buka Developer Tools browser (F12) → tab **Network** → klik request `rsvp.php`
  → lihat isinya. Pesan error PHP biasanya menjelaskan penyebabnya.
- Pastikan folder `api/` ikut ter-upload.

**5. Tabel `rsvp` sudah ada / mau mulai dari kosong**
- Di phpMyAdmin → database `undangan_db` → tab **SQL** → jalankan `TRUNCATE TABLE rsvp;`
  (mengosongkan isi tabel, strukturnya tetap).

**6. Ucapan spam berlebihan**
- API sudah membatasi maks. 3 kiriman per menit per IP + honeypot anti-bot.
- Untuk pengaman tambahan, hapus baris CORS di `api/rsvp.php` jika undangan
  dan API berada di domain yang sama.

**7. Ingin menambah/mengubah data ucapan manual?**
- phpMyAdmin → tabel `rsvp` → tab **Insert**, atau edit langsung di tab **Browse**.

---

## Bagian 6 — Catatan Teknis

- Semua query memakai **PDO prepared statements** → aman dari SQL injection.
- Output HTML di sisi klien di-escape (`esc()`) dan di halaman admin (`h()`)
  → aman dari XSS.
- Validasi berlaku ganda (JavaScript di browser + PHP di server):
  nama min. 2 huruf, kehadiran wajib dipilih, pesan maks. 500 karakter.
- API mendukung body JSON maupun form biasa; respons selalu JSON.
- Ucapan di "Untaian Doa" diambil per 12 (variabel `PER_PAGE` di `api/config.php`)
  dengan tombol "Muat ucapan lainnya" bila datanya lebih banyak.
- Waktu ucapan ditampilkan relatif ("5 mnt lalu", "2 hari lalu") dihitung dari
  `created_at` database.
