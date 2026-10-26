<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

date_default_timezone_set('Asia/Jakarta');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);

class DbSessionHandler implements SessionHandlerInterface
{
    private bool $available = true;

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        if (!$this->available) return '';
        try {
            $st = db()->prepare('SELECT data FROM user_sessions WHERE id = ? AND expires_at > NOW()');
            $st->execute([$id]);
            $col = $st->fetchColumn();
            return $col !== false ? (string)$col : '';
        } catch (\Throwable $e) {
            $this->available = false;
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        if (!$this->available) return true;
        try {
            $exp = date('Y-m-d H:i:s', time() + 86400 * 7);
            $driver = db()->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'pgsql') {
                $sql = 'INSERT INTO user_sessions (id, data, expires_at) VALUES (?, ?, ?)
                        ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, expires_at = EXCLUDED.expires_at';
            } else {
                $sql = 'INSERT INTO user_sessions (id, data, expires_at) VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)';
            }
            db()->prepare($sql)->execute([$id, $data, $exp]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        if (!$this->available) return true;
        try {
            db()->prepare('DELETE FROM user_sessions WHERE id = ?')->execute([$id]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        if (!$this->available) return 0;
        try {
            $st = db()->prepare('DELETE FROM user_sessions WHERE expires_at < NOW()');
            $st->execute();
            return $st->rowCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

if (getenv('VERCEL') || getenv('DB_DRIVER') === 'pgsql') {
    session_set_save_handler(new DbSessionHandler(), true);
}
session_start();

function cfg(string $group): array
{
    static $c;
    $c ??= require __DIR__ . '/../config/config.php';
    return $c[$group];
}

function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $c = cfg('db');
        $driver = $c['driver'] ?? 'mysql';
        if ($driver === 'pgsql') {
            $port = $c['port'] ?: 5432;
            $ssl  = $c['sslmode'] ? ";sslmode={$c['sslmode']}" : '';
            $dsn  = "pgsql:host={$c['host']};port={$port};dbname={$c['name']}{$ssl}";
        } else {
            $port = $c['port'] ?: 3306;
            $dsn  = "mysql:host={$c['host']};port={$port};dbname={$c['name']};charset=utf8mb4";
        }
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        if ($driver === 'pgsql') {
            $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
        } else {
            $pdo->exec("SET time_zone = '" . date('P') . "'");
        }
    }
    return $pdo;
}

function db_last_insert_id(string $table, string $col = 'id'): int
{
    $pdo = db();
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
        return (int)$pdo->lastInsertId("{$table}_{$col}_seq");
    }
    return (int)$pdo->lastInsertId();
}

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function redirect(string $url): never { header("Location: $url"); exit; }

/* ---------- Flash message ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }

function render_flash(): string
{
    if (empty($_SESSION['flash'])) return '';
    [$type, $msg] = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="alert alert-' . e($type) . '" role="alert">' . e($msg) . '</div>';
}

function render_errors(array $errors): string
{
    if (!$errors) return '';
    return '<div class="alert alert-error" role="alert">' . implode('<br>', array_map('e', $errors)) . '</div>';
}

/* ---------- CSRF ---------- */
function csrf_field(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}

function csrf_verify(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Sesi tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

/* ---------- Auth ---------- */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    $st = db()->prepare('SELECT id, nama, email, role FROM users WHERE id = ? AND is_verified = 1');
    $st->execute([$_SESSION['user_id']]);
    return $st->fetch() ?: null;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) redirect('login.php');
    return $u;
}

/* ---------- OTP ---------- */
function find_user_by_email(string $email): ?array
{
    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([$email]);
    return $st->fetch() ?: null;
}

function otp_cooldown_left(array $user): int
{
    if (empty($user['otp_sent_at'])) return 0;
    return max(0, cfg('otp')['resend_cooldown'] - (time() - strtotime($user['otp_sent_at'])));
}

/** Buat OTP baru, simpan hash-nya, lalu kirim lewat email. */
function issue_otp(int $userId): bool
{
    $o   = cfg('otp');
    $otp = str_pad((string)random_int(0, 10 ** $o['length'] - 1), $o['length'], '0', STR_PAD_LEFT);

    $st = db()->prepare('UPDATE users SET otp_hash = ?, otp_expires_at = ?, otp_attempts = 0, otp_sent_at = ? WHERE id = ?');
    $st->execute([
        password_hash($otp, PASSWORD_DEFAULT),
        date('Y-m-d H:i:s', time() + $o['ttl']),
        date('Y-m-d H:i:s'),
        $userId,
    ]);

    $u = db()->prepare('SELECT nama, email FROM users WHERE id = ?');
    $u->execute([$userId]);
    $user = $u->fetch();

    return send_otp_mail($user['email'], $user['nama'], $otp);
}

function send_otp_mail(string $to, string $name, string $otp): bool
{
    $m = cfg('mail');
    $minutes = (int)(cfg('otp')['ttl'] / 60);
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $m['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $m['username'];
        $mail->Password   = $m['password'];
        $mail->SMTPSecure = $m['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$m['port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($m['from_email'], $m['from_name']);
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Kode OTP SIPERA';
        $mail->Body = '
        <div style="font-family:Arial,sans-serif;max-width:420px;margin:auto;padding:24px;border:1px solid #eee;border-radius:8px">
          <h2 style="color:#6b0f1a;margin:0 0 12px">SIPERA</h2>
          <p>Halo ' . e($name) . ',</p>
          <p>Gunakan kode berikut untuk mengaktifkan akun Anda:</p>
          <p style="font-size:32px;letter-spacing:10px;font-weight:bold;color:#6b0f1a;margin:16px 0">' . e($otp) . '</p>
          <p style="color:#666;font-size:13px">Kode berlaku ' . $minutes . ' menit. Jangan bagikan kode ini kepada siapa pun.</p>
        </div>';
        $mail->AltBody = "Kode OTP SIPERA Anda: $otp (berlaku $minutes menit).";
        $mail->send();
        return true;
    } catch (MailException $ex) {
        error_log('SIPERA mail error: ' . $mail->ErrorInfo);
        return false;
    }
}

/* ---------- Komponen UI ---------- */
function icon(string $name): string
{
    $paths = [
        'user' => '<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-3.3 0-8 1.7-8 5v1h16v-1c0-3.3-4.7-5-8-5Z"/>',
        'mail' => '<path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm9 7L4.5 7v1.4L12 13.4l7.5-5V7L12 12Z"/>',
        'eye'  => '<path d="M12 5C6.5 5 2.7 9 1.5 12c1.2 3 5 7 10.500 7s9.300-4 10.500-7C21.300 9 17.500 5 12 5Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0-6a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>',
    ];
    return '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">' . $paths[$name] . '</svg>';
}

function field(string $type, string $name, string $placeholder, string $value = '', ?string $icon = null, string $auto = ''): string
{
    $left = $icon ? icon($icon) : '';
    $eye  = $type === 'password'
        ? '<button type="button" class="toggle-pass" aria-label="Tampilkan password">' . icon('eye') . '</button>' : '';
    return '<div class="field' . ($icon ? ' has-icon' : '') . '">' . $left
        . '<input type="' . $type . '" name="' . $name . '" placeholder="' . e($placeholder) . '" value="' . e($value) . '"'
        . ($auto ? ' autocomplete="' . $auto . '"' : '') . ' required>' . $eye . '</div>';
}
