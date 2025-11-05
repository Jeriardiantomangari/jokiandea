-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 04, 2025 at 10:35 AM
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

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nama`) VALUES
(1, 'admin', 'admin123', 'Administrator');

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
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id`, `nidn`, `nama`, `prodi`, `jenis_kelamin`, `alamat`, `no_hp`, `password`) VALUES
(3, '123456333334', 'Jeri Arianto', 'Farmasi', 'Laki-laki', 'jln. yoka Kompleks Eks.APDN', '081240288596', 'okedang'),
(4, '123456', 'abdul', 'Farmasi', 'Laki-laki', 'jayapura papua', '081240288596', 'okedang'),
(5, '1234562233', 'Jeri Arianto', 'Farmasi', 'Laki-laki', 'jln. yoka Kompleks Eks.APDN', '081240288596<', 'okedang');

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
(30, 22, 3, 2, 4, 'Selasa', '02:20:00', '02:30:00', 1, 2, 0);

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
(5, 3, 4, '12345', 'abdul j', '08000800', 'imk', '1762183039_Jeri_Ardianto_Mangari_22421007_Manajemen_Jaringan_Komputer.pdf', 'Disetujui', '2025-11-03 15:17:19');

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
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `nim`, `nama`, `jurusan`, `jenis_kelamin`, `alamat`, `no_hp`, `password`) VALUES
(3, '12345', 'abdul j', 'Farmasi', 'Perempuan', 'perumnas1', '08000800', 'swdef'),
(4, '12345678', 'Jeri Arianto', 'Farmasi', 'Laki-laki', 'jayapura', '08000800', 'okedang');

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
(22, 'mk002', 'imk', 2, '2', NULL, 'Modul_Praktikum_imk.pdf'),
(23, 'mk003', 'pbo', 2, '7', NULL, 'Modul_Praktikum_pbo.pdf'),
(24, 'mk004', 'okee', 1, '7', NULL, 'Modul_Praktikum_okee.pdf');

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
(3, 3, 5, 30, 22, 4, '2025-11-03 15:18:49');

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
(1, 'oyfv', 'eegg', 4, 'feee'),
(2, 'wefefdf', 'ddf', 30, 'feeepp');

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
(4, 'Ganjil', '2025/2026', 'Aktif');

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
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nidn` (`nidn`);

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
  ADD UNIQUE KEY `nim` (`nim`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi_detail`
--
ALTER TABLE `absensi_detail`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `absensi_sesi`
--
ALTER TABLE `absensi_sesi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jadwal_praktikum`
--
ALTER TABLE `jadwal_praktikum`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `kontrak_mk`
--
ALTER TABLE `kontrak_mk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `matakuliah_praktikum`
--
ALTER TABLE `matakuliah_praktikum`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `pilihan_jadwal`
--
ALTER TABLE `pilihan_jadwal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `semester`
--
ALTER TABLE `semester`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
