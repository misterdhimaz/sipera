<?php
// Helper modul Sekretaris: surat, distribusi, notifikasi, tampilan.

const SIFAT = ['biasa' => 'Biasa', 'penting' => 'Penting', 'segera' => 'Segera', 'rahasia' => 'Rahasia'];
const JENIS = ['Permohonan', 'Undangan', 'Pemberitahuan', 'Penawaran', 'Tugas', 'Kerjasama'];

function require_sekretaris(): array
{
    $u = current_user();
    if (!$u) redirect('../login.php');
    if ($u['role'] !== 'sekretaris') redirect('../dashboard.php');
    return $u;
}

function require_pimpinan(): array
{
    $u = current_user();
    if (!$u) redirect('../login.php');
    if ($u['role'] !== 'pimpinan') redirect('../dashboard.php');
    return $u;
}

/** Kirim notifikasi ke semua pengguna aktif dengan peran tertentu. */
function notify_role(string $role, string $tipe, string $pesan, string $link): void
{
    $st = db()->prepare('SELECT id FROM users WHERE role = ? AND is_verified = 1');
    $st->execute([$role]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $uid) notify((int)$uid, $tipe, $pesan, $link);
}

/** Lembar disposisi (kartu surat) — dipakai Sekretaris dan Pimpinan. */
function lembar_disposisi(array $s): string
{
    $ins = instansi();
    $tgl = date('d/m/Y', strtotime($s['tanggal_terima'] ?: $s['created_at']));
    $div = $s['divisi_nama'] ? 'Divisi ' . e(preg_replace('/^Divisi\s+/i', '', $s['divisi_nama'])) : '<i>Belum ditentukan</i>';
    $cat = $s['catatan_disposisi'] ? nl2br(e($s['catatan_disposisi'])) : '<i>Belum ada catatan disposisi.</i>';
    return '<section class="card paper" aria-label="Lembar disposisi">'
      . '<header class="paper-h"><span class="logo-o">AB</span><div><b>' . e($ins['nama']) . '</b><small>Sekretariat: ' . e($ins['alamat']) . '<br>Telepon: ' . e($ins['telp']) . '</small></div></header>'
      . '<table class="lembar">'
      . '<tr><td><b>Surat dari:</b> ' . e($s['pengirim']) . '</td><td><b>Diterima tgl:</b> ' . $tgl . '</td></tr>'
      . '<tr><td><b>No.Surat:</b><br>' . e($s['nomor_surat']) . '</td><td><b>Sifat:</b> ' . e(SIFAT[$s['sifat']]) . '</td></tr>'
      . '<tr><td><b>No.Agenda:</b> ' . (int)$s['no_agenda'] . '</td><td rowspan="2"><b>Diteruskan ke:</b><br>' . $div . '</td></tr>'
      . '<tr><td><b>Perihal:</b><br>' . e($s['perihal']) . '</td></tr>'
      . '<tr><td colspan="2" class="catatan"><b>Catatan:</b><br>' . $cat . '</td></tr>'
      . '</table></section>';
}

function instansi(): array
{
    $c = require __DIR__ . '/../config/config.php';
    return ($c['app'] ?? []) + [
        'nama' => 'PT. Mulia Bersama', 'alamat' => 'Jalan Pahlawan No.21', 'telp' => '08957728245',
    ];
}

function kode_surat(int $id): string { return 'SRT-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); }

function bulan_id(int $m): string
{
    return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$m];
}

function fmt_tanggal(?string $d): string
{
    if (!$d) return '-';
    $t = strtotime($d);
    return date('j', $t) . ' ' . bulan_id((int)date('n', $t)) . ' ' . date('Y', $t);
}

