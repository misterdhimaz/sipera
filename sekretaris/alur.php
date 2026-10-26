<?php
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../includes/surat.php";
require __DIR__ . "/../includes/layout_sek.php";
$user = require_sekretaris();
$pdo  = db();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $s = get_surat($id);
    if ($s && $s['status'] === 'selesai') {
        $pdo->prepare("UPDATE surat SET status='arsip', retensi='inaktif', retensi_tahun=2, alasan_arsip='Selesai diproses' WHERE id=?")->execute([$id]);
        log_distribusi($id, 'Surat dipindahkan ke arsip');
        flash('success', 'Surat berhasil dipindahkan ke arsip.');
        redirect('arsip.php');
    }
    flash('error', 'Surat hanya dapat diarsipkan setelah divisi selesai menindaklanjuti.');
    redirect("alur.php?id=$id");
}

$s = $id ? get_surat($id) : null;
if (!$s) {
    $id = (int)$pdo->query("SELECT id FROM surat WHERE status IN ('dikirim','selesai') ORDER BY updated_at DESC LIMIT 1")->fetchColumn()
       ?: (int)$pdo->query("SELECT surat_id FROM distribusi_log ORDER BY id DESC LIMIT 1")->fetchColumn();
    $s  = $id ? get_surat($id) : null;
}
$logs = [];
if ($s) {
    $st = $pdo->prepare('SELECT * FROM distribusi_log WHERE surat_id = ? ORDER BY created_at, id');
    $st->execute([$id]);
    $logs = $st->fetchAll();
}
$pilihan = $pdo->query('SELECT id, nomor_surat, perihal FROM surat WHERE id IN (SELECT DISTINCT surat_id FROM distribusi_log) ORDER BY updated_at DESC LIMIT 40')->fetchAll();

sek_header('Alur Distribusi', 'alur', $user);
?>
<?= render_flash() ?>
<section class="card">
  <h1 class="t-gold">Distribusi Surat</h1>
  <p>Dashboard sekretaris yang berfungsi penuh dalam pengawasan proses surat di dalam organisasi.</p>
</section>

<section class="card detail">
  <div class="detail-h">
    <h2>Detail Distribusi</h2>
    <?php if ($pilihan): ?>
    <form method="get"><select name="id" onchange="this.form.submit()" aria-label="Pilih surat">
      <?php foreach ($pilihan as $p): ?><option value="<?= (int)$p['id'] ?>"<?= (int)$p['id'] === $id ? ' selected' : '' ?>><?= e($p['nomor_surat']) ?> — <?= e(mb_strimwidth($p['perihal'], 0, 38, '…')) ?></option><?php endforeach; ?>
    </select></form>
    <?php endif; ?>
  </div>
  <?php if ($s): ?><p class="sel-ref"><?= e($s['perihal']) ?> · <?= e($s['pengirim']) ?></p><?php endif; ?>
  <ol class="timeline">
    <?php foreach ($logs as $i => $l): ?>
      <li class="<?= $i === count($logs) - 1 ? 'last' : '' ?>">
        <strong><?= e($l['keterangan']) ?></strong>
        <time datetime="<?= e($l['created_at']) ?>"><?= e(fmt_tanggal($l['created_at'])) ?> | <?= e(date('H.i', strtotime($l['created_at']))) ?> WIB</time>
      </li>
    <?php endforeach; ?>
    <?php if (!$logs): ?><li class="last"><strong>Belum ada riwayat distribusi.</strong></li><?php endif; ?>
  </ol>
</section>

<?php if ($s && $s['status'] === 'dikirim'): ?>
<div class="toast" role="status">
  <span class="toast-msg">Divisi belum menindaklanjuti surat!</span>
  <svg class="toast-av" viewBox="0 0 40 40" aria-hidden="true"><circle cx="20" cy="20" r="20" fill="#f7c9d0"/><circle cx="20" cy="18" r="8" fill="#f2c9a5"/><path d="M10 16c1-9 19-9 20 0-5-4-15-4-20 0z" fill="#3a2a22"/><path d="M8 40c0-9 6-13 12-13s12 4 12 13z" fill="#6b0f1a"/></svg>
  <button type="button" class="toast-x" data-dismiss=".toast" aria-label="Tutup"><?= ui_icon('x') ?></button>
</div>
<?php endif; ?>

<section class="card next">
  <h2 class="t-gold">Tindakan Selanjutnya</h2>
  <p>Pengurusan surat telah terselesaikan dengan baik dari awal hingga akhir. Masukkan surat ke arsip digital untuk pencarian lebih mudah ketika membuat dokumen serupa kedepannya.</p>
  <form method="post" data-confirm="Masukkan surat ini ke arsip?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
    <button class="b" type="submit"<?= ($s && $s['status'] === 'selesai') ? '' : ' disabled' ?>>Masuk ke arsip</button>
  </form>
  <?php if ($s && $s['status'] === 'dikirim'): ?><p style="margin-top:10px"><a class="b b-ghost" href="tindak_lanjut.php?id=<?= (int)$id ?>">Catat tindak lanjut divisi</a></p><?php endif; ?>
  <?php if (!$s || $s['status'] !== 'selesai'): ?><p class="hint">Tersedia setelah divisi tujuan menyelesaikan tindak lanjut.</p><?php endif; ?>
</section>
<?php sek_footer(); ?>
