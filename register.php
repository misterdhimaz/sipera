<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('dashboard.php');

$errors = [];
$old = ['nama' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nama  = trim($_POST['nama'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $conf  = $_POST['konfirmasi'] ?? '';
    $old   = compact('nama', 'email');

    if ($nama === '' || mb_strlen($nama) > 100)        $errors[] = 'Nama wajib diisi (maksimal 100 karakter).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'Format email tidak valid.';
    if (strlen($pass) < 8)                             $errors[] = 'Password minimal 8 karakter.';
    if ($pass !== $conf)                               $errors[] = 'Konfirmasi password tidak cocok.';

    if (!$errors) {
        $row  = find_user_by_email($email);
        if ($row && $row['is_verified']) {
            $errors[] = 'Email sudah terdaftar. Silakan login.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            if ($row) { // akun belum aktif: perbarui data & kirim OTP baru
                db()->prepare('UPDATE users SET nama = ?, password = ? WHERE id = ?')->execute([$nama, $hash, $row['id']]);
                $id = (int)$row['id'];
            } else {    // akun baru selalu berperan Sekretaris
                db()->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'sekretaris')")
                    ->execute([$nama, $email, $hash]);
                $id = db_last_insert_id('users');
            }
            $_SESSION['pending_email'] = $email;
            if (issue_otp($id)) flash('success', 'Kode OTP telah dikirim ke email Anda.');
            else                flash('error', 'Akun dibuat, tetapi email gagal terkirim. Klik "Kirim Ulang".');
            redirect('verify.php');
        }
    }
}

$title = 'Register';
require __DIR__ . '/includes/auth_header.php';
?>
      <h1>Register</h1>
      <?= render_errors($errors) ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <?= field('text', 'nama', 'Nama', $old['nama'], 'user', 'name') ?>
        <?= field('email', 'email', 'Email', $old['email'], 'mail', 'email') ?>
        <?= field('password', 'password', 'Password', '', null, 'new-password') ?>
        <?= field('password', 'konfirmasi', 'Konfirmasi Password', '', null, 'new-password') ?>
        <button class="btn" type="submit">Daftar</button>
      </form>
      <p class="muted center">Memiliki akun? <a href="login.php">Login</a></p>
<?php require __DIR__ . '/includes/auth_footer.php'; ?>
