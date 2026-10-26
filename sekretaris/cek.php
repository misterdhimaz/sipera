<?php
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../includes/surat.php";
require __DIR__ . "/../includes/layout_sek.php";
$user = require_sekretaris();
$pdo  = db();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$s  = get_surat($id);
if (!$s) { flash('error', 'Surat tidak ditemukan.'); redirect('manajemen.php'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $div = (int)($_POST['divisi_id'] ?? 0);
    $dv  = $pdo->prepare('SELECT nama FROM divisi WHERE id = ?'); $dv->execute([$div]);
    $nama = $dv->fetchColumn();
    if ($s['status'] !== 'disposisi') $error = 'Surat ini tidak sedang menunggu pengiriman ke divisi.';
    elseif (!$nama)                   $error = 'Pilih tujuan surat terlebih dahulu.';
    else {
        $pdo->prepare("UPDATE surat SET status='dikirim', divisi_id=? WHERE id=?")->execute([$div, $id]);
        log_distribusi($id, "Sekretaris mengirim surat sesuai disposisi ke $nama");
        log_distribusi($id, "Surat telah sampai ke $nama");
        $pdo->prepare("UPDATE notifikasi SET is_read=1 WHERE user_id=? AND link=?")->execute([$user['id'], "cek.php?id=$id"]);
        redirect("cek.php?id=$id&terkirim=1");
    }
}

$divisi = divisi_list();
$nm     = $s['file_name'] ?: ($s['perihal'] . '.pdf');
$opt    = isset($_GET['terkirim']) ? ['sukses' => ['Surat Telah Terkirim!', "alur.php?id=$id"]] : [];

sek_header('Cek Surat', 'manajemen', $user);
?>
<?= $error ? render_errors([$error]) : render_flash() ?>
<section class="card file-head">
  <?= ui_icon('file') ?><strong><?= e($nm) ?></strong>
  <?php if ($s['file_path']): ?><a class="small-link" href="file.php?id=<?= $id ?>" target="_blank" rel="noopener">Buka berkas asli</a><?php endif; ?>
</section>

<?= lembar_disposisi($s) ?>

<?php if ($s['status'] === 'disposisi'): ?>
  <form method="post" class="send-bar" data-confirm="Apakah Anda yakin?">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <select name="divisi_id" required aria-label="Pilih tujuan surat">
      <option value="">Pilih tujuan surat</option>
      <?php foreach ($divisi as $d): ?><option value="<?= (int)$d['id'] ?>"<?= (int)$s['divisi_id'] === (int)$d['id'] ? ' selected' : '' ?>><?= e($d['nama']) ?></option><?php endforeach; ?>
    </select>
    <button class="b" type="submit">Kirim</button>
  </form>
<?php else: ?>
  <div class="send-bar info">
    <span>Status: <?= e(status_teks($s)) ?></span>
    <?php if (in_array($s['status'], ['dikirim', 'selesai', 'arsip'], true)): ?><a class="b b-sm" href="alur.php?id=<?= $id ?>">Lihat alur</a>
    <?php elseif ($s['status'] === 'verifikasi'): ?><a class="b b-sm" href="manajemen.php?id=<?= $id ?>">Verifikasi surat</a><?php endif; ?>
  </div>
<?php endif; ?>
<?php sek_footer($opt); ?>
