<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/surat.php';
$user = require_sekretaris();

$s = get_surat((int)($_GET['id'] ?? 0));
$path = $s && $s['file_path'] ? resolve_upload_path($s['file_path']) : null;
if (!$path || !is_file($path)) { http_response_code(404); exit('Berkas tidak ditemukan.'); }

$dl = isset($_GET['dl']);
if ($dl) db()->prepare('INSERT INTO unduhan (surat_id, user_id) VALUES (?, ?)')->execute([$s['id'], $user['id']]);

$ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream';
$name = $s['file_name'] ?: basename($path);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . ($dl ? 'attachment' : 'inline') . "; filename*=UTF-8''" . rawurlencode($name));
readfile($path);
