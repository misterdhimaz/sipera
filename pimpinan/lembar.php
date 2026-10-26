<?php
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../includes/surat.php";
require __DIR__ . "/../includes/layout_pim.php";
$user = require_pimpinan();
$pdo  = db();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$s  = get_surat($id);
if (!$s) { flash('error', 'Surat tidak ditemukan.'); redirect('beranda.php'); }
if ($s['status'] === 'menunggu_disposisi' && !$s['disposisi_draft']) redirect("disposisi.php?id=$id");

$siap = $s['status'] === 'menunggu_disposisi' && $s['disposisi_draft'] && $s['divisi_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if ($siap) {
        $pdo->prepare("UPDATE surat SET status='disposisi', disposisi_draft=0, disposisi_at=NOW() WHERE id=?")->execute([$id]);
        log_distribusi($id, 'Pimpinan memberikan disposisi untuk Divisi ' . preg_replace('/^Divisi\s+/i', '', $s['divisi_nama']));
        notify_role('sekretaris', 'disposisi', 'Surat ' . $s['perihal'] . ' telah didisposisi oleh pimpinan.', "cek.php?id=$id");
        $pdo->prepare('UPDATE notifikasi SET is_read=1 WHERE user_id=? AND link=?')->execute([$user['id'], "disposisi.php?id=$id"]);
        redirect("lembar.php?id=$id&terkirim=1");
    }
    flash('error', 'Lembar disposisi ini tidak dapat dikirim.');
    redirect("lembar.php?id=$id");
}
$opt = isset($_GET['terkirim']) ? ['sukses' => ['Disposisi telah dikirim ke sekretaris!', 'beranda.php']] : [];

pim_header('Lembar Disposisi', 'disposisi', $user);
?>
<?= render_flash() ?>
<section>
  <h1 class="t-gold sec-title">Lembar Disposisi Digital</h1>
  <p class="lead-s">Berikut lembar disposisi digital yang telah dibuat. Boleh dilihat dahulu untuk isinya lebih lanjut.</p>
</section>

<?= lembar_disposisi($s) ?>

<?php if ($siap): ?>
  <div class="lembar-act">
    <form method="post" data-confirm="Apakah Anda yakin?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <button class="b" type="submit">Kirim ke sekretaris</button></form>
    <a class="b b-ghost" href="disposisi.php?id=<?= $id ?>">Ubah disposisi</a>
  </div>
<?php else: ?>
  <div class="send-bar info"><span>Status: <?= e(status_teks($s)) ?></span><a class="b b-sm" href="beranda.php">Kembali ke beranda</a></div>
<?php endif; ?>
<?php sek_footer($opt); ?>
