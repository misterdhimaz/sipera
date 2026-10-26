USE sipera;
-- Tindak lanjut divisi: kolom tambahan + data contoh. Jalankan SEKALI setelah database_pimpinan.sql.
ALTER TABLE surat
  ADD COLUMN tindak_lanjut_catatan TEXT NULL,
  ADD COLUMN tindak_lanjut_oleh VARCHAR(100) NULL,
  ADD COLUMN tindak_lanjut_at DATETIME NULL;

UPDATE surat SET
  tindak_lanjut_catatan = 'Jadwal telah disesuaikan dan dikonfirmasi ke pihak pengirim.',
  tindak_lanjut_oleh = 'Kepala Divisi',
  tindak_lanjut_at = (SELECT MAX(l.created_at) FROM distribusi_log l WHERE l.surat_id = surat.id)
WHERE status = 'selesai';

SET @sek = (SELECT id FROM users WHERE email = 'sekretaris@sipera.test');

INSERT INTO surat (nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,disposisi_at,created_by,created_at,updated_at) VALUES ('41/PTKEU/IX/2026','Permintaan Laporan Anggaran Triwulan III','PT. Kencana Finance','Permohonan','penting',CURDATE(),CURDATE(),131,'demo-surat.pdf','Permintaan Laporan Anggaran Triwulan III.pdf','dikirim','Berkas lolos pemeriksaan sistem.','Mohon ditindaklanjuti oleh Divisi Keuangan.',3,NOW()-INTERVAL 7400 MINUTE,@sek,NOW()-INTERVAL 7600 MINUTE,NOW()-INTERVAL 7200 MINUTE);
SET @sid = LAST_INSERT_ID();
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (@sid,'Sekretaris menerima surat dari PT. Kencana Finance',NOW()-INTERVAL 7600 MINUTE),(@sid,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 7500 MINUTE),(@sid,'Pimpinan memberikan disposisi untuk Divisi Keuangan',NOW()-INTERVAL 7400 MINUTE),(@sid,'Sekretaris mengirim surat sesuai disposisi ke Divisi Keuangan',NOW()-INTERVAL 7210 MINUTE),(@sid,'Surat telah sampai ke Divisi Keuangan',NOW()-INTERVAL 7200 MINUTE),(@sid,'Surat belum diproses lebih lanjut',NOW()-INTERVAL 7195 MINUTE);
INSERT INTO surat (nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,disposisi_at,created_by,created_at,updated_at) VALUES ('17/KHR/IX/2026','Permohonan Klarifikasi Perjanjian Kerjasama','Kantor Hukum Rahman','Permohonan','rahasia',CURDATE(),CURDATE(),132,'demo-surat.pdf','Permohonan Klarifikasi Perjanjian Kerjasama.pdf','dikirim','Berkas lolos pemeriksaan sistem.','Mohon ditindaklanjuti oleh Divisi Hukum.',5,NOW()-INTERVAL 4600 MINUTE,@sek,NOW()-INTERVAL 4800 MINUTE,NOW()-INTERVAL 4400 MINUTE);
SET @sid = LAST_INSERT_ID();
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (@sid,'Sekretaris menerima surat dari Kantor Hukum Rahman',NOW()-INTERVAL 4800 MINUTE),(@sid,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 4700 MINUTE),(@sid,'Pimpinan memberikan disposisi untuk Divisi Hukum',NOW()-INTERVAL 4600 MINUTE),(@sid,'Sekretaris mengirim surat sesuai disposisi ke Divisi Hukum',NOW()-INTERVAL 4410 MINUTE),(@sid,'Surat telah sampai ke Divisi Hukum',NOW()-INTERVAL 4400 MINUTE),(@sid,'Surat belum diproses lebih lanjut',NOW()-INTERVAL 4395 MINUTE);
INSERT INTO surat (nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,disposisi_at,created_by,created_at,updated_at) VALUES ('63/DINKOMINFO/X/2026','Undangan Sosialisasi Keamanan Data','Dinas Kominfo','Undangan','biasa',CURDATE(),CURDATE(),133,'demo-surat.pdf','Undangan Sosialisasi Keamanan Data.pdf','dikirim','Berkas lolos pemeriksaan sistem.','Mohon ditindaklanjuti oleh Divisi IT.',6,NOW()-INTERVAL 1700 MINUTE,@sek,NOW()-INTERVAL 1900 MINUTE,NOW()-INTERVAL 1500 MINUTE);
SET @sid = LAST_INSERT_ID();
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (@sid,'Sekretaris menerima surat dari Dinas Kominfo',NOW()-INTERVAL 1900 MINUTE),(@sid,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 1800 MINUTE),(@sid,'Pimpinan memberikan disposisi untuk Divisi IT',NOW()-INTERVAL 1700 MINUTE),(@sid,'Sekretaris mengirim surat sesuai disposisi ke Divisi IT',NOW()-INTERVAL 1510 MINUTE),(@sid,'Surat telah sampai ke Divisi IT',NOW()-INTERVAL 1500 MINUTE),(@sid,'Surat belum diproses lebih lanjut',NOW()-INTERVAL 1495 MINUTE);
INSERT INTO surat (nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,disposisi_at,created_by,created_at,updated_at) VALUES ('27/MNUSA/X/2026','Undangan Peliputan Kegiatan Perusahaan','Media Nusantara','Undangan','biasa',CURDATE(),CURDATE(),134,'demo-surat.pdf','Undangan Peliputan Kegiatan Perusahaan.pdf','dikirim','Berkas lolos pemeriksaan sistem.','Mohon ditindaklanjuti oleh Divisi Humas.',1,NOW()-INTERVAL 320 MINUTE,@sek,NOW()-INTERVAL 520 MINUTE,NOW()-INTERVAL 120 MINUTE);
SET @sid = LAST_INSERT_ID();
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (@sid,'Sekretaris menerima surat dari Media Nusantara',NOW()-INTERVAL 520 MINUTE),(@sid,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 420 MINUTE),(@sid,'Pimpinan memberikan disposisi untuk Divisi Humas',NOW()-INTERVAL 320 MINUTE),(@sid,'Sekretaris mengirim surat sesuai disposisi ke Divisi Humas',NOW()-INTERVAL 130 MINUTE),(@sid,'Surat telah sampai ke Divisi Humas',NOW()-INTERVAL 120 MINUTE),(@sid,'Surat belum diproses lebih lanjut',NOW()-INTERVAL 115 MINUTE);
