-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 21, 2026 at 05:11 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nuahseasonalapp_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
CREATE TABLE IF NOT EXISTS `activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
CREATE TABLE IF NOT EXISTS `applications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_id` int NOT NULL,
  `provider_id` int NOT NULL,
  `status` enum('applied','accepted','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'applied',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `provider_id` (`provider_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `job_id`, `provider_id`, `status`, `created_at`) VALUES
(1, 8, 2, '', '2026-04-27 13:15:54'),
(2, 5, 2, '', '2026-04-27 13:26:14'),
(3, 9, 7, '', '2026-05-25 08:30:28'),
(4, 9, 7, '', '2026-05-25 08:31:04'),
(5, 7, 7, '', '2026-05-25 08:32:26'),
(6, 5, 2, '', '2026-08-17 00:17:19'),
(7, 5, 2, '', '2026-08-17 00:21:43'),
(8, 10, 2, '', '2026-08-17 00:40:24'),
(9, 11, 2, '', '2026-08-18 14:28:04');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_user_id` int DEFAULT NULL,
  `job_id` int DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` date NOT NULL,
  `performed_by` int DEFAULT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `target_user_id` (`target_user_id`),
  KEY `job_id` (`job_id`)
) ENGINE=MyISAM AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `admin_id`, `action`, `target_user_id`, `job_id`, `timestamp`, `created_at`, `performed_by`, `role`) VALUES
(1, NULL, 'Job posted', 5, 3, '2026-04-01 11:44:50', '0000-00-00', NULL, NULL),
(2, NULL, 'Job posted', 5, 4, '2026-04-01 14:09:27', '0000-00-00', NULL, NULL),
(3, NULL, 'Job posted', 5, 6, '2026-04-01 20:17:54', '0000-00-00', NULL, NULL),
(4, NULL, 'Job posted', 1, 7, '2026-04-02 11:14:14', '0000-00-00', NULL, NULL),
(5, NULL, 'Job status updated to closed', 5, 1, '2026-04-21 15:09:27', '2026-04-21', NULL, NULL),
(6, NULL, 'Job status updated to closed', 5, 1, '2026-04-21 15:16:38', '2026-04-21', NULL, NULL),
(7, NULL, 'Job status updated to closed', 5, 1, '2026-04-21 15:43:35', '2026-04-21', NULL, NULL),
(8, NULL, 'Job updated', 5, 1, '2026-04-21 15:48:57', '2026-04-21', NULL, NULL),
(9, NULL, 'Job updated', 5, 1, '2026-04-21 15:50:02', '2026-04-21', NULL, NULL),
(10, NULL, 'Job posted', 5, 8, '2026-04-24 16:55:18', '2026-04-24', NULL, NULL),
(11, NULL, 'Job status updated to closed', 5, 4, '2026-04-24 17:13:19', '2026-04-24', NULL, NULL),
(12, NULL, 'Job status updated to posted', 5, 4, '2026-04-24 17:13:50', '2026-04-24', NULL, NULL),
(13, NULL, 'Job deleted', 5, 4, '2026-04-24 17:15:20', '2026-04-24', NULL, NULL),
(14, NULL, 'Job deleted', 5, 6, '2026-04-24 17:15:43', '2026-04-24', NULL, NULL),
(15, NULL, 'Job applied', 2, 8, '2026-04-27 13:15:54', '2026-04-27', NULL, NULL),
(16, NULL, 'Job saved', 2, 5, '2026-04-27 13:23:31', '2026-04-27', NULL, NULL),
(17, NULL, 'Job saved', 2, 8, '2026-04-27 13:23:42', '2026-04-27', NULL, NULL),
(18, NULL, 'Job applied', 2, 5, '2026-04-27 13:26:14', '2026-04-27', NULL, NULL),
(19, NULL, 'Job posted', 6, 9, '2026-05-25 08:27:05', '2026-05-25', NULL, NULL),
(20, NULL, 'Job applied', 7, 9, '2026-05-25 08:30:28', '2026-05-25', NULL, NULL),
(21, NULL, 'Job saved', 7, 8, '2026-05-25 08:30:35', '2026-05-25', NULL, NULL),
(22, NULL, 'Job saved', 7, 7, '2026-05-25 08:30:42', '2026-05-25', NULL, NULL),
(23, NULL, 'Job applied', 7, 9, '2026-05-25 08:31:04', '2026-05-25', NULL, NULL),
(24, NULL, 'Job applied', 7, 7, '2026-05-25 08:32:26', '2026-05-25', NULL, NULL),
(25, NULL, 'Job updated', 6, 9, '2026-05-25 08:42:14', '2026-05-25', NULL, NULL),
(26, NULL, 'Job status updated to closed', 6, 9, '2026-05-25 08:45:00', '2026-05-25', NULL, NULL),
(27, NULL, 'Job status updated to posted', 6, 9, '2026-05-25 08:45:35', '2026-05-25', NULL, NULL),
(28, NULL, 'Job unsaved', 2, 5, '2026-06-27 21:45:15', '2026-06-27', NULL, NULL),
(29, NULL, 'Job unsaved', 2, 8, '2026-06-27 21:45:29', '2026-06-27', NULL, NULL),
(30, NULL, 'Job posted', 1, 10, '2026-08-16 18:43:45', '2026-08-16', NULL, NULL),
(31, NULL, 'Job updated', 1, 10, '2026-08-16 23:14:50', '2026-08-16', NULL, NULL),
(32, NULL, 'Job updated', 1, 10, '2026-08-16 23:15:52', '2026-08-16', NULL, NULL),
(33, NULL, 'Job deleted', 1, 7, '2026-08-16 23:28:36', '2026-08-16', NULL, NULL),
(34, NULL, 'Job saved', 2, 10, '2026-08-17 00:17:14', '2026-08-17', NULL, NULL),
(35, NULL, 'Job applied', 2, 5, '2026-08-17 00:17:19', '2026-08-17', NULL, NULL),
(36, NULL, 'Job saved', 2, 9, '2026-08-17 00:21:24', '2026-08-17', NULL, NULL),
(37, NULL, 'Job applied', 2, 5, '2026-08-17 00:21:43', '2026-08-17', NULL, NULL),
(38, NULL, 'Job applied', 2, 10, '2026-08-17 00:40:24', '2026-08-17', NULL, NULL),
(39, NULL, 'Job posted', 1, 11, '2026-08-18 14:18:54', '2026-08-18', NULL, NULL),
(40, NULL, 'Job updated', 1, 10, '2026-08-18 14:20:29', '2026-08-18', NULL, NULL),
(41, NULL, 'Job saved', 2, 11, '2026-08-18 14:27:41', '2026-08-18', NULL, NULL),
(42, NULL, 'Job applied', 2, 11, '2026-08-18 14:28:04', '2026-08-18', NULL, NULL),
(43, NULL, 'Job status updated to posted', 1, 11, '2026-08-18 15:05:56', '2026-08-18', NULL, NULL),
(44, NULL, 'Job updated', 1, 10, '2026-08-18 15:24:22', '2026-08-18', NULL, NULL),
(45, NULL, 'Job posted', 1, 12, '2026-08-20 15:27:09', '2026-08-20', NULL, NULL),
(46, NULL, 'Job status updated to closed', 1, 11, '2026-08-20 15:27:56', '2026-08-20', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `details`
--

DROP TABLE IF EXISTS `details`;
CREATE TABLE IF NOT EXISTS `details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sales_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price` decimal(10,2) NOT NULL,
  `created_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sales_id` (`sales_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `location_address` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','posted','filled','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `client_id`, `title`, `description`, `location_lat`, `location_lng`, `location_address`, `status`, `created_at`, `image`, `payment_amount`) VALUES
(1, 5, 'Lawn Mower', 'Mow my lawn and clean up my driveway', 63.00000000, -111.00000000, '', '', '2026-04-01 11:38:45', NULL, 0.00),
(5, 5, 'Move a sofa', 'Help me move a sofa to another room', 55.00000000, -115.00000000, '', 'posted', '2026-04-01 20:16:20', NULL, 0.00),
(9, 6, 'Cleaning roof gutters and mow lawn', 'Help clean out the roof gutters at my residence', 5.56819300, -0.20858900, 'Kreative Creation Art Shop, Odanta Road, Asylum Down, Accra, Korle-Klottey Municipal District, Greater Accra Region, Ghana', 'posted', '2026-05-25 08:27:05', NULL, 0.00),
(8, 5, 'Shed painter', 'Help paint a garden shed', 5.55038400, -0.24947200, 'Naa Ayaman Street, Sukura, Accra, Ablekuma Central Municipal District, Greater Accra Region, Ghana', 'posted', '2026-04-24 16:55:18', NULL, 0.00),
(10, 1, 'Accra Lawn Mowing', 'Mow lawn at a residence in Matam', 5.57418100, -0.28894500, 'Mallam, Gbawe, Weija-Gbawe Municipal District, Greater Accra Region, GS-0167-1738, Ghana', 'posted', '2026-08-16 18:43:45', '1787066662_WhatsApp_Image_2026-07-28_at_05.20.50.jpeg', 0.00),
(11, 1, 'Window cleaning', 'Help a family clean their windows from dust and branches', 5.55950200, -0.23850500, 'Jagbele Close, Agbogbloshie, Abossey Okai, Accra, Ablekuma Central Municipal District, Greater Accra Region, Ghana', 'closed', '2026-08-18 14:18:53', '1787062733_WhatsApp_Image_2026-06-24_at_17.07.54__2_.jpeg', 30.00),
(12, 1, 'Clear the yard', 'Help clear the yard and mow the lawn', 5.55481700, -0.25450100, 'Otseame Adjor Road, New Russia, Russia, Accra, Ablekuma Central Municipal District, Greater Accra Region, Ghana', 'pending', '2026-08-20 15:27:09', '1787239628_WhatsApp_Image_2026-07-29_at_15.00.40.jpeg', 43.00);

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
CREATE TABLE IF NOT EXISTS `plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `photo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE IF NOT EXISTS `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `pay_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sales_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_details`
--

DROP TABLE IF EXISTS `sale_details`;
CREATE TABLE IF NOT EXISTS `sale_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sales_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_id` (`sales_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_jobs`
--

DROP TABLE IF EXISTS `saved_jobs`;
CREATE TABLE IF NOT EXISTS `saved_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `provider_id` int NOT NULL,
  `job_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_id` (`provider_id`,`job_id`),
  KEY `job_id` (`job_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `saved_jobs`
--

INSERT INTO `saved_jobs` (`id`, `provider_id`, `job_id`, `created_at`) VALUES
(6, 2, 9, '2026-08-17 00:21:24'),
(5, 2, 10, '2026-08-17 00:17:14'),
(3, 7, 8, '2026-05-25 08:30:35'),
(4, 7, 7, '2026-05-25 08:30:42'),
(7, 2, 11, '2026-08-18 14:27:41');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `plan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','expired') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('superadmin','manager','client','provider') COLLATE utf8mb4_unicode_ci DEFAULT 'client',
  `status` enum('active','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `firstname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `contact_info` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `theme_preference` enum('light','dark') COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `firstname`, `lastname`, `address`, `contact_info`, `photo`, `created_at`, `theme_preference`) VALUES
(1, 'Alice Johnson', 'alice@example.com', '$2y$10$umgbWUH9iHNf9v27Q7jdkeSed08uTVE3LvYaRHj/K0D.5RP8en9Cm', 'client', 'active', 'Alice', 'Johnson', '123 Market St', '555-1234', 'alice.jpg', '2026-02-28 21:41:36', 'light'),
(2, 'Bob Smith', 'bob@example.com', '$2y$10$umgbWUH9iHNf9v27Q7jdkeSed08uTVE3LvYaRHj/K0D.5RP8en9Cm', 'provider', 'active', 'Bob', 'Smith', '456 Service Rd', '555-5678', 'bob.jpg', '2026-02-28 21:41:36', 'light'),
(3, 'Carol Admin', 'carol@example.com', '$2y$10$umgbWUH9iHNf9v27Q7jdkeSed08uTVE3LvYaRHj/K0D.5RP8en9Cm', 'superadmin', 'active', 'Carol', 'Admin', '789 Admin Ave', '555-9999', 'carol.jpg', '2026-02-28 21:41:36', 'light'),
(4, 'David Manager', 'david@example.com', '$2y$10$umgbWUH9iHNf9v27Q7jdkeSed08uTVE3LvYaRHj/K0D.5RP8en9Cm', 'manager', 'active', 'David', 'Manager', '321 Manage Blvd', '555-4321', 'david.jpg', '2026-02-28 21:41:36', 'light'),
(5, 'Paul Tetteh Nerquaye-Tetteh', 'atuleptnt@gmail.com', '$2y$10$umgbWUH9iHNf9v27Q7jdkeSed08uTVE3LvYaRHj/K0D.5RP8en9Cm', 'client', 'active', NULL, NULL, NULL, NULL, NULL, '2026-04-01 09:54:52', 'light'),
(6, 'Kweku Mensah', 'km@gmail.com', '$2y$10$xgY9uRUa8npg0udR7P4OwO/goY3K14UL5g/qTLxhF9dyUkMuMHr9C', 'client', 'active', NULL, NULL, NULL, NULL, NULL, '2026-05-25 08:22:23', 'light'),
(7, 'Chris Tambo', 'ct@gmail.com', '$2y$10$R2FKjrkvp.v/4U7IRekKEeDDJGMS4xBYyoTsYI5p7Ri.B3WafuxQi', 'provider', 'active', NULL, NULL, NULL, NULL, NULL, '2026-05-25 08:29:40', 'light');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
