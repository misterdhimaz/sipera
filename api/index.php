<?php
/**
 * Entry point & Router untuk Vercel Serverless Functions (@vercel/php)
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = trim($uri, '/');

// 1. Root -> index.php
if ($uri === '' || $uri === 'index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

$baseDir = realpath(__DIR__ . '/..');

// 2. Cek jika path langsung mengarah ke file PHP yang valid
$candidate = realpath($baseDir . '/' . $uri);
if ($candidate && str_starts_with($candidate, $baseDir) && is_file($candidate)) {
    if (pathinfo($candidate, PATHINFO_EXTENSION) === 'php') {
        chdir(dirname($candidate));
        require $candidate;
        exit;
    }
}

// 3. Cek jika path tanpa ekstensi .php (misal /login -> /login.php atau /pimpinan/beranda)
$candidatePhp = realpath($baseDir . '/' . $uri . '.php');
if ($candidatePhp && str_starts_with($candidatePhp, $baseDir) && is_file($candidatePhp)) {
    chdir(dirname($candidatePhp));
    require $candidatePhp;
    exit;
}

// 4. File tidak ditemukan
http_response_code(404);
echo "404 Not Found: " . htmlspecialchars($uri, ENT_QUOTES, 'UTF-8');