/** "Hari ini, 10:05 WIB" / "Rabu, 14:20 WIB" / "3 Oktober 2026, 09:10 WIB" */
function fmt_waktu(?string $dt): string
{
    if (!$dt) return '-';
    $t    = strtotime($dt);
    $days = (int)round((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', $t))) / 86400);
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][(int)date('w', $t)];
    $jam  = date('H:i', $t) . ' WIB';
    if ($days <= 0) return "Hari ini, $jam";
    if ($days === 1) return "Kemarin, $jam";
    if ($days < 7)  return "$hari, $jam";
    return fmt_tanggal($dt) . ', ' . $jam;
}

function status_teks(array $s): string
{
    $d = !empty($s['divisi_nama']) ? 'divisi ' . preg_replace('/^Divisi\s+/i', '', $s['divisi_nama']) : 'divisi tujuan';
    return match ($s['status']) {
        'verifikasi'         => 'Menunggu verifikasi sekretaris',
        'menunggu_disposisi' => 'Menunggu disposisi pimpinan',
        'disposisi'          => 'Belum ditindaklanjuti setelah didisposisi',
        'dikirim'            => "Menunggu tindak lanjut $d",
        'selesai'            => "Telah ditindaklanjuti oleh $d",
        'arsip'              => 'Sudah diarsipkan',
        default              => '-',
    };
}

function badge(string $sifat): string
{
    return '<span class="badge badge-' . e($sifat) . '">' . e(SIFAT[$sifat] ?? $sifat) . '</span>';
}

function get_surat(int $id): ?array
{
    $st = db()->prepare('SELECT s.*, d.nama AS divisi_nama FROM surat s LEFT JOIN divisi d ON d.id = s.divisi_id WHERE s.id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function divisi_list(): array { return db()->query('SELECT id, nama FROM divisi ORDER BY nama')->fetchAll(); }

function log_distribusi(int $suratId, string $ket): void
{
    db()->prepare('INSERT INTO distribusi_log (surat_id, keterangan) VALUES (?, ?)')->execute([$suratId, $ket]);
}

function notify(int $userId, string $tipe, string $pesan, string $link): void
{
    db()->prepare('INSERT INTO notifikasi (user_id, tipe, pesan, link) VALUES (?, ?, ?, ?)')->execute([$userId, $tipe, $pesan, $link]);
}

function unread_count(int $userId): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND is_read = 0');
    $st->execute([$userId]);
    return (int)$st->fetchColumn();
}

/** Catatan pemeriksaan otomatis oleh sistem saat surat diunggah. */
function catatan_sistem(string $nomor, string $tanggal, int $exceptId = 0): string
{
    $st = db()->prepare('SELECT id FROM surat WHERE nomor_surat = ? AND id <> ? LIMIT 1');
    $st->execute([$nomor, $exceptId]);
    $notes = [];
    if ($dupId = $st->fetchColumn()) $notes[] = 'Nomor surat sama dengan ' . kode_surat((int)$dupId) . ', periksa kemungkinan duplikat.';
    if (strtotime($tanggal) > time())  $notes[] = 'Tanggal surat melewati hari ini.';
    return $notes ? implode(' ', $notes) : 'Berkas lolos pemeriksaan sistem.';
}

function get_upload_dir(): string
{
    $dir = __DIR__ . '/../storage/uploads';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    if (is_dir($dir) && is_writable($dir)) return $dir;

    $tmp = sys_get_temp_dir() . '/sipera_uploads';
    if (!is_dir($tmp)) @mkdir($tmp, 0775, true);
    return $tmp;
}

function resolve_upload_path(?string $filename): ?string
{
    if (!$filename) return null;
    $base = basename($filename);
    $local = __DIR__ . '/../storage/uploads/' . $base;
    if (is_file($local)) return $local;

    $tmp = sys_get_temp_dir() . '/sipera_uploads/' . $base;
    if (is_file($tmp)) return $tmp;

    return null;
}

/** Simpan berkas unggahan ke storage/uploads atau tmp. Mengembalikan [nama_tersimpan, nama_asli] atau null. */
function simpan_berkas(array $f, ?string &$err): ?array
{
    $code = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($code === UPLOAD_ERR_NO_FILE) { $err = 'Pilih berkas surat terlebih dahulu.'; return null; }
    if ($code !== UPLOAD_ERR_OK)      { $err = 'Berkas gagal diunggah.'; return null; }
    if ($f['size'] > 5 * 1024 * 1024) { $err = 'Ukuran berkas maksimal 5 MB.'; return null; }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext  = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
    if (!$ext) { $err = 'Format berkas harus PDF, JPG, atau PNG.'; return null; }
    $dir = get_upload_dir();
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) { $err = 'Berkas gagal disimpan.'; return null; }
    return [$name, mb_substr(basename($f['name']), 0, 200)];
}

function hapus_berkas(?string $path): void
{
    if ($path && !str_starts_with($path, 'demo-')) {
        $resolved = resolve_upload_path($path);
        if ($resolved && is_file($resolved)) @unlink($resolved);
    }
}

function pagination(int $page, int $pages): string
{
    $pages = max(1, $pages);
    $url = fn(int $p) => e('?' . http_build_query(array_merge($_GET, ['p' => $p])));
    $h = '<nav class="pager" aria-label="Halaman">';
    $h .= $page > 1 ? '<a class="pg" href="' . $url($page - 1) . '" aria-label="Sebelumnya">' . ui_icon('left') . '</a>'
                    : '<span class="pg dis">' . ui_icon('left') . '</span>';
    $to = min($pages, max(1, $page - 2) + 4);
    for ($i = max(1, $to - 4); $i <= $to; $i++) {
        $h .= '<a class="pg' . ($i === $page ? ' on' : '') . '" href="' . $url($i) . '">' . $i . '</a>';
    }
    $h .= $page < $pages ? '<a class="pg" href="' . $url($page + 1) . '" aria-label="Berikutnya">' . ui_icon('right') . '</a>'
                         : '<span class="pg dis">' . ui_icon('right') . '</span>';
    return $h . '</nav>';
}

function ui_icon(string $name): string
{
    static $p = [
        'home'   => 'M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z',
        'file'   => 'M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z',
        'grid'   => 'M4 8h4V4H4v4zm6 12h4v-4h-4v4zm-6 0h4v-4H4v4zm0-6h4v-4H4v4zm6 0h4v-4h-4v4zm6-10v4h4V4h-4zm-6 4h4V4h-4v4zm6 6h4v-4h-4v4zm0 6h4v-4h-4v4z',
        'logout' => 'M10.1 15.6 11.500 17l5-5-5-5-1.400 1.400 2.600 2.600H3v2h9.700l-2.600 2.600zM19 3H5a2 2 0 0 0-2 2v4h2V5h14v14H5v-4H3v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z',
        'down'   => 'M16.6 8.6 12 13.200 7.400 8.600 6 10l6 6 6-6z',
        'search' => 'M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z',
        'bell'   => 'M12 22c1.100 0 2-.9 2-2h-4c0 1.100.9 2 2 2zm6-6v-5c0-3.100-1.600-5.600-4.500-6.300V4a1.500 1.500 0 0 0-3 0v.7C7.600 5.400 6 7.900 6 11v5l-2 2v1h16v-1l-2-2z',
        'upload' => 'M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z',
        'download' => 'M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z',
        'hourglass' => 'M6 2v6l4 4-4 4v6h12v-6l-4-4 4-4V2H6zm10 14.500V20H8v-3.500l4-4 4 4zm-4-5-4-4V4h8v3.500l-4 4z',
        'x'      => 'M19 6.400 17.600 5 12 10.600 6.400 5 5 6.400 10.600 12 5 17.600 6.400 19 12 13.400 17.600 19 19 17.600 13.400 12z',
        'play'   => 'M8 5v14l11-7z',
        'eye'    => 'M12 4.500C7 4.500 2.700 7.600 1 12c1.700 4.400 6 7.500 11 7.500s9.300-3.100 11-7.500c-1.700-4.400-6-7.500-11-7.500zM12 17a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z',
        'edit'   => 'M3 17.250V21h3.750L17.800 9.940l-3.750-3.750L3 17.250zM20.700 7.040a1 1 0 0 0 0-1.410l-2.340-2.340a1 1 0 0 0-1.410 0l-1.830 1.830 3.750 3.750 1.830-1.830z',
        'trash'  => 'M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.500l-1-1h-5l-1 1H5v2h14V4z',
        'clock'  => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16zm.5-13H11v6l5.250 3.150.75-1.230-4.500-2.670V7z',
        'check'  => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm-2 15-5-5 1.400-1.400L10 14.200l7.600-7.600L19 8l-9 9z',
        'alert'  => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z',
        'history'=> 'M13 3a9 9 0 0 0-9 9H1l3.900 3.900.1.100L9 12H6a7 7 0 1 1 2.050 4.950l-1.420 1.420A9 9 0 1 0 13 3zm-1 5v5l4.300 2.540.7-1.210-3.500-2.080V8H12z',
        'filter' => 'M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z',
        'mail'   => 'M20 4H4a2 2 0 0 0-2 2l-.01 12a2 2 0 0 0 2 2H20a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z',
        'menu'   => 'M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z',
        'left'   => 'M15.400 7.400 14 6l-6 6 6 6 1.400-1.400L10.800 12z',
        'right'  => 'M10 6 8.600 7.400 13.200 12l-4.600 4.600L10 18l6-6z',
    ];
    return '<svg class="ui" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="' . ($p[$name] ?? '') . '"/></svg>';
}
