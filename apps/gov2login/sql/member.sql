-- phpMyAdmin SQL Dump
-- version 4.5.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 19, 2018 at 07:24 PM
-- Server version: 10.1.13-MariaDB
-- PHP Version: 5.6.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kpu_sidalih`
--

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `id` mediumint(7) UNSIGNED NOT NULL,
  `account_id` mediumint(5) UNSIGNED NOT NULL,
  `fullname` char(32) NOT NULL DEFAULT '',
  `nik` char(16) NOT NULL DEFAULT '',
  `email` char(64) NOT NULL DEFAULT '',
  `phone` char(16) NOT NULL DEFAULT '',
  `counter` mediumint(7) UNSIGNED NOT NULL DEFAULT '0',
  `status` enum('pending','active','suspended') NOT NULL DEFAULT 'pending',
  `role` enum('guest','member','admin','webmaster','owner','developer') NOT NULL DEFAULT 'guest',
  `lastlogin_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `prov_id` mediumint(7) UNSIGNED NOT NULL,
  `kab_id` mediumint(7) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

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
  MODIFY `id` mediumint(7) UNSIGNED NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
