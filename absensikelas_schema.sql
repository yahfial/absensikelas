-- AbsensiKelas (MySQL 8+)
-- Langkah 4A: DDL (CREATE DATABASE + TABLES + FK + INDEX)
-- Engine: InnoDB, Charset: utf8mb4

CREATE DATABASE IF NOT EXISTS absensikelas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE absensikelas;

-- ====== 1) USERS (Admin/Dosen) ======
CREATE TABLE IF NOT EXISTS users (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100) NOT NULL,
  email        VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role         ENUM('ADMIN','DOSEN') NOT NULL DEFAULT 'DOSEN',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ====== 2) KELAS ======
CREATE TABLE IF NOT EXISTS kelas (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_kelas VARCHAR(30) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_kelas_nama (nama_kelas)
) ENGINE=InnoDB;

-- ====== 3) MATA KULIAH ======
CREATE TABLE IF NOT EXISTS mata_kuliah (
  id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_mk   VARCHAR(20) NOT NULL,
  nama_mk   VARCHAR(120) NOT NULL,
  sks       TINYINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mk_kode (kode_mk)
) ENGINE=InnoDB;

-- ====== 4) MAHASISWA ======
CREATE TABLE IF NOT EXISTS mahasiswa (
  id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nim       VARCHAR(12) NOT NULL,
  nama      VARCHAR(120) NOT NULL,
  angkatan  CHAR(4) NOT NULL,
  kelas_id  BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_mhs_nim (nim),
  KEY idx_mhs_kelas (kelas_id),

  CONSTRAINT fk_mhs_kelas
    FOREIGN KEY (kelas_id) REFERENCES kelas(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ====== 5) PERTEMUAN ======
CREATE TABLE IF NOT EXISTS pertemuan (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tanggal       DATE NOT NULL,
  topik         VARCHAR(200) NULL,
  kelas_id      BIGINT UNSIGNED NOT NULL,
  mata_kuliah_id BIGINT UNSIGNED NOT NULL,
  dosen_id      BIGINT UNSIGNED NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  KEY idx_ptm_tanggal (tanggal),
  KEY idx_ptm_kelas (kelas_id),
  KEY idx_ptm_mk (mata_kuliah_id),
  KEY idx_ptm_dosen (dosen_id),

  CONSTRAINT fk_ptm_kelas
    FOREIGN KEY (kelas_id) REFERENCES kelas(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT fk_ptm_mk
    FOREIGN KEY (mata_kuliah_id) REFERENCES mata_kuliah(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT fk_ptm_dosen
    FOREIGN KEY (dosen_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ====== 6) ABSENSI ======
CREATE TABLE IF NOT EXISTS absensi (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pertemuan_id  BIGINT UNSIGNED NOT NULL,
  mahasiswa_id  BIGINT UNSIGNED NOT NULL,
  status        ENUM('HADIR','IZIN','SAKIT','ALPA') NOT NULL,
  jam_input     TIME NULL,
  keterangan    VARCHAR(200) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  KEY idx_abs_ptm (pertemuan_id),
  KEY idx_abs_mhs (mahasiswa_id),
  KEY idx_abs_status (status),

  -- Mencegah 1 mahasiswa absen 2x pada pertemuan yang sama:
  UNIQUE KEY uq_abs_unique (pertemuan_id, mahasiswa_id),

  CONSTRAINT fk_abs_ptm
    FOREIGN KEY (pertemuan_id) REFERENCES pertemuan(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

  CONSTRAINT fk_abs_mhs
    FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB;
