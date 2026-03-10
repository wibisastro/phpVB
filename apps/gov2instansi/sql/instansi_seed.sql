--
-- Tabel instansi (rename dari kementerian)
-- Data seed dari sdi/sql/sdi_kukarkab.sql
--

DROP TABLE IF EXISTS `instansi`;

CREATE TABLE `instansi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int UNSIGNED NOT NULL DEFAULT '0',
  `children` smallint UNSIGNED NOT NULL DEFAULT '0',
  `level` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `level_label` enum('eselon1','eselon2') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'eselon1',
  `portal` char(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `kode` char(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `nama` char(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `tahun` smallint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` smallint UNSIGNED NOT NULL DEFAULT '0',
  `modify_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modify_by` smallint UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Data instansi (Kabupaten Kutai Kartanegara)
--

INSERT INTO `instansi` (`id`, `parent_id`, `children`, `level`, `level_label`, `portal`, `kode`, `nama`, `tahun`, `created_at`, `created_by`, `modify_at`, `modify_by`) VALUES
(35, 0, 37, 1, 'eselon1', 'bupati', '35', 'Bupati', 2020, '2023-01-23 12:23:24', 0, '2023-01-23 12:23:24', 0),
(1, 35, 1, 2, 'eselon2', 'setda', '01', 'Sekretariat Daerah', 2020, '2023-01-24 06:43:25', 0, '2023-01-24 06:43:25', 0),
(2, 35, 0, 2, 'eselon2', 'bkpsdm', '02', 'Badan Kepegawaian Dan Pengembangan Sumber Daya Manusia', 2020, '2023-01-23 12:23:56', 0, '2023-01-23 12:23:56', 0),
(3, 35, 0, 2, 'eselon2', 'kesbangpol', '03', 'Badan Kesatuan Bangsa dan Politik', 2020, '2023-01-23 12:24:23', 0, '2023-01-23 12:24:23', 0),
(4, 35, 0, 2, 'eselon2', 'bapenda', '04', 'Badan Pendapatan Daerah', 2020, '2023-01-23 12:24:44', 0, '2023-01-23 12:24:44', 0),
(5, 35, 0, 2, 'eselon2', 'brida', '05', 'Badan Riset dan Inovasi Daerah', 2020, '2023-01-23 12:25:04', 0, '2023-01-23 12:25:04', 0),
(6, 35, 0, 2, 'eselon2', 'bkad', '06', 'Badan Keuangan dan Aset Daerah', 2020, '2023-01-23 12:25:21', 0, '2023-01-23 12:25:21', 0),
(7, 35, 0, 2, 'eselon2', 'bapperida', '07', 'Badan Perencanaan Pembangunan dan Riset Daerah', 2020, '2023-01-23 12:25:47', 0, '2023-01-23 12:25:47', 0),
(9, 35, 0, 2, 'eselon2', 'disarpus', '09', 'Dinas Perpustakaan dan Kearsipan', 2020, '2023-01-23 12:26:16', 0, '2023-01-23 12:26:16', 0),
(10, 35, 0, 2, 'eselon2', 'dispora', '10', 'Dinas Kepemudaan dan Olahraga', 2020, '2023-01-23 12:29:42', 0, '2023-01-23 12:29:42', 0),
(11, 35, 0, 2, 'eselon2', 'disdukcapil', '11', 'Dinas Kependudukan dan Catatan Sipil', 2020, '2023-01-23 12:29:59', 0, '2023-01-23 12:29:59', 0),
(12, 35, 0, 2, 'eselon2', 'dinkes', '12', 'Dinas Kesehatan, Pengendalian Penduduk dan Keluarga Berencana', 2020, '2023-01-23 12:30:15', 0, '2023-01-23 12:30:15', 0),
(13, 35, 0, 2, 'eselon2', 'dishanpan', '13', 'Dinas Ketahanan Pangan dan Pertanian', 2020, '2023-01-23 12:30:37', 0, '2023-01-23 12:30:37', 0),
(14, 35, 0, 2, 'eselon2', 'diskominfo', '14', 'Dinas Komunikasi dan Informatika', 2020, '2023-01-23 12:30:50', 0, '2023-01-23 12:30:50', 0),
(15, 35, 0, 2, 'eselon2', 'diskopkukm', '15', 'Dinas Koperasi, Usaha Kecil Menengah, Perindustrian dan Perdagangan', 2020, '2023-01-23 12:32:07', 0, '2023-01-23 12:32:07', 0),
(16, 35, 0, 2, 'eselon2', 'dlh', '16', 'Dinas Lingkungan Hidup', 2020, '2023-01-23 12:32:19', 0, '2023-01-23 12:32:19', 0),
(17, 35, 0, 2, 'eselon2', 'disbudporapar', '17', 'Dinas Kebudayaan, Kepemudaan Olahraga dan Pariwisata', 2020, '2023-01-23 12:32:37', 0, '2023-01-23 12:32:37', 0),
(18, 35, 0, 2, 'eselon2', 'putr', '18', 'Dinas Pekerjaan Umum dan Tata Ruang', 2020, '2023-01-23 12:32:47', 0, '2023-01-23 12:32:47', 0),
(19, 35, 0, 2, 'eselon2', 'bpbd', '19', 'Badan Penanggulangan Bencana Daerah', 2020, '2023-01-23 12:33:06', 0, '2023-01-23 12:33:06', 0),
(20, 35, 0, 2, 'eselon2', 'dpmd', '20', 'Dinas Pemberdayaan Masyarakat dan Desa', 2020, '2023-01-23 12:33:29', 0, '2023-01-23 12:33:21', 0),
(21, 35, 0, 2, 'eselon2', 'dsp3a', '21', 'Dinas Sosial, Pemberdayaan Perempuan dan Perlindungan Anak', 2020, '2023-01-23 12:33:48', 0, '2023-01-23 12:33:48', 0),
(22, 35, 0, 2, 'eselon2', 'dpmptsp', '22', 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu', 2020, '2023-01-23 12:34:10', 0, '2023-01-23 12:34:10', 0),
(23, 35, 0, 2, 'eselon2', 'disdikbud', '23', 'Dinas Pendidikan', 2020, '2023-01-23 12:34:34', 0, '2023-01-23 12:34:34', 0),
(24, 35, 0, 2, 'eselon2', 'dppkb', '24', 'Dinas Pengendalian Penduduk dan Keluarga Berencana', 2020, '2023-01-23 12:34:47', 0, '2023-01-23 12:34:47', 0),
(25, 35, 0, 2, 'eselon2', 'dishub', '25', 'Dinas Perhubungan', 2020, '2023-01-23 12:35:02', 0, '2023-01-23 12:35:02', 0),
(26, 35, 0, 2, 'eselon2', 'disindag', '26', 'Dinas Perindustrian dan Perdagangan', 2020, '2023-01-23 12:35:19', 0, '2023-01-23 12:35:19', 0),
(27, 35, 0, 2, 'eselon2', 'disbun', '27', 'Dinas Perkebunan', 2020, '2023-01-23 12:35:34', 0, '2023-01-23 12:35:34', 0),
(28, 35, 0, 2, 'eselon2', 'dptr', '28', 'Dinas Pertanahan dan Penataan Ruang', 2020, '2023-01-23 12:35:48', 0, '2023-01-23 12:35:48', 0),
(29, 35, 0, 2, 'eselon2', 'distanak', '29', 'Dinas Pertanian dan Peternakan', 2020, '2023-01-23 12:36:00', 0, '2023-01-23 12:36:00', 0),
(30, 35, 0, 2, 'eselon2', 'disperkim', '30', 'Dinas Perumahan dan Kawasan Permukiman', 2020, '2023-01-23 12:36:15', 0, '2023-01-23 12:36:15', 0),
(31, 35, 0, 2, 'eselon2', 'dinsos', '31', 'Dinas Sosial', 2020, '2023-01-23 12:36:30', 0, '2023-01-23 12:36:30', 0),
(32, 35, 0, 2, 'eselon2', 'disnaker', '32', 'Dinas Ketenagakerjaan', 2020, '2023-01-23 12:37:10', 0, '2023-01-23 12:37:10', 0),
(33, 35, 0, 2, 'eselon2', 'inspektorat', '33', 'Inspektorat Kabupaten', 2020, '2023-01-23 12:37:27', 0, '2023-01-23 12:37:27', 0),
(34, 35, 0, 2, 'eselon2', 'dkp', '34', 'Dinas Perikanan', 2020, '2023-01-23 13:29:52', 0, '2023-01-23 13:29:52', 0),
(37, 35, 0, 2, 'eselon2', 'satpolpp', '37', 'Satuan Polisi Pamong Praja', 2020, '2024-01-25 06:07:02', 0, '2024-01-25 06:07:02', 0),
(38, 35, 0, 2, 'eselon2', 'bappeda', '38', 'Badan Perencanaan Pembangunan Daerah', 2020, '2024-01-25 06:07:02', 0, '2024-01-25 06:07:02', 0),
(39, 35, 0, 2, 'eselon2', 'setdprd', '39', 'Sekretariat Dewan Perwakilan Rakyat Daerah', 2020, '2024-01-25 06:07:44', 0, '2024-01-25 06:07:44', 0),
(42, 35, 0, 2, 'eselon2', 'bps', '40', 'Badan Pusat Statistik', 2020, '2024-02-10 23:01:25', 0, '2024-02-10 23:01:25', 0);
