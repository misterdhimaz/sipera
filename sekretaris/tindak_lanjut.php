<?php
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../includes/surat.php";
require __DIR__ . "/../includes/layout_sek.php";
$user = require_sekretaris();
$pdo  = db();

const TERLAMBAT_HARI = 2;      // surat di divisi lebih dari ini dianggap terlambat
const JEDA_INGATKAN  = 3600;   // detik, jeda minimal antar pengingat

$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$errors = [];
$old    = ['tanggal' => date('Y-m-d'), 'oleh' => '', 'catatan' => ''];

function waktu_kirim(int $suratId): ?string
{
    $st = db()->prepare("SELECT MIN(created_at) FROM distribusi_log WHERE surat_id = ? AND keterangan LIKE 'Sekretaris mengirim surat%'");
    $st->execute([$suratId]);
    return $st->fetchColumn() ?: null;
}

/* ---------- Aksi POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = $_POST['action'] ?? '';
    $s   = get_surat($id);
    if (!$s || $s['status'] !== 'dikirim') {
        flash('error', 'Surat ini tidak sedang menunggu tindak lanjut divisi.');
        redirect('tindak_lanjut.php');
    }
    $dn = preg_replace('/^Divisi\s+/i', '', (string)$s['divisi_nama']);

    if ($act === 'catat') {
        $old = ['tanggal' => trim($_POST['tanggal'] ?? ''), 'oleh' => trim($_POST['oleh'] ?? ''), 'catatan' => trim($_POST['catatan'] ?? '')];
        $kirim = waktu_kirim($id) ?: $s['updated_at'];
        if ($old['catatan'] === '' || mb_strlen($old['catatan']) > 1000) $errors[] = 'Catatan hasil tindak lanjut wajib diisi (maksimal 1000 karakter).';
        if (mb_strlen($old['oleh']) > 100)                               $errors[] = 'Nama penanggung jawab maksimal 100 karakter.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['tanggal']) || !strtotime($old['tanggal'])) $errors[] = 'Tanggal tindak lanjut tidak valid.';
        elseif ($old['tanggal'] > date('Y-m-d'))                         $errors[] = 'Tanggal tindak lanjut tidak boleh di masa depan.';
        elseif ($old['tanggal'] < date('Y-m-d', strtotime($kirim)))      $errors[] = 'Tanggal tindak lanjut tidak boleh sebelum surat dikirim ke divisi.';
        if (!$errors) {
            $ts = $old['tanggal'] === date('Y-m-d') ? time() : strtotime($old['tanggal'] . ' 17:00:00');
            $ts = date('Y-m-d H:i:s', max($ts, strtotime($kirim) + 60));
            $pdo->prepare("UPDATE surat SET status='selesai', tindak_lanjut_catatan=?, tindak_lanjut_oleh=?, tindak_lanjut_at=? WHERE id=?")
                ->execute([$old['catatan'], $old['oleh'] ?: null, $ts, $id]);
            $lg = $pdo->prepare('INSERT INTO distribusi_log (surat_id, keterangan, created_at) VALUES (?, ?, ?)');
            $lg->execute([$id, 'Surat telah dibaca dan ditindaklanjuti oleh Divisi ' . $dn, $ts]);
            $lg->execute([$id, 'Proses distribusi selesai', $ts]);
            notify_role('pimpinan', 'selesai', 'Surat ' . $s['perihal'] . ' telah ditindaklanjuti oleh Divisi ' . $dn . '.', 'beranda.php');
            flash('success', 'Tindak lanjut tercatat. Surat ditandai selesai dan dapat dimasukkan ke arsip.');
            redirect("tindak_lanjut.php?id=$id");
        }
    } elseif ($act === 'ingatkan') {
        $st = $pdo->prepare("SELECT MAX(created_at) FROM distribusi_log WHERE surat_id = ? AND keterangan LIKE 'Sekretaris mengingatkan%'");
        $st->execute([$id]);
        $last = $st->fetchColumn();
        if ($last && time() - strtotime($last) < JEDA_INGATKAN) {
            flash('error', 'Divisi ini baru saja diingatkan (' . fmt_waktu($last) . '). Coba lagi nanti.');
        } else {
            log_distribusi($id, "Sekretaris mengingatkan Divisi $dn untuk menindaklanjuti surat");
            flash('success', "Pengingat untuk Divisi $dn dicatat pada alur distribusi.");
        }
        redirect("tindak_lanjut.php?id=$id");
    } elseif ($act === 'alihkan') {
        $baru = (int)($_POST['divisi_id'] ?? 0);
        $dv = $pdo->prepare('SELECT nama FROM divisi WHERE id = ?'); $dv->execute([$baru]);
        $nb = $dv->fetchColumn();
        if (!$nb)                                   flash('error', 'Pilih divisi tujuan yang valid.');
        elseif ($baru === (int)$s['divisi_id'])     flash('error', 'Surat sudah berada di divisi tersebut.');
        else {
            $pdo->prepare('UPDATE surat SET divisi_id = ? WHERE id = ?')->execute([$baru, $id]);
            log_distribusi($id, "Sekretaris mengalihkan surat dari Divisi $dn ke Divisi $nb");
            log_distribusi($id, "Surat telah sampai ke Divisi $nb");
            flash('success', "Surat dialihkan ke Divisi $nb.");
        }
        redirect("tindak_lanjut.php?id=$id");
    }
}

/* ---------- Tampilan detail ---------- */
if ($id) {
    $s = get_surat($id);
    if (!$s || !in_array($s['status'], ['dikirim', 'selesai'], true)) {
        flash('error', 'Surat tidak ditemukan atau belum dikirim ke divisi.');
        redirect('tindak_lanjut.php');
    }
    $kirim  = waktu_kirim($id) ?: $s['updated_at'];
    $st = $pdo->prepare("SELECT MAX(created_at) FROM distribusi_log WHERE surat_id = ? AND keterangan LIKE 'Sekretaris mengingatkan%'");
    $st->execute([$id]);
    $lastRemind = $st->fetchColumn();
    $divisi = divisi_list();
    $hari   = (int)floor((time() - strtotime($kirim)) / 86400);

    sek_header('Tindak Lanjut Divisi', 'tindak', $user);
    echo render_flash(), render_errors($errors);
?>
<a class="back" href="tindak_lanjut.php">← Kembali ke daftar</a>
<section class="card">
  <h1 class="t-gold">Tindak Lanjut Surat</h1>
  <dl class="info-grid">
    <div><dt>ID</dt><dd><?= e(kode_surat((int)$s['id'])) ?></dd></div>
    <div><dt>Nomor Surat</dt><dd><?= e($s['nomor_surat']) ?></dd></div>
    <div><dt>Perihal</dt><dd><?= e($s['perihal']) ?></dd></div>
    <div><dt>Pengirim</dt><dd><?= e($s['pengirim']) ?></dd></div>
    <div><dt>Divisi tujuan</dt><dd><?= e($s['divisi_nama']) ?></dd></div>
    <div><dt>Dikirim ke divisi</dt><dd><?= e(fmt_waktu($kirim)) ?><?php if ($s['status'] === 'dikirim' && $hari >= TERLAMBAT_HARI): ?> <span class="late">Terlambat <?= $hari ?> hari</span><?php endif; ?></dd></div>
    <div><dt>Status</dt><dd><span class="pill <?= $s['status'] === 'selesai' ? 'pill-ok' : '' ?>"><?= $s['status'] === 'selesai' ? 'Sudah ditindaklanjuti' : 'Menunggu tindak lanjut' ?></span></dd></div>
  </dl>
  <p style="margin-top:10px">
    <a class="small-link" style="margin:0" href="alur.php?id=<?= $id ?>">Lihat alur distribusi</a>
    <?php if ($s['file_path']): ?> · <a class="small-link" style="margin:0" href="file.php?id=<?= $id ?>" target="_blank" rel="noopener">Buka berkas surat</a><?php endif; ?>
  </p>
</section>

<?= lembar_disposisi($s) ?>

<?php if ($s['status'] === 'selesai'): ?>
<section class="card">
  <h2 class="t-gold">Hasil Tindak Lanjut</h2>
  <dl class="info-grid">
    <div><dt>Tanggal</dt><dd><?= e(fmt_tanggal($s['tindak_lanjut_at'])) ?></dd></div>
    <div><dt>Penanggung jawab divisi</dt><dd><?= e($s['tindak_lanjut_oleh'] ?: '-') ?></dd></div>
  </dl>
  <p style="margin-top:8px"><b>Catatan:</b><br><?= nl2br(e($s['tindak_lanjut_catatan'] ?: '-')) ?></p>
  <p style="margin-top:12px"><a class="b b-sm" href="alur.php?id=<?= $id ?>">Lanjut ke arsip</a></p>
</section>
<?php else: ?>
<section class="card form-card">
  <h2 class="t-gold">Catat Tindak Lanjut</h2>
  <p style="margin-bottom:10px">Isi setelah Divisi <?= e(preg_replace('/^Divisi\s+/i', '', (string)$s['divisi_nama'])) ?> menyampaikan hasil tindak lanjut surat ini.</p>
  <form method="post" class="stack" data-confirm="Tandai surat ini sudah ditindaklanjuti?">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="catat">
    <div class="row2">
      <label>Tanggal tindak lanjut<input type="date" name="tanggal" value="<?= e($old['tanggal']) ?>" max="<?= date('Y-m-d') ?>" required></label>
      <label>Penanggung jawab divisi (opsional)<input name="oleh" value="<?= e($old['oleh']) ?>" maxlength="100"></label>
    </div>
    <label>Catatan hasil tindak lanjut<textarea name="catatan" rows="4" maxlength="1000" required><?= e($old['catatan']) ?></textarea></label>
    <div class="form-act"><button class="b" type="submit">Simpan &amp; tandai selesai</button></div>
  </form>
</section>

<div class="duo">
  <section class="card">
    <h2 class="t-gold">Ingatkan Divisi</h2>
    <p style="margin-bottom:10px">Catat pengingat pada alur distribusi agar terlihat jelas bahwa divisi sudah diminta menindaklanjuti.</p>
    <p class="hint" style="margin:0 0 10px"><?= $lastRemind ? 'Terakhir diingatkan: ' . e(fmt_waktu($lastRemind)) : 'Belum pernah diingatkan.' ?></p>
    <form method="post" data-confirm="Catat pengingat untuk divisi ini?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="ingatkan">
      <button class="b b-sm" type="submit">Ingatkan sekarang</button></form>
  </section>
  <section class="card">
    <h2 class="t-gold">Alihkan ke Divisi Lain</h2>
    <form method="post" class="stack" data-confirm="Alihkan surat ini ke divisi lain?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="alihkan">
      <label>Divisi tujuan baru
        <select name="divisi_id" required><option value="">Pilih divisi</option>
          <?php foreach ($divisi as $d): if ((int)$d['id'] === (int)$s['divisi_id']) continue; ?><option value="<?= (int)$d['id'] ?>"><?= e($d['nama']) ?></option><?php endforeach; ?>
        </select></label>
      <div class="form-act" style="justify-content:flex-start"><button class="b b-sm" type="submit">Alihkan</button></div>
    </form>
  </section>
</div>
<?php endif; ?>
<?php sek_footer(); exit; }

