CREATE DATABASE IF NOT EXISTS sipera CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sipera;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('sekretaris','pimpinan') NOT NULL DEFAULT 'sekretaris',
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  otp_hash VARCHAR(255) NULL,
  otp_expires_at DATETIME NULL,
  otp_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  otp_sent_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Akun awal (sudah aktif). Ganti password setelah login pertama.
-- pimpinan@sipera.test   / Pimpinan123
-- sekretaris@sipera.test / Sekretaris123
INSERT INTO users (nama, email, password, role, is_verified) VALUES
('Pimpinan SIPERA',   'pimpinan@sipera.test',   '$2y$10$yu7Gt9He08U0lDSHHuUKneZ.FeNpRRPuRV9bV0up2nA.b15Pyp5ta', 'pimpinan',   1),
('Sekretaris SIPERA', 'sekretaris@sipera.test', '$2y$10$nD3OdmAoqgr1.bEgc4IiQ.nkgxBt4HANi4tYzMt.Q4kbcpwH9Ua56', 'sekretaris', 1);
