-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 05, 2025 at 08:35 AM
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
(7, 'User', 'user@gmail.com', NULL, '$2y$12$86wxB.Xd5G/Tb8DwqFvX.eQJa/RDSPS8pKWLVUVFq32bzDbanQx1O', NULL, '2025-09-05 00:49:03', '2025-09-05 00:49:03');

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
(7, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-02', '2025-09-02', '2025-09-02', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-02 10:15:24'),
(8, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-02', '2025-09-02', '2025-09-02', '{\"EV_MSG\": \"System status TECO is active (ORD 155000072353)\", \"EV_SUBRC\": 1, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"E\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"00000000000000000001 confirmations are incorrect. For details see parameter DETAIL_RETURN\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"E\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"System status TECO is active (ORD 155000072353)\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-02 17:00:07'),
(9, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"Order 155000072353 is already being processed by ABAPER01\", \"EV_SUBRC\": 1, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"E\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"00000000000000000001 confirmations are incorrect. For details see parameter DETAIL_RETURN\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"E\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Order 155000072353 is already being processed by ABAPER01\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 08:00:58'),
(10, '155000072353', '0010', '10006149', 2, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"Order 155000072353 is already being processed by ABAPER01\", \"EV_SUBRC\": 1, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"E\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"00000000000000000001 confirmations are incorrect. For details see parameter DETAIL_RETURN\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"E\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Order 155000072353 is already being processed by ABAPER01\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 08:06:41'),
(11, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 08:09:50'),
(12, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 09:10:45'),
(13, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 11:06:18'),
(14, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 11:34:38'),
(15, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 11:50:17'),
(16, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 12:02:38'),
(17, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 12:03:16'),
(18, '155000072353', '0020', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"Order 155000072353 is already being processed by AUTO_EMAIL\", \"EV_SUBRC\": 1, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"E\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"00000000000000000001 confirmations are incorrect. For details see parameter DETAIL_RETURN\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"E\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Order 155000072353 is already being processed by AUTO_EMAIL\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 12:03:19'),
(19, '155000072353', '0020', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 12:09:30'),
(20, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 12:10:40'),
(21, '155000072353', '0020', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"Order 155000072353 is already being processed by AUTO_EMAIL\", \"EV_SUBRC\": 1, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"E\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"00000000000000000001 confirmations are incorrect. For details see parameter DETAIL_RETURN\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"E\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Order 155000072353 is already being processed by AUTO_EMAIL\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 12:10:41'),
(22, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 14:29:50'),
(23, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 15:01:21'),
(24, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 15:11:55'),
(25, '155000072353', '0010', '10006149', 1, 'PC', '2025-09-03', '2025-09-03', '2025-09-03', '{\"EV_MSG\": \"\", \"EV_SUBRC\": 0, \"IT_RETURN\": [{\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}, {\"ID\": \"\", \"ROW\": 0, \"TYPE\": \"I\", \"FIELD\": \"\", \"LOG_NO\": \"\", \"NUMBER\": \"000\", \"SYSTEM\": \"\", \"MESSAGE\": \"Confirmation of order 155000072353 saved\", \"PARAMETER\": \"\", \"LOG_MSG_NO\": \"000000\", \"MESSAGE_V1\": \"\", \"MESSAGE_V2\": \"\", \"MESSAGE_V3\": \"\", \"MESSAGE_V4\": \"\"}]}', '2025-09-03 15:40:38');

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
(166, '226100072691', '0010', '10006149', 'WC767', 'C11', 'ZP01', '2000', '0000074270', '20266100', 'SUB ASSY TOP EXT', 'ST', 2.000, 0.000, 2.000, 'PALANG KUDA TOP GROUP', 'MOCHAMAD FATANY RASIS', '2025-09-05', '2025-09-05', '', '', '{\"IEDZ\": \"\", \"ISDZ\": \"\", \"AUFNR\": \"226100072691\", \"CHARG\": \"0000074270\", \"DISPO\": \"C11\", \"GLTRP\": \"05.09.2025\", \"GSTRP\": \"05.09.2025\", \"LTXA1\": \"PALANG KUDA TOP GROUP\", \"MAKTX\": \"SUB ASSY TOP EXT\", \"MANDT\": \"\", \"MEINH\": \"ST\", \"PERNR\": \"10006149\", \"SNAME\": \"MOCHAMAD FATANY RASIS\", \"STEUS\": \"ZP01\", \"WEMNG\": 0.0, \"WERKS\": \"2000\", \"ARBPL0\": \"WC767\", \"MATNRX\": \"20266100\", \"VORNRX\": \"10\", \"QTY_SPK\": 2.0, \"QTY_SPX\": 2.0}', '2025-09-05 08:00:53'),
(168, '226100072690', '0010', '10006149', 'WC767', 'C11', 'ZP01', '2000', '0000074269', '20266099', 'SUB ASSY TOP TABLE', 'ST', 4.000, 0.000, 4.000, 'PALANG KUDA TOP GROUP', 'MOCHAMAD FATANY RASIS', '2025-09-05', '2025-09-05', '', '', '{\"IEDZ\": \"\", \"ISDZ\": \"\", \"AUFNR\": \"226100072690\", \"CHARG\": \"0000074269\", \"DISPO\": \"C11\", \"GLTRP\": \"05.09.2025\", \"GSTRP\": \"05.09.2025\", \"LTXA1\": \"PALANG KUDA TOP GROUP\", \"MAKTX\": \"SUB ASSY TOP TABLE\", \"MANDT\": \"\", \"MEINH\": \"ST\", \"PERNR\": \"10006149\", \"SNAME\": \"MOCHAMAD FATANY RASIS\", \"STEUS\": \"ZP01\", \"WEMNG\": 0.0, \"WERKS\": \"2000\", \"ARBPL0\": \"WC767\", \"MATNRX\": \"20266099\", \"VORNRX\": \"10\", \"QTY_SPK\": 4.0, \"QTY_SPX\": 4.0}', '2025-09-05 08:02:51'),
(179, '361000015582', '0010', '10006149', 'WC223', 'MF3', 'ZP01', '3000', '4300019090', '90009702', 'MTL FERRULE F.024 VG 54X54X79MM PMR', 'ST', 36.000, 20.000, 16.000, 'MF POWDER COATING', 'MOCHAMAD FATANY RASIS', '2025-09-05', '2025-09-05', '', '', '{\"IEDZ\": \"\", \"ISDZ\": \"\", \"AUFNR\": \"361000015582\", \"CHARG\": \"4300019090\", \"DISPO\": \"MF3\", \"GLTRP\": \"05.09.2025\", \"GSTRP\": \"05.09.2025\", \"LTXA1\": \"MF POWDER COATING\", \"MAKTX\": \"MTL FERRULE F.024 VG 54X54X79MM PMR\", \"MANDT\": \"\", \"MEINH\": \"ST\", \"PERNR\": \"10006149\", \"SNAME\": \"MOCHAMAD FATANY RASIS\", \"STEUS\": \"ZP01\", \"WEMNG\": 20.0, \"WERKS\": \"3000\", \"ARBPL0\": \"WC223\", \"MATNRX\": \"90009702\", \"VORNRX\": \"10\", \"QTY_SPK\": 36.0, \"QTY_SPX\": 16.0}', '2025-09-05 11:29:51');

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
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `yppi019_confirm_log`
--
ALTER TABLE `yppi019_confirm_log`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `yppi019_data`
--
ALTER TABLE `yppi019_data`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
