-- Ecolife — skema database (MySQL 5.7+ / MariaDB 10.3+)
-- Impor lewat phpMyAdmin (tab Import) atau: mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS ecolife2
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecolife2;

-- Akun pengguna. Role hanya diubah di sisi server (admin/index.php atau database/create_admin.php).
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(60)  NOT NULL,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at    DATETIME NOT NULL,
  last_login_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Satu baris = satu kali menekan "Simpan hari ini".
CREATE TABLE IF NOT EXISTS habit_entries (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NOT NULL,
  entry_date   DATE NOT NULL,
  total_points SMALLINT UNSIGNED NOT NULL,
  created_at   DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_entries_user (user_id, id),
  CONSTRAINT fk_entries_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rincian kebiasaan per pencatatan. UNIQUE (user, tanggal, kebiasaan) menjamin satu
-- kebiasaan hanya memberi poin SATU KALI per hari, meski permintaan dikirim ulang.
CREATE TABLE IF NOT EXISTS habit_entry_items (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_id   INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  entry_date DATE NOT NULL,
  habit_key  VARCHAR(32) NOT NULL,
  points     TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_day_habit (user_id, entry_date, habit_key),
  CONSTRAINT fk_items_entry FOREIGN KEY (entry_id) REFERENCES habit_entries (id) ON DELETE CASCADE,
  CONSTRAINT fk_items_user  FOREIGN KEY (user_id)  REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Riwayat prediksi (hanya hasil, bukan data isian).
CREATE TABLE IF NOT EXISTS predictions (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NOT NULL,
  result_class VARCHAR(10)  NOT NULL,
  p_tinggi     DECIMAL(5,1) NOT NULL,
  p_sedang     DECIMAL(5,1) NOT NULL,
  p_rendah     DECIMAL(5,1) NOT NULL,
  created_at   DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pred_user (user_id, id),
  CONSTRAINT fk_pred_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pembatasan percobaan login (anti brute-force).
CREATE TABLE IF NOT EXISTS login_attempts (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip           VARCHAR(45)  NOT NULL,
  email        VARCHAR(150) NOT NULL,
  attempted_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_attempts_email (email, attempted_at),
  KEY idx_attempts_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