/* ---------- Tampilan daftar ---------- */
$fd = (int)($_GET['divisi'] ?? 0);
$fs = in_array($_GET['status'] ?? '', ['selesai', 'semua'], true) ? $_GET['status'] : 'menunggu';
$q  = trim($_GET['q'] ?? '');

$where = [$fs === 'menunggu' ? "s.status = 'dikirim'" : ($fs === 'selesai' ? "s.status = 'selesai'" : "s.status IN ('dikirim','selesai')")];
$args = [];
if ($fd) { $where[] = 's.divisi_id = ?'; $args[] = $fd; }
if ($q !== '') {
    $where[] = '(s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.pengirim LIKE ?)';
    array_push($args, "%$q%", "%$q%", "%$q%");
}
$w = implode(' AND ', $where);

$ring = $pdo->query("SELECT d.id, d.nama, COALESCE(SUM(CASE WHEN s.status='dikirim' THEN 1 ELSE 0 END),0) menunggu, COALESCE(SUM(CASE WHEN s.status='selesai' THEN 1 ELSE 0 END),0) selesai
                     FROM divisi d LEFT JOIN surat s ON s.divisi_id = d.id AND s.status IN ('dikirim','selesai')
                     GROUP BY d.id, d.nama ORDER BY menunggu DESC, d.nama")->fetchAll();

$per = 5;
$c = $pdo->prepare("SELECT COUNT(*) FROM surat s WHERE $w"); $c->execute($args);
$total = (int)$c->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page  = min($pages, max(1, (int)($_GET['p'] ?? 1)));
$st = $pdo->prepare("SELECT s.*, d.nama AS divisi_nama,
        (SELECT MIN(l.created_at) FROM distribusi_log l WHERE l.surat_id = s.id AND l.keterangan LIKE 'Sekretaris mengirim surat%') AS dikirim_at
      FROM surat s LEFT JOIN divisi d ON d.id = s.divisi_id WHERE $w
      ORDER BY CASE WHEN s.status = 'dikirim' THEN 1 ELSE 2 END ASC, dikirim_at ASC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($args);
$rows = $st->fetchAll();
$from = $total ? ($page - 1) * $per + 1 : 0;

$url = fn(array $o) => e('?' . http_build_query(array_filter(array_merge(['divisi' => $fd, 'status' => $fs, 'q' => $q], $o), fn($v) => $v !== '' && $v !== 0 && $v !== null)));

sek_header('Tindak Lanjut Divisi', 'tindak', $user);
?>
<?= render_flash() ?>
<section class="card">
  <h1 class="t-gold">Tindak Lanjut Divisi</h1>
  <p>Pantau surat yang sudah dikirim ke tiap divisi, catat hasil tindak lanjutnya, dan ingatkan divisi yang belum merespons.</p>
</section>

<div class="div-grid">
  <a class="div-card<?= $fd === 0 ? ' on' : '' ?>" href="<?= $url(['divisi' => 0, 'p' => 0]) ?>"><b>Semua divisi</b><small><?= array_sum(array_column($ring, 'menunggu')) ?> menunggu</small></a>
  <?php foreach ($ring as $d): ?>
    <a class="div-card<?= $fd === (int)$d['id'] ? ' on' : '' ?>" href="<?= $url(['divisi' => (int)$d['id'], 'p' => 0]) ?>">
      <b><?= e($d['nama']) ?></b><small><span class="wait"><?= (int)$d['menunggu'] ?> menunggu</span> · <?= (int)$d['selesai'] ?> selesai</small></a>
  <?php endforeach; ?>
</div>

<form class="tools" method="get">
  <?php if ($fd): ?><input type="hidden" name="divisi" value="<?= $fd ?>"><?php endif; ?>
  <div class="tabs small" role="tablist">
    <?php foreach (['menunggu' => 'Menunggu', 'selesai' => 'Selesai', 'semua' => 'Semua'] as $k => $v): ?>
      <a href="<?= $url(['status' => $k, 'p' => 0]) ?>" class="<?= $fs === $k ? 'on' : '' ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </div>
  <input type="hidden" name="status" value="<?= e($fs) ?>">
  <div class="search search-light"><?= ui_icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nomor, perihal, atau pengirim" aria-label="Cari"></div>
</form>

<section class="board">
  <div class="board-bar"><span>Menampilkan <?= $total ? "$from-" . ($from + count($rows) - 1) : 0 ?> dari <?= $total ?> surat</span></div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Nomor Surat</th><th>Perihal &amp; Pengirim</th><th>Divisi</th><th>Dikirim</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r):
          $hari = $r['dikirim_at'] ? (int)floor((time() - strtotime($r['dikirim_at'])) / 86400) : 0;
          $late = $r['status'] === 'dikirim' && $hari >= TERLAMBAT_HARI; ?>
        <tr>
          <td><?= e($r['nomor_surat']) ?><br><small><?= e(kode_surat((int)$r['id'])) ?></small></td>
          <td><strong><?= e($r['perihal']) ?></strong><br><small><?= e($r['pengirim']) ?></small></td>
          <td><?= e($r['divisi_nama']) ?></td>
          <td><?= e(fmt_waktu($r['dikirim_at'])) ?><?php if ($late): ?><span class="late">Terlambat <?= $hari ?> hari</span><?php endif; ?></td>
          <td><?php if ($r['status'] === 'selesai'): ?><span class="pill pill-ok">Selesai</span><?php else: ?><span class="pill <?= $late ? 'pill-late' : '' ?>">Menunggu</span><?php endif; ?></td>
          <td class="aksi"><a class="b b-sm" href="tindak_lanjut.php?id=<?= (int)$r['id'] ?>"><?= $r['status'] === 'selesai' ? 'Lihat' : 'Tindak lanjuti' ?></a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="empty">Tidak ada surat pada filter ini.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="board-foot"><?= pagination($page, $pages) ?></div>
</section>
<?php sek_footer(); ?>
