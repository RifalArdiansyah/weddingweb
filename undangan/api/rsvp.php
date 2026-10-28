<?php
/* ============================================================
   API RSVP & UCAPAN — undangan digital Leony & Fikhri
   ------------------------------------------------------------
   GET  api/rsvp.php?action=list&page=1
        → daftar ucapan terbaru (JSON, terbaru dulu, per halaman)

   POST api/rsvp.php
        body JSON  { nama, hadir, jumlah, pesan }
        atau form (application/x-www-form-urlencoded)
        → simpan konfirmasi ke database, balas data yang tersimpan

   Semua respons JSON: { ok, message?, data?, total?, page?, pages?, errors? }
   ============================================================ */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require __DIR__ . '/config.php';
require __DIR__ . '/db.php';

/* CORS longgar agar bisa diuji dari file:// atau domain berbeda.
   Jika sudah dipasang di hosting dengan domain yang sama, baris ini
   boleh dihapus untuk keamanan ekstra. */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/** Balas error lalu berhenti. */
function fail(int $code, string $msg, array $extra = array()): void {
    http_response_code($code);
    echo json_encode(array_merge(array('ok' => false, 'message' => $msg), $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

/** Ubah 1 baris tabel rsvp menjadi format yang dikonsumsi JavaScript. */
function to_entry(array $r): array {
    return array(
        'id' => (int)$r['id'],
        'n'  => $r['nama'],
        'a'  => $r['kehadiran'],                          // hadir | ragu | tidak
        'j'  => (int)$r['jumlah_tamu'] . ' tamu',
        'm'  => (string)($r['pesan'] ?? ''),
        't'  => strtotime($r['created_at']) * 1000,       // epoch milidetik
    );
}

const VALID_HADIR = array('hadir', 'ragu', 'tidak');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $pdo = db();

    /* ================= GET — daftar ucapan ================= */
    if ($method === 'GET' && ($_GET['action'] ?? '') === 'list') {

        $total = (int)$pdo->query('SELECT COUNT(*) FROM rsvp')->fetchColumn();
        $pages = max(1, (int)ceil($total / PER_PAGE));
        $page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page  = min(max(1, $page), $pages);
        $limit = max(1, min(50, PER_PAGE));
        $offset = ($page - 1) * $limit;

        $st = $pdo->prepare(
            'SELECT id, nama, kehadiran, jumlah_tamu, pesan, created_at
             FROM rsvp
             ORDER BY id DESC
             LIMIT :lim OFFSET :off'
        );
        $st->bindValue(':lim', $limit,  PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();

        $data = array();
        foreach ($st->fetchAll() as $row) {
            $data[] = to_entry($row);
        }

        echo json_encode(array(
            'ok'    => true,
            'total' => $total,
            'page'  => $page,
            'pages' => $pages,
            'count' => count($data),
            'data'  => $data,
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ================= POST — kirim RSVP baru ================= */
    if ($method === 'POST') {

        /* Terima body JSON maupun form biasa */
        $in = $_POST;
        if (empty($in)) {
            $raw = file_get_contents('php://input');
            if ($raw !== false && $raw !== '') {
                $json = json_decode($raw, true);
                if (is_array($json)) $in = $json;
            }
        }

        /* Honeypot anti-bot: field tersembunyi "website" harus kosong.
           Jika terisi (bot), pura-pura sukses agar bot tidak mencoba lagi. */
        if (!empty($in['website'])) {
            echo json_encode(array('ok' => true, 'message' => 'Terima kasih!'), JSON_UNESCAPED_UNICODE);
            exit;
        }

        /* ---------- Validasi sisi server ---------- */
        $nama   = trim((string)($in['nama']   ?? ''));
        $hadir  = trim((string)($in['hadir']  ?? ''));
        $jumlah = (int)($in['jumlah'] ?? 1);
        $pesan  = trim((string)($in['pesan']  ?? ''));

        $errors = array();
        if (mb_strlen($nama) < 2) {
            $errors['nama'] = 'Mohon isi nama (min. 2 huruf).';
        } elseif (mb_strlen($nama) > MAX_NAME) {
            $errors['nama'] = 'Nama terlalu panjang (maks. ' . MAX_NAME . ' huruf).';
        }
        if (!in_array($hadir, VALID_HADIR, true)) {
            $errors['hadir'] = 'Mohon pilih konfirmasi kehadiran.';
        }
        if ($jumlah < 1 || $jumlah > MAX_GUESTS) {
            $jumlah = 1;
        }
        if (mb_strlen($pesan) > MAX_MESSAGE) {
            $errors['pesan'] = 'Pesan terlalu panjang (maks. ' . MAX_MESSAGE . ' huruf).';
        }
        if ($errors) {
            fail(422, 'Mohon periksa kembali isian Anda.', array('errors' => $errors));
        }

        /* ---------- Pembatasan spam sederhana per IP ---------- */
        $ip     = substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
        $cutoff = date('Y-m-d H:i:s', time() - THROTTLE_SEC);
        $st = $pdo->prepare('SELECT COUNT(*) FROM rsvp WHERE ip_address = ? AND created_at > ?');
        $st->execute(array($ip, $cutoff));
        if ((int)$st->fetchColumn() >= THROTTLE_MAX) {
            fail(429, 'Terlalu banyak kiriman dari perangkat Anda. Mohon tunggu ' . THROTTLE_SEC . ' detik lagi.');
        }

        /* ---------- Simpan (prepared statement = aman dari SQL injection) ---------- */
        $st = $pdo->prepare(
            'INSERT INTO rsvp (nama, kehadiran, jumlah_tamu, pesan, ip_address)
             VALUES (:nama, :hadir, :jumlah, :pesan, :ip)'
        );
        $st->execute(array(
            ':nama'   => $nama,
            ':hadir'  => $hadir,
            ':jumlah' => $jumlah,
            ':pesan'  => ($pesan !== '' ? $pesan : null),
            ':ip'     => $ip,
        ));

        /* Ambil kembali baris yang baru disimpan (created_at versi database) */
        $st = $pdo->prepare(
            'SELECT id, nama, kehadiran, jumlah_tamu, pesan, created_at
             FROM rsvp WHERE id = ?'
        );
        $st->execute(array((int)$pdo->lastInsertId()));
        $row = $st->fetch();

        echo json_encode(array(
            'ok'      => true,
            'message' => 'Terima kasih, ' . $nama . '! Konfirmasi dan ucapan Anda tersimpan.',
            'data'    => $row ? to_entry($row) : null,
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ================= Selain itu ================= */
    if ($method === 'GET' && empty($_GET['action'])) {
        echo json_encode(array(
            'ok'      => true,
            'message' => 'API RSVP berjalan. Gunakan ?action=list untuk membaca data, POST untuk mengirim.',
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }

    fail(405, 'Permintaan tidak dikenal.');

} catch (Throwable $e) {
    $detail = DEBUG_MODE ? ' (' . $e->getMessage() . ')' : '';
    fail(500, 'Terjadi kesalahan pada server.' . $detail);
}
