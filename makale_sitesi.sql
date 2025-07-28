-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 28 Tem 2025, 20:42:23
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `makale_sitesi`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ads`
--

CREATE TABLE `ads` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` text NOT NULL,
  `position` enum('header','sidebar','sidebar_bottom','content_top','content_middle','content_bottom','mobile_fixed_bottom','footer') NOT NULL,
  `type` enum('banner','widget','popup','text') NOT NULL DEFAULT 'banner',
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ads`
--

INSERT INTO `ads` (`id`, `name`, `code`, `position`, `type`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(14, '1', '<a href=\"https://www.mshowto.org\" target=\"_blank\">\r\n<img src=\"https://www.mshowto.org/images/cozumortagi/mavi/MSHOWTOBanner_600x120.jpg\" \r\nalt=\"Türkiye\'nin en doğru, dolu dolu ve hatasız anlatımları ile teknik yazılarına, makalelerine, video\'larına, \r\nseminerlerine, forum sayfasına ve sektörün önde gelenlerine ulaşabileceğiniz teknik topluluğu, MSHOWTO\" \r\nwidth=\"600\" height=\"120\" /></a>', 'sidebar', 'banner', 1, 0, '2025-07-14 10:23:08', '2025-07-14 10:23:08'),
(15, '2', '<a href=\"https://www.mshowto.org\" target=\"_blank\">\r\n<img src=\"https://www.mshowto.org/images/cozumortagi/mavi/MSHOWTOBanner_600x120.jpg\" \r\nalt=\"Türkiye\'nin en doğru, dolu dolu ve hatasız anlatımları ile teknik yazılarına, makalelerine, video\'larına, \r\nseminerlerine, forum sayfasına ve sektörün önde gelenlerine ulaşabileceğiniz teknik topluluğu, MSHOWTO\" \r\nwidth=\"600\" height=\"120\" /></a>', 'mobile_fixed_bottom', 'banner', 1, 0, '2025-07-14 10:24:35', '2025-07-14 10:24:35');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ad_statistics`
--

