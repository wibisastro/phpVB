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
-- Table structure for table `options`
--

CREATE TABLE `options` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `parent_id` smallint(5) UNSIGNED NOT NULL,
  `app` char(64) NOT NULL,
  `type` enum('checkbox','radio','option','textbox','text','service') NOT NULL DEFAULT 'option' COMMENT 'level 2 = checbox/radio/textbox/text, level 1=option,service',
  `level` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'hanya 2 level',
  `level_label` enum('cluster','option') NOT NULL,
  `privilege` char(16) NOT NULL DEFAULT '',
  `nama` char(32) NOT NULL,
  `keterangan` char(255) DEFAULT NULL,
  `status` enum('on','off') NOT NULL DEFAULT 'off',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` char(18) DEFAULT '0',
  `children` int(11) NOT NULL DEFAULT 0,
  `value` text DEFAULT NULL,
  `modify_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `modify_by` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
