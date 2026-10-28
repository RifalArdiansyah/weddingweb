<?php
/* ============================================================
   ADMIN — DATA RSVP & UCAPAN (undangan digital Hawa & Adam)
   ------------------------------------------------------------
   Buka:  http://localhost/undangan/admin/
   Login memakai password dari api/config.php (ADMIN_PASS).

   Fitur: ringkasan statistik, daftar konfirmasi (filter + cari),
   hapus ucapan, unduh CSV (bisa dibuka di Excel).
   ============================================================ */
session_start();
require __DIR__ . '/../api/config.php';
require __DIR__ . '/../api/db.php';

/* ---------- Logout ---------- */
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

/* ---------- Login ---------- */
$err = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['password'])) {
    if (hash_equals(ADMIN_PASS, (string)$_POST['password'])) {
        $_SESSION['undangan_admin'] = true;
        session_regenerate_id(true);
    } else {
        $err = 'Password salah.';
    }
}
$logged = !empty($_SESSION['undangan_admin']);

/* ---------- CSRF token ---------- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

/* ---------- Hapus entri ---------- */
$flash = '';
if ($logged && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete_id'])) {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $flash = 'Token keamanan tidak valid, silakan coba lagi.';
    } else {
        $st = db()->prepare('DELETE FROM rsvp WHERE id = ?');
        $st->execute(array((int)$_POST['delete_id']));
        $flash = 'Ucapan #' . (int)$_POST['delete_id'] . ' telah dihapus.';
    }
}

/* ---------- Ambil data (hanya jika login) ---------- */
$stats  = array('total' => 0, 'hadir' => 0, 'ragu' => 0, 'tidak' => 0, 'tamu' => 0);
$rows   = array();
$q      = '';
$filter = '';

if ($logged) {
    /* Statistik ringkas */
    $r = db()->query(
        "SELECT
           COUNT(*)                                            AS total,
           SUM(kehadiran = 'hadir')                            AS hadir,
           SUM(kehadiran = 'ragu')                             AS ragu,
           SUM(kehadiran = 'tidak')                            AS tidak,
           COALESCE(SUM(CASE WHEN kehadiran = 'hadir' THEN jumlah_tamu END), 0) AS tamu
         FROM rsvp"
    )->fetch();
    foreach ($stats as $k => $v) { $stats[$k] = (int)($r[$k] ?? 0); }

    /* Filter & pencarian */
    $q      = trim((string)($_GET['q'] ?? ''));
    $filter = (string)($_GET['kehadiran'] ?? '');
    $sql    = 'SELECT id, nama, kehadiran, jumlah_tamu, pesan, ip_address, created_at FROM rsvp';
    $where  = array();
    $params = array();
    if (in_array($filter, array('hadir', 'ragu', 'tidak'), true)) {
        $where[] = 'kehadiran = ?';
        $params[] = $filter;
    }
    if ($q !== '') {
        $where[] = '(nama LIKE ? OR pesan LIKE ?)';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }
    if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $sql .= ' ORDER BY id DESC LIMIT 1000';

    $st = db()->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    /* ---------- Ekspor CSV ---------- */
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="rsvp-undangan.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
        fputcsv($out, array('ID', 'Waktu', 'Nama', 'Kehadiran', 'Jumlah Tamu', 'Ucapan', 'IP'));
        foreach ($rows as $r2) {
            fputcsv($out, array(
                $r2['id'], $r2['created_at'], $r2['nama'], $r2['kehadiran'],
                $r2['jumlah_tamu'], $r2['pesan'], $r2['ip_address'],
            ));
        }
        fclose($out);
        exit;
    }
}

$label = array('hadir' => 'Hadir', 'ragu' => 'Masih Ragu', 'tidak' => 'Berhalangan');
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Admin RSVP — Hawa &amp; Adam</title>
<style>
:root{--ivory:#F8F3E9;--cream:#F3E9DA;--gold:#C5A46D;--taupe:#A18B78;--brown:#3A2926;--wine:#542A32;--white:#fff}
*{box-sizing:border-box}
body{margin:0;background:var(--ivory);color:var(--brown);font-family:Georgia,'Times New Roman',serif;line-height:1.6}
.wrap{max-width:960px;margin:0 auto;padding:32px 20px 60px}
h1{font-family:'Playfair Display',Georgia,serif;font-weight:500;font-size:1.7rem;margin:0 0 4px}
.sub{font-size:.78rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);font-family:Helvetica,Arial,sans-serif;margin:0}
.card{background:var(--white);border:1px solid rgba(197,164,109,.4);border-radius:14px;padding:22px;margin-top:18px}
.login input{width:100%;padding:.8em 1em;border:1px solid rgba(58,41,38,.25);border-radius:9px;font-size:1rem;margin:14px 0}
button,.btn{font-family:Helvetica,Arial,sans-serif;font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;cursor:pointer;
  background:var(--brown);color:var(--ivory);border:0;border-radius:99px;padding:.85em 1.6em;text-decoration:none;display:inline-block}
