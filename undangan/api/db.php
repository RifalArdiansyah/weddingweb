<?php
/* ============================================================
   KONEKSI DATABASE (PDO MySQL/MariaDB)
   Dipakai oleh api/rsvp.php dan admin/index.php.
   Kredensial diatur di api/config.php.
   ============================================================ */

/**
 * Mengembalikan satu objek PDO yang dipakai bersama (singleton).
 * Jika koneksi gagal, API menjawab JSON 500 dengan pesan yang jelas
 * agar mudah diketahui saat setup XAMPP/hosting.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $opt = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
        } catch (PDOException $e) {
            $detail = DEBUG_MODE
                ? ' (' . $e->getMessage() . ')'
                : '';
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                'ok'      => false,
                'message' => 'Koneksi database gagal. Pastikan MySQL/XAMPP berjalan, database "' . DB_NAME . '" sudah dibuat di phpMyAdmin, dan kredensial di api/config.php benar.' . $detail,
            ), JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    return $pdo;
}
