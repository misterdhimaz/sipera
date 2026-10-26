<?php
function sek_header(string $title, string $active, array $user): void
{
    $initial = mb_strtoupper(mb_substr($user['nama'], 0, 1));
    $unread  = unread_count((int)$user['id']);
    $nav = fn(string $key, string $href, string $label) =>
        '<a href="' . $href . '" class="' . ($active === $key ? 'on' : '') . '"' . ($active === $key ? ' aria-current="page"' : '') . '>' . $label . '</a>';
    $suratOpen = in_array($active, ['manajemen', 'alur', 'tindak'], true);
    $menunggu  = (int)db()->query("SELECT COUNT(*) FROM surat WHERE status = 'dikirim'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> — SIPERA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/sekretaris.css">
</head>
<body class="app">
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <div class="sb-logo">SIPERA</div>
    <nav class="nav" aria-label="Menu utama">
      <?= $nav('beranda', 'beranda.php', ui_icon('home') . 'Beranda') ?>
      <details class="grp"<?= $suratOpen ? ' open' : '' ?>>
        <summary class="<?= $suratOpen ? 'on' : '' ?>"><?= ui_icon('file') ?>Surat<?= ui_icon('down') ?></summary>
        <div class="sub">
          <?= $nav('manajemen', 'manajemen.php', 'Manajemen Surat') ?>
          <?= $nav('alur', 'alur.php', 'Alur Distribusi') ?>
          <?= $nav('tindak', 'tindak_lanjut.php', 'Tindak Lanjut Divisi' . ($menunggu ? '<span class="nav-n">' . $menunggu . '</span>' : '')) ?>
        </div>
      </details>
      <?= $nav('arsip', 'arsip.php', ui_icon('grid') . 'Arsip') ?>
    </nav>
    <form method="post" action="../logout.php" class="sb-out"><?= csrf_field() ?>
      <button type="submit"><?= ui_icon('logout') ?>Keluar</button>
    </form>
  </aside>

  <div class="main-col">
    <header class="topbar-s">
      <button class="icon-btn menu-btn" type="button" data-menu aria-label="Buka menu"><?= ui_icon('menu') ?></button>
      <form class="search" method="get" action="manajemen.php" role="search">
        <?= ui_icon('search') ?><input type="search" name="q" placeholder="Cari..." value="<?= e($_GET['q'] ?? '') ?>" aria-label="Cari surat">
      </form>
      <div class="top-right">
        <a class="bell<?= $active === 'notif' ? ' on' : '' ?>" href="notifikasi.php" aria-label="Notifikasi<?= $unread ? ", $unread belum dibaca" : '' ?>">
          <?= ui_icon('bell') ?><?php if ($unread): ?><span class="dot"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
        </a>
        <div class="me"><span class="av"><?= e($initial) ?></span><span class="me-t"><?= e($user['nama']) ?><small>Sekretaris</small></span></div>
      </div>
    </header>
    <main class="content">
<?php
}

function sek_footer(array $opt = []): void
{
    $ok = $opt['sukses'] ?? null;          // [teks, url_lanjut, ikon = 'check'|'hourglass']
    $ik = $ok[2] ?? 'check';
?>
    </main>
  </div>
</div>

<div class="modal" id="mConfirm" hidden>
  <div class="modal-box" role="alertdialog" aria-modal="true" aria-labelledby="mText">
    <div class="halo halo-red"><span data-ic="alert"><?= ui_icon('alert') ?></span><span data-ic="trash" hidden><?= ui_icon('trash') ?></span></div>
    <p id="mText" data-text>Apakah Anda yakin?</p>
    <div class="modal-act"><button type="button" class="b" data-no>Batal</button><button type="button" class="b" data-yes>Ya</button></div>
  </div>
</div>

<div class="modal" id="mOk" hidden<?php if ($ok): ?> data-show data-next="<?= e($ok[1]) ?>" data-delay="<?= $ik === 'hourglass' ? 2400 : 1800 ?>"<?php endif; ?>>
  <div class="modal-box" role="status">
    <div class="halo <?= $ik === 'hourglass' ? 'halo-gray' : 'halo-green' ?>"><?= ui_icon($ik) ?></div>
    <p><?= nl2br(e($ok[0] ?? '')) ?></p>
  </div>
</div>
<script src="../assets/js/sekretaris.js"></script>
</body>
</html>
<?php
}
