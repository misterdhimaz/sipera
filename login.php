<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('dashboard.php');

$errors = [];
$email  = '';
const MAX_FAIL = 5;
const LOCK_SEC = 60;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $lock  = $_SESSION['login_lock'] ?? 0;

    if ($lock > time()) {
        $errors[] = 'Terlalu banyak percobaan. Coba lagi dalam ' . ($lock - time()) . ' detik.';
    } else {
        $user = find_user_by_email($email);
        if (!$user || !password_verify($pass, $user['password'])) {
            $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
            if ($_SESSION['login_fail'] >= MAX_FAIL) {
                $_SESSION['login_lock'] = time() + LOCK_SEC;
                $_SESSION['login_fail'] = 0;
            }
            $errors[] = 'Email atau password salah.';
        } elseif (!$user['is_verified']) {
            $_SESSION['pending_email'] = $email;
            if (otp_cooldown_left($user) === 0) issue_otp((int)$user['id']);
            flash('error', 'Akun belum diaktifkan. Masukkan kode OTP yang dikirim ke email Anda.');
            redirect('verify.php');
        } else {
            session_regenerate_id(true);
            unset($_SESSION['login_fail'], $_SESSION['login_lock']);
            $_SESSION['user_id'] = (int)$user['id'];
            redirect('dashboard.php');
        }
    }
}

$title = 'Login';
require __DIR__ . '/includes/auth_header.php';
?>
      <h1>Login</h1>
      <?= render_flash() ?>
      <?= render_errors($errors) ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <?= field('email', 'email', 'Email', $email, 'user', 'email') ?>
        <?= field('password', 'password', 'Password', '', null, 'current-password') ?>
        <div class="right"><a class="small" href="#">Lupa Password?</a></div>
        <button class="btn" type="submit">Masuk</button>
      </form>
      <p class="muted center">Belum memiliki akun? <a href="register.php">Daftar</a></p>
<?php require __DIR__ . '/includes/auth_footer.php'; ?>
