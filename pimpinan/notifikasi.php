<?php
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../includes/surat.php";
require __DIR__ . "/../includes/layout_pim.php";
$user = require_pimpinan();
$pdo  = db();

$uid = (int)$user['id'];

if (isset($_GET['buka'])) {   // buka notifikasi: tandai dibaca lalu menuju tautannya
    $st = $pdo->prepare('SELECT * FROM notifikasi WHERE id = ? AND user_id = ?');
    $st->execute([(int)$_GET['buka'], $uid]);
    if ($n = $st->fetch()) {
        $pdo->prepare('UPDATE notifikasi SET is_read = 1 WHERE id = ?')->execute([$n['id']]);
        redirect(preg_match('#^[a-z]+\.php(\?[\w=&]*)?$#', $n['link']) ? $n['link'] : 'notifikasi.php');
    }
    redirect('notifikasi.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'hapus') { $pdo->prepare('DELETE FROM notifikasi WHERE user_id = ?')->execute([$uid]); redirect('notifikasi.php?dihapus=1'); }
    if (($_POST['action'] ?? '') === 'dibaca') $pdo->prepare('UPDATE notifikasi SET is_read = 1 WHERE user_id = ?')->execute([$uid]);
    redirect('notifikasi.php');
}
$st = $pdo->prepare('SELECT * FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 100');
$st->execute([$uid]);
$list = $st->fetchAll();

pim_header('Notifikasi', 'notif', $user);
?>
<section class="card notif">
  <div class="notif-h">
    <h1>Notifikasi</h1>
    <div class="notif-act">
      <form method="post" data-confirm="Anda yakin ingin menghapus semua pesan?" data-icon="trash"><?= csrf_field() ?><input type="hidden" name="action" value="hapus"><button class="b b-xs" type="submit">Hapus Semua</button></form>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="dibaca"><button class="b b-xs" type="submit">Tandai telah dibaca</button></form>
    </div>
  </div>
  <ul class="notif-list">
    <?php foreach ($list as $n): ?>
      <li class="<?= $n['is_read'] ? '' : 'unread' ?>">
        <a href="?buka=<?= (int)$n['id'] ?>"><span><?= e($n['pesan']) ?></span><time><?= e(fmt_waktu($n['created_at'])) ?></time></a>
      </li>
    <?php endforeach; ?>
    <?php if (!$list): ?><li class="empty">Belum ada notifikasi.</li><?php endif; ?>
  </ul>
</section>
<?php sek_footer(isset($_GET['dihapus']) ? ['sukses' => ['Pesan berhasil dihapus!', 'notifikasi.php']] : []); ?>
