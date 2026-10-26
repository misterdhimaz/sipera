<?php
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../includes/surat.php";
require __DIR__ . "/../includes/layout_sek.php";
$user = require_sekretaris();
$pdo  = db();

$errors = [];
$in = ['perihal' => '', 'pengirim' => '', 'jenis' => '', 'sifat' => 'biasa', 'tanggal_surat' => '', 'nomor_surat' => ''];

/* ---------- Aksi POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = $_POST['action'] ?? '';

    if ($act === 'upload' || $act === 'update') {
        foreach ($in as $k => $_) $in[$k] = trim($_POST[$k] ?? '');
        $id = (int)($_POST['id'] ?? 0);
        if ($in['perihal'] === '' || mb_strlen($in['perihal']) > 200) $errors[] = 'Nama surat wajib diisi (maksimal 200 karakter).';
        if ($in['pengirim'] === '')                                   $errors[] = 'Pengirim wajib diisi.';
        if (!in_array($in['jenis'], JENIS, true))                     $errors[] = 'Pilih jenis surat.';
        if (!isset(SIFAT[$in['sifat']]))                              $errors[] = 'Pilih sifat surat.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $in['tanggal_surat'])) $errors[] = 'Tanggal surat tidak valid.';
        if ($in['nomor_surat'] === '')                                $errors[] = 'Nomor surat wajib diisi.';

        $file = null;
        $hasFile = ($_FILES['berkas']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if (!$errors && ($act === 'upload' || $hasFile)) {
            $ferr = null;
            $file = simpan_berkas($_FILES['berkas'] ?? [], $ferr);
            if (!$file) $errors[] = $ferr;
        }

        if (!$errors) {
            $note = catatan_sistem($in['nomor_surat'], $in['tanggal_surat'], $id);
            if ($act === 'upload') {
                $agenda = (int)$pdo->query('SELECT COALESCE(MAX(no_agenda),0)+1 FROM surat')->fetchColumn();
                $pdo->prepare("INSERT INTO surat (nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda,
                                 file_path, file_name, status, catatan_sistem, created_by)
                               VALUES (?,?,?,?,?,?,CURRENT_DATE,?,?,?,'verifikasi',?,?)")
                    ->execute([$in['nomor_surat'], $in['perihal'], $in['pengirim'], $in['jenis'], $in['sifat'], $in['tanggal_surat'],
                               $agenda, $file[0], $file[1], $note, $user['id']]);
                $newId = db_last_insert_id('surat');
                log_distribusi($newId, 'Sekretaris menerima surat dari ' . $in['pengirim']);
                notify((int)$user['id'], 'verifikasi', 'Surat dari ' . $in['pengirim'] . ' belum dilakukan verifikasi lebih lanjut. Lakukan verifikasi sekarang.', "manajemen.php?id=$newId");
                flash('success', 'Surat berhasil diunggah dan menunggu verifikasi.');
                redirect("manajemen.php?id=$newId");
            }
            $old = get_surat($id);
            if ($old && $old['status'] === 'verifikasi') {
                $pdo->prepare('UPDATE surat SET nomor_surat=?, perihal=?, pengirim=?, jenis=?, sifat=?, tanggal_surat=?, catatan_sistem=? WHERE id=?')
                    ->execute([$in['nomor_surat'], $in['perihal'], $in['pengirim'], $in['jenis'], $in['sifat'], $in['tanggal_surat'], $note, $id]);
                if ($file) {
                    hapus_berkas($old['file_path']);
                    $pdo->prepare('UPDATE surat SET file_path=?, file_name=? WHERE id=?')->execute([$file[0], $file[1], $id]);
                }
                flash('success', 'Data surat diperbarui.');
            } else flash('error', 'Hanya surat yang menunggu verifikasi yang dapat diubah.');
            redirect("manajemen.php?id=$id");
        }
    } elseif ($act === 'terima' || $act === 'tolak') {
        $id = (int)($_POST['id'] ?? 0);
        $s  = get_surat($id);
        if ($s && $s['status'] === 'verifikasi') {
            if ($act === 'terima') {
                $pdo->prepare("UPDATE surat SET status='menunggu_disposisi' WHERE id=?")->execute([$id]);
                log_distribusi($id, 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan');
                notify_role('pimpinan', 'surat_baru', 'Sekretaris baru saja mengirimi surat ' . $s['perihal'] . ' dari pihak ' . $s['pengirim'] . '. Mohon agar suratnya dapat didisposisikan segera.', "disposisi.php?id=$id");
                flash('success', 'Surat diterima dan dikirim ke pimpinan.');
            } else {
                $pdo->prepare("UPDATE surat SET status='arsip', retensi='inaktif', retensi_tahun=2, alasan_arsip='Ditolak saat verifikasi' WHERE id=?")->execute([$id]);
                log_distribusi($id, 'Surat ditolak saat verifikasi dan disimpan ke arsip');
                flash('success', 'Surat ditolak dan disimpan ke arsip.');
            }
            $pdo->prepare("UPDATE notifikasi SET is_read=1 WHERE user_id=? AND link=?")->execute([$user['id'], "manajemen.php?id=$id"]);
        } else flash('error', 'Surat ini tidak lagi menunggu verifikasi.');
        redirect('manajemen.php');
    } elseif ($act === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $s  = get_surat($id);
        if ($s && $s['status'] === 'verifikasi') {
            hapus_berkas($s['file_path']);
            $pdo->prepare('DELETE FROM surat WHERE id=?')->execute([$id]);
            flash('success', 'Surat dihapus.');
        } else flash('error', 'Surat yang sudah diproses tidak dapat dihapus.');
        redirect('manajemen.php');
    }
}

/* ---------- Data tampilan ---------- */
$edit = null;
if (isset($_GET['edit']) && ($e = get_surat((int)$_GET['edit'])) && $e['status'] === 'verifikasi') {
    $edit = $e;
    if (!$errors) $in = array_intersect_key($e, $in);
}
$selId = (int)($_GET['id'] ?? 0);
$sel   = $selId ? get_surat($selId) : null;
if (!$sel) $sel = $pdo->query("SELECT s.*, d.nama divisi_nama FROM surat s LEFT JOIN divisi d ON d.id=s.divisi_id WHERE s.status='verifikasi' ORDER BY s.id DESC LIMIT 1")->fetch() ?: null;
$canDecide = $sel && $sel['status'] === 'verifikasi';

