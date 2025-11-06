-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 06, 2025 at 04:54 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_laboratorium`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi_detail`
--

CREATE TABLE `absensi_detail` (
  `id` int NOT NULL,
  `id_sesi` int NOT NULL,
  `id_mahasiswa` int NOT NULL,
  `status` enum('Hadir','Alpha','Izin') NOT NULL,
  `dicatat_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `absensi_detail`
--

INSERT INTO `absensi_detail` (`id`, `id_sesi`, `id_mahasiswa`, `status`, `dicatat_pada`) VALUES
(18, 19, 7, 'Hadir', '2025-11-06 01:52:22'),
(19, 19, 6, 'Alpha', '2025-11-06 01:52:22'),
(20, 19, 8, 'Izin', '2025-11-06 01:52:22'),
(21, 20, 7, 'Izin', '2025-11-06 01:53:15'),
(22, 20, 6, 'Alpha', '2025-11-06 01:53:15'),
(23, 20, 8, 'Hadir', '2025-11-06 01:53:15'),
(24, 21, 7, 'Alpha', '2025-11-06 01:54:50'),
(25, 21, 6, 'Hadir', '2025-11-06 01:54:50'),
(26, 21, 8, 'Alpha', '2025-11-06 01:54:50');

-- --------------------------------------------------------

--
-- Table structure for table `absensi_sesi`
--

CREATE TABLE `absensi_sesi` (
  `id` int NOT NULL,
  `id_jadwal` int NOT NULL,
  `id_dosen` int NOT NULL,
  `mulai_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `selesai_at` datetime DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `absensi_sesi`
--

INSERT INTO `absensi_sesi` (`id`, `id_jadwal`, `id_dosen`, `mulai_at`, `selesai_at`, `keterangan`) VALUES
(19, 36, 10, '2025-11-06 01:52:15', '2025-11-06 01:52:22', NULL),
(20, 35, 11, '2025-11-06 01:53:07', '2025-11-06 01:53:15', NULL),
(21, 37, 12, '2025-11-06 01:53:53', '2025-11-06 01:54:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id` int NOT NULL,
  `nidn` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `prodi` enum('Farmasi','Analis Kesehatan') NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') NOT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id`, `nidn`, `nama`, `prodi`, `jenis_kelamin`, `alamat`, `no_hp`, `user_id`) VALUES
(10, '22421001', 'Jeri', 'Farmasi', 'Laki-laki', 'jayapura papua ', '080008006799', 5),
(11, '22421002', 'abdul ', 'Analis Kesehatan', 'Laki-laki', 'jayapura papua ', '080008006788', 6),
(12, '22421003', 'Putri', 'Farmasi', 'Perempuan', 'jayapura papua ', '080008006777', 7);

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_praktikum`
--

CREATE TABLE `jadwal_praktikum` (
  `id` int NOT NULL,
  `id_mk` int NOT NULL,
  `id_dosen` int NOT NULL,
  `id_ruangan` int NOT NULL,
  `id_semester` int NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `kuota` int NOT NULL,
  `kuota_awal` int DEFAULT NULL,
  `peserta` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jadwal_praktikum`
--

INSERT INTO `jadwal_praktikum` (`id`, `id_mk`, `id_dosen`, `id_ruangan`, `id_semester`, `hari`, `jam_mulai`, `jam_selesai`, `kuota`, `kuota_awal`, `peserta`) VALUES
(35, 27, 11, 5, 5, 'Senin', '02:20:00', '02:30:00', 17, 20, 0),
(36, 26, 10, 4, 5, 'Selasa', '02:20:00', '02:30:00', 17, 20, 0),
(37, 25, 12, 3, 5, 'Rabu', '02:20:00', '02:30:00', 27, 30, 0);

-- --------------------------------------------------------

--
-- Table structure for table `kontrak_mk`
--

CREATE TABLE `kontrak_mk` (
  `id` int NOT NULL,
  `id_mahasiswa` int NOT NULL,
  `id_semester` int DEFAULT NULL,
  `nim` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `mk_dikontrak` varchar(1000) NOT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kontrak_mk`
--

INSERT INTO `kontrak_mk` (`id`, `id_mahasiswa`, `id_semester`, `nim`, `nama`, `no_hp`, `mk_dikontrak`, `bukti_pembayaran`, `status`, `created_at`) VALUES
(12, 6, 5, '22421007', 'Kaneji', '080008006766', 'Kerangka Manusia,Organ Dalam Manusia,Organ Tubuh manusia', '1762361154_Jeri_Ardianto_Mangari_22421007_Manajemen_Jaringan_Komputer.pdf', 'Disetujui', '2025-11-05 16:45:54'),
(13, 7, 5, '22421008', 'Jago', '080008006755', 'Kerangka Manusia,Organ Dalam Manusia,Organ Tubuh manusia', '1762361181_Jeri_Ardianto_Mangari_22421007_Manajemen_Jaringan_Komputer.pdf', 'Disetujui', '2025-11-05 16:46:21'),
(14, 8, 5, '22421009', 'Mullet', '080008006744', 'Kerangka Manusia,Organ Dalam Manusia,Organ Tubuh manusia', '1762361204_Jeri_Ardianto_Mangari_22421007_Manajemen_Jaringan_Komputer.pdf', 'Disetujui', '2025-11-05 16:46:44');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jurusan` enum('Farmasi','Analis Kesehatan') NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') NOT NULL,
  `alamat` text,
  `no_hp` varchar(20) DEFAULT NULL,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `nim`, `nama`, `jurusan`, `jenis_kelamin`, `alamat`, `no_hp`, `user_id`) VALUES
(6, '22421007', 'Kaneji', 'Farmasi', 'Laki-laki', 'perumnas1', '080008006766', 8),
(7, '22421008', 'Jago', 'Farmasi', 'Laki-laki', 'jayapura papua ', '080008006755', 9),
(8, '22421009', 'Mullet', 'Farmasi', 'Laki-laki', 'jayapura papua ', '080008006744', 10);

-- --------------------------------------------------------

--
-- Table structure for table `matakuliah_praktikum`
--

CREATE TABLE `matakuliah_praktikum` (
  `id` int NOT NULL,
  `kode_mk` varchar(20) NOT NULL,
  `nama_mk` varchar(100) NOT NULL,
  `sks` int NOT NULL,
  `semester` varchar(10) NOT NULL,
  `id_semester` int DEFAULT NULL,
  `modul` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `matakuliah_praktikum`
--

INSERT INTO `matakuliah_praktikum` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`, `id_semester`, `modul`) VALUES
(25, 'Mk1', 'Organ Tubuh manusia', 1, '7', NULL, 'Modul_Praktikum_Organ_Tubuh_manusia.pdf'),
(26, 'Mk2', 'Organ Dalam Manusia', 1, '5', NULL, 'Modul_Praktikum_Organ_Dalam_Manusia.pdf'),
(27, 'Mk3', 'Kerangka Manusia', 2, '5', NULL, 'Modul_Praktikum_Kerangka_Manusia.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `pilihan_jadwal`
--

CREATE TABLE `pilihan_jadwal` (
  `id` int NOT NULL,
  `id_mahasiswa` int NOT NULL,
  `id_kontrak` int DEFAULT NULL,
  `id_jadwal` int NOT NULL,
  `id_mk` int NOT NULL,
  `id_semester` int DEFAULT NULL,
  `tanggal_daftar` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pilihan_jadwal`
--

INSERT INTO `pilihan_jadwal` (`id`, `id_mahasiswa`, `id_kontrak`, `id_jadwal`, `id_mk`, `id_semester`, `tanggal_daftar`) VALUES
(9, 6, 12, 35, 27, 5, '2025-11-05 16:48:10'),
(10, 6, 12, 36, 26, 5, '2025-11-05 16:48:16'),
(11, 6, 12, 37, 25, 5, '2025-11-05 16:48:19'),
(12, 7, 13, 35, 27, 5, '2025-11-05 16:49:00'),
(13, 7, 13, 36, 26, 5, '2025-11-05 16:49:04'),
(14, 7, 13, 37, 25, 5, '2025-11-05 16:49:07'),
(15, 8, 14, 35, 27, 5, '2025-11-05 16:49:28'),
(16, 8, 14, 36, 26, 5, '2025-11-05 16:49:31'),
(17, 8, 14, 37, 25, 5, '2025-11-05 16:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `ruangan`
--

CREATE TABLE `ruangan` (
  `id` int NOT NULL,
  `kode_ruangan` varchar(20) NOT NULL,
  `nama_ruangan` varchar(100) NOT NULL,
  `kapasitas` int NOT NULL,
  `lokasi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ruangan`
--

INSERT INTO `ruangan` (`id`, `kode_ruangan`, `nama_ruangan`, `kapasitas`, `lokasi`) VALUES
(3, 'R01', 'Lab organ tubuh', 30, ' FIKES'),
(4, 'R02', 'Lab organ Dalam', 30, ' FIKES'),
(5, 'R03', 'Lab Kerangka Manusia', 30, 'FIKES');

-- --------------------------------------------------------

--
-- Table structure for table `semester`
--

CREATE TABLE `semester` (
  `id` int NOT NULL,
  `nama_semester` varchar(50) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `status` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `semester`
--

INSERT INTO `semester` (`id`, `nama_semester`, `tahun_ajaran`, `status`) VALUES
(5, 'Ganjil', '2025/2026', 'Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('Admin','Dosen','Mahasiswa') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `password`, `role`, `created_at`, `updated_at`) VALUES
(4, 'Administrator', 'admin123', 'Admin', '2025-11-05 23:18:05', NULL),
(5, 'Jeri', '22421001', 'Dosen', '2025-11-06 01:35:45', NULL),
(6, 'abdul ', '22421002', 'Dosen', '2025-11-06 01:36:21', NULL),
(7, 'Putri', '22421003', 'Dosen', '2025-11-06 01:37:08', NULL),
(8, 'Kaneji', '22421007', 'Mahasiswa', '2025-11-06 01:37:39', NULL),
(9, 'Jago', '22421008', 'Mahasiswa', '2025-11-06 01:38:24', NULL),
(10, 'Mullet', '22421009', 'Mahasiswa', '2025-11-06 01:38:51', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi_detail`
--
ALTER TABLE `absensi_detail`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unik_sesi_mahasiswa` (`id_sesi`,`id_mahasiswa`),
  ADD KEY `fk_absensi_detail_sesi` (`id_sesi`),
  ADD KEY `fk_absensi_detail_mahasiswa` (`id_mahasiswa`);

--
-- Indexes for table `absensi_sesi`
--
ALTER TABLE `absensi_sesi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_absensi_sesi_jadwal` (`id_jadwal`),
  ADD KEY `fk_absensi_sesi_dosen` (`id_dosen`);

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nidn` (`nidn`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `jadwal_praktikum`
--
ALTER TABLE `jadwal_praktikum`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_jadwal_ruang_waktu` (`id_semester`,`id_ruangan`,`hari`,`jam_mulai`,`jam_selesai`),
  ADD KEY `id_mk` (`id_mk`),
  ADD KEY `id_dosen` (`id_dosen`),
  ADD KEY `id_ruangan` (`id_ruangan`),
  ADD KEY `fk_jadwal_semester` (`id_semester`);

--
-- Indexes for table `kontrak_mk`
--
ALTER TABLE `kontrak_mk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unik_kontrak_per_mahasiswa_semester` (`id_mahasiswa`,`id_semester`),
  ADD KEY `id_mahasiswa` (`id_mahasiswa`),
  ADD KEY `fk_kontrak_semester` (`id_semester`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `matakuliah_praktikum`
--
ALTER TABLE `matakuliah_praktikum`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_mk` (`kode_mk`),
  ADD KEY `fk_matakuliah_semester` (`id_semester`);

--
-- Indexes for table `pilihan_jadwal`
--
ALTER TABLE `pilihan_jadwal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unik_pilihan_per_mahasiswa_mk_semester` (`id_mahasiswa`,`id_mk`,`id_semester`),
  ADD KEY `id_mahasiswa` (`id_mahasiswa`),
  ADD KEY `id_jadwal` (`id_jadwal`),
  ADD KEY `fk_pilihan_semester` (`id_semester`),
  ADD KEY `fk_pilihan_kontrak` (`id_kontrak`),
  ADD KEY `fk_pilihan_mk` (`id_mk`);

--
-- Indexes for table `ruangan`
--
ALTER TABLE `ruangan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_ruangan` (`kode_ruangan`);

--
-- Indexes for table `semester`
--
ALTER TABLE `semester`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi_detail`
--
ALTER TABLE `absensi_detail`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `absensi_sesi`
--
ALTER TABLE `absensi_sesi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `jadwal_praktikum`
--
ALTER TABLE `jadwal_praktikum`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `kontrak_mk`
--
ALTER TABLE `kontrak_mk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `matakuliah_praktikum`
--
ALTER TABLE `matakuliah_praktikum`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `pilihan_jadwal`
--
ALTER TABLE `pilihan_jadwal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `semester`
--
ALTER TABLE `semester`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi_detail`
--
ALTER TABLE `absensi_detail`
  ADD CONSTRAINT `fk_absensi_detail_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_absensi_detail_sesi` FOREIGN KEY (`id_sesi`) REFERENCES `absensi_sesi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `absensi_sesi`
--
ALTER TABLE `absensi_sesi`
  ADD CONSTRAINT `fk_absensi_sesi_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_absensi_sesi_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_praktikum` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dosen`
--
ALTER TABLE `dosen`
  ADD CONSTRAINT `fk_dosen_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `jadwal_praktikum`
--
ALTER TABLE `jadwal_praktikum`
  ADD CONSTRAINT `fk_jadwal_semester` FOREIGN KEY (`id_semester`) REFERENCES `semester` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_praktikum_ibfk_1` FOREIGN KEY (`id_mk`) REFERENCES `matakuliah_praktikum` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_praktikum_ibfk_2` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_praktikum_ibfk_3` FOREIGN KEY (`id_ruangan`) REFERENCES `ruangan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kontrak_mk`
--
ALTER TABLE `kontrak_mk`
  ADD CONSTRAINT `fk_kontrak_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_kontrak_semester` FOREIGN KEY (`id_semester`) REFERENCES `semester` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `fk_mhs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `matakuliah_praktikum`
--
ALTER TABLE `matakuliah_praktikum`
  ADD CONSTRAINT `fk_matakuliah_semester` FOREIGN KEY (`id_semester`) REFERENCES `semester` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pilihan_jadwal`
--
ALTER TABLE `pilihan_jadwal`
  ADD CONSTRAINT `fk_pilihan_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_praktikum` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pilihan_kontrak` FOREIGN KEY (`id_kontrak`) REFERENCES `kontrak_mk` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pilihan_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pilihan_mk` FOREIGN KEY (`id_mk`) REFERENCES `matakuliah_praktikum` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pilihan_semester` FOREIGN KEY (`id_semester`) REFERENCES `semester` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