button.ghost,.btn.ghost{background:transparent;color:var(--brown);border:1px solid var(--taupe)}
.err{color:var(--wine);font-size:.9rem}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-top:18px}
.stat{background:var(--white);border:1px solid rgba(197,164,109,.4);border-radius:14px;padding:16px 18px;text-align:center}
.stat b{display:block;font-size:1.7rem;font-family:'Playfair Display',Georgia,serif}
.stat span{font-size:.62rem;letter-spacing:.16em;text-transform:uppercase;color:var(--taupe);font-family:Helvetica,Arial,sans-serif}
.bar{display:flex;flex-wrap:wrap;gap:10px;margin:20px 0 12px}
.bar input[type=text],.bar select{padding:.7em .9em;border:1px solid rgba(58,41,38,.25);border-radius:9px;font-size:.95rem;font-family:inherit}
.bar input[type=text]{flex:1;min-width:160px}
table{width:100%;border-collapse:collapse;background:var(--white);border-radius:12px;overflow:hidden;font-size:.92rem}
th,td{padding:10px 12px;border-bottom:1px solid rgba(58,41,38,.08);text-align:left;vertical-align:top}
th{background:var(--cream);font-family:Helvetica,Arial,sans-serif;font-size:.6rem;letter-spacing:.16em;text-transform:uppercase;color:var(--taupe)}
tr:last-child td{border-bottom:0}
.tag{font-family:Helvetica,Arial,sans-serif;font-size:.58rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;
  padding:.25em .7em;border-radius:99px;border:1px solid}
.tag.hadir{color:#7A5C28;border-color:rgba(197,164,109,.65);background:rgba(197,164,109,.13)}
.tag.ragu{color:#8a6d3b;border-color:rgba(218,165,32,.4);background:rgba(218,165,32,.08)}
.tag.tidak{color:var(--taupe);border-color:rgba(161,139,120,.5);background:rgba(161,139,120,.1)}
.del{background:none;border:1px solid rgba(84,42,50,.35);color:var(--wine);border-radius:99px;padding:.3em .9em;font-size:.58rem}
.pesan-cell{max-width:340px;white-space:pre-wrap}
.time-cell{white-space:nowrap;font-size:.8rem;color:var(--taupe)}
.flash{background:rgba(197,164,109,.16);border:1px solid rgba(197,164,109,.5);border-radius:9px;padding:.8em 1.1em;font-size:.85rem;margin-top:14px}
.empty{color:var(--taupe);font-style:italic;text-align:center;padding:30px 0}
.top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
@media(max-width:640px){th,td{padding:8px}.pesan-cell{max-width:180px}.ip-cell{display:none}}
</style>
</head>
<body>
<div class="wrap">

<?php if (!$logged): ?>
  <p class="sub">Panel Admin</p>
  <h1>RSVP &amp; Ucapan — Hawa &amp; Adam</h1>
  <div class="card login">
    <p style="margin:0">Masukkan password admin untuk melihat data konfirmasi kehadiran.</p>
    <?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif; ?>
    <form method="post" autocomplete="off">
      <input type="password" name="password" placeholder="Password admin" required autofocus>
      <button type="submit">Masuk</button>
    </form>
  </div>

<?php else: ?>
  <div class="top">
    <div>
      <p class="sub">Panel Admin</p>
      <h1>RSVP &amp; Ucapan — Hawa &amp; Adam</h1>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn ghost" href="?export=csv">Unduh CSV</a>
      <a class="btn ghost" href="?logout=1">Keluar</a>
    </div>
  </div>

  <div class="stats">
    <div class="stat"><b><?= $stats['total'] ?></b><span>Total Konfirmasi</span></div>
    <div class="stat"><b><?= $stats['hadir'] ?></b><span>Hadir</span></div>
    <div class="stat"><b><?= $stats['tamu'] ?></b><span>Estimasi Tamu</span></div>
    <div class="stat"><b><?= $stats['ragu'] ?></b><span>Masih Ragu</span></div>
    <div class="stat"><b><?= $stats['tidak'] ?></b><span>Berhalangan</span></div>
  </div>

  <?php if ($flash): ?><div class="flash"><?= h($flash) ?></div><?php endif; ?>

  <form class="bar" method="get">
    <input type="text" name="q" value="<?= h($q) ?>" placeholder="Cari nama / isi ucapan…">
    <select name="kehadiran">
      <option value="">Semua kehadiran</option>
      <option value="hadir"   <?= $filter === 'hadir'   ? 'selected' : '' ?>>Hadir</option>
      <option value="ragu"    <?= $filter === 'ragu'    ? 'selected' : '' ?>>Masih Ragu</option>
      <option value="tidak"   <?= $filter === 'tidak'   ? 'selected' : '' ?>>Berhalangan</option>
    </select>
    <button type="submit" class="ghost">Terapkan</button>
  </form>

  <div class="card" style="padding:0;overflow:auto">
    <table>
      <thead>
        <tr><th>Waktu</th><th>Nama</th><th>Kehadiran</th><th>Tamu</th><th>Ucapan</th><th class="ip-cell">IP</th><th></th></tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7" class="empty">Belum ada data<?= ($q !== '' || $filter !== '') ? ' yang cocok dengan filter' : '' ?>.</td></tr>
      <?php else: foreach ($rows as $r3): ?>
        <tr>
          <td class="time-cell"><?= h($r3['created_at']) ?></td>
          <td><b><?= h($r3['nama']) ?></b></td>
          <td><span class="tag <?= h($r3['kehadiran']) ?>"><?= h($label[$r3['kehadiran']] ?? $r3['kehadiran']) ?></span></td>
          <td><?= (int)$r3['jumlah_tamu'] ?></td>
          <td class="pesan-cell"><?= h($r3['pesan']) ?></td>
          <td class="ip-cell time-cell"><?= h($r3['ip_address']) ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Hapus ucapan #<?= (int)$r3['id'] ?> dari <?= h($r3['nama']) ?>?')">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="delete_id" value="<?= (int)$r3['id'] ?>">
              <button type="submit" class="del">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <p style="font-size:.75rem;color:var(--taupe);margin-top:12px">Menampilkan <?= count($rows) ?> baris (maks. 1000). Data tersimpan di database MySQL, tabel <b>rsvp</b>.</p>
<?php endif; ?>

</div>
</body>
</html>
