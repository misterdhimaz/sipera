USE sipera;
-- Modul Pimpinan: kolom tambahan + data contoh. Jalankan SEKALI setelah database_sekretaris.sql.
ALTER TABLE surat
  ADD COLUMN disposisi_draft TINYINT(1) NOT NULL DEFAULT 0 AFTER catatan_disposisi,
  ADD COLUMN disposisi_at DATETIME NULL AFTER disposisi_draft;

UPDATE users SET nama = 'Andika Sanjaya' WHERE email = 'pimpinan@sipera.test';
SET @pim = (SELECT id FROM users WHERE email = 'pimpinan@sipera.test');
SET @sek = (SELECT id FROM users WHERE email = 'sekretaris@sipera.test');

-- Surat yang sudah didisposisi pada data contoh sebelumnya
UPDATE surat SET disposisi_at = (SELECT MIN(l.created_at) FROM distribusi_log l
  WHERE l.surat_id = surat.id AND l.keterangan LIKE 'Pimpinan memberikan disposisi%')
WHERE status IN ('disposisi','dikirim','selesai');

INSERT INTO surat (nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,disposisi_draft,divisi_id,created_by,created_at,updated_at) VALUES ('035/C/PTASIA/IX/2026','Undangan Rapat Bersama Seluruh Perusahaan Mebel','PT. Asia Cipto Budi','Undangan','penting',CURDATE()+INTERVAL -1 DAY,CURDATE()+INTERVAL -1 DAY,126,'demo-surat.pdf','Undangan Rapat Bersama Seluruh Perusahaan Mebel.pdf','menunggu_disposisi','Berkas lolos pemeriksaan sistem.',NULL,0,NULL,@sek,NOW()-INTERVAL 150 MINUTE,NOW()-INTERVAL 90 MINUTE);
SET @sid = LAST_INSERT_ID();
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (@sid,'Sekretaris menerima surat dari PT. Asia Cipto Budi',NOW()-INTERVAL 150 MINUTE),(@sid,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 90 MINUTE);
INSERT INTO surat (nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,disposisi_draft,divisi_id,created_by,created_at,updated_at) VALUES ('112/PTMAJU/IX/2026','Permohonan Audiensi dengan Pimpinan','PT. Maju Sentosa','Permohonan','segera',CURDATE()+INTERVAL 0 DAY,CURDATE()+INTERVAL 0 DAY,127,'demo-surat.pdf','Permohonan Audiensi dengan Pimpinan.pdf','menunggu_disposisi','Berkas lolos pemeriksaan sistem.',NULL,0,NULL,@sek,NOW()-INTERVAL 100 MINUTE,NOW()-INTERVAL 40 MINUTE);
SET @sid = LAST_INSERT_ID();
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (@sid,'Sekretaris menerima surat dari PT. Maju Sentosa',NOW()-INTERVAL 100 MINUTE),(@sid,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 40 MINUTE);
INSERT INTO surat (nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,disposisi_draft,divisi_id,created_by,created_at,updated_at) VALUES ('012/AC.D/ITB/IX/2026','Permintaan Delegasi ke Seminar Nasional ITB x TeD','Institut Teknologi Bandung','Permohonan','biasa',CURDATE()+INTERVAL -2 DAY,CURDATE()+INTERVAL -2 DAY,128,'demo-surat.pdf','Permintaan Delegasi ke Seminar Nasional ITB x TeD.pdf','menunggu_disposisi','Berkas lolos pemeriksaan sistem.',NULL,0,NULL,@sek,NOW()-INTERVAL 1760 MINUTE,NOW()-INTERVAL 1700 MINUTE);
SET @sid = LAST_INSERT_ID();
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (@sid,'Sekretaris menerima surat dari Institut Teknologi Bandung',NOW()-INTERVAL 1760 MINUTE),(@sid,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 1700 MINUTE);
INSERT INTO surat (nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,disposisi_draft,divisi_id,created_by,created_at,updated_at) VALUES ('045/AB-D/PTARDA/IX/2026','Surat Permohonan Kerjasama Sponsor untuk acara ArdoFest','PT. Arda Santara','Kerjasama','biasa',CURDATE()+INTERVAL -2 DAY,CURDATE()+INTERVAL -2 DAY,129,'demo-surat.pdf','Surat Permohonan Kerjasama Sponsor untuk acara ArdoFest.pdf','menunggu_disposisi','Berkas lolos pemeriksaan sistem.',NULL,0,NULL,@sek,NOW()-INTERVAL 1560 MINUTE,NOW()-INTERVAL 1500 MINUTE);
SET @sid = LAST_INSERT_ID();
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (@sid,'Sekretaris menerima surat dari PT. Arda Santara',NOW()-INTERVAL 1560 MINUTE),(@sid,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 1500 MINUTE);
INSERT INTO surat (nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,disposisi_draft,divisi_id,created_by,created_at,updated_at) VALUES ('09/CVBERKAH/IX/2026','Penawaran Kerjasama Pengadaan ATK','CV. Berkah Abadi','Penawaran','penting',CURDATE()+INTERVAL -3 DAY,CURDATE()+INTERVAL -3 DAY,130,'demo-surat.pdf','Penawaran Kerjasama Pengadaan ATK.pdf','menunggu_disposisi','Berkas lolos pemeriksaan sistem.','Mohon dibandingkan dengan penawaran vendor lain sebelum diputuskan.',1,3,@sek,NOW()-INTERVAL 2260 MINUTE,NOW()-INTERVAL 2200 MINUTE);
SET @sid = LAST_INSERT_ID();
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (@sid,'Sekretaris menerima surat dari CV. Berkah Abadi',NOW()-INTERVAL 2260 MINUTE),(@sid,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 2200 MINUTE);

INSERT INTO notifikasi (user_id, tipe, pesan, link, is_read, created_at) VALUES
(@pim,'surat_baru','Sekretaris baru saja mengirimi surat kerja sama dari pihak PT. Artha Elektronik. Mohon agar suratnya dapat didisposisikan segera.','disposisi.php?id=6',0,NOW()-INTERVAL 30 MINUTE),
(@pim,'pengingat','Anda masih belum menindaklanjuti surat permintaan delegasi seminar nasional dari Institut Teknologi Bandung. Mohon segera ditindaklanjuti.','disposisi.php?id=23',0,NOW()-INTERVAL 40 MINUTE),
(@pim,'surat_baru','Sekretaris telah mengirimi surat terbaru kepada Anda, boleh dilihat dan diberikan arahan suratnya lebih lanjut.','disposisi.php?id=22',0,NOW()-INTERVAL 70 MINUTE);
