USE sipera;
-- Modul Sekretaris: tabel tambahan + data contoh. Jalankan SEKALI setelah database.sql.
CREATE TABLE IF NOT EXISTS divisi (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS surat (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nomor_surat VARCHAR(100) NOT NULL,
  perihal VARCHAR(200) NOT NULL,
  pengirim VARCHAR(150) NOT NULL,
  jenis VARCHAR(50) NOT NULL,
  sifat ENUM('biasa','penting','segera','rahasia') NOT NULL DEFAULT 'biasa',
  tanggal_surat DATE NOT NULL,
  tanggal_terima DATE NULL,
  no_agenda INT UNSIGNED NOT NULL DEFAULT 0,
  file_path VARCHAR(255) NULL,
  file_name VARCHAR(200) NULL,
  status ENUM('verifikasi','menunggu_disposisi','disposisi','dikirim','selesai','arsip') NOT NULL DEFAULT 'verifikasi',
  catatan_sistem TEXT NULL,
  catatan_disposisi TEXT NULL,
  divisi_id INT UNSIGNED NULL,
  retensi ENUM('aktif','inaktif','permanen') NOT NULL DEFAULT 'aktif',
  retensi_tahun TINYINT UNSIGNED NULL,
  alasan_arsip VARCHAR(100) NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status), INDEX idx_nomor (nomor_surat),
  FOREIGN KEY (divisi_id) REFERENCES divisi(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS distribusi_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  surat_id INT UNSIGNED NOT NULL,
  keterangan VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (surat_id),
  FOREIGN KEY (surat_id) REFERENCES surat(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifikasi (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  tipe VARCHAR(30) NOT NULL,
  pesan TEXT NOT NULL,
  link VARCHAR(255) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id, is_read),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS unduhan (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  surat_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (surat_id) REFERENCES surat(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

UPDATE users SET nama = 'Sartika Putri' WHERE email = 'sekretaris@sipera.test';
SET @sek = (SELECT id FROM users WHERE email = 'sekretaris@sipera.test');

INSERT INTO divisi (nama) VALUES ('Humas'), ('HRD'), ('Keuangan'), ('Operasional'), ('Hukum'), ('IT');

INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (1,'001/C/PTABDI/IX/2026','Undangan Rapat Implementasi Sistem AEChaR','PT. Teknologi Berkarya','Undangan','penting',CURDATE()+INTERVAL -3 DAY,CURDATE()+INTERVAL -3 DAY,121,'demo-surat.pdf','Undangan Rapat Implementasi Sistem AEChaR.pdf','verifikasi','Nomor surat belum dapat diverifikasi otomatis. Periksa kembali berkas.',NULL,NULL,'aktif',NULL,NULL,@sek,NOW()-INTERVAL 125 MINUTE,NOW()-INTERVAL 125 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (1,'Sekretaris menerima surat dari PT. Teknologi Berkarya',NOW()-INTERVAL 125 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (2,'015/PersadaJaya/IX/2026','Permohonan Kerjasama Proyek Jangka Panjang PT. Angkasa Permata','PT. Persada Jaya','Permohonan','penting',CURDATE()+INTERVAL -3 DAY,CURDATE()+INTERVAL -3 DAY,122,'demo-surat.pdf','Permohonan Kerjasama Proyek Jangka Panjang PT. Angkasa Permata.pdf','verifikasi','Berkas lolos pemeriksaan sistem.',NULL,NULL,'aktif',NULL,NULL,@sek,NOW()-INTERVAL 200 MINUTE,NOW()-INTERVAL 200 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (2,'Sekretaris menerima surat dari PT. Persada Jaya',NOW()-INTERVAL 200 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (3,'012/AK.FE/MNA/IX/2026','Surat Permohonan Sponsor ke PT. Angkasa Permata','CV. Ramah Asia','Permohonan','rahasia',CURDATE()+INTERVAL -4 DAY,CURDATE()+INTERVAL -4 DAY,123,'demo-surat.pdf','Surat Permohonan Sponsor ke PT. Angkasa Permata.pdf','verifikasi','Tanggal surat tidak terbaca jelas pada berkas.',NULL,NULL,'aktif',NULL,NULL,@sek,NOW()-INTERVAL 1500 MINUTE,NOW()-INTERVAL 1500 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (3,'Sekretaris menerima surat dari CV. Ramah Asia',NOW()-INTERVAL 1500 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (4,'85/PTABDI/VIII/2026','Surat Permohonan Dana Produksi Meja','PT. Abdi Jaya','Permohonan','penting',CURDATE()+INTERVAL -9 DAY,CURDATE()+INTERVAL -9 DAY,118,'demo-surat.pdf','Surat Permohonan Dana Produksi Meja.pdf','verifikasi','Berkas lolos pemeriksaan sistem.',NULL,NULL,'aktif',NULL,NULL,@sek,NOW()-INTERVAL 2900 MINUTE,NOW()-INTERVAL 2900 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (4,'Sekretaris menerima surat dari PT. Abdi Jaya',NOW()-INTERVAL 2900 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (5,'12/CVJAYA/IX/2026','Undangan Rapat Koordinasi Divisi','CV. Jaya Sembada','Undangan','rahasia',CURDATE()+INTERVAL -6 DAY,CURDATE()+INTERVAL -6 DAY,119,'demo-surat.pdf','Undangan Rapat Koordinasi Divisi.pdf','verifikasi','Berkas lolos pemeriksaan sistem.',NULL,NULL,'aktif',NULL,NULL,@sek,NOW()-INTERVAL 3100 MINUTE,NOW()-INTERVAL 3100 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (5,'Sekretaris menerima surat dari CV. Jaya Sembada',NOW()-INTERVAL 3100 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (6,'30/PTARTHA/IX/2026','Surat Permohonan Kerjasama dengan Bank Sumselbabel','PT. Artha Elektronik','Kerjasama','penting',CURDATE()+INTERVAL -7 DAY,CURDATE()+INTERVAL -7 DAY,117,'demo-surat.pdf','Surat Permohonan Kerjasama dengan Bank Sumselbabel.pdf','menunggu_disposisi','Berkas lolos pemeriksaan sistem.',NULL,NULL,'aktif',NULL,NULL,@sek,NOW()-INTERVAL 3000 MINUTE,NOW()-INTERVAL 2600 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (6,'Sekretaris menerima surat dari PT. Artha Elektronik',NOW()-INTERVAL 3000 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (6,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 2600 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (7,'012/C/PTABDI/IX/2026','Surat Permohonan Kerjasama dengan PT.Mulia Bersama','PT. Abdi Jaya','Kerjasama','segera',CURDATE()+INTERVAL -5 DAY,CURDATE()+INTERVAL -5 DAY,124,'demo-surat.pdf','Surat Permohonan Kerjasama dengan PT.Mulia Bersama.pdf','disposisi','Berkas lolos pemeriksaan sistem.','Mohon untuk divisi Humas agar dapat mendiskusikan terlebih dahulu terkait surat ini dan berikan keputusan kalian apabila sudah menemukan titik temunya.',1,'aktif',NULL,NULL,@sek,NOW()-INTERVAL 1700 MINUTE,NOW()-INTERVAL 300 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (7,'Sekretaris menerima surat dari PT. Abdi Jaya',NOW()-INTERVAL 1700 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (7,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 1000 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (7,'Pimpinan memberikan disposisi untuk Divisi Humas',NOW()-INTERVAL 300 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (8,'70/PTSANJAYA/IX/2026','Surat Tugas ke Bank Sumselbabel','PT. Angkasa Permata','Tugas','rahasia',CURDATE()+INTERVAL -4 DAY,CURDATE()+INTERVAL -4 DAY,120,'demo-surat.pdf','Surat Tugas ke Bank Sumselbabel.pdf','disposisi','Berkas lolos pemeriksaan sistem.','Segera tindak lanjuti dan laporkan hasilnya kepada pimpinan.',3,'aktif',NULL,NULL,@sek,NOW()-INTERVAL 2700 MINUTE,NOW()-INTERVAL 1400 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (8,'Sekretaris menerima surat dari PT. Angkasa Permata',NOW()-INTERVAL 2700 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (8,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 2050 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (8,'Pimpinan memberikan disposisi untuk Divisi Keuangan',NOW()-INTERVAL 1400 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (9,'75/PTSANJAYA/VI/2026','Surat Kunjungan kerja ke OJK','PT. Angkasa Permata','Pemberitahuan','biasa',CURDATE()+INTERVAL -12 DAY,CURDATE()+INTERVAL -12 DAY,110,'demo-surat.pdf','Surat Kunjungan kerja ke OJK.pdf','dikirim','Berkas lolos pemeriksaan sistem.','Koordinasikan jadwal kunjungan.',2,'aktif',NULL,NULL,@sek,NOW()-INTERVAL 5800 MINUTE,NOW()-INTERVAL 1450 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (9,'Sekretaris menerima surat dari PT. Angkasa Permata',NOW()-INTERVAL 5800 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (9,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 4930 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (9,'Pimpinan memberikan disposisi untuk Divisi HRD',NOW()-INTERVAL 4060 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (9,'Sekretaris mengirim surat sesuai disposisi ke Divisi HRD',NOW()-INTERVAL 3190 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (9,'Surat telah sampai ke Divisi HRD',NOW()-INTERVAL 2320 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (9,'Surat belum diproses lebih lanjut',NOW()-INTERVAL 1450 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (10,'33/PTMURNI/IX/2026','Undangan Seminar Tata Kelola Arsip','PT. Cipta Murni','Undangan','biasa',CURDATE()+INTERVAL -10 DAY,CURDATE()+INTERVAL -10 DAY,112,'demo-surat.pdf','Undangan Seminar Tata Kelola Arsip.pdf','dikirim','Berkas lolos pemeriksaan sistem.','Wakilkan dua orang peserta.',4,'aktif',NULL,NULL,@sek,NOW()-INTERVAL 4300 MINUTE,NOW()-INTERVAL 2000 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (10,'Sekretaris menerima surat dari PT. Cipta Murni',NOW()-INTERVAL 4300 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (10,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 3840 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (10,'Pimpinan memberikan disposisi untuk Divisi Operasional',NOW()-INTERVAL 3380 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (10,'Sekretaris mengirim surat sesuai disposisi ke Divisi Operasional',NOW()-INTERVAL 2920 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (10,'Surat telah sampai ke Divisi Operasional',NOW()-INTERVAL 2460 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (10,'Surat belum diproses lebih lanjut',NOW()-INTERVAL 2000 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (11,'19/CVCAHAYA/VIII/2026','Pemberitahuan Perubahan Jadwal Audit','CV. Asia Cahaya','Pemberitahuan','penting',CURDATE()+INTERVAL -14 DAY,CURDATE()+INTERVAL -14 DAY,105,'demo-surat.pdf','Pemberitahuan Perubahan Jadwal Audit.pdf','selesai','Berkas lolos pemeriksaan sistem.','Sesuaikan jadwal internal.',2,'aktif',NULL,NULL,@sek,NOW()-INTERVAL 7000 MINUTE,NOW()-INTERVAL 10 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (11,'Sekretaris menerima surat dari CV. Asia Cahaya',NOW()-INTERVAL 7000 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (11,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 6001 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (11,'Pimpinan memberikan disposisi untuk Divisi HRD',NOW()-INTERVAL 5002 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (11,'Sekretaris mengirim surat sesuai disposisi ke Divisi HRD',NOW()-INTERVAL 4004 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (11,'Surat telah sampai ke Divisi HRD',NOW()-INTERVAL 3005 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (11,'Surat belum diproses lebih lanjut',NOW()-INTERVAL 2007 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (11,'Surat telah dibaca dan ditindaklanjuti',NOW()-INTERVAL 1008 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (11,'Proses distribusi selesai',NOW()-INTERVAL 10 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (12,'08/PTBUDI/VIII/2026','Penawaran Layanan Pemeliharaan Server','PT. Setia Budi','Penawaran','biasa',CURDATE()+INTERVAL -20 DAY,CURDATE()+INTERVAL -20 DAY,100,'demo-surat.pdf','Penawaran Layanan Pemeliharaan Server.pdf','selesai','Berkas lolos pemeriksaan sistem.','Pelajari penawaran dan beri tanggapan.',6,'aktif',NULL,NULL,@sek,NOW()-INTERVAL 9000 MINUTE,NOW()-INTERVAL 2900 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (12,'Sekretaris menerima surat dari PT. Setia Budi',NOW()-INTERVAL 9000 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (12,'Surat diverifikasi sekretaris dan diteruskan ke pimpinan',NOW()-INTERVAL 8128 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (12,'Pimpinan memberikan disposisi untuk Divisi IT',NOW()-INTERVAL 7257 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (12,'Sekretaris mengirim surat sesuai disposisi ke Divisi IT',NOW()-INTERVAL 6385 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (12,'Surat telah sampai ke Divisi IT',NOW()-INTERVAL 5514 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (12,'Surat belum diproses lebih lanjut',NOW()-INTERVAL 4642 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (12,'Surat telah dibaca dan ditindaklanjuti',NOW()-INTERVAL 3771 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (12,'Proses distribusi selesai',NOW()-INTERVAL 2900 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (13,'45/PTSANJAYA/VIII/2023','Surat Permohonan Kerjasama Sanjaya dengan PT. Cipto Murni','PT. Cipto Murni','Kerjasama','biasa',CURDATE()+INTERVAL -1100 DAY,CURDATE()+INTERVAL -1100 DAY,45,'demo-surat.pdf','Surat Permohonan Kerjasama Sanjaya dengan PT. Cipto Murni.pdf','arsip',NULL,NULL,NULL,'inaktif',2,'Selesai diproses',@sek,NOW()-INTERVAL 0 MINUTE,NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (13,'Sekretaris menerima surat dari PT. Cipto Murni',NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (13,'Surat dipindahkan ke arsip',NOW()-INTERVAL 0 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (14,'50/PTSANJAYA/IX/2023','Surat Permohonan Kerjasama Sanjaya dengan PT. Asia Cahaya','PT. Asia Cahaya','Kerjasama','biasa',CURDATE()+INTERVAL -1050 DAY,CURDATE()+INTERVAL -1050 DAY,50,'demo-surat.pdf','Surat Permohonan Kerjasama Sanjaya dengan PT. Asia Cahaya.pdf','arsip',NULL,NULL,NULL,'inaktif',2,'Selesai diproses',@sek,NOW()-INTERVAL 0 MINUTE,NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (14,'Sekretaris menerima surat dari PT. Asia Cahaya',NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (14,'Surat dipindahkan ke arsip',NOW()-INTERVAL 0 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (15,'62/PTSANJAYA/XII/2023','Surat Permohonan Kerjasama Sanjaya dengan PT. Setia Budi','PT. Setia Budi','Kerjasama','penting',CURDATE()+INTERVAL -950 DAY,CURDATE()+INTERVAL -950 DAY,62,'demo-surat.pdf','Surat Permohonan Kerjasama Sanjaya dengan PT. Setia Budi.pdf','arsip',NULL,NULL,NULL,'permanen',NULL,'Selesai diproses',@sek,NOW()-INTERVAL 0 MINUTE,NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (15,'Sekretaris menerima surat dari PT. Setia Budi',NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (15,'Surat dipindahkan ke arsip',NOW()-INTERVAL 0 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (16,'101/PTABDI/III/2024','Perjanjian Kerjasama Distribusi Produk','PT. Abdi Jaya','Kerjasama','penting',CURDATE()+INTERVAL -600 DAY,CURDATE()+INTERVAL -600 DAY,101,'demo-surat.pdf','Perjanjian Kerjasama Distribusi Produk.pdf','arsip',NULL,NULL,NULL,'permanen',NULL,'Selesai diproses',@sek,NOW()-INTERVAL 0 MINUTE,NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (16,'Sekretaris menerima surat dari PT. Abdi Jaya',NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (16,'Surat dipindahkan ke arsip',NOW()-INTERVAL 0 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (17,'22/CVJAYA/V/2024','Undangan Rapat Tahunan Pemegang Saham','CV. Jaya Sembada','Undangan','biasa',CURDATE()+INTERVAL -500 DAY,CURDATE()+INTERVAL -500 DAY,22,'demo-surat.pdf','Undangan Rapat Tahunan Pemegang Saham.pdf','arsip',NULL,NULL,NULL,'inaktif',2,'Selesai diproses',@sek,NOW()-INTERVAL 0 MINUTE,NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (17,'Sekretaris menerima surat dari CV. Jaya Sembada',NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (17,'Surat dipindahkan ke arsip',NOW()-INTERVAL 0 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (18,'07/PTARTHA/I/2025','Penawaran Perangkat Elektronik Kantor','PT. Artha Elektronik','Penawaran','biasa',CURDATE()+INTERVAL -300 DAY,CURDATE()+INTERVAL -300 DAY,7,'demo-surat.pdf','Penawaran Perangkat Elektronik Kantor.pdf','arsip',NULL,NULL,NULL,'aktif',NULL,'Selesai diproses',@sek,NOW()-INTERVAL 0 MINUTE,NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (18,'Sekretaris menerima surat dari PT. Artha Elektronik',NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (18,'Surat dipindahkan ke arsip',NOW()-INTERVAL 0 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (19,'39/PTMURNI/IV/2025','Pemberitahuan Libur Bersama Perusahaan','PT. Cipto Murni','Pemberitahuan','biasa',CURDATE()+INTERVAL -200 DAY,CURDATE()+INTERVAL -200 DAY,39,'demo-surat.pdf','Pemberitahuan Libur Bersama Perusahaan.pdf','arsip',NULL,NULL,NULL,'aktif',NULL,'Selesai diproses',@sek,NOW()-INTERVAL 0 MINUTE,NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (19,'Sekretaris menerima surat dari PT. Cipto Murni',NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (19,'Surat dipindahkan ke arsip',NOW()-INTERVAL 0 MINUTE);
INSERT INTO surat (id,nomor_surat,perihal,pengirim,jenis,sifat,tanggal_surat,tanggal_terima,no_agenda,file_path,file_name,status,catatan_sistem,catatan_disposisi,divisi_id,retensi,retensi_tahun,alasan_arsip,created_by,created_at,updated_at) VALUES (20,'58/PTABDI/VII/2025','Laporan Hasil Audit Internal Semester I','PT. Abdi Jaya','Pemberitahuan','rahasia',CURDATE()+INTERVAL -100 DAY,CURDATE()+INTERVAL -100 DAY,58,'demo-surat.pdf','Laporan Hasil Audit Internal Semester I.pdf','arsip',NULL,NULL,NULL,'permanen',NULL,'Selesai diproses',@sek,NOW()-INTERVAL 0 MINUTE,NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (20,'Sekretaris menerima surat dari PT. Abdi Jaya',NOW()-INTERVAL 0 MINUTE);
INSERT INTO distribusi_log (surat_id,keterangan,created_at) VALUES (20,'Surat dipindahkan ke arsip',NOW()-INTERVAL 0 MINUTE);

INSERT INTO unduhan (surat_id, user_id, created_at) VALUES (12,@sek,NOW()-INTERVAL 2 DAY),(13,@sek,NOW()-INTERVAL 1 DAY),(14,@sek,NOW()-INTERVAL 3 HOUR);

INSERT INTO notifikasi (user_id, tipe, pesan, link, is_read, created_at) VALUES
(@sek,'verifikasi','Surat dari PT. Teknologi Berkarya belum dilakukan verifikasi lebih lanjut. Lakukan verifikasi sekarang.','manajemen.php?id=1',0,NOW()-INTERVAL 125 MINUTE),
(@sek,'tindak_lanjut','Terdapat beberapa surat yang belum ditindaklanjuti oleh pihak tujuan. Dimohon untuk dapat ditindaklanjuti.','alur.php?id=9',0,NOW()-INTERVAL 1 DAY),
(@sek,'disposisi','Surat Permohonan Kerjasama dengan PT.Mulia Bersama telah didisposisi oleh pimpinan.','cek.php?id=7',0,NOW()-INTERVAL 300 MINUTE);

-- Sesuaikan tanggal surat arsip dengan periode pada nomor surat
UPDATE surat SET tanggal_surat=CURDATE()-INTERVAL 1170 DAY, tanggal_terima=CURDATE()-INTERVAL 1170 DAY WHERE id=12;
UPDATE surat SET tanggal_surat=CURDATE()-INTERVAL 1130 DAY, tanggal_terima=CURDATE()-INTERVAL 1130 DAY WHERE id=13;
UPDATE surat SET tanggal_surat=CURDATE()-INTERVAL 1030 DAY, tanggal_terima=CURDATE()-INTERVAL 1030 DAY WHERE id=14;
UPDATE surat SET tanggal_surat=CURDATE()-INTERVAL 940 DAY, tanggal_terima=CURDATE()-INTERVAL 940 DAY WHERE id=15;
UPDATE surat SET tanggal_surat=CURDATE()-INTERVAL 900 DAY, tanggal_terima=CURDATE()-INTERVAL 900 DAY WHERE id=16;
UPDATE surat SET tanggal_surat=CURDATE()-INTERVAL 650 DAY, tanggal_terima=CURDATE()-INTERVAL 650 DAY WHERE id=17;
UPDATE surat SET tanggal_surat=CURDATE()-INTERVAL 540 DAY, tanggal_terima=CURDATE()-INTERVAL 540 DAY WHERE id=18;
UPDATE surat SET tanggal_surat=CURDATE()-INTERVAL 470 DAY, tanggal_terima=CURDATE()-INTERVAL 470 DAY WHERE id=19;
