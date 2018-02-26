-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 29, 2017 at 06:54 AM
-- Server version: 10.1.26-MariaDB-0+deb9u1
-- PHP Version: 7.0.19-1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `krisna_2018`
--

-- --------------------------------------------------------

--
-- Table structure for table `tematik`
--

CREATE TABLE `tematik` (
  `id` int(11) NOT NULL,
  `kode` varchar(10) NOT NULL,
  `nama` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tematik`
--

INSERT INTO `tematik` (`id`, `kode`, `nama`) VALUES
(1, '001', 'Anggaran Infrastruktur'),
(2, '002', 'Kerjasama Selatan-Selatan dan Triangular (KSST)'),
(3, '003', 'Anggaran Responsif Gender'),
(4, '004', 'Mitigasi perubahan Iklim'),
(5, '005', 'Anggaran Pendidikan'),
(6, '006', 'Anggaran Kesehatan'),
(7, '007', 'Adaptasi perubahan iklim'),
(8, '000', 'Bukan Tematik');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tematik`
--
ALTER TABLE `tematik`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tematik`
--
ALTER TABLE `tematik`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
