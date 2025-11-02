-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 02, 2025 at 03:30 PM
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
(4, '123456', 'abdul ', 'Farmasi', 'Laki-laki', 'jayapura papua ', '081240288596', 'okedang');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_praktikum`
--

CREATE TABLE `jadwal_praktikum` (
  `id` int NOT NULL,
  `id_mk` int NOT NULL,
  `id_dosen` int NOT NULL,
  `id_ruangan` int NOT NULL,
  `hari` varchar(20) NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `kuota` int NOT NULL,
  `kuota_awal` int DEFAULT NULL,
  `peserta` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jadwal_praktikum`
--

INSERT INTO `jadwal_praktikum` (`id`, `id_mk`, `id_dosen`, `id_ruangan`, `hari`, `jam_mulai`, `jam_selesai`, `kuota`, `kuota_awal`, `peserta`) VALUES
(22, 22, 3, 1, 'senin', '11:38:00', '14:41:00', 5, 5, 0),
(26, 23, 3, 1, 'senin', '07:09:00', '09:20:00', 20, 20, 0),
(27, 23, 3, 1, 'senin', '02:20:00', '02:30:00', 19, 20, 0);

-- --------------------------------------------------------

--
-- Table structure for table `kontrak_mk`
--

CREATE TABLE `kontrak_mk` (
  `id` int NOT NULL,
  `id_mahasiswa` int NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `mk_dikontrak` varchar(255) NOT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kontrak_mk`
--

INSERT INTO `kontrak_mk` (`id`, `id_mahasiswa`, `nim`, `nama`, `no_hp`, `mk_dikontrak`, `bukti_pembayaran`, `status`, `created_at`) VALUES
(6, 3, '12345', 'abdul j', '08000800', 'imk', '1762067688_Jeri Ardianto Mangari_22421007_Manajemen Jaringan Komputer.pdf', 'Disetujui', '2025-11-02 07:14:48'),
(9, 4, '12345678', 'Jeri Arianto', '08000800', 'imk,okee,pbo', '1762097330_Jeri Ardianto Mangari_22421007_Manajemen Jaringan Komputer.pdf', 'Menunggu', '2025-11-02 15:28:50');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jurusan` enum('Farmasi','Analis Kesehatan') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
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
  `modul` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `matakuliah_praktikum`
--

INSERT INTO `matakuliah_praktikum` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`, `modul`) VALUES
(22, 'mk002', 'imk', 2, '2', 'Modul_Praktikum_imk.pdf'),
(23, 'mk003', 'pbo', 2, '7', 'Modul_Praktikum_pbo.pdf'),
(24, 'mk004', 'okee', 1, '7', 'Modul_Praktikum_okee.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `pilihan_jadwal`
--

CREATE TABLE `pilihan_jadwal` (
  `id` int NOT NULL,
  `id_mahasiswa` int NOT NULL,
  `id_jadwal` int NOT NULL,
  `tanggal_daftar` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pilihan_jadwal`
--

INSERT INTO `pilihan_jadwal` (`id`, `id_mahasiswa`, `id_jadwal`, `tanggal_daftar`) VALUES
(12, 4, 22, '2025-11-02 14:51:17'),
(13, 4, 27, '2025-11-02 15:18:38');

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

--
-- Indexes for dumped tables
--

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
  ADD KEY `id_mk` (`id_mk`),
  ADD KEY `id_dosen` (`id_dosen`),
  ADD KEY `id_ruangan` (`id_ruangan`);

--
-- Indexes for table `kontrak_mk`
--
ALTER TABLE `kontrak_mk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_mahasiswa` (`id_mahasiswa`);

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
  ADD UNIQUE KEY `kode_mk` (`kode_mk`);

--
-- Indexes for table `pilihan_jadwal`
--
ALTER TABLE `pilihan_jadwal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_mahasiswa` (`id_mahasiswa`),
  ADD KEY `id_jadwal` (`id_jadwal`);

--
-- Indexes for table `ruangan`
--
ALTER TABLE `ruangan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_ruangan` (`kode_ruangan`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jadwal_praktikum`
--
ALTER TABLE `jadwal_praktikum`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `kontrak_mk`
--
ALTER TABLE `kontrak_mk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jadwal_praktikum`
--
ALTER TABLE `jadwal_praktikum`
  ADD CONSTRAINT `jadwal_praktikum_ibfk_1` FOREIGN KEY (`id_mk`) REFERENCES `matakuliah_praktikum` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_praktikum_ibfk_2` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_praktikum_ibfk_3` FOREIGN KEY (`id_ruangan`) REFERENCES `ruangan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kontrak_mk`
--
ALTER TABLE `kontrak_mk`
  ADD CONSTRAINT `kontrak_mk_ibfk_1` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pilihan_jadwal`
--
ALTER TABLE `pilihan_jadwal`
  ADD CONSTRAINT `pilihan_jadwal_ibfk_1` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pilihan_jadwal_ibfk_2` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_praktikum` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
