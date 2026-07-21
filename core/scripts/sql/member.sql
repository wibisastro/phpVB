-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 13, 2026 at 07:19 AM
-- Server version: 11.6.2-MariaDB-ubu2004
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gov2ayam`
--

-- --------------------------------------------------------

--
-- Table structure for table `member`
--
-- R1 role-framework (21 Jul 2026): enum role disamakan dgn taksonomi kanonik
-- 6-nilai (apps/gov2login/sql/member.sql). Portal lama hasil dump 4-nilai
-- migrasi manual:
--   ALTER TABLE member MODIFY role
--     enum('guest','member','admin','webmaster','owner','developer')
--     NOT NULL DEFAULT 'guest';
-- Sejak R1 gate TIDAK lagi membaca urutan enum DB (level dari enum UserRole),
-- tapi kolom tetap harus bisa MENYIMPAN keenam nilai.
--

CREATE TABLE `member` (
  `id` mediumint(8) UNSIGNED NOT NULL,
  `account_id` char(18) DEFAULT NULL,
  `fullname` char(32) NOT NULL DEFAULT '',
  `email` char(64) NOT NULL DEFAULT '',
  `status` enum('pending','active','suspended') NOT NULL DEFAULT 'pending',
  `role` enum('guest','member','admin','webmaster','owner','developer') NOT NULL DEFAULT 'guest',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `lastlogin_at` datetime NOT NULL DEFAULT current_timestamp(),
  `counter` mediumint(8) UNSIGNED NOT NULL DEFAULT 0,
  `kab_id` mediumint(8) UNSIGNED NOT NULL DEFAULT 0,
  `prov_id` mediumint(8) UNSIGNED NOT NULL DEFAULT 0,
  `kec_id` mediumint(8) UNSIGNED NOT NULL DEFAULT 0,
  `attr` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`id`,`kab_id`) USING BTREE,
  ADD KEY `email` (`email`),
  ADD KEY `kab_id` (`prov_id`,`kab_id`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
