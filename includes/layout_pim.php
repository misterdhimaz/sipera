<?php
require_once __DIR__ . '/layout_sek.php';

function pim_header(string $title, string $active, array $user): void
{
    $initial = mb_strtoupper(mb_substr($user['nama'], 0, 1));
    $unread  = unread_count((int)$user['id']);
    $nav = fn(string $key, string $href, string $icon, string $label) =>
        '<a href="' . $href . '" class="' . ($active === $key ? 'on' : '') . '"' . ($active === $key ? ' aria-current="page"' : '') . '>' . ui_icon($icon) . $label . '</a>';
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
      <?= $nav('beranda', 'beranda.php', 'home', 'Beranda') ?>
      <?= $nav('disposisi', 'disposisi.php', 'edit', 'Disposisi Digital') ?>
    </nav>
    <form method="post" action="../logout.php" class="sb-out"><?= csrf_field() ?>
      <button type="submit"><?= ui_icon('logout') ?>Keluar</button>
    </form>
  </aside>

  <div class="main-col">
    <header class="topbar-s">
      <button class="icon-btn menu-btn" type="button" data-menu aria-label="Buka menu"><?= ui_icon('menu') ?></button>
      <form class="search" method="get" action="beranda.php" role="search">
        <?= ui_icon('search') ?><input type="search" name="q" placeholder="Cari..." value="<?= e($_GET['q'] ?? '') ?>" aria-label="Cari surat">
      </form>
      <div class="top-right">
        <a class="bell<?= $active === 'notif' ? ' on' : '' ?>" href="notifikasi.php" aria-label="Notifikasi<?= $unread ? ", $unread belum dibaca" : '' ?>">
          <?= ui_icon('bell') ?><?php if ($unread): ?><span class="dot"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
        </a>
        <div class="me"><span class="av"><?= e($initial) ?></span><span class="me-t"><?= e($user['nama']) ?><small>Pimpinan</small></span></div>
      </div>
    </header>
    <main class="content">
<?php
}
