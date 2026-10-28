-- ============================================================
-- DATABASE UNDANGAN DIGITAL — HAWA & ADAM
-- ------------------------------------------------------------
-- CARA PAKAI (pilih salah satu):
--   A. phpMyAdmin  →  tab "Import"  →  pilih file ini  →  "Kirim/Go"
--   B. phpMyAdmin  →  tab "SQL"     →  salin seluruh isi file ini  →  "Kirim/Go"
--
-- File ini akan:
--   1. Membuat database  : undangan_db
--   2. Membuat tabel     : rsvp
--   3. Mengisi 4 ucapan contoh (boleh dihapus lewat phpMyAdmin/admin)
-- ============================================================

CREATE DATABASE IF NOT EXISTS undangan_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE undangan_db;

CREATE TABLE IF NOT EXISTS rsvp (
  id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  nama        VARCHAR(100)     NOT NULL,
  kehadiran   ENUM('hadir','ragu','tidak') NOT NULL,
  jumlah_tamu TINYINT UNSIGNED NOT NULL DEFAULT 1,
  pesan       VARCHAR(500)     DEFAULT NULL,
  ip_address  VARCHAR(45)      DEFAULT NULL,
  created_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_created (created_at),
  KEY idx_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Data contoh (sesuai tampilan demo sebelumnya).
-- Aman dijalankan ulang: hanya diisi bila tabel masih kosong.
-- ------------------------------------------------------------
INSERT INTO rsvp (nama, kehadiran, jumlah_tamu, pesan, created_at)
SELECT * FROM (
  SELECT 'Salsabilla & Keluarga' AS nama, 'hadir' AS kehadiran, 2 AS jumlah_tamu,
         'Barakallahu lakuma wa baraka alaikuma. Semoga menjadi keluarga yang sakinah, mawaddah, warahmah.' AS pesan,
         NOW() - INTERVAL 2 DAY AS created_at
  UNION ALL
  SELECT 'Bpk. Romy Saputra', 'hadir', 1,
         'Selamat menempuh hidup baru! Insya Allah kami hadir merayakan bersama.',
         NOW() - INTERVAL 4 DAY
  UNION ALL
  SELECT 'Rifal Ardiansyah', 'ragu', 1,
         'Doa terbaik untuk kalian berdua. Kabari aku ya kalau ada perubahan jadwal.',
         NOW() - INTERVAL 6 DAY
  UNION ALL
  SELECT 'Dimas Prasetyo', 'tidak', 1,
         'Maaf belum bisa hadir karena tugas di luar kota. Selamat ya, Hawa & Adam!',
         NOW() - INTERVAL 7 DAY
) AS contoh
WHERE NOT EXISTS (SELECT 1 FROM rsvp);
