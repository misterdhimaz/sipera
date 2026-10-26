<?php
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../includes/surat.php";
require __DIR__ . "/../includes/layout_pim.php";
$user = require_pimpinan();
$pdo  = db();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$s  = $id ? get_surat($id) : null;
if ($s && $s['status'] !== 'menunggu_disposisi') {
    flash('error', 'Surat ini sudah tidak menunggu disposisi.');
    redirect('beranda.php');
}

$errors = [];
$divisi = divisi_list();
$val = ['divisi_id' => (int)($s['divisi_id'] ?? 0), 'catatan' => (string)($s['catatan_disposisi'] ?? '')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $val = ['divisi_id' => (int)($_POST['divisi_id'] ?? 0), 'catatan' => trim($_POST['catatan'] ?? '')];
    $ada = in_array($val['divisi_id'], array_column($divisi, 'id'), false) || in_array((string)$val['divisi_id'], array_column($divisi, 'id'), true);
    if (!$s)                                   $errors[] = 'Pilih surat dari antrean terlebih dahulu.';
    if (!$ada)                                 $errors[] = 'Pilih divisi tujuan (Diteruskan ke).';
    if ($val['catatan'] === '')                $errors[] = 'Catatan dari pimpinan wajib diisi.';
    if (mb_strlen($val['catatan']) > 1000)     $errors[] = 'Catatan maksimal 1000 karakter.';
    if (!$errors) {
        $pdo->prepare('UPDATE surat SET divisi_id = ?, catatan_disposisi = ?, disposisi_draft = 1 WHERE id = ?')
            ->execute([$val['divisi_id'], $val['catatan'], $id]);
        redirect("disposisi.php?id=$id&dibuat=1");
    }
}

$antrean = $pdo->query("SELECT id, nomor_surat, perihal FROM surat WHERE status='menunggu_disposisi' ORDER BY CASE sifat WHEN 'segera' THEN 1 WHEN 'rahasia' THEN 2 WHEN 'penting' THEN 3 WHEN 'biasa' THEN 4 ELSE 5 END, updated_at DESC LIMIT 100")->fetchAll();
$dis = $s ? '' : ' disabled';
$opt = isset($_GET['dibuat']) && $s
    ? ['sukses' => ["Data telah berhasil diinput!\nLembar disposisi tengah dibuat.....", "lembar.php?id=$id", 'hourglass']] : [];

pim_header('Disposisi Digital', 'disposisi', $user);
?>
<section>
  <h1 class="t-gold sec-title">Disposisi Digital</h1>
  <p class="lead-s">Form untuk membuat lembar disposisi yang nantinya ditujukan ke divisi terkait sesuai informasi pimpinan.</p>
</section>
<?= render_errors($errors) ?>

<section class="card dispo">
  <form method="get" class="stack pick">
    <label>Pilih surat dari antrean
      <select name="id" onchange="this.form.submit()">
        <option value="">— Pilih surat —</option>
        <?php foreach ($antrean as $a): ?><option value="<?= (int)$a['id'] ?>"<?= (int)$a['id'] === $id ? ' selected' : '' ?>><?= e($a['nomor_surat']) ?> — <?= e(mb_strimwidth($a['perihal'], 0, 40, '…')) ?></option><?php endforeach; ?>
      </select></label>
    <?php if (!$antrean): ?><small class="muted">Belum ada surat yang menunggu disposisi.</small><?php endif; ?>
  </form>

  <form method="post" class="stack" style="margin-top:14px">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <label>Surat dari<input readonly value="<?= e($s['pengirim'] ?? '') ?>"<?= $dis ?>></label>
    <label>Diterima tanggal<input readonly value="<?= $s ? e(date('d/m/Y', strtotime($s['tanggal_terima'] ?: $s['created_at']))) : '' ?>"<?= $dis ?>></label>
    <label>Nomor Surat<input readonly value="<?= e($s['nomor_surat'] ?? '') ?>"<?= $dis ?>></label>
    <div class="row2">
      <label>No. Agenda<input readonly value="<?= $s ? (int)$s['no_agenda'] : '' ?>"<?= $dis ?>></label>
      <label>Sifat<input readonly value="<?= $s ? e(SIFAT[$s['sifat']]) : '' ?>"<?= $dis ?>></label>
    </div>
    <label>Perihal<textarea readonly rows="2"<?= $dis ?>><?= e($s['perihal'] ?? '') ?></textarea></label>
    <label>Diteruskan ke
      <select name="divisi_id" required<?= $dis ?>>
        <option value="">Pilih divisi</option>
        <?php foreach ($divisi as $d): ?><option value="<?= (int)$d['id'] ?>"<?= $val['divisi_id'] === (int)$d['id'] ? ' selected' : '' ?>><?= e($d['nama']) ?></option><?php endforeach; ?>
      </select></label>
    <label>Catatan dari pimpinan<textarea name="catatan" rows="5" maxlength="1000" required<?= $dis ?>><?= e($val['catatan']) ?></textarea></label>
    <div class="form-act">
      <button class="b" type="submit"<?= $dis ?>>Selesai</button>
      <?php if ($s && $s['file_path']): ?><a class="b b-ghost" href="file.php?id=<?= $id ?>" target="_blank" rel="noopener">Baca surat</a><?php endif; ?>
    </div>
  </form>
</section>
<?php sek_footer($opt); ?>
