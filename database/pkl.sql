-- ============================================================
-- Sistem Informasi Monitoring PKL
-- File  : pkl.sql
-- Cara  : Import lewat phpMyAdmin (pilih database dulu atau
--         biarkan script ini yang membuat database-nya)
-- ============================================================

-- Buat & pilih database
CREATE DATABASE IF NOT EXISTS `pkl_monitoring`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pkl_monitoring`;

-- ============================================================
-- 1. TABEL users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(100)    NOT NULL,
  `username`   VARCHAR(50)     NOT NULL,
  `password`   VARCHAR(255)    NOT NULL,
  `role`       ENUM('admin','siswa') NOT NULL DEFAULT 'siswa',
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. TABEL siswa
-- ============================================================
CREATE TABLE IF NOT EXISTS `siswa` (
  `id`                        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`                   INT UNSIGNED NOT NULL,
  `nis`                       VARCHAR(20)  NOT NULL,
  `kelas`                     VARCHAR(20)  NOT NULL,
  `tempat_pkl`                VARCHAR(150) NOT NULL,
  `nama_pembimbing_industri`  VARCHAR(100) NOT NULL,
  `no_hp_pembimbing_industri` VARCHAR(20)  NOT NULL,
  `guru_pembimbing_id`        INT UNSIGNED NOT NULL,
  `tgl_mulai`                 DATE         NOT NULL,
  `tgl_selesai`               DATE         NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nis` (`nis`),
  UNIQUE KEY `uq_user_id` (`user_id`),
  KEY `idx_guru_pembimbing` (`guru_pembimbing_id`),
  KEY `idx_kelas` (`kelas`),
  CONSTRAINT `fk_siswa_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_siswa_guru`
    FOREIGN KEY (`guru_pembimbing_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. TABEL jurnal_harian