$banner = $pdo->prepare("SELECT * FROM notifikasi WHERE user_id=? AND tipe='disposisi' AND is_read=0 ORDER BY id DESC LIMIT 1");
$banner->execute([$user['id']]);
$banner = $banner->fetch();

// Daftar surat
$q = trim($_GET['q'] ?? ''); $fs = $_GET['sifat'] ?? ''; $fj = $_GET['jenis'] ?? '';
$where = ["s.status <> 'arsip'"]; $args = [];
if ($q !== '') {
    $where[] = "(s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.pengirim LIKE ? OR CONCAT('SRT-', LPAD(s.id,4,'0')) LIKE ?)";
    array_push($args, "%$q%", "%$q%", "%$q%", "%$q%");
}
if (isset(SIFAT[$fs]))             { $where[] = 's.sifat = ?'; $args[] = $fs; }
if (in_array($fj, JENIS, true))    { $where[] = 's.jenis = ?'; $args[] = $fj; }
$w = implode(' AND ', $where);
$per = 5;
$cnt = $pdo->prepare("SELECT COUNT(*) FROM surat s WHERE $w"); $cnt->execute($args);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page  = min($pages, max(1, (int)($_GET['p'] ?? 1)));
$st = $pdo->prepare("SELECT s.* FROM surat s WHERE $w ORDER BY s.id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($args);
$rows = $st->fetchAll();
$from = $total ? ($page - 1) * $per + 1 : 0;

sek_header('Manajemen Surat', 'manajemen', $user);
?>
<?php if ($banner): ?>
<div class="notice" role="alert">
  <span class="notice-ic"><?= ui_icon('x') ?></span>
  <div>
    <strong>Ada surat baru dari pimpinan!</strong>
    <p>Surat telah didisposisi oleh pimpinan. Dimohon untuk menindaklanjuti surat dengan memberikan surat ke bagian yang dituju.</p>
    <a class="b b-sm" href="notifikasi.php?buka=<?= (int)$banner['id'] ?>">Lihat Surat</a>
  </div>
  <button type="button" class="notice-x" data-dismiss=".notice" aria-label="Tutup"><?= ui_icon('x') ?></button>
</div>
<?php endif; ?>

<?= render_flash() ?>
<?= render_errors($errors) ?>

<section class="card">
  <h1 class="t-gold">Manajemen Surat</h1>
  <p>Tempat dalam mengelola berbagai surat baik itu pembuatan dan pengeluaran surat.</p>
</section>

<label class="drop" id="drop">
  <input type="file" name="berkas" id="berkas" form="fSurat" accept=".pdf,.jpg,.jpeg,.png" hidden>
  <?= ui_icon('upload') ?>
  <strong>Upload surat disini</strong>
  <small id="fileName"><?= $edit ? 'Biarkan kosong untuk tetap memakai berkas lama' : 'PDF, JPG, atau PNG — maksimal 5 MB' ?></small>
</label>

<div class="duo">
  <section class="card form-card">
    <h2 class="t-gold"><?= $edit ? 'Ubah Data Dokumen' : 'Data Dokumen' ?></h2>
    <form method="post" enctype="multipart/form-data" id="fSurat" class="stack">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $edit ? 'update' : 'upload' ?>">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
      <label>Nama Surat<input name="perihal" value="<?= e($in['perihal']) ?>" maxlength="200" required></label>
      <label>Pengirim<input name="pengirim" value="<?= e($in['pengirim']) ?>" maxlength="150" required></label>
      <label>Jenis Surat
        <select name="jenis" required><option value="">Pilih jenis</option>
          <?php foreach (JENIS as $j): ?><option<?= $in['jenis'] === $j ? ' selected' : '' ?>><?= e($j) ?></option><?php endforeach; ?>
        </select></label>
      <label>Sifat Surat
        <select name="sifat" required><?php foreach (SIFAT as $k => $v): ?><option value="<?= $k ?>"<?= $in['sifat'] === $k ? ' selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
      <label>Tanggal Surat<input type="date" name="tanggal_surat" value="<?= e($in['tanggal_surat']) ?>" required></label>
      <label>Nomor Surat<input name="nomor_surat" value="<?= e($in['nomor_surat']) ?>" maxlength="100" required></label>
      <div class="form-act">
        <button class="b" type="submit"><?= $edit ? 'Simpan' : 'Kirim' ?></button>
        <?php if ($edit): ?><a class="b b-ghost" href="manajemen.php">Batal</a><?php endif; ?>
      </div>
    </form>
  </section>

  <div class="side-col">
    <section class="card status-card">
      <small>Status surat saat ini</small>
      <?php if ($sel): ?>
        <div class="status-now"><?= ui_icon('hourglass') ?>
          <strong><?= $sel['status'] === 'verifikasi' ? 'MENUNGGU VERIFIKASI DARI SEKRETARIS - Pending' : e(strtoupper(status_teks($sel))) ?></strong></div>
        <p class="sel-ref"><?= e(kode_surat((int)$sel['id'])) ?> · <?= e($sel['perihal']) ?></p>
      <?php else: ?>
        <div class="status-now"><strong>Tidak ada surat yang menunggu verifikasi.</strong></div>
      <?php endif; ?>
    </section>
    <section class="card decide">
      <h3>Keputusan Verifikator</h3>
      <p><?= $sel && $sel['catatan_sistem'] ? e($sel['catatan_sistem']) . ' ' : '' ?>Telah dilakukan verifikasi berkas oleh sistem dan ditemukan beberapa masalah. Verifikator setelahnya mengecek ulang berkas yang diinput untuk mengetahui kesalahan surat.</p>
      <div class="decide-btns">
        <?php foreach (['tolak' => ['TOLAK', 'simpan ke arsip', 'x', 'Tolak surat ini dan simpan ke arsip?'], 'terima' => ['TERIMA', 'kirim surat ke pimpinan', 'play', 'Terima surat ini dan kirim ke pimpinan?']] as $act => [$lbl, $cap, $ic, $q2]): ?>
          <form method="post" data-confirm="<?= e($q2) ?>"><?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $act ?>"><input type="hidden" name="id" value="<?= (int)($sel['id'] ?? 0) ?>">
            <button type="submit" class="decide-b decide-<?= $act ?>"<?= $canDecide ? '' : ' disabled' ?>><?= ui_icon($ic) ?><b><?= $lbl ?></b><small><?= $cap ?></small></button>
          </form>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>

<form class="tools" method="get">
  <div class="search search-light"><?= ui_icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari Surat, Instansi, Nomor Surat..." aria-label="Cari surat"></div>
  <select name="sifat" class="b b-sel" onchange="this.form.submit()" aria-label="Filter sifat surat">
    <option value="">Sifat Surat</option><?php foreach (SIFAT as $k => $v): ?><option value="<?= $k ?>"<?= $fs === $k ? ' selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
  </select>
  <select name="jenis" class="b b-sel" onchange="this.form.submit()" aria-label="Filter jenis surat">
    <option value="">Jenis Surat</option><?php foreach (JENIS as $j): ?><option<?= $fj === $j ? ' selected' : '' ?>><?= e($j) ?></option><?php endforeach; ?>
  </select>
</form>

<section class="board">
  <div class="board-bar light"><span>Menampilkan <?= $total ? "$from-" . ($from + count($rows) - 1) : 0 ?> dari <?= $total ?> surat</span></div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>ID</th><th>Nomor Surat</th><th>Nama Surat &amp; Pengirim</th><th>Sifat</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e(kode_surat((int)$r['id'])) ?></td>
          <td><?= e($r['nomor_surat']) ?></td>
          <td><strong><?= e($r['perihal']) ?></strong><br><small><?= e($r['pengirim']) ?></small></td>
          <td><?= badge($r['sifat']) ?></td>
          <td class="aksi">
            <a class="icon-link" href="cek.php?id=<?= (int)$r['id'] ?>" aria-label="Lihat"><?= ui_icon('eye') ?></a>
            <?php if ($r['status'] === 'verifikasi'): ?>
              <a class="icon-link" href="manajemen.php?edit=<?= (int)$r['id'] ?>#fSurat" aria-label="Ubah"><?= ui_icon('edit') ?></a>
              <form method="post" data-confirm="Hapus surat ini?"><?= csrf_field() ?><input type="hidden" name="action" value="hapus"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="icon-link" type="submit" aria-label="Hapus"><?= ui_icon('trash') ?></button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="empty">Belum ada surat yang cocok.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="board-foot"><?= pagination($page, $pages) ?></div>
</section>
<?php sek_footer(); ?>
