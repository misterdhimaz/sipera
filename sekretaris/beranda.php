<?php
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../includes/surat.php";
require __DIR__ . "/../includes/layout_sek.php";
$user = require_sekretaris();
$pdo  = db();

$stat = $pdo->query("SELECT COALESCE(SUM(CASE WHEN status<>'arsip' THEN 1 ELSE 0 END),0) masuk,
                            COALESCE(SUM(CASE WHEN status IN ('verifikasi','disposisi') THEN 1 ELSE 0 END),0) belum,
                            COALESCE(SUM(CASE WHEN status='selesai' THEN 1 ELSE 0 END),0) selesai,
                            COALESCE(SUM(CASE WHEN status='arsip' THEN 1 ELSE 0 END),0) arsip FROM surat")->fetch();

$tab   = in_array($_GET['sifat'] ?? '', ['penting', 'biasa'], true) ? $_GET['sifat'] : '';
$where = "s.status IN ('verifikasi','disposisi','dikirim','selesai')";
if ($tab === 'penting') $where .= " AND s.sifat IN ('penting','segera','rahasia')";
if ($tab === 'biasa')   $where .= " AND s.sifat = 'biasa'";
$order = "ORDER BY CASE s.status WHEN 'disposisi' THEN 1 WHEN 'verifikasi' THEN 2 WHEN 'dikirim' THEN 3 WHEN 'selesai' THEN 4 ELSE 5 END, s.updated_at DESC";
$base  = "SELECT s.*, d.nama AS divisi_nama FROM surat s LEFT JOIN divisi d ON d.id = s.divisi_id WHERE $where $order";

// Unduh daftar sebagai CSV
if (isset($_GET['unduh'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="daftar-surat-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Nomor Surat', 'Perihal', 'Pengirim', 'Sifat', 'Status', 'Diperbarui']);
    foreach ($pdo->query($base) as $r) {
        fputcsv($out, [kode_surat((int)$r['id']), $r['nomor_surat'], $r['perihal'], $r['pengirim'], SIFAT[$r['sifat']], status_teks($r), $r['updated_at']]);
    }
    exit;
}

$per   = 3;
$total = (int)$pdo->query("SELECT COUNT(*) FROM surat s WHERE $where")->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page  = min($pages, max(1, (int)($_GET['p'] ?? 1)));
$rows  = $pdo->query("$base LIMIT $per OFFSET " . (($page - 1) * $per))->fetchAll();

$tabUrl = fn(string $t) => e('?' . http_build_query($t ? ['sifat' => $t] : []));

sek_header('Beranda', 'beranda', $user);
?>
<section class="card hello">
  <div>
    <h1 class="t-gold">Halo!</h1>
    <p>Walaupun banyak surat yang harus diurus, tapi kamu harus tetap semangat ya!</p>
  </div>
  <svg class="illu" viewBox="0 0 120 90" aria-hidden="true">
    <rect x="8" y="78" width="104" height="4" rx="2" fill="#ddd"/>
    <rect x="44" y="36" width="30" height="30" rx="3" fill="#c9a55b"/><rect x="50" y="44" width="18" height="3" fill="#fff"/><rect x="50" y="51" width="12" height="3" fill="#fff"/>
    <circle cx="28" cy="30" r="8" fill="#f2c9a5"/><path d="M14 74c0-18 6-30 14-30s14 12 14 30z" fill="#5b87c9"/><path d="M20 26c2-9 14-9 17 0-6-3-11-3-17 0z" fill="#3a2a22"/>
    <circle cx="96" cy="26" r="8" fill="#f2c9a5"/><path d="M82 74c0-20 6-32 14-32s14 12 14 32z" fill="#6b0f1a"/><path d="M88 22c3-9 14-8 16 1-6-3-10-3-16-1z" fill="#3a2a22"/>
    <path d="M60 22l3 6 6 1-5 4 1 6-5-3-5 3 1-6-5-4 6-1z" fill="#e6b84a"/>
  </svg>
</section>

<div class="stats">
  <div class="stat"><span class="stat-l"><?= ui_icon('mail') ?>Surat Masuk</span><strong><?= (int)$stat['masuk'] ?></strong></div>
  <div class="stat"><span class="stat-l"><?= ui_icon('clock') ?>Surat belum ditindak</span><strong><?= (int)$stat['belum'] ?></strong></div>
  <div class="stat"><span class="stat-l"><?= ui_icon('check') ?>Surat selesai</span><strong><?= (int)$stat['selesai'] ?></strong></div>
  <div class="stat"><span class="stat-l"><?= ui_icon('grid') ?>Total Arsip</span><strong><?= (int)$stat['arsip'] ?></strong></div>
</div>

<section class="card list-head">
  <div>
    <h2 class="t-gold">List Surat yang perlu dikelola</h2>
    <p>Berikut merupakan daftar surat yang perlu diperhatikan lebih lanjut</p>
  </div>
  <div class="tabs" role="tablist">
    <a href="<?= $tabUrl('') ?>" class="<?= $tab === '' ? 'on' : '' ?>">Semua</a>
    <a href="<?= $tabUrl('penting') ?>" class="<?= $tab === 'penting' ? 'on' : '' ?>">Penting</a>
    <a href="<?= $tabUrl('biasa') ?>" class="<?= $tab === 'biasa' ? 'on' : '' ?>">Biasa</a>
  </div>
</section>

<section class="board">
  <div class="board-bar">
    <span><?= ui_icon('filter') ?>Menampilkan <?= count($rows) ?> dari <?= $total ?> surat</span>
    <a class="b-white" href="?<?= e(http_build_query(array_merge($_GET, ['unduh' => 1]))) ?>"><?= ui_icon('download') ?>Unduh</a>
  </div>
  <?php foreach ($rows as $r):
      [$label, $href] = match ($r['status']) {
          'verifikasi' => ['Cek Surat', 'manajemen.php?id=' . $r['id']],
          'disposisi'  => ['Cek Surat', 'cek.php?id=' . $r['id']],
          'dikirim'    => ['Tindak Lanjut', 'tindak_lanjut.php?id=' . $r['id']],
          default      => ['Lihat alur', 'alur.php?id=' . $r['id']],
      }; ?>
    <article class="item">
      <div class="item-main">
        <div class="item-top"><strong><?= e($r['nomor_surat']) ?></strong><?= badge($r['sifat']) ?></div>
        <div class="item-time"><?= ui_icon('clock') ?><?= e(fmt_waktu($r['updated_at'])) ?></div>
        <div class="item-title"><?= e($r['perihal']) ?> <?= e($r['pengirim']) ?></div>
        <div class="item-status">Status: <?= e(status_teks($r)) ?></div>
      </div>
      <div class="item-act">
        <a class="b b-sm" href="<?= e($href) ?>"><?= $label ?></a>
        <a class="icon-link" href="cek.php?id=<?= (int)$r['id'] ?>" aria-label="Lihat detail <?= e($r['nomor_surat']) ?>"><?= ui_icon('eye') ?></a>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if (!$rows): ?><p class="empty">Tidak ada surat yang perlu dikelola.</p><?php endif; ?>
  <div class="board-foot"><?= pagination($page, $pages) ?></div>
</section>
<?php sek_footer(); ?>
