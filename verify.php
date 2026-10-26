<?php
require __DIR__ . '/includes/bootstrap.php';

$email = $_SESSION['pending_email'] ?? '';
$user  = $email ? find_user_by_email($email) : null;
if (!$user || $user['is_verified']) redirect('login.php');

$errors = [];
$len    = cfg('otp')['length'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? 'verify';

    if ($action === 'resend') {
        $left = otp_cooldown_left($user);
        if ($left > 0)                         flash('error', "Tunggu $left detik sebelum mengirim ulang kode.");
        elseif (issue_otp((int)$user['id']))   flash('success', 'Kode OTP baru telah dikirim ke email Anda.');
        else                                   flash('error', 'Email gagal terkirim. Periksa konfigurasi SMTP.');
        redirect('verify.php');
    }

    $otp = preg_replace('/\D/', '', $_POST['otp'] ?? '');
    if (strlen($otp) !== $len)                                          $errors[] = "Masukkan $len digit kode OTP.";
    elseif (!$user['otp_hash'] || strtotime($user['otp_expires_at']) < time()) $errors[] = 'Kode OTP sudah kedaluwarsa. Klik "Kirim Ulang".';
    elseif ($user['otp_attempts'] >= cfg('otp')['max_attempts'])        $errors[] = 'Terlalu banyak percobaan salah. Klik "Kirim Ulang" untuk kode baru.';
    elseif (!password_verify($otp, $user['otp_hash'])) {
        db()->prepare('UPDATE users SET otp_attempts = otp_attempts + 1 WHERE id = ?')->execute([$user['id']]);
        $errors[] = 'Kode OTP salah.';
    } else {
        db()->prepare('UPDATE users SET is_verified = 1, otp_hash = NULL, otp_expires_at = NULL, otp_attempts = 0 WHERE id = ?')
            ->execute([$user['id']]);
        unset($_SESSION['pending_email']);
        flash('success', 'Akun berhasil diaktifkan. Silakan login.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verifikasi Kode — SIPERA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="verify-page">
  <main class="verify-card">
    <h1>Verifikasi Kode</h1>
    <p class="lead">Tolong masukkan kode OTP yang telah kami<br>kirim ke <?= e($email) ?></p>
    <?= render_flash() ?>
    <?= render_errors($errors) ?>

    <form id="otpForm" method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="verify">
      <input type="hidden" name="otp" id="otpValue">
      <div class="otp" data-length="<?= $len ?>">
        <?php for ($i = 0; $i < $len; $i++): ?>
          <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit <?= $i + 1 ?>">
        <?php endfor; ?>
      </div>
    </form>

    <form method="post" class="resend">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="resend">
      Tidak mendapat kode? <button type="submit" class="link">Kirim Ulang</button>
    </form>

    <button class="btn" type="submit" form="otpForm">Kirim</button>
    <p class="muted center"><a href="login.php">Kembali ke login</a></p>
  </main>
  <script src="assets/js/app.js"></script>
</body>
</html>
