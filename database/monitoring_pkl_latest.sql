/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: monitoring_pkl
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `jurnal_harian`
--

DROP TABLE IF EXISTS `jurnal_harian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jurnal_harian` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `siswa_id` int(10) unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `kegiatan` text NOT NULL,
  `kendala` text DEFAULT NULL COMMENT 'Boleh kosong',
  `status_verifikasi` enum('menunggu','diverifikasi','ditolak') NOT NULL DEFAULT 'menunggu',
  `catatan_admin` text DEFAULT NULL COMMENT 'Catatan guru saat verifikasi',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_siswa_tanggal` (`siswa_id`,`tanggal`),
  KEY `idx_status` (`status_verifikasi`),
  KEY `idx_tanggal` (`tanggal`),
  CONSTRAINT `fk_jurnal_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jurnal_harian`
--

LOCK TABLES `jurnal_harian` WRITE;
/*!40000 ALTER TABLE `jurnal_harian` DISABLE KEYS */;
INSERT INTO `jurnal_harian` VALUES
(2,1,'2026-09-25','Input data warga ke sistem dan rekap arsip surat.','Koneksi internet sempat lambat.','diverifikasi','Bagus, lanjutkan.','2026-10-26 14:54:39','2026-10-26 14:54:39'),
(3,2,'2026-09-24','Instalasi ulang Windows dan update driver pelanggan.',NULL,'diverifikasi','Lebih lengkap lagi','2026-10-26 14:54:39','2026-10-26 15:08:30'),
(4,1,'2026-11-18','membersihkan data kotor menggunakan OpenRefine',NULL,'menunggu',NULL,'2026-11-18 13:25:45','2026-11-18 13:25:45'),
(5,3,'2026-11-18','Membuat Website menggunakian Spreadsheets dan Google Appscript',NULL,'menunggu',NULL,'2026-11-18 13:27:41','2026-11-18 13:27:41');
/*!40000 ALTER TABLE `jurnal_harian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nilai`
--

DROP TABLE IF EXISTS `nilai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nilai` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `siswa_id` int(10) unsigned NOT NULL,
  `aspek_penilaian` varchar(50) NOT NULL COMMENT 'Contoh: Kedisiplinan, Kompetensi, Sikap',
  `nilai` tinyint(3) unsigned NOT NULL COMMENT 'Rentang 0-100',
  `tanggal_input` date NOT NULL,
  `diberikan_oleh` int(10) unsigned NOT NULL COMMENT 'Relasi ke users.id (admin/guru)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_siswa_aspek` (`siswa_id`,`aspek_penilaian`),
  KEY `idx_nilai_siswa` (`siswa_id`),
  KEY `idx_nilai_pemberi` (`diberikan_oleh`),
  CONSTRAINT `fk_nilai_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nilai_user` FOREIGN KEY (`diberikan_oleh`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nilai`
--

LOCK TABLES `nilai` WRITE;
/*!40000 ALTER TABLE `nilai` DISABLE KEYS */;
INSERT INTO `nilai` VALUES
(1,1,'Kedisiplinan',90,'2026-11-18',1),
(2,1,'Kompetensi',86,'2026-11-18',1),
(3,1,'Sikap',88,'2026-11-18',1),
(4,2,'Kedisiplinan',85,'2026-09-26',1),
(5,3,'Kedisiplinan',85,'2026-11-18',1),
(6,3,'Kompetensi',51,'2026-11-18',1),
(7,3,'Sikap',85,'2026-11-18',1),
(8,3,'Kerja Sama',79,'2026-11-18',1),
(9,1,'Kerja Sama',75,'2026-11-18',1);
/*!40000 ALTER TABLE `nilai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `siswa`
--

DROP TABLE IF EXISTS `siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `siswa` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'Relasi ke users.id (akun siswa)',
  `nis` varchar(20) NOT NULL COMMENT 'Nomor Induk Siswa',
  `kelas` varchar(20) NOT NULL COMMENT 'Contoh: XII RPL 1',
  `tempat_pkl` varchar(150) NOT NULL COMMENT 'Nama perusahaan/instansi',
  `alamat_pkl` varchar(255) DEFAULT NULL COMMENT 'Alamat tempat PKL, boleh kosong',
  `nama_pembimbing_industri` varchar(100) NOT NULL COMMENT 'Mentor di tempat PKL, hanya data',
  `no_hp_pembimbing_industri` varchar(20) DEFAULT NULL COMMENT 'Boleh kosong',
  `guru_pembimbing_id` int(10) unsigned NOT NULL COMMENT 'Relasi ke users.id (guru/admin)',
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL COMMENT 'Tidak boleh sebelum tgl_mulai',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nis` (`nis`),
  UNIQUE KEY `uq_user_id` (`user_id`),
  KEY `idx_guru_pembimbing` (`guru_pembimbing_id`),
  KEY `idx_kelas` (`kelas`),
  CONSTRAINT `fk_siswa_guru` FOREIGN KEY (`guru_pembimbing_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_siswa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `siswa`
--

LOCK TABLES `siswa` WRITE;
/*!40000 ALTER TABLE `siswa` DISABLE KEYS */;
INSERT INTO `siswa` VALUES
(1,2,'18689','XII RPL','Edusoft Center','Jl. Ngenden, Ngebrak, Gentan','Khomarudin Syarif','08111111111',4,'2027-01-05','2027-03-05'),
(2,3,'2324002','XII RPL','Edusoft Center','Jl. Ngenden, Ngebrak, Gentan','Khomarudin Syarif','08222222222',7,'2026-08-03','2026-11-27'),
(3,5,'18690','XII RPL','Edusoft Center','Jl. Ngenden, Ngebrak, Gentan','Khomarudin Syarif','08111111111',1,'2027-01-05','2027-03-05'),
(4,9,'18659','XII RPL','Edusoft Center','Jl. Ngenden, Ngebrak, Gentan','Khomarudin Syarif','08989898989',6,'2027-01-05','2027-03-05'),
(5,10,'18666','XII RPL','Edusoft Center','Jl. Contoh No. 1, Kota Contoh','Khomarudin Syarif','08111111111',8,'2027-01-05','2027-03-05');
/*!40000 ALTER TABLE `siswa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Hasil password_hash(), bukan teks asli',
  `role` enum('admin','siswa') NOT NULL DEFAULT 'siswa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Arif Joko Sukatmo, S.Kom','admin','$2y$10$JoXF4fsiRZfWbhX1E3.eP.2gDl5aYXznY96S9ewnZPEawc/xNxvMC','admin','2026-10-26 14:54:39'),
(2,'Sandya Gifta Ulimaz Sofiamagaski','siswa1','$2y$10$A.u4fJEKIKvtUs6g66u4Le3ct8AKj3cmMqKrhCvW9cKv.hijO5dfa','siswa','2026-10-26 14:54:39'),
(3,'Junior Alfredo Benerd Setiawan','siswa2','$2y$10$A.u4fJEKIKvtUs6g66u4Le3ct8AKj3cmMqKrhCvW9cKv.hijO5dfa','siswa','2026-10-26 14:54:39'),
(4,'Binar Aris Purwaka S.Kom','guru1','$2y$10$3vM./Wuh17QguimIieKIdOyL1QufX3e58ZEvLEJ.A0IatHM2zP/bq','admin','2026-10-26 15:07:16'),
(5,'Siti Fatimah Nur Azzahra','siswa3','$2y$10$A.u4fJEKIKvtUs6g66u4Le3ct8AKj3cmMqKrhCvW9cKv.hijO5dfa','siswa','2026-10-26 15:12:37'),
(6,'Dinda Riski Pramessiwi, S.Pd.','Dinda','$2y$10$nkmNBGEjdV7TSCADxPdc2.xPmvdGhcx47qk7LXwA.CIObapcprTqK','admin','2026-10-27 02:20:01'),
(7,'Sri Waluya, S.Pd','Waluya','$2y$10$C5AXOqUrMds9/4wnqPzD0uNKmNrkh.I6qXUOD.mRYCr1PoV1gpXQK','admin','2026-10-27 02:22:34'),
(8,'Nafiah Nur Bachriah, S.ST.Par','Nafiah','$2y$10$tvxInumIaRD65xGhnNzPL.BJzKBFWXBTFByo0k9BU21TmHZZ7KVY.','admin','2026-10-27 02:26:00'),
(9,'Abryan Yoga Pratama','siswa4','$2y$10$A.u4fJEKIKvtUs6g66u4Le3ct8AKj3cmMqKrhCvW9cKv.hijO5dfa','siswa','2026-10-27 02:30:18'),
(10,'Aziz Achmad Juniar','Siswa5','$2y$10$A.u4fJEKIKvtUs6g66u4Le3ct8AKj3cmMqKrhCvW9cKv.hijO5dfa','siswa','2026-10-27 02:32:35');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-18 22:32:52