-- ============================================================
CREATE TABLE IF NOT EXISTS `jurnal_harian` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `siswa_id`           INT UNSIGNED NOT NULL,
  `tanggal`            DATE         NOT NULL,
  `kegiatan`           TEXT         NOT NULL,
  `kendala`            TEXT,
  `status_verifikasi`  ENUM('menunggu','diverifikasi','ditolak')
                         NOT NULL DEFAULT 'menunggu',
  `catatan_admin`      TEXT,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_siswa_id`  (`siswa_id`),
  KEY `idx_tanggal`   (`tanggal`),
  KEY `idx_status`    (`status_verifikasi`),
  CONSTRAINT `fk_jurnal_siswa`
    FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. TABEL nilai
-- ============================================================
CREATE TABLE IF NOT EXISTS `nilai` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `siswa_id`         INT UNSIGNED NOT NULL,
  `aspek_penilaian`  ENUM('Kedisiplinan','Kompetensi','Sikap','Kerja Sama')
                       NOT NULL,
  `nilai`            TINYINT UNSIGNED NOT NULL
                       CHECK (`nilai` BETWEEN 0 AND 100),
  `tanggal_input`    DATE         NOT NULL,
  `diberikan_oleh`   INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  -- Satu siswa hanya boleh punya 1 nilai per aspek
  UNIQUE KEY `uq_siswa_aspek` (`siswa_id`, `aspek_penilaian`),
  KEY `idx_nilai_siswa`  (`siswa_id`),
  KEY `idx_nilai_pemberi` (`diberikan_oleh`),
  CONSTRAINT `fk_nilai_siswa`
    FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nilai_user`
    FOREIGN KEY (`diberikan_oleh`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA DUMMY
-- Password semua akun: Demo@1234
-- Hash dibuat dengan password_hash('Demo@1234', PASSWORD_BCRYPT)
-- Jika login gagal, jalankan dulu: http://localhost/Monitoring-PKL/database/reset_password.php
-- ============================================================

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`) VALUES
-- Admin / Guru Pembimbing
(1, 'Budi Santoso, S.Kom',  'admin',    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77bqiV', 'admin'),
(2, 'Siti Rahayu, S.Pd',   'guru2',    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77bqiV', 'admin'),
-- Siswa
(3, 'Andi Pratama',         'andi',     '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77bqiV', 'siswa'),
(4, 'Dewi Lestari',         'dewi',     '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77bqiV', 'siswa'),
(5, 'Fajar Nugroho',        'fajar',    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77bqiV', 'siswa'),
(6, 'Maya Sari',            'maya',     '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77bqiV', 'siswa'),
(7, 'Rizky Firmansyah',     'rizky',    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77bqiV', 'siswa');

INSERT INTO `siswa`
  (`id`,`user_id`,`nis`,`kelas`,`tempat_pkl`,
   `nama_pembimbing_industri`,`no_hp_pembimbing_industri`,
   `guru_pembimbing_id`,`tgl_mulai`,`tgl_selesai`) VALUES
(1, 3, '2324001', 'XII RPL 1', 'PT Maju Jaya Teknologi',
   'Ahmad Fauzi', '08123456701', 1, '2026-07-01', '2026-09-30'),
(2, 4, '2324002', 'XII RPL 1', 'CV Kreasi Digital',
   'Rina Wati', '08123456702', 1, '2026-07-01', '2026-09-30'),
(3, 5, '2324003', 'XII RPL 2', 'Dinas Kominfo Kota',
   'Hendra Gunawan', '08123456703', 2, '2026-07-01', '2026-09-30'),
(4, 6, '2324004', 'XII RPL 2', 'Bank BPD Cabang Utama',
   'Sri Wahyuni', '08123456704', 2, '2026-07-01', '2026-09-30'),
(5, 7, '2324005', 'XII TKJ 1', 'PT Nusa Network Solutions',
   'Deni Kurniawan', '08123456705', 1, '2026-07-01', '2026-09-30');

INSERT INTO `jurnal_harian`
  (`siswa_id`,`tanggal`,`kegiatan`,`kendala`,`status_verifikasi`,`catatan_admin`) VALUES
-- Andi (siswa_id=1)
(1,'2026-07-01','Orientasi pengenalan lingkungan kerja dan perkenalan dengan tim IT.',
   NULL,'diverifikasi', NULL),
(1,'2026-07-02','Membantu instalasi software antivirus di 10 unit komputer.',
   'Satu unit komputer tidak mau booting.','diverifikasi','Kegiatan sudah sesuai rencana PKL.'),
(1,'2026-07-03','Belajar dasar jaringan LAN bersama teknisi senior.',
   NULL,'menunggu', NULL),
(1,'2026-07-04','Membantu perbaikan kabel jaringan di lantai 2.',
   'Kabel terlalu pendek, harus ganti kabel baru.','ditolak','Tuliskan kegiatan lebih detail dan sertakan solusi yang dilakukan.'),
(1,'2026-07-07','Membuat dokumentasi inventaris perangkat keras.',
   NULL,'menunggu', NULL),
-- Dewi (siswa_id=2)
(2,'2026-07-01','Perkenalan tim dan briefing proyek desain grafis.',
   NULL,'diverifikasi', NULL),
(2,'2026-07-02','Membuat desain banner promosi menggunakan Canva.',
   NULL,'diverifikasi', NULL),
(2,'2026-07-03','Revisi desain sesuai masukan klien.',
   'Klien meminta perubahan warna tema.','menunggu', NULL),
-- Fajar (siswa_id=3)
(3,'2026-07-01','Mempelajari sistem informasi yang digunakan Dinas.',
   NULL,'diverifikasi', NULL),
(3,'2026-07-02','Membantu entry data penduduk ke dalam sistem.',
   'Sistem sempat down selama 30 menit.','menunggu', NULL),
-- Maya (siswa_id=4)
(4,'2026-07-01','Orientasi dan pengenalan prosedur kerja bank.',
   NULL,'diverifikasi', NULL),
(4,'2026-07-02','Membantu teller melayani nasabah.',
   NULL,'diverifikasi', NULL),
-- Rizky (siswa_id=5)
(5,'2026-07-01','Setup server baru untuk keperluan backup data.',
   NULL,'menunggu', NULL),
(5,'2026-07-02','Konfigurasi VLAN pada switch manageable.',
   'Dokumentasi konfigurasi lama tidak lengkap.','menunggu', NULL);

INSERT INTO `nilai`
  (`siswa_id`,`aspek_penilaian`,`nilai`,`tanggal_input`,`diberikan_oleh`) VALUES
-- Nilai Andi
(1,'Kedisiplinan', 88,'2026-09-01',1),
(1,'Kompetensi',   85,'2026-09-01',1),
(1,'Sikap',        90,'2026-09-01',1),
(1,'Kerja Sama',   87,'2026-09-01',1),
-- Nilai Dewi
(2,'Kedisiplinan', 92,'2026-09-01',1),
(2,'Kompetensi',   89,'2026-09-01',1),
(2,'Sikap',        91,'2026-09-01',1),
(2,'Kerja Sama',   90,'2026-09-01',1),
-- Nilai Fajar (belum semua diisi)
(3,'Kedisiplinan', 80,'2026-09-01',2),
(3,'Kompetensi',   78,'2026-09-01',2);
