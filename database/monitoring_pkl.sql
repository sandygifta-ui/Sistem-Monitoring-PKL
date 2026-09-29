-- ============================================================
-- Sistem Informasi Monitoring PKL
-- File    : monitoring_pkl.sql
-- Cara    : Buka phpMyAdmin → tab Import → pilih file ini → Go
-- ============================================================

CREATE DATABASE IF NOT EXISTS `monitoring_pkl`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `monitoring_pkl`;

-- ============================================================
-- 1. TABEL users
--    Akun login: admin (guru pembimbing) dan siswa
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(100)  NOT NULL,
  `username`   VARCHAR(50)   NOT NULL,
  `password`   VARCHAR(255)  NOT NULL COMMENT 'Hasil password_hash(), bukan teks asli',
  `role`       ENUM('admin','siswa') NOT NULL DEFAULT 'siswa',
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. TABEL siswa
--    Data PKL tiap siswa — tempat & mentor bisa beda-beda
-- ============================================================
CREATE TABLE IF NOT EXISTS `siswa` (
  `id`                        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`                   INT UNSIGNED  NOT NULL COMMENT 'Relasi ke users.id (akun siswa)',
  `nis`                       VARCHAR(20)   NOT NULL COMMENT 'Nomor Induk Siswa',
  `kelas`                     VARCHAR(20)   NOT NULL COMMENT 'Contoh: XII RPL 1',
  `tempat_pkl`                VARCHAR(150)  NOT NULL COMMENT 'Nama perusahaan/instansi',
  `alamat_pkl`                VARCHAR(255)  DEFAULT NULL COMMENT 'Alamat tempat PKL, boleh kosong',
  `nama_pembimbing_industri`  VARCHAR(100)  NOT NULL COMMENT 'Mentor di tempat PKL, hanya data',
  `no_hp_pembimbing_industri` VARCHAR(20)   DEFAULT NULL COMMENT 'Boleh kosong',
  `guru_pembimbing_id`        INT UNSIGNED  NOT NULL COMMENT 'Relasi ke users.id (guru/admin)',
  `tgl_mulai`                 DATE          NOT NULL,
  `tgl_selesai`               DATE          NOT NULL COMMENT 'Tidak boleh sebelum tgl_mulai',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nis`     (`nis`),
  UNIQUE KEY `uq_user_id` (`user_id`),
  KEY `idx_guru_pembimbing` (`guru_pembimbing_id`),
  KEY `idx_kelas`           (`kelas`),
  CONSTRAINT `fk_siswa_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_siswa_guru`
    FOREIGN KEY (`guru_pembimbing_id`)
    REFERENCES `users`(`id`)
    ON DELETE RESTRICT   -- Guru tidak bisa dihapus selama masih membimbing siswa
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. TABEL jurnal_harian
--    Catatan kegiatan harian siswa, 1 jurnal per siswa per hari
-- ============================================================
CREATE TABLE IF NOT EXISTS `jurnal_harian` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `siswa_id`          INT UNSIGNED  NOT NULL,
  `tanggal`           DATE          NOT NULL,
  `kegiatan`          TEXT          NOT NULL,
  `kendala`           TEXT          DEFAULT NULL COMMENT 'Boleh kosong',
  `status_verifikasi` ENUM('menunggu','diverifikasi','ditolak')
                      NOT NULL DEFAULT 'menunggu',
  `catatan_admin`     TEXT          DEFAULT NULL COMMENT 'Catatan guru saat verifikasi',
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  -- 1 jurnal per siswa per hari
  UNIQUE KEY `uq_siswa_tanggal` (`siswa_id`, `tanggal`),
  KEY `idx_status`  (`status_verifikasi`),
  KEY `idx_tanggal` (`tanggal`),
  CONSTRAINT `fk_jurnal_siswa`
    FOREIGN KEY (`siswa_id`)
    REFERENCES `siswa`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. TABEL nilai
--    Penilaian per aspek untuk tiap siswa, 1 nilai per aspek per siswa
-- ============================================================
CREATE TABLE IF NOT EXISTS `nilai` (
  `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `siswa_id`         INT UNSIGNED    NOT NULL,
  `aspek_penilaian`  VARCHAR(50)     NOT NULL COMMENT 'Contoh: Kedisiplinan, Kompetensi, Sikap',
  `nilai`            TINYINT UNSIGNED NOT NULL COMMENT 'Rentang 0-100',
  `tanggal_input`    DATE            NOT NULL,
  `diberikan_oleh`   INT UNSIGNED    NOT NULL COMMENT 'Relasi ke users.id (admin/guru)',
  PRIMARY KEY (`id`),
  -- 1 nilai per aspek per siswa
  UNIQUE KEY `uq_siswa_aspek` (`siswa_id`, `aspek_penilaian`),
  KEY `idx_nilai_siswa`   (`siswa_id`),
  KEY `idx_nilai_pemberi` (`diberikan_oleh`),
  CONSTRAINT `fk_nilai_siswa`
    FOREIGN KEY (`siswa_id`)
    REFERENCES `siswa`(`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_nilai_user`
    FOREIGN KEY (`diberikan_oleh`)
    REFERENCES `users`(`id`)
    ON DELETE RESTRICT   -- Admin/guru tidak bisa dihapus jika pernah memberi nilai
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA DEMO
-- admin  → password: admin123
-- siswa1 → password: siswa123
-- siswa2 → password: siswa123
--
-- Hash dibuat dengan password_hash(), sesuai versi PHP di XAMPP.
-- Jika login gagal, jalankan:
--   http://localhost/Monitoring-PKL/database/reset_password.php
-- ============================================================

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`) VALUES
(1, 'Bu Rina Wulandari, S.Kom', 'admin',  '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77bqiV', 'admin'),
(2, 'Sandya Gifta',             'siswa1', '$2y$10$u.GkGB6XLFO.JQtKDwayle96zG9jGRXVpHpLlq4RA1o8oNzUOFT3S', 'siswa'),
(3, 'Bagas Pratama',            'siswa2', '$2y$10$u.GkGB6XLFO.JQtKDwayle96zG9jGRXVpHpLlq4RA1o8oNzUOFT3S', 'siswa');

INSERT INTO `siswa`
  (`id`,`user_id`,`nis`,`kelas`,`tempat_pkl`,`alamat_pkl`,
   `nama_pembimbing_industri`,`no_hp_pembimbing_industri`,
   `guru_pembimbing_id`,`tgl_mulai`,`tgl_selesai`) VALUES
(1, 2, '2324001', 'XII RPL', 'Kantor Kelurahan Contoh',
   'Jl. Contoh No. 1, Kota Contoh',
   'Bu Sari', '08111111111', 1, '2026-08-03', '2026-11-27'),
(2, 3, '2324002', 'XII RPL', 'Toko Komputer Maju',
   'Jl. Maju No. 5, Kota Contoh',
   'Pak Andi', '08222222222', 1, '2026-08-03', '2026-11-27');

INSERT INTO `jurnal_harian`
  (`siswa_id`,`tanggal`,`kegiatan`,`kendala`,`status_verifikasi`,`catatan_admin`) VALUES
(1, '2026-09-24',
   'Membantu pelayanan loket dan scan dokumen.',
   NULL, 'menunggu', NULL),
(1, '2026-09-25',
   'Input data warga ke sistem dan rekap arsip surat.',
   'Koneksi internet sempat lambat.', 'diverifikasi', 'Bagus, lanjutkan.'),
(2, '2026-09-24',
   'Instalasi ulang Windows dan update driver pelanggan.',
   NULL, 'menunggu', NULL);

INSERT INTO `nilai`
  (`siswa_id`,`aspek_penilaian`,`nilai`,`tanggal_input`,`diberikan_oleh`) VALUES
(1, 'Kedisiplinan', 90, '2026-09-26', 1),
(1, 'Kompetensi',   86, '2026-09-26', 1),
(1, 'Sikap',        88, '2026-09-26', 1),
(2, 'Kedisiplinan', 85, '2026-09-26', 1);
