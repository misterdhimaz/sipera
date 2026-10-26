<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/surat.php';
require_pimpinan();

$s = get_surat((int)($_GET['id'] ?? 0));
$path = $s && $s['file_path'] ? resolve_upload_path($s['file_path']) : null;
if (!$path || !is_file($path)) { http_response_code(404); exit('Berkas tidak ditemukan.'); }

$ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header("Content-Disposition: inline; filename*=UTF-8''" . rawurlencode($s['file_name'] ?: basename($path)));
readfile($path);
