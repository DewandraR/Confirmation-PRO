-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 13, 2025 at 05:21 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `yppi019`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(7, 'User', 'user@gmail.com', NULL, '$2y$12$86wxB.Xd5G/Tb8DwqFvX.eQJa/RDSPS8pKWLVUVFq32bzDbanQx1O', NULL, '2025-09-05 00:49:03', '2025-09-05 00:49:03'),
(9, 'abaper01', 'abaper01@kmi.local', NULL, '$2y$12$y/0KeQmj3fwlwyzFVnFk2.VtNm3JyQSJ4o9ZCWe5XZeyzLInUbd5y', NULL, '2025-09-08 03:21:18', '2025-09-08 03:21:18'),
(11, 'auto_email', 'auto_email@kmi.local', NULL, '$2y$12$nnTyVyvZhKzAWm7cdj7Z6eiOxvJmeMw3U9WB157Lh47Tez2psDCTq', NULL, '2025-09-12 22:07:50', '2025-09-12 22:07:50');

-- --------------------------------------------------------

--
-- Table structure for table `yppi019_backdate_log`
--

CREATE TABLE `yppi019_backdate_log` (
  `id` bigint NOT NULL,
  `AUFNR` varchar(20) NOT NULL,
  `VORNR` varchar(10) DEFAULT NULL,
  `PERNR` varchar(20) DEFAULT NULL,
  `QTY` decimal(18,3) DEFAULT NULL,
  `MEINH` varchar(10) DEFAULT NULL,
  `BUDAT` date NOT NULL,
  `TODAY` date NOT NULL,
  `ARBPL0` varchar(40) DEFAULT NULL,
  `MAKTX` varchar(200) DEFAULT NULL,
  `SAP_RETURN` json DEFAULT NULL,
  `CONFIRMED_AT` datetime DEFAULT NULL,
  `CREATED_AT` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `yppi019_backdate_log`
--

INSERT INTO `yppi019_backdate_log` (`id`, `AUFNR`, `VORNR`, `PERNR`, `QTY`, `MEINH`, `BUDAT`, `TODAY`, `ARBPL0`, `MAKTX`, `SAP_RETURN`, `CONFIRMED_AT`, `CREATED_AT`) VALUES
(1, '155000072353', '0020', '10006149', 1.000, 'ST', '2025-09-10', '2025-09-12', 'WC031', 'LMT BOARD WHITE OAK 18X500X1300 L 41', '{\"EV_MSG\": null, \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": null, \"ROW\": 0, \"TYPE\": null, \"FIELD\": null, \"LOG_NO\": null, \"NUMBER\": \"000\", \"SYSTEM\": null, \"MESSAGE\": null, \"PARAMETER\": null, \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": null, \"MESSAGE_V2\": null, \"MESSAGE_V3\": null, \"MESSAGE_V4\": null}, {\"ID\": null, \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": null, \"LOG_NO\": null, \"NUMBER\": \"000\", \"SYSTEM\": null, \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": null, \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": null, \"MESSAGE_V2\": null, \"MESSAGE_V3\": null, \"MESSAGE_V4\": null}]}', '2025-09-12 07:09:27', '2025-09-12 14:09:28');

-- --------------------------------------------------------

--
-- Table structure for table `yppi019_confirm_log`
--

CREATE TABLE `yppi019_confirm_log` (
  `id` bigint NOT NULL,
  `AUFNR` varchar(20) NOT NULL,
  `VORNR` varchar(10) DEFAULT NULL,
  `PERNR` varchar(20) DEFAULT NULL,
  `PSMNG` decimal(18,0) DEFAULT NULL,
  `MEINH` varchar(10) DEFAULT NULL,
  `GSTRP` date DEFAULT NULL,
  `GLTRP` date DEFAULT NULL,
  `BUDAT` date DEFAULT NULL,
  `SAP_RETURN` json DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `yppi019_confirm_log`
--

INSERT INTO `yppi019_confirm_log` (`id`, `AUFNR`, `VORNR`, `PERNR`, `PSMNG`, `MEINH`, `GSTRP`, `GLTRP`, `BUDAT`, `SAP_RETURN`, `created_at`) VALUES
(26, '155000072353', '0020', '10006149', 1, 'PC', '2025-09-12', '2025-09-12', '2025-09-11', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-12 11:33:27'),
(27, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-12', '2025-09-12', '2025-09-10', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-12 13:52:17'),
(28, '155000072353', '0020', '10006149', 1, 'PC', '2025-09-12', '2025-09-12', '2025-09-10', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-12 14:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `yppi019_data`
--

CREATE TABLE `yppi019_data` (
  `id` bigint NOT NULL,
  `AUFNR` varchar(20) NOT NULL,
  `VORNRX` varchar(10) DEFAULT NULL,
  `PERNR` varchar(20) DEFAULT NULL,
  `ARBPL0` varchar(40) DEFAULT NULL,
  `DISPO` varchar(10) DEFAULT NULL,
  `STEUS` varchar(8) DEFAULT NULL,
  `WERKS` varchar(10) DEFAULT NULL,
  `CHARG` varchar(20) DEFAULT NULL,
  `MATNRX` varchar(40) DEFAULT NULL,
  `MAKTX` varchar(200) DEFAULT NULL,
  `MEINH` varchar(10) DEFAULT NULL,
  `QTY_SPK` decimal(18,3) DEFAULT NULL,
  `WEMNG` decimal(18,3) DEFAULT NULL,
  `QTY_SPX` decimal(18,3) DEFAULT NULL,
  `LTXA1` varchar(200) DEFAULT NULL,
  `SNAME` varchar(100) DEFAULT NULL,
  `GSTRP` date DEFAULT NULL,
  `GLTRP` date DEFAULT NULL,
  `ISDZ` varchar(20) DEFAULT NULL,
  `IEDZ` varchar(20) DEFAULT NULL,
  `RAW_JSON` json NOT NULL,
  `fetched_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `yppi019_data`
--

INSERT INTO `yppi019_data` (`id`, `AUFNR`, `VORNRX`, `PERNR`, `ARBPL0`, `DISPO`, `STEUS`, `WERKS`, `CHARG`, `MATNRX`, `MAKTX`, `MEINH`, `QTY_SPK`, `WEMNG`, `QTY_SPX`, `LTXA1`, `SNAME`, `GSTRP`, `GLTRP`, `ISDZ`, `IEDZ`, `RAW_JSON`, `fetched_at`) VALUES
(2992, '361000015582', '0010', '10006149', 'WC223', 'MF3', 'ZP01', '3000', '4300019090', '90009702', 'MTL FERRULE F.024 VG 54X54X79MM PMR', 'ST', 36.000, 20.000, 16.000, 'MF POWDER COATING', 'MOCHAMAD FATANY RASIS', '2025-09-13', '2025-09-13', '', '', '{\"IEDZ\": \"\", \"ISDZ\": \"\", \"AUFNR\": \"361000015582\", \"CHARG\": \"4300019090\", \"DISPO\": \"MF3\", \"GLTRP\": \"13.09.2025\", \"GSTRP\": \"13.09.2025\", \"LTXA1\": \"MF POWDER COATING\", \"MAKTX\": \"MTL FERRULE F.024 VG 54X54X79MM PMR\", \"MANDT\": \"\", \"MEINH\": \"ST\", \"PERNR\": \"10006149\", \"SNAME\": \"MOCHAMAD FATANY RASIS\", \"STEUS\": \"ZP01\", \"WEMNG\": 20.0, \"WERKS\": \"3000\", \"ARBPL0\": \"WC223\", \"MATNRX\": \"90009702\", \"VORNRX\": \"10\", \"QTY_SPK\": 36.0, \"QTY_SPX\": 16.0}', '2025-09-13 12:12:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `yppi019_backdate_log`
--
ALTER TABLE `yppi019_backdate_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aufnr` (`AUFNR`),
  ADD KEY `idx_budat` (`BUDAT`),
  ADD KEY `idx_pernr` (`PERNR`);

--
-- Indexes for table `yppi019_confirm_log`
--
ALTER TABLE `yppi019_confirm_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aufnr` (`AUFNR`);

--
-- Indexes for table `yppi019_data`
--
ALTER TABLE `yppi019_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_key` (`AUFNR`,`VORNRX`,`ARBPL0`,`CHARG`),
  ADD KEY `idx_aufnr` (`AUFNR`),
  ADD KEY `idx_pernr` (`PERNR`),
  ADD KEY `idx_arbpl` (`ARBPL0`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `yppi019_backdate_log`
--
ALTER TABLE `yppi019_backdate_log`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `yppi019_confirm_log`
--
ALTER TABLE `yppi019_confirm_log`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `yppi019_data`
--
ALTER TABLE `yppi019_data`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2993;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
