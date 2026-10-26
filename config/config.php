<?php
// Konfigurasi SIPERA — otomatis membaca Environment Variables jika ada (Vercel/Supabase),
// atau fallback ke konfigurasi lokal jika ENV tidak diset.

return [
    'db' => [
        'driver'   => getenv('DB_DRIVER') ?: 'mysql',                     // 'mysql' atau 'pgsql'
        'host'     => getenv('DB_HOST') ?: 'localhost',
        'port'     => (int)(getenv('DB_PORT') ?: (getenv('DB_DRIVER') === 'pgsql' ? 5432 : 3306)),
        'name'     => getenv('DB_NAME') ?: 'sipera',
        'user'     => getenv('DB_USER') ?: 'root',
        'pass'     => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'sslmode'  => getenv('DB_SSLMODE') ?: 'require',                   // 'require' untuk Supabase
    ],
    'mail' => [
        'host'       => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
        'port'       => (int)(getenv('MAIL_PORT') ?: 587),
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',               // 'tls' (587) atau 'ssl' (465)
        'username'   => getenv('MAIL_USERNAME') ?: 'kucingoki3344@gmail.com',
        'password'   => getenv('MAIL_PASSWORD') ?: 'lmvs afvr grkb gzbn',
        'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'kucingoki3344@gmail.com',
        'from_name'  => getenv('MAIL_FROM_NAME') ?: 'SIPERA',
    ],
    'app' => [
        'nama'   => 'PT. Mulia Bersama',
        'alamat' => 'Jalan Pahlawan No.21',
        'telp'   => '08957728245',
    ],
    'otp' => [
        'length'          => 4,
        'ttl'             => 600,
        'max_attempts'    => 5,
        'resend_cooldown' => 60,
    ],
];
