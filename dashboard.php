<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
redirect($user['role'] === 'pimpinan' ? 'pimpinan/beranda.php' : 'sekretaris/beranda.php');
