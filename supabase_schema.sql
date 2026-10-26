-- =================================================================
-- SIPERA Schema & Data for Supabase (PostgreSQL)
-- Jalankan script ini langsung di Supabase SQL Editor
-- =================================================================

-- 1. Hapus tabel lama jika ada (cascade)
DROP TABLE IF EXISTS user_sessions CASCADE;
DROP TABLE IF EXISTS unduhan CASCADE;
DROP TABLE IF EXISTS notifikasi CASCADE;
DROP TABLE IF EXISTS distribusi_log CASCADE;
DROP TABLE IF EXISTS surat CASCADE;
DROP TABLE IF EXISTS divisi CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- 2. Buat tabel
CREATE TABLE users (
  id SERIAL PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(30) NOT NULL DEFAULT 'sekretaris' CHECK (role IN ('sekretaris','pimpinan')),
  is_verified SMALLINT NOT NULL DEFAULT 0,
  otp_hash VARCHAR(255) NULL,
  otp_expires_at TIMESTAMP NULL,
  otp_attempts SMALLINT NOT NULL DEFAULT 0,
  otp_sent_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE divisi (
  id SERIAL PRIMARY KEY,
  nama VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE surat (
  id SERIAL PRIMARY KEY,
  nomor_surat VARCHAR(100) NOT NULL,
  perihal VARCHAR(200) NOT NULL,
  pengirim VARCHAR(150) NOT NULL,
  jenis VARCHAR(50) NOT NULL,
  sifat VARCHAR(20) NOT NULL DEFAULT 'biasa' CHECK (sifat IN ('biasa','penting','segera','rahasia')),
  tanggal_surat DATE NOT NULL,
  tanggal_terima DATE NULL,
  no_agenda INT NOT NULL DEFAULT 0,
  file_path VARCHAR(255) NULL,
  file_name VARCHAR(200) NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'verifikasi' CHECK (status IN ('verifikasi','menunggu_disposisi','disposisi','dikirim','selesai','arsip')),
  catatan_sistem TEXT NULL,
  catatan_disposisi TEXT NULL,
  disposisi_draft SMALLINT NOT NULL DEFAULT 0,
  disposisi_at TIMESTAMP NULL,
  divisi_id INT NULL REFERENCES divisi(id) ON DELETE SET NULL,
  retensi VARCHAR(20) NOT NULL DEFAULT 'aktif' CHECK (retensi IN ('aktif','inaktif','permanen')),
  retensi_tahun SMALLINT NULL,
  alasan_arsip VARCHAR(100) NULL,
  tindak_lanjut_catatan TEXT NULL,
  tindak_lanjut_oleh VARCHAR(100) NULL,
  tindak_lanjut_at TIMESTAMP NULL,
  created_by INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_surat_status ON surat(status);
CREATE INDEX idx_surat_nomor ON surat(nomor_surat);

CREATE TABLE distribusi_log (
  id SERIAL PRIMARY KEY,
  surat_id INT NOT NULL REFERENCES surat(id) ON DELETE CASCADE,
  keterangan VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_distribusi_surat_id ON distribusi_log(surat_id);

CREATE TABLE notifikasi (
  id SERIAL PRIMARY KEY,
  user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  tipe VARCHAR(30) NOT NULL,
  pesan TEXT NOT NULL,
  link VARCHAR(255) NOT NULL,
  is_read SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_notifikasi_user ON notifikasi(user_id, is_read);

CREATE TABLE unduhan (
  id SERIAL PRIMARY KEY,
  surat_id INT NOT NULL REFERENCES surat(id) ON DELETE CASCADE,
  user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Tabel penyimpanan session PHP agar login tidak hilang di Vercel (Serverless)
CREATE TABLE user_sessions (
  id VARCHAR(128) PRIMARY KEY,
  data TEXT NOT NULL,
  expires_at TIMESTAMP NOT NULL
);
CREATE INDEX idx_sessions_expires ON user_sessions(expires_at);

-- 3. Data Awal (Seed Data)
-- Data: users
INSERT INTO users (id, nama, email, password, role, is_verified, otp_hash, otp_expires_at, otp_attempts, otp_sent_at, created_at) VALUES ('1', 'Andika Sanjaya', 'pimpinan@sipera.test', '$2y$10$yu7Gt9He08U0lDSHHuUKneZ.FeNpRRPuRV9bV0up2nA.b15Pyp5ta', 'pimpinan', '1', NULL, NULL, '0', NULL, '2026-10-26 23:23:32');
INSERT INTO users (id, nama, email, password, role, is_verified, otp_hash, otp_expires_at, otp_attempts, otp_sent_at, created_at) VALUES ('2', 'Sartika Putri', 'sekretaris@sipera.test', '$2y$10$nD3OdmAoqgr1.bEgc4IiQ.nkgxBt4HANi4tYzMt.Q4kbcpwH9Ua56', 'sekretaris', '1', NULL, NULL, '0', NULL, '2026-10-26 23:23:32');
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));

-- Data: divisi
INSERT INTO divisi (id, nama) VALUES ('1', 'Humas');
INSERT INTO divisi (id, nama) VALUES ('2', 'HRD');
INSERT INTO divisi (id, nama) VALUES ('3', 'Keuangan');
INSERT INTO divisi (id, nama) VALUES ('4', 'Operasional');
INSERT INTO divisi (id, nama) VALUES ('5', 'Hukum');
INSERT INTO divisi (id, nama) VALUES ('6', 'IT');
SELECT setval('divisi_id_seq', (SELECT MAX(id) FROM divisi));

-- Data: surat
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('1', '001/C/PTABDI/IX/2026', 'Undangan Rapat Implementasi Sistem AEChaR', 'PT. Teknologi Berkarya', 'Undangan', 'penting', '2026-10-23', '2026-10-23', '121', 'demo-surat.pdf', 'Undangan Rapat Implementasi Sistem AEChaR.pdf', 'verifikasi', 'Nomor surat belum dapat diverifikasi otomatis. Periksa kembali berkas.', NULL, '0', NULL, NULL, 'aktif', NULL, NULL, '2', '2026-10-26 21:18:35', '2026-10-26 21:18:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('2', '015/PersadaJaya/IX/2026', 'Permohonan Kerjasama Proyek Jangka Panjang PT. Angkasa Permata', 'PT. Persada Jaya', 'Permohonan', 'penting', '2026-10-23', '2026-10-23', '122', 'demo-surat.pdf', 'Permohonan Kerjasama Proyek Jangka Panjang PT. Angkasa Permata.pdf', 'verifikasi', 'Berkas lolos pemeriksaan sistem.', NULL, '0', NULL, NULL, 'aktif', NULL, NULL, '2', '2026-10-26 20:03:35', '2026-10-26 20:03:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('3', '012/AK.FE/MNA/IX/2026', 'Surat Permohonan Sponsor ke PT. Angkasa Permata', 'CV. Ramah Asia', 'Permohonan', 'rahasia', '2026-10-22', '2026-10-22', '123', 'demo-surat.pdf', 'Surat Permohonan Sponsor ke PT. Angkasa Permata.pdf', 'verifikasi', 'Tanggal surat tidak terbaca jelas pada berkas.', NULL, '0', NULL, NULL, 'aktif', NULL, NULL, '2', '2026-10-25 22:23:35', '2026-10-25 22:23:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('4', '85/PTABDI/VIII/2026', 'Surat Permohonan Dana Produksi Meja', 'PT. Abdi Jaya', 'Permohonan', 'penting', '2026-10-17', '2026-10-17', '118', 'demo-surat.pdf', 'Surat Permohonan Dana Produksi Meja.pdf', 'verifikasi', 'Berkas lolos pemeriksaan sistem.', NULL, '0', NULL, NULL, 'aktif', NULL, NULL, '2', '2026-10-24 23:03:35', '2026-10-24 23:03:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('5', '12/CVJAYA/IX/2026', 'Undangan Rapat Koordinasi Divisi', 'CV. Jaya Sembada', 'Undangan', 'rahasia', '2026-10-20', '2026-10-20', '119', 'demo-surat.pdf', 'Undangan Rapat Koordinasi Divisi.pdf', 'verifikasi', 'Berkas lolos pemeriksaan sistem.', NULL, '0', NULL, NULL, 'aktif', NULL, NULL, '2', '2026-10-24 19:43:35', '2026-10-24 19:43:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('6', '30/PTARTHA/IX/2026', 'Surat Permohonan Kerjasama dengan Bank Sumselbabel', 'PT. Artha Elektronik', 'Kerjasama', 'penting', '2026-10-19', '2026-10-19', '117', 'demo-surat.pdf', 'Surat Permohonan Kerjasama dengan Bank Sumselbabel.pdf', 'menunggu_disposisi', 'Berkas lolos pemeriksaan sistem.', NULL, '0', NULL, NULL, 'aktif', NULL, NULL, '2', '2026-10-24 21:23:35', '2026-10-25 04:03:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('7', '012/C/PTABDI/IX/2026', 'Surat Permohonan Kerjasama dengan PT.Mulia Bersama', 'PT. Abdi Jaya', 'Kerjasama', 'segera', '2026-10-21', '2026-10-21', '124', 'demo-surat.pdf', 'Surat Permohonan Kerjasama dengan PT.Mulia Bersama.pdf', 'disposisi', 'Berkas lolos pemeriksaan sistem.', 'Mohon untuk divisi Humas agar dapat mendiskusikan terlebih dahulu terkait surat ini dan berikan keputusan kalian apabila sudah menemukan titik temunya.', '0', '2026-10-26 18:23:35', '1', 'aktif', NULL, NULL, '2', '2026-10-25 19:03:35', '2026-10-26 23:23:39', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('8', '70/PTSANJAYA/IX/2026', 'Surat Tugas ke Bank Sumselbabel', 'PT. Angkasa Permata', 'Tugas', 'rahasia', '2026-10-22', '2026-10-22', '120', 'demo-surat.pdf', 'Surat Tugas ke Bank Sumselbabel.pdf', 'disposisi', 'Berkas lolos pemeriksaan sistem.', 'Segera tindak lanjuti dan laporkan hasilnya kepada pimpinan.', '0', '2026-10-26 00:03:35', '3', 'aktif', NULL, NULL, '2', '2026-10-25 02:23:35', '2026-10-26 23:23:39', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('9', '75/PTSANJAYA/VI/2026', 'Surat Kunjungan kerja ke OJK', 'PT. Angkasa Permata', 'Pemberitahuan', 'biasa', '2026-10-14', '2026-10-14', '110', 'demo-surat.pdf', 'Surat Kunjungan kerja ke OJK.pdf', 'dikirim', 'Berkas lolos pemeriksaan sistem.', 'Koordinasikan jadwal kunjungan.', '0', '2026-10-24 03:43:35', '2', 'aktif', NULL, NULL, '2', '2026-10-22 22:43:35', '2026-10-26 23:23:39', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('10', '33/PTMURNI/IX/2026', 'Undangan Seminar Tata Kelola Arsip', 'PT. Cipta Murni', 'Undangan', 'biasa', '2026-10-16', '2026-10-16', '112', 'demo-surat.pdf', 'Undangan Seminar Tata Kelola Arsip.pdf', 'dikirim', 'Berkas lolos pemeriksaan sistem.', 'Wakilkan dua orang peserta.', '0', '2026-10-24 15:03:35', '4', 'aktif', NULL, NULL, '2', '2026-10-23 23:43:35', '2026-10-26 23:23:39', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('11', '19/CVCAHAYA/VIII/2026', 'Pemberitahuan Perubahan Jadwal Audit', 'CV. Asia Cahaya', 'Pemberitahuan', 'penting', '2026-10-12', '2026-10-12', '105', 'demo-surat.pdf', 'Pemberitahuan Perubahan Jadwal Audit.pdf', 'selesai', 'Berkas lolos pemeriksaan sistem.', 'Sesuaikan jadwal internal.', '0', '2026-10-23 12:01:35', '2', 'aktif', NULL, NULL, '2', '2026-10-22 02:43:35', '2026-10-26 23:23:42', 'Jadwal telah disesuaikan dan dikonfirmasi ke pihak pengirim.', 'Kepala Divisi', '2026-10-26 23:13:35');
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('12', '08/PTBUDI/VIII/2026', 'Penawaran Layanan Pemeliharaan Server', 'PT. Setia Budi', 'Penawaran', 'biasa', '2023-08-13', '2023-08-13', '100', 'demo-surat.pdf', 'Penawaran Layanan Pemeliharaan Server.pdf', 'selesai', 'Berkas lolos pemeriksaan sistem.', 'Pelajari penawaran dan beri tanggapan.', '0', '2026-10-21 22:26:35', '6', 'aktif', NULL, NULL, '2', '2026-10-20 17:23:35', '2026-10-26 23:23:42', 'Jadwal telah disesuaikan dan dikonfirmasi ke pihak pengirim.', 'Kepala Divisi', '2026-10-24 23:03:35');
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('13', '45/PTSANJAYA/VIII/2023', 'Surat Permohonan Kerjasama Sanjaya dengan PT. Cipto Murni', 'PT. Cipto Murni', 'Kerjasama', 'biasa', '2023-09-22', '2023-09-22', '45', 'demo-surat.pdf', 'Surat Permohonan Kerjasama Sanjaya dengan PT. Cipto Murni.pdf', 'arsip', NULL, NULL, '0', NULL, NULL, 'inaktif', '2', 'Selesai diproses', '2', '2026-10-26 23:23:35', '2026-10-26 23:23:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('14', '50/PTSANJAYA/IX/2023', 'Surat Permohonan Kerjasama Sanjaya dengan PT. Asia Cahaya', 'PT. Asia Cahaya', 'Kerjasama', 'biasa', '2023-12-31', '2023-12-31', '50', 'demo-surat.pdf', 'Surat Permohonan Kerjasama Sanjaya dengan PT. Asia Cahaya.pdf', 'arsip', NULL, NULL, '0', NULL, NULL, 'inaktif', '2', 'Selesai diproses', '2', '2026-10-26 23:23:35', '2026-10-26 23:23:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('15', '62/PTSANJAYA/XII/2023', 'Surat Permohonan Kerjasama Sanjaya dengan PT. Setia Budi', 'PT. Setia Budi', 'Kerjasama', 'penting', '2024-03-30', '2024-03-30', '62', 'demo-surat.pdf', 'Surat Permohonan Kerjasama Sanjaya dengan PT. Setia Budi.pdf', 'arsip', NULL, NULL, '0', NULL, NULL, 'permanen', NULL, 'Selesai diproses', '2', '2026-10-26 23:23:35', '2026-10-26 23:23:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('16', '101/PTABDI/III/2024', 'Perjanjian Kerjasama Distribusi Produk', 'PT. Abdi Jaya', 'Kerjasama', 'penting', '2024-05-09', '2024-05-09', '101', 'demo-surat.pdf', 'Perjanjian Kerjasama Distribusi Produk.pdf', 'arsip', NULL, NULL, '0', NULL, NULL, 'permanen', NULL, 'Selesai diproses', '2', '2026-10-26 23:23:35', '2026-10-26 23:23:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('17', '22/CVJAYA/V/2024', 'Undangan Rapat Tahunan Pemegang Saham', 'CV. Jaya Sembada', 'Undangan', 'biasa', '2025-01-14', '2025-01-14', '22', 'demo-surat.pdf', 'Undangan Rapat Tahunan Pemegang Saham.pdf', 'arsip', NULL, NULL, '0', NULL, NULL, 'inaktif', '2', 'Selesai diproses', '2', '2026-10-26 23:23:35', '2026-10-26 23:23:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('18', '07/PTARTHA/I/2025', 'Penawaran Perangkat Elektronik Kantor', 'PT. Artha Elektronik', 'Penawaran', 'biasa', '2025-05-04', '2025-05-04', '7', 'demo-surat.pdf', 'Penawaran Perangkat Elektronik Kantor.pdf', 'arsip', NULL, NULL, '0', NULL, NULL, 'aktif', NULL, 'Selesai diproses', '2', '2026-10-26 23:23:35', '2026-10-26 23:23:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('19', '39/PTMURNI/IV/2025', 'Pemberitahuan Libur Bersama Perusahaan', 'PT. Cipto Murni', 'Pemberitahuan', 'biasa', '2025-07-13', '2025-07-13', '39', 'demo-surat.pdf', 'Pemberitahuan Libur Bersama Perusahaan.pdf', 'arsip', NULL, NULL, '0', NULL, NULL, 'aktif', NULL, 'Selesai diproses', '2', '2026-10-26 23:23:35', '2026-10-26 23:23:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('20', '58/PTABDI/VII/2025', 'Laporan Hasil Audit Internal Semester I', 'PT. Abdi Jaya', 'Pemberitahuan', 'rahasia', '2026-07-18', '2026-07-18', '58', 'demo-surat.pdf', 'Laporan Hasil Audit Internal Semester I.pdf', 'arsip', NULL, NULL, '0', NULL, NULL, 'permanen', NULL, 'Selesai diproses', '2', '2026-10-26 23:23:35', '2026-10-26 23:23:35', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('21', '035/C/PTASIA/IX/2026', 'Undangan Rapat Bersama Seluruh Perusahaan Mebel', 'PT. Asia Cipto Budi', 'Undangan', 'penting', '2026-10-25', '2026-10-25', '126', 'demo-surat.pdf', 'Undangan Rapat Bersama Seluruh Perusahaan Mebel.pdf', 'menunggu_disposisi', 'Berkas lolos pemeriksaan sistem.', NULL, '0', NULL, NULL, 'aktif', NULL, NULL, '2', '2026-10-26 20:53:39', '2026-10-26 21:53:39', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('22', '112/PTMAJU/IX/2026', 'Permohonan Audiensi dengan Pimpinan', 'PT. Maju Sentosa', 'Permohonan', 'segera', '2026-10-26', '2026-10-26', '127', 'demo-surat.pdf', 'Permohonan Audiensi dengan Pimpinan.pdf', 'menunggu_disposisi', 'Berkas lolos pemeriksaan sistem.', NULL, '0', NULL, NULL, 'aktif', NULL, NULL, '2', '2026-10-26 21:43:39', '2026-10-26 22:43:39', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('23', '012/AC.D/ITB/IX/2026', 'Permintaan Delegasi ke Seminar Nasional ITB x TeD', 'Institut Teknologi Bandung', 'Permohonan', 'biasa', '2026-10-24', '2026-10-24', '128', 'demo-surat.pdf', 'Permintaan Delegasi ke Seminar Nasional ITB x TeD.pdf', 'menunggu_disposisi', 'Berkas lolos pemeriksaan sistem.', NULL, '0', NULL, NULL, 'aktif', NULL, NULL, '2', '2026-10-25 18:03:39', '2026-10-25 19:03:39', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('24', '045/AB-D/PTARDA/IX/2026', 'Surat Permohonan Kerjasama Sponsor untuk acara ArdoFest', 'PT. Arda Santara', 'Kerjasama', 'biasa', '2026-10-24', '2026-10-24', '129', 'demo-surat.pdf', 'Surat Permohonan Kerjasama Sponsor untuk acara ArdoFest.pdf', 'menunggu_disposisi', 'Berkas lolos pemeriksaan sistem.', NULL, '0', NULL, NULL, 'aktif', NULL, NULL, '2', '2026-10-25 21:23:39', '2026-10-25 22:23:39', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('25', '09/CVBERKAH/IX/2026', 'Penawaran Kerjasama Pengadaan ATK', 'CV. Berkah Abadi', 'Penawaran', 'penting', '2026-10-23', '2026-10-23', '130', 'demo-surat.pdf', 'Penawaran Kerjasama Pengadaan ATK.pdf', 'menunggu_disposisi', 'Berkas lolos pemeriksaan sistem.', 'Mohon dibandingkan dengan penawaran vendor lain sebelum diputuskan.', '1', NULL, '3', 'aktif', NULL, NULL, '2', '2026-10-25 09:43:39', '2026-10-25 10:43:39', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('26', '41/PTKEU/IX/2026', 'Permintaan Laporan Anggaran Triwulan III', 'PT. Kencana Finance', 'Permohonan', 'penting', '2026-10-26', '2026-10-26', '131', 'demo-surat.pdf', 'Permintaan Laporan Anggaran Triwulan III.pdf', 'dikirim', 'Berkas lolos pemeriksaan sistem.', 'Mohon ditindaklanjuti oleh Divisi Keuangan.', '0', '2026-10-21 20:03:42', '3', 'aktif', NULL, NULL, '2', '2026-10-21 16:43:42', '2026-10-21 23:23:42', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('27', '17/KHR/IX/2026', 'Permohonan Klarifikasi Perjanjian Kerjasama', 'Kantor Hukum Rahman', 'Permohonan', 'rahasia', '2026-10-26', '2026-10-26', '132', 'demo-surat.pdf', 'Permohonan Klarifikasi Perjanjian Kerjasama.pdf', 'dikirim', 'Berkas lolos pemeriksaan sistem.', 'Mohon ditindaklanjuti oleh Divisi Hukum.', '0', '2026-10-23 18:43:42', '5', 'aktif', NULL, NULL, '2', '2026-10-23 15:23:42', '2026-10-23 22:03:42', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('28', '63/DINKOMINFO/X/2026', 'Undangan Sosialisasi Keamanan Data', 'Dinas Kominfo', 'Undangan', 'biasa', '2026-10-26', '2026-10-26', '133', 'demo-surat.pdf', 'Undangan Sosialisasi Keamanan Data.pdf', 'dikirim', 'Berkas lolos pemeriksaan sistem.', 'Mohon ditindaklanjuti oleh Divisi IT.', '0', '2026-10-25 19:03:42', '6', 'aktif', NULL, NULL, '2', '2026-10-25 15:43:42', '2026-10-25 22:23:42', NULL, NULL, NULL);
INSERT INTO surat (id, nomor_surat, perihal, pengirim, jenis, sifat, tanggal_surat, tanggal_terima, no_agenda, file_path, file_name, status, catatan_sistem, catatan_disposisi, disposisi_draft, disposisi_at, divisi_id, retensi, retensi_tahun, alasan_arsip, created_by, created_at, updated_at, tindak_lanjut_catatan, tindak_lanjut_oleh, tindak_lanjut_at) VALUES ('29', '27/MNUSA/X/2026', 'Undangan Peliputan Kegiatan Perusahaan', 'Media Nusantara', 'Undangan', 'biasa', '2026-10-26', '2026-10-26', '134', 'demo-surat.pdf', 'Undangan Peliputan Kegiatan Perusahaan.pdf', 'dikirim', 'Berkas lolos pemeriksaan sistem.', 'Mohon ditindaklanjuti oleh Divisi Humas.', '0', '2026-10-26 18:03:42', '1', 'aktif', NULL, NULL, '2', '2026-10-26 14:43:42', '2026-10-26 21:23:42', NULL, NULL, NULL);
SELECT setval('surat_id_seq', (SELECT MAX(id) FROM surat));

-- Data: distribusi_log
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('1', '1', 'Sekretaris menerima surat dari PT. Teknologi Berkarya', '2026-10-26 21:18:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('2', '2', 'Sekretaris menerima surat dari PT. Persada Jaya', '2026-10-26 20:03:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('3', '3', 'Sekretaris menerima surat dari CV. Ramah Asia', '2026-10-25 22:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('4', '4', 'Sekretaris menerima surat dari PT. Abdi Jaya', '2026-10-24 23:03:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('5', '5', 'Sekretaris menerima surat dari CV. Jaya Sembada', '2026-10-24 19:43:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('6', '6', 'Sekretaris menerima surat dari PT. Artha Elektronik', '2026-10-24 21:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('7', '6', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-25 04:03:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('8', '7', 'Sekretaris menerima surat dari PT. Abdi Jaya', '2026-10-25 19:03:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('9', '7', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-26 06:43:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('10', '7', 'Pimpinan memberikan disposisi untuk Divisi Humas', '2026-10-26 18:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('11', '8', 'Sekretaris menerima surat dari PT. Angkasa Permata', '2026-10-25 02:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('12', '8', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-25 13:13:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('13', '8', 'Pimpinan memberikan disposisi untuk Divisi Keuangan', '2026-10-26 00:03:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('14', '9', 'Sekretaris menerima surat dari PT. Angkasa Permata', '2026-10-22 22:43:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('15', '9', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-23 13:13:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('16', '9', 'Pimpinan memberikan disposisi untuk Divisi HRD', '2026-10-24 03:43:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('17', '9', 'Sekretaris mengirim surat sesuai disposisi ke Divisi HRD', '2026-10-24 18:13:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('18', '9', 'Surat telah sampai ke Divisi HRD', '2026-10-25 08:43:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('19', '9', 'Surat belum diproses lebih lanjut', '2026-10-25 23:13:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('20', '10', 'Sekretaris menerima surat dari PT. Cipta Murni', '2026-10-23 23:43:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('21', '10', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-24 07:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('22', '10', 'Pimpinan memberikan disposisi untuk Divisi Operasional', '2026-10-24 15:03:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('23', '10', 'Sekretaris mengirim surat sesuai disposisi ke Divisi Operasional', '2026-10-24 22:43:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('24', '10', 'Surat telah sampai ke Divisi Operasional', '2026-10-25 06:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('25', '10', 'Surat belum diproses lebih lanjut', '2026-10-25 14:03:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('26', '11', 'Sekretaris menerima surat dari CV. Asia Cahaya', '2026-10-22 02:43:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('27', '11', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-22 19:22:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('28', '11', 'Pimpinan memberikan disposisi untuk Divisi HRD', '2026-10-23 12:01:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('29', '11', 'Sekretaris mengirim surat sesuai disposisi ke Divisi HRD', '2026-10-24 04:39:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('30', '11', 'Surat telah sampai ke Divisi HRD', '2026-10-24 21:18:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('31', '11', 'Surat belum diproses lebih lanjut', '2026-10-25 13:56:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('32', '11', 'Surat telah dibaca dan ditindaklanjuti', '2026-10-26 06:35:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('33', '11', 'Proses distribusi selesai', '2026-10-26 23:13:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('34', '12', 'Sekretaris menerima surat dari PT. Setia Budi', '2026-10-20 17:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('35', '12', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-21 07:55:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('36', '12', 'Pimpinan memberikan disposisi untuk Divisi IT', '2026-10-21 22:26:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('37', '12', 'Sekretaris mengirim surat sesuai disposisi ke Divisi IT', '2026-10-22 12:58:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('38', '12', 'Surat telah sampai ke Divisi IT', '2026-10-23 03:29:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('39', '12', 'Surat belum diproses lebih lanjut', '2026-10-23 18:01:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('40', '12', 'Surat telah dibaca dan ditindaklanjuti', '2026-10-24 08:32:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('41', '12', 'Proses distribusi selesai', '2026-10-24 23:03:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('42', '13', 'Sekretaris menerima surat dari PT. Cipto Murni', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('43', '13', 'Surat dipindahkan ke arsip', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('44', '14', 'Sekretaris menerima surat dari PT. Asia Cahaya', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('45', '14', 'Surat dipindahkan ke arsip', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('46', '15', 'Sekretaris menerima surat dari PT. Setia Budi', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('47', '15', 'Surat dipindahkan ke arsip', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('48', '16', 'Sekretaris menerima surat dari PT. Abdi Jaya', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('49', '16', 'Surat dipindahkan ke arsip', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('50', '17', 'Sekretaris menerima surat dari CV. Jaya Sembada', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('51', '17', 'Surat dipindahkan ke arsip', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('52', '18', 'Sekretaris menerima surat dari PT. Artha Elektronik', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('53', '18', 'Surat dipindahkan ke arsip', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('54', '19', 'Sekretaris menerima surat dari PT. Cipto Murni', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('55', '19', 'Surat dipindahkan ke arsip', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('56', '20', 'Sekretaris menerima surat dari PT. Abdi Jaya', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('57', '20', 'Surat dipindahkan ke arsip', '2026-10-26 23:23:35');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('58', '21', 'Sekretaris menerima surat dari PT. Asia Cipto Budi', '2026-10-26 20:53:39');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('59', '21', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-26 21:53:39');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('60', '22', 'Sekretaris menerima surat dari PT. Maju Sentosa', '2026-10-26 21:43:39');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('61', '22', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-26 22:43:39');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('62', '23', 'Sekretaris menerima surat dari Institut Teknologi Bandung', '2026-10-25 18:03:39');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('63', '23', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-25 19:03:39');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('64', '24', 'Sekretaris menerima surat dari PT. Arda Santara', '2026-10-25 21:23:39');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('65', '24', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-25 22:23:39');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('66', '25', 'Sekretaris menerima surat dari CV. Berkah Abadi', '2026-10-25 09:43:39');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('67', '25', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-25 10:43:39');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('68', '26', 'Sekretaris menerima surat dari PT. Kencana Finance', '2026-10-21 16:43:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('69', '26', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-21 18:23:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('70', '26', 'Pimpinan memberikan disposisi untuk Divisi Keuangan', '2026-10-21 20:03:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('71', '26', 'Sekretaris mengirim surat sesuai disposisi ke Divisi Keuangan', '2026-10-21 23:13:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('72', '26', 'Surat telah sampai ke Divisi Keuangan', '2026-10-21 23:23:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('73', '26', 'Surat belum diproses lebih lanjut', '2026-10-21 23:28:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('74', '27', 'Sekretaris menerima surat dari Kantor Hukum Rahman', '2026-10-23 15:23:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('75', '27', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-23 17:03:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('76', '27', 'Pimpinan memberikan disposisi untuk Divisi Hukum', '2026-10-23 18:43:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('77', '27', 'Sekretaris mengirim surat sesuai disposisi ke Divisi Hukum', '2026-10-23 21:53:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('78', '27', 'Surat telah sampai ke Divisi Hukum', '2026-10-23 22:03:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('79', '27', 'Surat belum diproses lebih lanjut', '2026-10-23 22:08:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('80', '28', 'Sekretaris menerima surat dari Dinas Kominfo', '2026-10-25 15:43:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('81', '28', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-25 17:23:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('82', '28', 'Pimpinan memberikan disposisi untuk Divisi IT', '2026-10-25 19:03:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('83', '28', 'Sekretaris mengirim surat sesuai disposisi ke Divisi IT', '2026-10-25 22:13:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('84', '28', 'Surat telah sampai ke Divisi IT', '2026-10-25 22:23:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('85', '28', 'Surat belum diproses lebih lanjut', '2026-10-25 22:28:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('86', '29', 'Sekretaris menerima surat dari Media Nusantara', '2026-10-26 14:43:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('87', '29', 'Surat diverifikasi sekretaris dan diteruskan ke pimpinan', '2026-10-26 16:23:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('88', '29', 'Pimpinan memberikan disposisi untuk Divisi Humas', '2026-10-26 18:03:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('89', '29', 'Sekretaris mengirim surat sesuai disposisi ke Divisi Humas', '2026-10-26 21:13:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('90', '29', 'Surat telah sampai ke Divisi Humas', '2026-10-26 21:23:42');
INSERT INTO distribusi_log (id, surat_id, keterangan, created_at) VALUES ('91', '29', 'Surat belum diproses lebih lanjut', '2026-10-26 21:28:42');
SELECT setval('distribusi_log_id_seq', (SELECT MAX(id) FROM distribusi_log));

-- Data: notifikasi
INSERT INTO notifikasi (id, user_id, tipe, pesan, link, is_read, created_at) VALUES ('1', '2', 'verifikasi', 'Surat dari PT. Teknologi Berkarya belum dilakukan verifikasi lebih lanjut. Lakukan verifikasi sekarang.', 'manajemen.php?id=1', '0', '2026-10-26 21:18:35');
INSERT INTO notifikasi (id, user_id, tipe, pesan, link, is_read, created_at) VALUES ('2', '2', 'tindak_lanjut', 'Terdapat beberapa surat yang belum ditindaklanjuti oleh pihak tujuan. Dimohon untuk dapat ditindaklanjuti.', 'alur.php?id=9', '0', '2026-10-25 23:23:35');
INSERT INTO notifikasi (id, user_id, tipe, pesan, link, is_read, created_at) VALUES ('3', '2', 'disposisi', 'Surat Permohonan Kerjasama dengan PT.Mulia Bersama telah didisposisi oleh pimpinan.', 'cek.php?id=7', '0', '2026-10-26 18:23:35');
INSERT INTO notifikasi (id, user_id, tipe, pesan, link, is_read, created_at) VALUES ('4', '1', 'surat_baru', 'Sekretaris baru saja mengirimi surat kerja sama dari pihak PT. Artha Elektronik. Mohon agar suratnya dapat didisposisikan segera.', 'disposisi.php?id=6', '0', '2026-10-26 22:53:39');
INSERT INTO notifikasi (id, user_id, tipe, pesan, link, is_read, created_at) VALUES ('5', '1', 'pengingat', 'Anda masih belum menindaklanjuti surat permintaan delegasi seminar nasional dari Institut Teknologi Bandung. Mohon segera ditindaklanjuti.', 'disposisi.php?id=23', '1', '2026-10-26 22:43:39');
INSERT INTO notifikasi (id, user_id, tipe, pesan, link, is_read, created_at) VALUES ('6', '1', 'surat_baru', 'Sekretaris telah mengirimi surat terbaru kepada Anda, boleh dilihat dan diberikan arahan suratnya lebih lanjut.', 'disposisi.php?id=22', '0', '2026-10-26 22:13:39');
SELECT setval('notifikasi_id_seq', (SELECT MAX(id) FROM notifikasi));

-- Data: unduhan
INSERT INTO unduhan (id, surat_id, user_id, created_at) VALUES ('1', '12', '2', '2026-10-24 23:23:35');
INSERT INTO unduhan (id, surat_id, user_id, created_at) VALUES ('2', '13', '2', '2026-10-25 23:23:35');
INSERT INTO unduhan (id, surat_id, user_id, created_at) VALUES ('3', '14', '2', '2026-10-26 20:23:35');
SELECT setval('unduhan_id_seq', (SELECT MAX(id) FROM unduhan));