CREATE TABLE `ad_statistics` (
  `id` int(11) NOT NULL,
  `ad_id` int(11) NOT NULL,
  `impressions` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ad_statistics`
--

INSERT INTO `ad_statistics` (`id`, `ad_id`, `impressions`, `clicks`, `created_at`, `updated_at`) VALUES
(1, 15, 49, 0, '2025-07-14 10:29:54', '2025-07-26 16:09:24');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ai_articles`
--

CREATE TABLE `ai_articles` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `ai_type` enum('gemini','chatgpt','grok') NOT NULL,
  `prompt` text DEFAULT NULL,
  `word_count` int(11) DEFAULT 0,
  `processing_time` float DEFAULT 0,
  `cover_image_url` text DEFAULT NULL,
  `image1_url` text DEFAULT NULL,
  `image2_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `language_id` int(11) DEFAULT 1,
  `is_premium` tinyint(1) DEFAULT 0,
  `is_private` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_headline` tinyint(1) DEFAULT 0,
  `status` enum('draft','published','pending') DEFAULT 'pending',
  `views_count` int(11) DEFAULT 0,
  `meta_title` varchar(160) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `article_statistics`
--

CREATE TABLE `article_statistics` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `views` int(11) DEFAULT 0,
  `unique_views` int(11) DEFAULT 0,
  `avg_reading_time` int(11) DEFAULT 0,
  `shares` int(11) DEFAULT 0,
  `comments` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `article_tags`
--

CREATE TABLE `article_tags` (
  `article_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `banned_users`
--

CREATE TABLE `banned_users` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `ban_date` datetime DEFAULT current_timestamp(),
  `expiry_date` datetime DEFAULT NULL,
  `banned_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `meta_title` varchar(160) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cloudflare_settings`
--

CREATE TABLE `cloudflare_settings` (
  `id` int(11) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 0,
  `site_key` varchar(255) DEFAULT '',
  `secret_key` varchar(255) DEFAULT '',
  `login_enabled` tinyint(1) DEFAULT 0,
  `register_enabled` tinyint(1) DEFAULT 0,
  `contact_enabled` tinyint(1) DEFAULT 0,
  `article_enabled` tinyint(1) DEFAULT 0,
  `difficulty` enum('easy','normal','hard') DEFAULT 'normal',
  `theme` enum('light','dark','auto') DEFAULT 'light',
  `language` enum('tr','en') DEFAULT 'tr',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `cloudflare_settings`
--

INSERT INTO `cloudflare_settings` (`id`, `is_enabled`, `site_key`, `secret_key`, `login_enabled`, `register_enabled`, `contact_enabled`, `article_enabled`, `difficulty`, `theme`, `language`, `created_at`, `updated_at`) VALUES
(1, 0, '0x4AAAAAABmofqZ58JUW3xCH', '0x4AAAAAABmofvkyutqezmz5RuU-YmirtZ8', 1, 1, 1, 1, 'hard', 'auto', 'tr', '2025-07-01 10:36:21', '2025-07-28 17:37:02');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `headline_articles`
--

CREATE TABLE `headline_articles` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `display_order` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ip_bans`
--

CREATE TABLE `ip_bans` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reason` text DEFAULT NULL,
  `ban_date` datetime DEFAULT current_timestamp(),
  `expiry_date` datetime DEFAULT NULL,
  `banned_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `ip_bans`
--

INSERT INTO `ip_bans` (`id`, `ip_address`, `reason`, `ban_date`, `expiry_date`, `banned_by`, `is_active`) VALUES
(1, '127.0.0.1', 'Çok fazla kayıt denemesi (muhtemel bot)', '2025-07-26 15:45:24', '2025-07-27 14:45:24', NULL, 0),
(2, '127.0.0.1', 'Çok fazla kayıt denemesi (muhtemel bot)', '2025-07-26 15:48:35', '2025-07-27 14:48:35', NULL, 0),
(3, '127.0.0.1', 'Çok fazla kayıt denemesi (muhtemel bot)', '2025-07-26 16:03:02', '2025-07-27 15:03:02', NULL, 0),
(4, '127.0.0.1', 'Çok fazla kayıt denemesi (muhtemel bot)', '2025-07-26 16:04:15', '2025-07-27 15:04:15', NULL, 0),
(5, '127.0.0.1', 'Çok fazla kayıt denemesi (muhtemel bot)', '2025-07-26 16:41:35', '2025-07-27 15:41:35', NULL, 0),
(6, '127.0.0.1', 'Çok fazla kayıt denemesi (muhtemel bot)', '2025-07-26 17:15:15', '2025-07-27 16:15:15', NULL, 0),
(7, '127.0.0.1', 'Çok fazla kayıt denemesi (muhtemel bot)', '2025-07-26 17:51:39', '2025-07-27 16:51:39', NULL, 0),
(8, '127.0.0.1', 'Çok fazla kayıt denemesi (muhtemel bot)', '2025-07-26 18:25:04', '2025-07-27 17:25:04', NULL, 0),
(9, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 19:41:03', '2025-07-26 19:41:03', NULL, 0),
(10, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 19:41:22', '2025-07-26 19:41:22', NULL, 0),
(11, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 19:41:46', '2025-07-26 19:41:46', NULL, 0),
(12, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 19:42:43', '2025-07-26 19:42:43', NULL, 0),
(13, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 19:44:13', '2025-07-26 19:44:13', NULL, 0),
(14, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 19:45:33', '2025-07-26 19:45:33', NULL, 0),
(15, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 19:45:50', '2025-07-26 19:45:50', NULL, 0),
(16, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 19:48:12', '2025-07-26 19:48:12', NULL, 0),
(17, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 19:48:34', '2025-07-26 19:48:34', NULL, 0),
(18, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 19:49:09', '2025-07-26 19:49:09', NULL, 0),
(19, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 20:25:14', '2025-07-26 20:25:14', NULL, 0),
(20, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 20:25:36', '2025-07-26 20:25:36', NULL, 0),
(21, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 20:26:05', '2025-07-26 20:26:05', NULL, 0),
(22, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 20:26:31', '2025-07-26 20:26:31', NULL, 0),
(23, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 20:28:50', '2025-07-26 20:28:50', NULL, 0),
(24, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 20:29:15', '2025-07-26 20:29:15', NULL, 0),
(25, '127.0.0.1', 'Çok fazla kayıt denemesi (muhtemel bot)', '2025-07-26 20:30:19', '2025-07-27 19:30:19', NULL, 0),
(26, '127.0.0.1', 'Çok fazla başarısız giriş denemesi', '2025-07-26 20:31:55', '2025-07-26 20:31:55', NULL, 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `languages`
--

CREATE TABLE `languages` (
  `id` int(11) NOT NULL,
  `code` varchar(5) NOT NULL,
  `name` varchar(50) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `languages`
--

INSERT INTO `languages` (`id`, `code`, `name`, `is_default`, `is_active`, `created_at`) VALUES
(1, 'tr', 'Türkçe', 1, 1, '2025-07-01 09:56:02'),
(2, 'en', 'English', 0, 1, '2025-07-01 09:56:02');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `attempt_time` datetime DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `username`, `attempt_time`, `success`) VALUES
(1, '::1', 'admin', '2025-07-28 20:18:12', 0),
(2, '::1', 'admin', '2025-07-28 20:18:13', 1),
(3, '127.0.0.1', 'co', '2025-07-28 20:27:55', 0),
(4, '127.0.0.1', 'co', '2025-07-28 20:27:55', 1),
(5, '127.0.0.1', 'bv', '2025-07-28 20:36:14', 0),
(6, '127.0.0.1', 'bv', '2025-07-28 20:36:14', 1),
(7, '127.0.0.1', 'bv', '2025-07-28 20:37:15', 0),
(8, '127.0.0.1', 'bv', '2025-07-28 20:38:01', 0),
(9, '127.0.0.1', 'bv', '2025-07-28 20:38:11', 0),
(10, '127.0.0.1', 'bv', '2025-07-28 20:38:22', 0),
(11, '127.0.0.1', 'bv', '2025-07-28 20:39:14', 0),
(12, '127.0.0.1', 'bv', '2025-07-28 20:39:59', 0),
(13, '127.0.0.1', 'bv', '2025-07-28 20:40:23', 0),
(14, '127.0.0.1', 'bv', '2025-07-28 20:40:24', 1),
(15, '127.0.0.1', 'bv', '2025-07-28 20:42:15', 0),
(16, '127.0.0.1', 'bv', '2025-07-28 20:42:44', 0),
(17, '127.0.0.1', 'bv', '2025-07-28 20:42:44', 1),
(18, '127.0.0.1', 'bv', '2025-07-28 20:46:23', 0),
(19, '127.0.0.1', 'bv', '2025-07-28 20:55:45', 0),
(20, '127.0.0.1', 'bv', '2025-07-28 20:57:07', 0),
(21, '127.0.0.1', 'bv', '2025-07-28 20:57:07', 1),
(22, '127.0.0.1', 'bv', '2025-07-28 21:06:21', 0),
(23, '127.0.0.1', 'bv', '2025-07-28 21:06:21', 1),
(24, '127.0.0.1', 'bc', '2025-07-28 21:23:55', 0),
(25, '127.0.0.1', 'bv', '2025-07-28 21:24:01', 0),
(26, '127.0.0.1', 'bv', '2025-07-28 21:24:01', 1),
(27, '127.0.0.1', 'bv', '2025-07-28 21:31:20', 0),
(28, '127.0.0.1', 'bv', '2025-07-28 21:31:20', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `reply_to_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `is_deleted_by_sender` tinyint(1) DEFAULT 0,
  `is_deleted_by_receiver` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `online_users`
--

CREATE TABLE `online_users` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) NOT NULL,
  `last_activity` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `page_views`
--

CREATE TABLE `page_views` (
  `id` int(11) NOT NULL,
  `page_url` varchar(500) NOT NULL,
  `page_title` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referer` varchar(500) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet') DEFAULT 'desktop',
  `browser` varchar(100) DEFAULT NULL,
  `operating_system` varchar(100) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `reading_time` int(11) DEFAULT 0,
  `bounce` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 24 hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `promo_codes`
--

CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percent','fixed') DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `expires_at` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `created_at` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `rate_limits`
--

INSERT INTO `rate_limits` (`id`, `ip_address`, `action`, `created_at`) VALUES
(1, '::1', 'login', 1752597166),
(2, '::1', 'login', 1752597181),
(3, '::1', 'login', 1752597234),
(4, '::1', 'login', 1752597354);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `registration_attempts`
--

CREATE TABLE `registration_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `attempt_time` datetime DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `registration_attempts`
--

INSERT INTO `registration_attempts` (`id`, `ip_address`, `username`, `email`, `attempt_time`, `success`, `user_agent`) VALUES
(1, '127.0.0.1', 'test', 'test@te.com', '2025-07-26 15:43:22', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(2, '127.0.0.1', 'test', 'test@te.com', '2025-07-26 15:43:22', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(3, '127.0.0.1', 'tr', 'tr@tr.com', '2025-07-26 15:45:24', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(4, '127.0.0.1', 'tr', 'tr@tr.com', '2025-07-26 15:45:24', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(5, '127.0.0.1', 'tr', 'tr@tr.com', '2025-07-26 15:48:35', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(6, '127.0.0.1', 'tr', 'tr@tr.com', '2025-07-26 15:48:35', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(7, '127.0.0.1', 'bu', 'bu@bu.com', '2025-07-26 16:03:02', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(8, '127.0.0.1', 'bu', 'bu@bu.com', '2025-07-26 16:03:02', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(9, '127.0.0.1', 'bu', 'bu@bu.com', '2025-07-26 16:04:15', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(10, '127.0.0.1', 'bu', 'bu@bu.com', '2025-07-26 16:04:15', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(11, '127.0.0.1', 'yeni', 'ye@ye.com', '2025-07-26 16:41:35', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(12, '127.0.0.1', 'yeni', 'ye@ye.com', '2025-07-26 16:41:35', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(13, '127.0.0.1', 'yeniden', 'yen@ye.com', '2025-07-26 17:15:15', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(14, '127.0.0.1', 'yeniden', 'yen@ye.com', '2025-07-26 17:15:15', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(15, '127.0.0.1', 'ne', 'ne@ne.com', '2025-07-26 17:51:39', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(16, '127.0.0.1', 'ne', 'ne@ne.com', '2025-07-26 17:51:40', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(17, '127.0.0.1', 'un', 'un@un.com', '2025-07-26 18:25:04', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(18, '127.0.0.1', 'un', 'un@un.com', '2025-07-26 18:25:04', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(19, '127.0.0.1', 'test', 'test@te.com', '2025-07-26 19:50:44', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(20, '127.0.0.1', 'test', 'test@te.com', '2025-07-26 19:50:44', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(21, '127.0.0.1', 'co', 'co@co.com', '2025-07-26 20:30:19', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(22, '127.0.0.1', 'co', 'co@co.com', '2025-07-26 20:30:19', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(23, '127.0.0.1', 'bv', 'bv@bv.com', '2025-07-27 00:46:33', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0'),
(24, '127.0.0.1', 'bv', 'bv@bv.com', '2025-07-27 00:46:33', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `remember_tokens`
--

INSERT INTO `remember_tokens` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(3, 1, 'd2b6001a415273c8854864962c331b6920ff9a798fe2c4823282f3311c1311ac', '2025-08-25 13:15:36', '2025-07-26 14:15:36'),
(5, 1, '21e2f23be8a16ce62cc78e2fdcd599ce8f20febe6c59a25c4b90c5616d2b7dd3', '2025-08-25 15:32:14', '2025-07-26 16:32:14'),
(6, 1, '761926c2650397c81fcf5b22fb8facf339850617630087d2a969cfbdc7b9d7cf', '2025-08-25 21:39:44', '2025-07-26 22:39:44'),
(7, 1, '170a895c16aa779317763587ebfca804845225a90cc93ba34710bd4ce25e69aa', '2025-08-26 08:22:20', '2025-07-27 09:22:20'),
(8, 1, '5db88f0f929d9daf36d42b7dc81d93a0dd5ddb4ef2d94a0f1c05abf4197783a2', '2025-08-27 16:18:13', '2025-07-28 17:18:13');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `safe_ips`
--

CREATE TABLE `safe_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(50) NOT NULL,
  `last_successful_login` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `safe_ips`
--

INSERT INTO `safe_ips` (`id`, `ip_address`, `username`, `last_successful_login`) VALUES
(1, '127.0.0.1', 'co', '2025-07-28 20:27:55'),
(6, '::1', 'admin', '2025-07-28 20:18:13'),
(7, '127.0.0.1', 'bv', '2025-07-28 21:31:20');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','textarea','number','boolean','json') DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `is_editable` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`, `created_at`, `updated_at`) VALUES
(1, 'site_title', 'Localhost', 'text', 'Site başlığı', 1, '2025-07-01 09:56:02', '2025-07-26 22:52:57'),
(2, 'site_description', 'Modern makale sitesi', 'textarea', 'Site açıklaması', 1, '2025-07-01 09:56:02', '2025-07-26 22:52:57'),
(3, 'site_logo', 'assets/logo1.png', 'text', 'Site logosu', 1, '2025-07-01 09:56:02', '2025-07-26 22:52:57'),
(4, 'site_favicon', 'assets/images/favicon/favicon.png', 'text', 'Site favicon', 1, '2025-07-01 09:56:02', '2025-07-15 18:38:08'),
(5, 'smtp_host', 'localhost', 'text', 'SMTP sunucu adresi', 1, '2025-07-01 09:56:02', '2025-07-15 18:29:20'),
(6, 'smtp_port', '587', 'number', 'SMTP port', 1, '2025-07-01 09:56:02', '2025-07-15 18:29:20'),
(7, 'smtp_username', 'localhost', 'text', 'SMTP kullanıcı adı', 1, '2025-07-01 09:56:02', '2025-07-15 18:29:20'),
(8, 'smtp_password', '0000000', 'text', 'SMTP şifre', 1, '2025-07-01 09:56:02', '2025-07-15 18:29:20'),
(9, 'stripe_public_key', '', 'text', 'Stripe public key', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(10, 'stripe_secret_key', '', 'text', 'Stripe secret key', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(11, 'paytr_merchant_id', '', 'text', 'PayTR merchant ID', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(12, 'paytr_merchant_key', '', 'text', 'PayTR merchant key', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(13, 'paytr_merchant_salt', '', 'text', 'PayTR merchant salt', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(14, 'payment_test_mode', '1', 'boolean', 'Test modu aktif', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(15, 'articles_per_page', '4', 'number', 'Sayfa başına makale sayısı', 1, '2025-07-01 09:56:02', '2025-07-15 14:53:00'),
(16, 'enable_comments', '1', 'boolean', 'Yorum sistemi aktif', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(17, 'auto_approve_comments', '0', 'boolean', 'Yorumları otomatik onayla', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(18, 'maintenance_mode', '0', 'boolean', 'Bakım modu', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(19, 'google_analytics', '<!-- Google tag (gtag.js) -->\r\n<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-H0R85LJQJD\"></script>\r\n<script>\r\n  window.dataLayer = window.dataLayer || [];\r\n  function gtag(){dataLayer.push(arguments);}\r\n  gtag(\'js\', new Date());\r\n\r\n  gtag(\'config\', \'G-H0R85LJQJD\');\r\n</script>', 'textarea', 'Google Analytics kodu', 1, '2025-07-01 09:56:02', '2025-07-02 10:35:37'),
(20, 'social_facebook', '', 'text', 'Facebook URL', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(21, 'social_twitter', '', 'text', 'Twitter URL', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(22, 'social_instagram', '', 'text', 'Instagram URL', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(23, 'social_youtube', '', 'text', 'YouTube URL', 1, '2025-07-01 09:56:02', '2025-07-01 09:56:02'),
(24, 'cookie_consent_enabled', '1', 'text', NULL, 1, '2025-07-02 11:19:10', '2025-07-02 12:58:47'),
(25, 'cookie_consent_text', 'Bu web sitesi, size en iyi deneyimi sunmak için çerezler kullanır.', 'text', NULL, 1, '2025-07-02 11:19:10', '2025-07-02 11:19:10'),
(26, 'cookie_consent_button_text', 'Kabul Et', 'text', NULL, 1, '2025-07-02 11:19:10', '2025-07-02 11:19:10'),
(27, 'cookie_consent_position', 'bottom-left', 'text', NULL, 1, '2025-07-02 11:19:10', '2025-07-02 14:39:50'),
(28, 'cookie_consent_theme', 'light', 'text', NULL, 1, '2025-07-02 11:19:10', '2025-07-02 14:27:12'),
(29, 'cookie_consent_show_link', '1', 'text', NULL, 1, '2025-07-02 11:19:10', '2025-07-02 11:19:10'),
(30, 'cookie_consent_link_text', 'Daha fazla bilgi', 'text', NULL, 1, '2025-07-02 11:19:10', '2025-07-02 11:19:10'),
(31, 'cookie_analytics_enabled', '1', 'text', NULL, 1, '2025-07-02 11:19:10', '2025-07-02 11:19:10'),
(32, 'cookie_marketing_enabled', '1', 'text', NULL, 1, '2025-07-02 11:19:10', '2025-07-02 11:19:10'),
(780, 'sitemap_enabled', '1', 'text', NULL, 1, '2025-07-02 20:38:39', '2025-07-02 20:38:39'),
(781, 'sitemap_auto_generate', '1', 'text', NULL, 1, '2025-07-02 20:38:39', '2025-07-02 20:38:39'),
(782, 'sitemap_include_images', '0', 'text', NULL, 1, '2025-07-02 20:38:39', '2025-07-02 20:38:39'),
(783, 'sitemap_include_users', '0', 'text', NULL, 1, '2025-07-02 20:38:39', '2025-07-02 20:38:39'),
(784, 'sitemap_priority_homepage', '1.0', 'text', NULL, 1, '2025-07-02 20:38:39', '2025-07-02 20:38:39'),
(785, 'sitemap_priority_articles', '0.8', 'text', NULL, 1, '2025-07-02 20:38:39', '2025-07-02 20:38:39'),
(786, 'sitemap_priority_categories', '0.7', 'text', NULL, 1, '2025-07-02 20:38:39', '2025-07-02 20:38:39'),
(787, 'sitemap_priority_static', '0.5', 'text', NULL, 1, '2025-07-02 20:38:39', '2025-07-02 20:38:39'),
(788, 'sitemap_changefreq_homepage', 'daily', 'text', NULL, 1, '2025-07-02 20:38:39', '2025-07-02 20:38:39'),
(789, 'sitemap_changefreq_articles', 'monthly', 'text', NULL, 1, '2025-07-02 20:38:39', '2025-07-02 20:38:39'),
(790, 'sitemap_changefreq_categories', 'weekly', 'text', NULL, 1, '2025-07-02 20:38:39', '2025-07-02 20:38:39'),
(791, 'sitemap_changefreq_static', 'yearly', 'text', NULL, 1, '2025-07-02 20:38:39', '2025-07-02 20:38:39'),
(792, 'sitemap_last_cron_run', '2025-07-02 23:12:19', 'text', NULL, 1, '2025-07-02 21:12:19', '2025-07-02 21:12:19'),
(793, 'auto_backup_enabled', '1', 'text', 'Otomatik yedekleme etkin/pasif', 1, '2025-07-02 21:33:34', '2025-07-10 18:06:40'),
(794, 'backup_frequency', 'daily', 'text', 'Yedekleme sıklığı (daily, weekly, monthly)', 1, '2025-07-02 21:33:34', '2025-07-10 18:06:40'),
(795, 'backup_retention', '30', 'text', 'Yedeklerin saklanma süresi (gün)', 1, '2025-07-02 21:33:34', '2025-07-10 18:06:40'),
(796, 'canonical_tag', 'http://localhost', 'text', 'Site canonical URL ayarı', 1, '2025-07-02 22:07:24', '2025-07-03 09:16:22'),
(797, 'seo_description', '13', 'text', 'Site SEO meta açıklaması', 1, '2025-07-02 22:07:24', '2025-07-03 09:16:22'),
(798, 'seo_tags', '13', 'text', 'Site SEO meta etiketleri (virgülle ayırarak)', 1, '2025-07-02 22:07:24', '2025-07-03 09:16:22'),
(799, 'favicon_url', '/assets/images/favicon_1751533027.png', 'text', NULL, 1, '2025-07-03 08:51:08', '2025-07-03 08:57:07'),
(800, 'apple_touch_icon', '', 'text', NULL, 1, '2025-07-03 08:51:08', '2025-07-03 08:51:08'),
(801, 'android_icon', '', 'text', NULL, 1, '2025-07-03 08:51:08', '2025-07-03 08:51:08'),
(802, 'favicon', '/assets/images/favicon/favicon.png', 'text', NULL, 1, '2025-07-03 09:08:25', '2025-07-03 09:16:22'),
(803, 'site_url', 'http://localhost', 'text', 'Site URL (örn: https://example.com)', 1, '2025-07-03 21:42:06', '2025-07-03 21:42:06'),
(804, 'last_auto_backup', '2025-07-10 20:07:10', 'text', NULL, 1, '2025-07-10 18:03:09', '2025-07-10 18:07:10'),
(805, 'headline_display_type', 'carousel', 'text', NULL, 1, '2025-07-10 20:38:18', '2025-07-15 18:27:49'),
(806, 'headline_auto_change', '1', 'text', NULL, 1, '2025-07-10 20:38:18', '2025-07-15 18:27:49'),
(807, 'headline_change_interval', '5000', 'text', NULL, 1, '2025-07-10 20:38:18', '2025-07-15 18:27:49'),
(819, 'headline_selected_articles', '', 'text', 'Manşet için seçilen makale ID\'leri (virgülle ayrılmış)', 1, '2025-07-10 20:49:56', '2025-07-10 20:49:56'),
(820, 'recent_articles_display_type', 'pagination', 'text', NULL, 1, '2025-07-15 14:44:13', '2025-07-15 14:53:00'),
(821, 'popular_articles_display_type', 'pagination', 'text', NULL, 1, '2025-07-15 14:44:13', '2025-07-15 14:53:00'),
(822, 'featured_articles_display', 'pagination', 'text', NULL, 1, '2025-07-15 15:30:02', '2025-07-15 15:32:41'),
(823, 'featured_articles_per_page', '3', 'text', NULL, 1, '2025-07-15 15:30:02', '2025-07-15 16:23:05'),
(824, 'featured_articles_infinite_load', '0', 'text', NULL, 1, '2025-07-15 15:30:02', '2025-07-15 15:32:41'),
(825, 'popular_articles_display', 'pagination', 'text', NULL, 1, '2025-07-15 15:32:43', '2025-07-15 15:33:30'),
(826, 'popular_articles_per_page', '4', 'text', NULL, 1, '2025-07-15 15:32:43', '2025-07-15 16:23:05'),
(827, 'popular_articles_infinite_load', '0', 'text', NULL, 1, '2025-07-15 15:32:43', '2025-07-15 15:33:30'),
(828, 'recent_articles_display', 'pagination', 'text', NULL, 1, '2025-07-15 15:32:55', '2025-07-15 15:33:32'),
(829, 'recent_articles_per_page', '4', 'text', NULL, 1, '2025-07-15 15:32:55', '2025-07-15 16:23:05'),
(830, 'recent_articles_infinite_load', '0', 'text', NULL, 1, '2025-07-15 15:32:55', '2025-07-15 15:33:32'),
(831, 'article_display_type', 'pagination', 'text', NULL, 1, '2025-07-15 15:45:16', '2025-07-15 15:49:22'),
(832, 'article_per_page', '8', 'text', NULL, 1, '2025-07-15 15:45:16', '2025-07-15 15:49:22'),
(833, 'featured_article_display_type', 'pagination', 'text', NULL, 1, '2025-07-15 15:45:16', '2025-07-15 15:49:22'),
(834, 'featured_article_per_page', '4', 'text', NULL, 1, '2025-07-15 15:45:16', '2025-07-15 15:49:22'),
(835, 'featured_articles_pagination_type', 'pagination', 'text', 'Öne çıkan makaleler sayfalama türü (pagination/infinite)', 1, '2025-07-15 16:01:39', '2025-07-15 16:23:05'),
(836, 'recent_articles_pagination_type', 'pagination', 'text', 'Son eklenen makaleler sayfalama türü (pagination/infinite)', 1, '2025-07-15 16:01:39', '2025-07-15 16:23:05'),
(837, 'popular_articles_pagination_type', 'pagination', 'text', 'Popüler makaleler sayfalama türü (pagination/infinite)', 1, '2025-07-15 16:01:39', '2025-07-15 16:23:05'),
(838, 'site_logo_dark', 'assets/logo2.png', 'text', NULL, 1, '2025-07-26 22:52:41', '2025-07-26 22:52:57');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_statistics`
--

CREATE TABLE `site_statistics` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_views` int(11) DEFAULT 0,
  `unique_visitors` int(11) DEFAULT 0,
  `bounce_rate` decimal(5,2) DEFAULT 0.00,
  `avg_session_duration` int(11) DEFAULT 0,
  `new_users` int(11) DEFAULT 0,
  `returning_users` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_months` int(11) NOT NULL,
  `duration_type` enum('day','month','year') DEFAULT 'month',
  `sort_order` int(11) DEFAULT 0,
  `features` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `description`, `price`, `duration_months`, `duration_type`, `sort_order`, `features`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Ücretsiz', 'Temel özelliklere erişim', 0.00, 1, 'month', 0, 'Ücretsiz makalelere erişim\nReklamlar aktif\nStandart destek', 1, '2025-07-01 12:56:02', '2025-07-01 12:56:02'),
(2, 'Aylık Premium', 'Aylık premium üyelik planı', 29.90, 1, 'month', 0, 'Tüm premium makalelere erişim\r\nReklamsız deneyim\r\nÖncelikli destek\r\nPDF indirme\r\nÜyelere Mesaj Göderimi', 1, '2025-07-01 12:56:02', '2025-07-26 17:57:08'),
(3, 'Yıllık Premium', 'Yıllık premium üyelik planı (2 ay bedava)', 299.90, 12, 'month', 0, 'Tüm premium makalelere erişim\r\nReklamsız deneyim\r\nÖncelikli destek\r\nPDF indirme\r\nÜyelere Mesaj Göderimi\r\n%17 indirim', 1, '2025-07-01 12:56:02', '2025-07-26 17:57:15');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `usage_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `tags`
--

INSERT INTO `tags` (`id`, `name`, `slug`, `usage_count`, `created_at`) VALUES
(1, 'dsds', 'dsds', 0, '2025-07-01 11:18:58'),
(2, 'Kuantum', 'kuantum', 0, '2025-07-10 19:12:35'),
(3, 'Telemedicine', 'telemedicine', 0, '2025-07-10 19:15:50'),
(4, 'Akıllı Telefonlar', 'akilli-telefonlar', 1, '2025-07-10 19:18:11'),
(6, 'Turizm', 'turizm', 5, '2025-07-10 19:20:43'),
(7, 'dasdas', 'dasdas', 2, '2025-07-10 19:25:58'),
(8, 'sdd', 'sdd', 0, '2025-07-10 19:26:45'),
(9, 'asdsa', 'asdsa', 0, '2025-07-10 19:28:10'),
(10, 'dasd', 'dasd', 1, '2025-07-10 19:31:36'),
(11, 'fsdf', 'fsdf', 0, '2025-07-10 19:31:57'),
(12, '12', '12', 0, '2025-07-10 19:34:57'),
(13, '434', '434', 0, '2025-07-10 19:37:13'),
(15, 'sdfds', 'sdfds', 0, '2025-07-10 19:39:45'),
(16, 'dsadasd', 'dsadasd', 1, '2025-07-10 19:43:26'),
(17, 'dasdasdas', 'dasdasdas', 0, '2025-07-10 19:43:45'),
(18, 'dsad', 'dsad', 0, '2025-07-10 19:47:53'),
(19, 'fsdfsd', 'fsdfsd', 1, '2025-07-10 19:50:33'),
(20, 'sadsa', 'sadsa', 0, '2025-07-10 19:51:40'),
(23, 'fsdfsdf', 'fsdfsdf', 0, '2025-07-10 19:58:35'),
(29, '232', '232', 0, '2025-07-10 20:20:20'),
(31, 'dasdasd', 'dasdasd', 0, '2025-07-10 20:30:54');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `traffic_sources`
--

CREATE TABLE `traffic_sources` (
  `id` int(11) NOT NULL,
  `source_type` enum('direct','organic','social','referral','email','campaign') NOT NULL,
  `source_name` varchar(255) DEFAULT NULL,
  `medium` varchar(100) DEFAULT NULL,
  `campaign` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `visitors` int(11) DEFAULT 0,
  `sessions` int(11) DEFAULT 0,
  `page_views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `is_premium` tinyint(1) DEFAULT 0,
  `premium_expires_at` datetime DEFAULT NULL,
  `two_factor_secret` varchar(32) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `theme_preference` enum('light','dark') DEFAULT 'light',
  `language_preference` varchar(5) DEFAULT 'tr',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `twitter` varchar(100) DEFAULT NULL,
  `facebook` varchar(100) DEFAULT NULL,
  `instagram` varchar(100) DEFAULT NULL,
  `linkedin` varchar(100) DEFAULT NULL,
  `tiktok` varchar(100) DEFAULT NULL,
  `youtube` varchar(100) DEFAULT NULL,
  `github` varchar(100) DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` enum('active','banned','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_image`, `bio`, `is_approved`, `is_premium`, `premium_expires_at`, `two_factor_secret`, `is_admin`, `theme_preference`, `language_preference`, `created_at`, `updated_at`, `location`, `website`, `twitter`, `facebook`, `instagram`, `linkedin`, `tiktok`, `youtube`, `github`, `last_ip`, `last_login`, `is_active`, `status`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$7kIn.GMLrNlKJbtc8C5eweVO93MIw9KspKWxcpeRtnnH2ccJ.li/u', 'assets/images/profiles/6865860ac95f1_1751483914.jpg', '12', 1, 0, NULL, NULL, 1, 'light', 'tr', '2025-07-28 18:38:51', '2025-07-28 18:38:51', '12', 'http://localhost/', '1', '1', '1', '1', '1', '1', '1', '::1', '2025-07-28 20:18:13', 1, 'active');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_subscriptions`
--

CREATE TABLE `user_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `status` enum('active','expired','cancelled') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `ad_statistics`
--
ALTER TABLE `ad_statistics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_id` (`ad_id`);

--
-- Tablo için indeksler `ai_articles`
--
ALTER TABLE `ai_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`);

--
-- Tablo için indeksler `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `language_id` (`language_id`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_is_premium` (`is_premium`),
  ADD KEY `idx_is_featured` (`is_featured`),
  ADD KEY `idx_is_headline` (`is_headline`),
  ADD KEY `idx_published_at` (`published_at`),
  ADD KEY `idx_views_count` (`views_count`);

--
-- Tablo için indeksler `article_statistics`
--
ALTER TABLE `article_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_article_date` (`article_id`,`date`);

--
-- Tablo için indeksler `article_tags`
--
ALTER TABLE `article_tags`
  ADD PRIMARY KEY (`article_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Tablo için indeksler `banned_users`
--
ALTER TABLE `banned_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `banned_by` (`banned_by`);

--
-- Tablo için indeksler `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Tablo için indeksler `cloudflare_settings`
--
ALTER TABLE `cloudflare_settings`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_article_id` (`article_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_is_approved` (`is_approved`);

--
-- Tablo için indeksler `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `replied_by` (`replied_by`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_replied_at` (`replied_at`);

--
-- Tablo için indeksler `headline_articles`
--
ALTER TABLE `headline_articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_article` (`article_id`);

--
-- Tablo için indeksler `ip_bans`
--
ALTER TABLE `ip_bans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `banned_by` (`banned_by`);

--
-- Tablo için indeksler `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Tablo için indeksler `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reply_to_id` (`reply_to_id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_receiver_id` (`receiver_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Tablo için indeksler `online_users`
--
ALTER TABLE `online_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `page_views`
--
ALTER TABLE `page_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`created_at`),
  ADD KEY `idx_page_url` (`page_url`(255)),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_id` (`session_id`);

--
-- Tablo için indeksler `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `token` (`token`);

--
-- Tablo için indeksler `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Tablo için indeksler `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Tablo için indeksler `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_action_time` (`ip_address`,`action`,`created_at`);

--
-- Tablo için indeksler `registration_attempts`
--
ALTER TABLE `registration_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `safe_ips`
--
ALTER TABLE `safe_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`,`username`);

--
-- Tablo için indeksler `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Tablo için indeksler `site_statistics`
--
ALTER TABLE `site_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date`);

--
-- Tablo için indeksler `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sort_order` (`sort_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Tablo için indeksler `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_usage_count` (`usage_count`);

--
-- Tablo için indeksler `traffic_sources`
--
ALTER TABLE `traffic_sources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_source_date` (`source_type`,`source_name`,`date`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_is_approved` (`is_approved`),
  ADD KEY `idx_is_premium` (`is_premium`);

--
-- Tablo için indeksler `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Tablo için AUTO_INCREMENT değeri `ad_statistics`
--
ALTER TABLE `ad_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `ai_articles`
--
ALTER TABLE `ai_articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Tablo için AUTO_INCREMENT değeri `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `article_statistics`
--
ALTER TABLE `article_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `banned_users`
--
ALTER TABLE `banned_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `cloudflare_settings`
--
ALTER TABLE `cloudflare_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `headline_articles`
--
ALTER TABLE `headline_articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Tablo için AUTO_INCREMENT değeri `ip_bans`
--
ALTER TABLE `ip_bans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Tablo için AUTO_INCREMENT değeri `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Tablo için AUTO_INCREMENT değeri `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `online_users`
--
ALTER TABLE `online_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=261;

--
-- Tablo için AUTO_INCREMENT değeri `page_views`
--
ALTER TABLE `page_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `registration_attempts`
--
ALTER TABLE `registration_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Tablo için AUTO_INCREMENT değeri `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `safe_ips`
--
ALTER TABLE `safe_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=839;

--
-- Tablo için AUTO_INCREMENT değeri `site_statistics`
--
ALTER TABLE `site_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Tablo için AUTO_INCREMENT değeri `traffic_sources`
--
ALTER TABLE `traffic_sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `ad_statistics`
--
ALTER TABLE `ad_statistics`
  ADD CONSTRAINT `ad_statistics_ibfk_1` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ai_articles`
--
ALTER TABLE `ai_articles`
  ADD CONSTRAINT `ai_articles_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `articles_ibfk_3` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `article_tags`
--
ALTER TABLE `article_tags`
  ADD CONSTRAINT `article_tags_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `banned_users`
--
ALTER TABLE `banned_users`
  ADD CONSTRAINT `banned_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `banned_users_ibfk_2` FOREIGN KEY (`banned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `headline_articles`
--
ALTER TABLE `headline_articles`
  ADD CONSTRAINT `headline_articles_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ip_bans`
--
ALTER TABLE `ip_bans`
  ADD CONSTRAINT `ip_bans_ibfk_1` FOREIGN KEY (`banned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `online_users`
--
ALTER TABLE `online_users`
  ADD CONSTRAINT `online_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`);

--
-- Tablo kısıtlamaları `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD CONSTRAINT `user_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`),
  ADD CONSTRAINT `user_subscriptions_ibfk_3` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
