<?php
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../includes/surat.php";
require __DIR__ . "/../includes/layout_pim.php";
$user = require_pimpinan();
$pdo  = db();

$stat = $pdo->query("SELECT COALESCE(SUM(CASE WHEN status='menunggu_disposisi' AND disposisi_draft=0 THEN 1 ELSE 0 END),0) belum,
                            COALESCE(SUM(CASE WHEN disposisi_at IS NOT NULL THEN 1 ELSE 0 END),0) selesai,
                            COALESCE(SUM(CASE WHEN status='menunggu_disposisi' AND disposisi_draft=1 THEN 1 ELSE 0 END),0) revisi FROM surat")->fetch();

$tab = in_array($_GET['sifat'] ?? '', ['penting', 'biasa'], true) ? $_GET['sifat'] : '';
$q   = trim($_GET['q'] ?? '');
$where = ["status = 'menunggu_disposisi'"]; $args = [];
if ($tab === 'penting') $where[] = "sifat IN ('penting','segera','rahasia')";
if ($tab === 'biasa')   $where[] = "sifat = 'biasa'";
if ($q !== '') { $where[] = '(nomor_surat LIKE ? OR perihal LIKE ? OR pengirim LIKE ?)'; array_push($args, "%$q%", "%$q%", "%$q%"); }
$w = implode(' AND ', $where);

$per = 4;
$c = $pdo->prepare("SELECT COUNT(*) FROM surat WHERE $w"); $c->execute($args);
$total = (int)$c->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page  = min($pages, max(1, (int)($_GET['p'] ?? 1)));
$st = $pdo->prepare("SELECT * FROM surat WHERE $w ORDER BY CASE sifat WHEN 'segera' THEN 1 WHEN 'rahasia' THEN 2 WHEN 'penting' THEN 3 WHEN 'biasa' THEN 4 ELSE 5 END, updated_at DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($args);
$rows = $st->fetchAll();

$tabUrl = fn(string $t) => e('?' . http_build_query(array_filter(['sifat' => $t, 'q' => $q])));

pim_header('Beranda', 'beranda', $user);
?>
<?= render_flash() ?>
<section class="card hello">
  <div>
    <h1 class="t-gold">Halo!</h1>
    <p>Hari ini banyak surat yang perlu ditindak, mari kita selesaikan dengan baik!</p>
  </div>
  <svg class="illu" viewBox="0 0 120 90" aria-hidden="true">
    <rect x="8" y="78" width="104" height="4" rx="2" fill="#ddd"/>
    <rect x="62" y="30" width="34" height="44" rx="3" fill="#fff" stroke="#c9a55b" stroke-width="2"/>
    <path d="M69 44l4 4 8-9" stroke="#1a9a3a" stroke-width="3" fill="none"/><rect x="69" y="56" width="20" height="3" fill="#ccc"/><rect x="69" y="63" width="14" height="3" fill="#ccc"/>
    <circle cx="30" cy="30" r="9" fill="#f2c9a5"/><path d="M14 76c0-20 7-32 16-32s16 12 16 32z" fill="#6b0f1a"/><path d="M21 26c3-10 15-10 18 0-6-3-12-3-18 0z" fill="#3a2a22"/>
    <circle cx="105" cy="20" r="3" fill="#e6b84a"/><circle cx="14" cy="14" r="2" fill="#5b87c9"/>
  </svg>
</section>

<div class="stats three">
  <div class="stat"><span class="stat-l"><?= ui_icon('clock') ?>Surat belum ditindak</span><strong><?= (int)$stat['belum'] ?></strong></div>
  <div class="stat"><span class="stat-l"><?= ui_icon('check') ?>Surat selesai disposisi</span><strong><?= (int)$stat['selesai'] ?></strong></div>
  <div class="stat"><span class="stat-l"><?= ui_icon('edit') ?>Surat perlu direvisi</span><strong><?= (int)$stat['revisi'] ?></strong></div>
</div>

<section class="card list-head">
  <div>
    <h2 class="t-gold">Antrean Surat Masuk</h2>
    <p>Berikut merupakan surat yang perlu ditindak lanjut.</p>
  </div>
  <div class="tabs">
    <a href="<?= $tabUrl('') ?>" class="<?= $tab === '' ? 'on' : '' ?>">Semua</a>
    <a href="<?= $tabUrl('penting') ?>" class="<?= $tab === 'penting' ? 'on' : '' ?>">Penting</a>
    <a href="<?= $tabUrl('biasa') ?>" class="<?= $tab === 'biasa' ? 'on' : '' ?>">Biasa</a>
  </div>
</section>

<section class="board">
  <?php foreach ($rows as $r):
      $draft = (int)$r['disposisi_draft'] === 1; ?>
    <article class="item">
      <div class="item-main">
        <div class="item-top"><strong><?= e($r['nomor_surat']) ?></strong><?= badge($r['sifat']) ?></div>
        <div class="item-time"><?= ui_icon('clock') ?><?= e(fmt_waktu($r['updated_at'])) ?></div>
        <div class="item-title"><?= e($r['perihal']) ?> <?= e($r['pengirim']) ?></div>
        <?php if ($draft): ?><div class="item-status">Lembar disposisi sudah dibuat, belum dikirim ke sekretaris.</div><?php endif; ?>
      </div>
      <div class="item-act">
        <a class="b b-sm" href="<?= $draft ? 'lembar.php' : 'disposisi.php' ?>?id=<?= (int)$r['id'] ?>"><?= $draft ? 'Lihat Lembar' : 'Isi Disposisi' ?></a>
        <?php if ($r['file_path']): ?>
          <a class="icon-link" href="file.php?id=<?= (int)$r['id'] ?>" target="_blank" rel="noopener" aria-label="Baca berkas <?= e($r['nomor_surat']) ?>"><?= ui_icon('eye') ?></a>
        <?php else: ?><span class="icon-link dis" title="Berkas tidak tersedia"><?= ui_icon('eye') ?></span><?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if (!$rows): ?><p class="empty">Tidak ada surat yang menunggu disposisi.</p><?php endif; ?>
  <div class="board-foot"><?= pagination($page, $pages) ?></div>
</section>
<?php sek_footer(); ?>
