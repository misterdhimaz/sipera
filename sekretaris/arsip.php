<?php
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../includes/surat.php";
require __DIR__ . "/../includes/layout_sek.php";
$user = require_sekretaris();
$pdo  = db();

$stat = $pdo->query("SELECT COUNT(*) total, COALESCE(SUM(CASE WHEN retensi='inaktif' THEN 1 ELSE 0 END),0) nonaktif FROM surat WHERE status='arsip'")->fetch();
$terunduh = (int)$pdo->query('SELECT COUNT(DISTINCT surat_id) FROM unduhan')->fetchColumn();

$q = trim($_GET['q'] ?? ''); $fp = (int)($_GET['periode'] ?? 0); $fr = $_GET['retensi'] ?? '';
$where = ["status = 'arsip'"]; $args = [];
if ($q !== '') { $where[] = '(nomor_surat LIKE ? OR perihal LIKE ? OR pengirim LIKE ?)'; array_push($args, "%$q%", "%$q%", "%$q%"); }
if ($fp)       { $where[] = 'EXTRACT(YEAR FROM tanggal_surat) = ?'; $args[] = $fp; }
if (in_array($fr, ['aktif', 'inaktif', 'permanen'], true)) { $where[] = 'retensi = ?'; $args[] = $fr; }
$w = implode(' AND ', $where);

$per = 5;
$c = $pdo->prepare("SELECT COUNT(*) FROM surat WHERE $w"); $c->execute($args);
$total = (int)$c->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page  = min($pages, max(1, (int)($_GET['p'] ?? 1)));
$st = $pdo->prepare("SELECT * FROM surat WHERE $w ORDER BY tanggal_surat DESC, id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($args);
$rows   = $st->fetchAll();
$tahun  = $pdo->query("SELECT DISTINCT EXTRACT(YEAR FROM tanggal_surat) y FROM surat WHERE status='arsip' ORDER BY y DESC")->fetchAll(PDO::FETCH_COLUMN);
$riwayat = $pdo->query('SELECT u.created_at, s.nomor_surat, s.perihal, us.nama FROM unduhan u JOIN surat s ON s.id=u.surat_id JOIN users us ON us.id=u.user_id ORDER BY u.id DESC LIMIT 20')->fetchAll();

$retensiTeks = fn(array $r) => match ($r['retensi']) {
    'permanen' => 'Simpan Permanen',
    'inaktif'  => 'Inaktif (Retensi ' . (int)$r['retensi_tahun'] . ' tahun)',
    default    => 'Aktif',
};

sek_header('Arsip', 'arsip', $user);
?>
<?= render_flash() ?>
<section class="card arsip-head">
  <div>
    <h1 class="t-gold">Arsip Surat</h1>
    <p>Kumpulan surat-surat yang terdapat di dalam perusahaan. Surat-surat ini dikumpulkan untuk kebutuhan perusahaan mendatang.</p>
  </div>
  <button class="b b-sm" type="button" onclick="document.getElementById('riwayat').showModal()"><?= ui_icon('history') ?>Riwayat Unduh</button>
</section>

<div class="stats three">
  <div class="stat"><span class="stat-l">Total surat tersimpan</span><strong><?= (int)$stat['total'] ?><?= ui_icon('mail') ?></strong></div>
  <div class="stat"><span class="stat-l">Surat terunduh</span><strong><?= $terunduh ?><?= ui_icon('download') ?></strong></div>
  <div class="stat"><span class="stat-l">Arsip nonaktif</span><strong><?= (int)$stat['nonaktif'] ?><?= ui_icon('x') ?></strong></div>
</div>

<form class="tools box" method="get">
  <div class="search search-light"><?= ui_icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nomor, perihal, atau pengirim" aria-label="Cari arsip"></div>
  <details class="filter"<?= ($fp || $fr) ? ' open' : '' ?>>
    <summary aria-label="Filter"><?= ui_icon('filter') ?></summary>
    <div class="pop">
      <label>Periode<select name="periode" onchange="this.form.submit()"><option value="">Semua</option>
        <?php foreach ($tahun as $y): ?><option<?= $fp === (int)$y ? ' selected' : '' ?>><?= (int)$y ?></option><?php endforeach; ?></select></label>
      <label>Retensi<select name="retensi" onchange="this.form.submit()"><option value="">Semua</option>
        <?php foreach (['aktif' => 'Aktif', 'inaktif' => 'Inaktif', 'permanen' => 'Simpan Permanen'] as $k => $v): ?><option value="<?= $k ?>"<?= $fr === $k ? ' selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
    </div>
  </details>
</form>

<section class="board">
  <div class="board-bar"><span>Menampilkan <?= $total ?> surat</span></div>
  <div class="tbl-wrap">
    <table class="tbl arsip-tbl">
      <thead><tr><th>Nomor Surat</th><th>Perihal Surat &amp; Pengirim</th><th>Periode</th><th>Retensi</th><th><span class="sr">Unduh</span></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['nomor_surat']) ?></td>
          <td><?= e($r['perihal']) ?><br><small><?= e($r['pengirim']) ?></small></td>
          <td><?= e(date('Y', strtotime($r['tanggal_surat']))) ?></td>
          <td><?= e($retensiTeks($r)) ?></td>
          <td><?php if ($r['file_path']): ?><a class="icon-link" href="file.php?id=<?= (int)$r['id'] ?>&amp;dl=1" aria-label="Unduh <?= e($r['nomor_surat']) ?>"><?= ui_icon('download') ?></a>
              <?php else: ?><span class="icon-link dis" title="Berkas tidak tersedia"><?= ui_icon('download') ?></span><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="empty">Tidak ada arsip yang cocok.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="board-foot"><?= pagination($page, $pages) ?></div>
</section>

<dialog id="riwayat" class="dlg">
  <h2 class="t-gold">Riwayat Unduh</h2>
  <?php if ($riwayat): ?>
    <ul class="hist"><?php foreach ($riwayat as $h): ?>
      <li><strong><?= e($h['nomor_surat']) ?></strong> <?= e($h['perihal']) ?><small><?= e($h['nama']) ?> · <?= e(fmt_waktu($h['created_at'])) ?></small></li>
    <?php endforeach; ?></ul>
  <?php else: ?><p>Belum ada surat yang diunduh.</p><?php endif; ?>
  <form method="dialog"><button class="b b-sm">Tutup</button></form>
</dialog>
<?php sek_footer(); ?>
