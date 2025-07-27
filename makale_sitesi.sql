-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 27 Tem 2025, 12:22:30
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

--
-- Tablo döküm verisi `ai_articles`
--

INSERT INTO `ai_articles` (`id`, `article_id`, `ai_type`, `prompt`, `word_count`, `processing_time`, `cover_image_url`, `image1_url`, `image2_url`, `created_at`) VALUES
(5, 51, 'gemini', 'Kategori: \"Sağlık\" için bir makale yaz. Yaklaşık 500 kelime uzunluğunda olsun. SEO uyumlu, bilgilendirici ve ilgi çekici bir başlık ve içerik oluştur. Türkçe dil bilgisi kurallarına uygun, akıcı ve anlaşılır bir dil kullan. İçeriği HTML formatında döndür, başlık için <h1> etiketi kullan, alt başlıklar için <h2> ve <h3> kullan. Makalenin sonunda okuyucuyu harekete geçiren bir çağrı ifadesi ekle.', 500, 6.32471, 'https://images.unsplash.com/photo-1592580715317-19adca36288e?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwyfHxTYSVDNCU5RmwlQzQlQjFrbCVDNCVCMSUyMFlhJUM1JTlGYW0lMjAlQzQlQjAlQzMlQTdpbiUyMFByYXRpayUyMCVDNCVCMHB1JUMzJUE3bGFyJUM0JUIxJ', 'https://images.unsplash.com/photo-1605606437828-598340dfaeb7?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwxfHxTYSVDNCU5RmwlQzQlQjFrbCVDNCVCMSUyMFlhJUM1JTlGYW0lMjAlQzQlQjAlQzMlQTdpbiUyMFByYXRpayUyMCVDNCVCMHB1JUMzJUE3bGFyJUM0JUIxJ', 'https://images.unsplash.com/photo-1592580715317-19adca36288e?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwyfHxTYSVDNCU5RmwlQzQlQjFrbCVDNCVCMSUyMFlhJUM1JTlGYW0lMjAlQzQlQjAlQzMlQTdpbiUyMFByYXRpayUyMCVDNCVCMHB1JUMzJUE3bGFyJUM0JUIxJ', '2025-07-26 21:37:19'),
(6, 52, 'gemini', 'Kategori: \"Gezi Rehberi\" için bir makale yaz. Yaklaşık 500 kelime uzunluğunda olsun. SEO uyumlu, bilgilendirici ve ilgi çekici bir başlık ve içerik oluştur. Türkçe dil bilgisi kurallarına uygun, akıcı ve anlaşılır bir dil kullan. İçeriği HTML formatında döndür, başlık için <h1> etiketi kullan, alt başlıklar için <h2> ve <h3> kullan. Makalenin sonunda okuyucuyu harekete geçiren bir çağrı ifadesi ekle.', 500, 6.52271, 'https://images.unsplash.com/photo-1719163893241-b36167f81a31?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwzfHxVbnV0dWxtYXolMjBCaXIlMjBHZXppJTIwJUM0JUIwJUMzJUE3aW4lMjBUYW0lMjBLYXBzYW1sJUM0JUIxJTIwR2V6aSUyMFJlaGJlcml8ZW58MXwwfHx8M', 'https://images.unsplash.com/photo-1692895591954-451050db22fd?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwxfHxVbnV0dWxtYXolMjBCaXIlMjBHZXppJTIwJUM0JUIwJUMzJUE3aW4lMjBUYW0lMjBLYXBzYW1sJUM0JUIxJTIwR2V6aSUyMFJlaGJlcml8ZW58MXwwfHx8M', 'https://images.unsplash.com/photo-1570714436355-2556087f0912?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwyfHxVbnV0dWxtYXolMjBCaXIlMjBHZXppJTIwJUM0JUIwJUMzJUE3aW4lMjBUYW0lMjBLYXBzYW1sJUM0JUIxJTIwR2V6aSUyMFJlaGJlcml8ZW58MXwwfHx8M', '2025-07-26 21:38:17'),
(7, 53, 'gemini', 'Kategori: \"Gezi Rehberi\" için tamamen özgün ve benzersiz bir makale yaz. Makale başlığı kesinlikle daha önce hiç kullanılmamış, çarpıcı ve dikkat çekici olmalı. Başlık oluştururken şu teknikleri kullan: sorular sor, rakamlar kullan, güçlü sıfatlar ekle, merak uyandır ve duygusal tepki yaratacak ifadeler kullan. Başlıklarda klişelerden kaçın. Yaklaşık 500 kelime uzunluğunda olsun. SEO uyumlu, bilgilendirici ve ilgi çekici benzersiz bir başlık oluştur. Başlıkta beklenmedik kelime kombinasyonları, orijinal fikirler ve çarpıcı ifadeler kullan. Farklı başlık yapıları dene: \'Nasıl...\', \'X Adımda...\', \'... Hakkında Bilmeniz Gereken X Şey\', \'...nın Sırları\' gibi kalıplar yerine daha yaratıcı ifadeler kullan. Türkçe dil bilgisi kurallarına uygun, akıcı ve anlaşılır bir dil kullan. İçeriği HTML formatında döndür, başlık için <h1> etiketi kullan, alt başlıklar için <h2> ve <h3> kullan. Makalenin sonunda okuyucuyu harekete geçiren bir çağrı ifadesi ekle. Önemli: Her üretim tamamen benzersiz olmalıdır. Benzersiz Kimlik: 1b27ea02, Rastgele Faktör: 6212, Tarih: 1753566536.', 500, 7.09757, 'https://images.unsplash.com/photo-1690061522034-5fe90656d29b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwxfHxLYXklQzQlQjFwJTIwJUM1JTlFZWhpcmxlcmluJTIwRiVDNCVCMXMlQzQlQjFsdCVDNCVCMXMlQzQlQjElM0ElMjA3JTIwR2l6bGklMjBDZW5uZXQlMkMlM', 'https://images.unsplash.com/photo-1690061522034-5fe90656d29b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwxfHxLYXklQzQlQjFwJTIwJUM1JTlFZWhpcmxlcmluJTIwRiVDNCVCMXMlQzQlQjFsdCVDNCVCMXMlQzQlQjElM0ElMjA3JTIwR2l6bGklMjBDZW5uZXQlMkMlM', 'https://images.unsplash.com/photo-1717539780863-75b1635259cb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwyfHxLYXklQzQlQjFwJTIwJUM1JTlFZWhpcmxlcmluJTIwRiVDNCVCMXMlQzQlQjFsdCVDNCVCMXMlQzQlQjElM0ElMjA3JTIwR2l6bGklMjBDZW5uZXQlMkMlM', '2025-07-26 21:49:33');

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

--
-- Tablo döküm verisi `articles`
--

INSERT INTO `articles` (`id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, `category_id`, `user_id`, `language_id`, `is_premium`, `is_private`, `is_featured`, `is_headline`, `status`, `views_count`, `meta_title`, `meta_description`, `meta_keywords`, `published_at`, `created_at`, `updated_at`) VALUES
(19, 'Kuantum Bilgisayarın Geleceği', 'kuantum-bilgisayarin-gelecegi', '<p dir=\"ltr\" data-pm-slice=\"1 1 []\">Kuantum bilgisayarı, klasik bilgisayarlardan farklı olarak kuantum mekaniği prensiplerine dayalı hesaplama yapabilen bir teknolojidir. Geleneksel bilgisayarlarda bitler 0 veya 1 değerini alırken, kuantum bilgisayarlarda kubitler s&uuml;perpozisyon ve dolaşıklık gibi kuantum &ouml;zelliklerini kullanarak aynı anda birden fazla durumu temsil edebilir. Bu, kuantum bilgisayarların belirli problemleri &ccedil;&ouml;zmede klasik bilgisayarlardan kat kat daha hızlı olabileceği anlamına gelir.</p>\r\n<h2 dir=\"ltr\">Kuantum Bilgisayarın G&uuml;n&uuml;m&uuml;zdeki Durumu</h2>\r\n<p dir=\"ltr\">2025 itibarıyla kuantum bilgisayarı teknolojisi h&acirc;l&acirc; gelişim aşamasındadır. IBM, Google, Microsoft gibi teknoloji devleri ve D-Wave gibi uzmanlaşmış şirketler, kuantum bilgisayarların pratik uygulamalarını hayata ge&ccedil;irmek i&ccedil;in yoğun &ccedil;alışmalar y&uuml;r&uuml;tmektedir. &Ouml;rneğin, IBM\'in 2023\'te tanıttığı 433 kubitlik Osprey işlemcisi, kuantum hesaplama kapasitesinde &ouml;nemli bir adımdı. Ancak, kuantum bilgisayarlar hen&uuml;z genel ama&ccedil;lı kullanıma hazır değildir ve genellikle yalnızca belirli problemler i&ccedil;in optimize edilmiştir.</p>\r\n<h3 dir=\"ltr\">Mevcut Uygulamalar</h3>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>Kriptografi</strong>: Kuantum bilgisayarlar, klasik şifreleme y&ouml;ntemlerini (&ouml;rneğin RSA) kırabilecek potansiyele sahiptir. Bu nedenle, kuantum-diren&ccedil;li şifreleme algoritmaları &uuml;zerine &ccedil;alışmalar hız kazanmıştır.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Kimya ve Malzeme Bilimi</strong>: Kuantum bilgisayarlar, molek&uuml;llerin kuantum d&uuml;zeyde sim&uuml;lasyonunu yaparak yeni ila&ccedil;ların veya malzemelerin keşfini hızlandırabilir.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Optimizasyon Problemleri</strong>: Lojistik, finans ve yapay zeka gibi alanlarda karmaşık optimizasyon problemlerini &ccedil;&ouml;zmek i&ccedil;in kuantum algoritmaları umut vadetmektedir.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">Gelecekteki Potansiyel</h2>\r\n<p dir=\"ltr\">Kuantum bilgisayarın geleceği, hem teknolojik hem de toplumsal a&ccedil;ıdan b&uuml;y&uuml;k bir d&ouml;n&uuml;ş&uuml;m vaat ediyor. &Ouml;n&uuml;m&uuml;zdeki 10-15 yıl i&ccedil;inde aşağıdaki gelişmelerin ger&ccedil;ekleşmesi bekleniyor:</p>\r\n<ol class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>Hata D&uuml;zeltme ve &Ouml;l&ccedil;eklenebilirlik</strong>: Kuantum bilgisayarların g&uuml;venilirliği, hata d&uuml;zeltme algoritmalarındaki ilerlemelerle artacak. Bu, daha b&uuml;y&uuml;k ve daha karmaşık problemlerin &ccedil;&ouml;z&uuml;lmesini m&uuml;mk&uuml;n kılacak.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Kuantum İnterneti</strong>: Kuantum dolaşıklığına dayalı iletişim ağları, ultra g&uuml;venli veri transferini sağlayabilir.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>End&uuml;striyel Uygulamalar</strong>: Enerji, sağlık, finans ve yapay zeka gibi sekt&ouml;rlerde kuantum bilgisayarlar, mevcut teknolojilerin sınırlarını zorlayarak yenilik&ccedil;i &ccedil;&ouml;z&uuml;mler sunabilir.</p>\r\n</li>\r\n</ol>\r\n<h2 dir=\"ltr\">Zorluklar ve Engeller</h2>\r\n<p dir=\"ltr\">Kuantum bilgisayarın yaygınlaşmasının &ouml;n&uuml;nde bazı &ouml;nemli engeller bulunmaktadır:</p>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>Teknik Zorluklar</strong>: Kuantum sistemleri, &ccedil;evresel g&uuml;r&uuml;lt&uuml;ye karşı son derece hassastır ve bu, kubitlerin kararlılığını korumayı zorlaştırır.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Maliyet</strong>: Kuantum bilgisayarların &uuml;retimi ve bakımı şu anda olduk&ccedil;a pahalıdır.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Yetkinlik Eksikliği</strong>: Kuantum programlama ve algoritma geliştirme konusunda uzmanlaşmış profesyonellerin sayısı h&acirc;l&acirc; sınırlıdır.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">Sonu&ccedil;</h2>\r\n<p dir=\"ltr\">Kuantum bilgisayarı, insanlığın hesaplama g&uuml;c&uuml;n&uuml; yeniden tanımlama potansiyeline sahiptir. Hen&uuml;z başlangı&ccedil; aşamasında olmasına rağmen, bu teknoloji bilim, end&uuml;stri ve g&uuml;venlik alanlarında devrim yaratabilir. Gelecek yıllarda, kuantum teknolojisinin gelişimiyle birlikte, g&uuml;nl&uuml;k hayatımızda da etkilerini g&ouml;rmemiz muhtemeldir. Ancak, bu potansiyelin realize edilmesi i&ccedil;in hem teknik hem de toplumsal hazırlıkların tamamlanması gerekiyor.</p>', 'Kuantum bilgisayarı, klasik bilgisayarlardan farklı olarak kuantum mekaniği prensiplerine dayalı hesaplama yapabilen bir teknolojidir.', 'https://ioturkiye.com/wp-content/uploads/2022/09/5144681b9f6c20e88e6e96cb6c78af8b-e1663409429771.jpeg', 13, 1, 1, 0, 0, 0, 0, 'published', 1, 'Kuantum', 'Kuantum', NULL, NULL, '2025-07-10 19:12:35', '2025-07-10 19:12:51'),
(20, 'Telemedicine ve Sağlık Hizmetlerinin Geleceği', 'telemedicine-ve-saglik-hizmetlerinin-gelecegi', '<p dir=\"ltr\" data-pm-slice=\"1 1 []\">Telemedicine, sağlık hizmetlerinin dijital platformlar &uuml;zerinden sunulmasını sağlayan bir teknolojidir. &Ouml;zellikle 2020\'lerin başında pandemiyle birlikte pop&uuml;lerlik kazanan telemedicine, hastaların doktorlarla video konferans, mobil uygulamalar veya &ccedil;evrimi&ccedil;i portallar aracılığıyla iletişim kurmasını m&uuml;mk&uuml;n kılıyor. Bu makale, telemedicine&rsquo;in sağlık sekt&ouml;r&uuml;ndeki mevcut etkilerini, avantajlarını, zorluklarını ve gelecekteki potansiyelini ele alıyor.</p>\r\n<h2 dir=\"ltr\">Telemedicine&rsquo;in G&uuml;n&uuml;m&uuml;zdeki Rol&uuml;</h2>\r\n<p dir=\"ltr\">2025 itibarıyla telemedicine, sağlık hizmetlerinin ayrılmaz bir par&ccedil;ası haline gelmiştir. D&uuml;nya genelinde hastaneler, klinikler ve &ouml;zel sağlık kuruluşları, rutin kontrollerden kronik hastalık y&ouml;netimine kadar bir&ccedil;ok alanda telemedicine&rsquo;i entegre etmiştir. &Ouml;rneğin:</p>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>Uzak B&ouml;lgelerdeki Erişim</strong>: Kırsal veya sağlık altyapısının sınırlı olduğu b&ouml;lgelerde yaşayan hastalar, uzman doktorlara erişim sağlayabiliyor.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Psikolojik Destek</strong>: Online terapi ve danışmanlık hizmetleri, ruh sağlığı alanında b&uuml;y&uuml;k bir boşluğu dolduruyor.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Kronik Hastalık Takibi</strong>: Diyabet, hipertansiyon gibi kronik hastalıkları olan hastalar, wearable cihazlar ve telemedicine platformları aracılığıyla d&uuml;zenli olarak izleniyor.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">Avantajları</h2>\r\n<p dir=\"ltr\">Telemedicine, hem hastalara hem de sağlık sistemine &ouml;nemli faydalar sunar:</p>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>Erişim Kolaylığı</strong>: Hastalar, evlerinden &ccedil;ıkmadan sağlık hizmetlerine ulaşabilir, bu da &ouml;zellikle yaşlılar ve engelli bireyler i&ccedil;in b&uuml;y&uuml;k bir avantajdır.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Maliyet Etkinliği</strong>: Fiziksel ziyaretlerin azalması, hem hastalar hem de sağlık kuruluşları i&ccedil;in maliyetleri d&uuml;ş&uuml;r&uuml;r.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Zaman Tasarrufu</strong>: Randevu s&uuml;relerinin kısalması ve seyahat ihtiyacının ortadan kalkması, hasta memnuniyetini artırır.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Erken M&uuml;dahale</strong>: Telemedicine, semptomların erken teşhisini ve hızlı m&uuml;dahaleyi kolaylaştırır.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">Zorluklar ve Engeller</h2>\r\n<p dir=\"ltr\">Telemedicine&rsquo;in yaygınlaşması, bazı &ouml;nemli zorluklarla karşı karşıyadır:</p>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>Teknolojik Altyapı</strong>: İnternet erişimi olmayan veya teknolojiye aşina olmayan bireyler, telemedicine hizmetlerinden yeterince faydalanamıyor.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Veri G&uuml;venliği</strong>: Hasta verilerinin korunması, siber g&uuml;venlik tehditleri nedeniyle kritik bir konudur. Sağlık verilerinin k&ouml;t&uuml;ye kullanımı, ciddi etik ve yasal sorunlara yol a&ccedil;abilir.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Tanı Sınırlamaları</strong>: Fiziksel muayene gerektiren durumlarda, telemedicine yetersiz kalabilir. &Ouml;rneğin, bazı hastalıkların teşhisi i&ccedil;in laboratuvar testleri veya g&ouml;r&uuml;nt&uuml;leme teknikleri gereklidir.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Reg&uuml;lasyonlar</strong>: &Uuml;lkeler arasında telemedicine uygulamalarına ilişkin yasal d&uuml;zenlemeler farklılık g&ouml;steriyor, bu da uluslararası hizmet sunumunu zorlaştırıyor.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">Gelecekteki Potansiyel</h2>\r\n<p dir=\"ltr\">Telemedicine, yapay zeka (AI), giyilebilir teknolojiler ve 5G gibi yeniliklerle birleştiğinde, sağlık hizmetlerinin geleceğini yeniden şekillendirme potansiyeline sahiptir:</p>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>AI Destekli Teşhis</strong>: Yapay zeka, telemedicine platformlarında semptom analizi ve erken teşhis i&ccedil;in kullanılabilir. &Ouml;rneğin, deri lezyonlarının g&ouml;r&uuml;nt&uuml; analizleriyle cilt kanseri teşhisi yapılabiliyor.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Giyilebilir Cihazlar</strong>: Akıllı saatler ve sens&ouml;rler, hastaların vital bulgularını ger&ccedil;ek zamanlı olarak doktorlara iletebilir.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Kişiselleştirilmiş Tıp</strong>: Telemedicine, genetik veriler ve hasta ge&ccedil;mişiyle birleştirildiğinde, bireyselleştirilmiş tedavi planları sunabilir.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>K&uuml;resel Sağlık Ağı</strong>: 5G ve uydu internet teknolojileri, telemedicine&rsquo;in d&uuml;nya &ccedil;apında daha erişilebilir hale gelmesini sağlayabilir.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">Sonu&ccedil;</h2>\r\n<p dir=\"ltr\">Telemedicine, sağlık hizmetlerini daha erişilebilir, verimli ve hasta odaklı hale getirerek modern tıbbın &ouml;nemli bir bileşeni olmuştur. Ancak, teknolojinin tam potansiyeline ulaşması i&ccedil;in altyapı eksikliklerinin giderilmesi, veri g&uuml;venliğinin sağlanması ve yasal &ccedil;er&ccedil;evenin standardize edilmesi gerekiyor. Gelecek yıllarda, telemedicine&rsquo;in sağlık sekt&ouml;r&uuml;ndeki etkisi daha da artarak, hem bireylerin yaşam kalitesini y&uuml;kseltecek hem de k&uuml;resel sağlık sistemlerini d&ouml;n&uuml;şt&uuml;recektir.</p>', 'Telemedicine, sağlık hizmetlerinin dijital platformlar üzerinden sunulmasını sağlayan bir teknolojidir.', 'https://medya24com.teimg.com/medya24-com/uploads/2024/07/artificial-intelligence-and-telemedicine.jpg', 14, 1, 1, 0, 0, 0, 0, 'published', 0, 'Telemedicine', 'Telemedicine', NULL, NULL, '2025-07-10 19:15:50', '2025-07-10 19:15:50'),
(21, 'Akıllı Telefonların Evrimi ve Geleceği', 'akilli-telefonlarin-evrimi-ve-gelecegi', '<p dir=\"ltr\" data-pm-slice=\"1 1 []\">Akıllı telefonlar, modern yaşamın vazge&ccedil;ilmez bir par&ccedil;ası haline gelmiştir. İlk olarak 1990\'larda ortaya &ccedil;ıkan basit cep telefonlarından, g&uuml;n&uuml;m&uuml;zde yapay zeka destekli, &ccedil;ok işlevli cihazlara kadar uzanan bu yolculuk, teknolojinin insan hayatındaki d&ouml;n&uuml;şt&uuml;r&uuml;c&uuml; g&uuml;c&uuml;n&uuml; g&ouml;zler &ouml;n&uuml;ne seriyor. Bu makale, akıllı telefonların evrimini, mevcut durumunu ve gelecekteki potansiyelini inceliyor.</p>\r\n<h2 dir=\"ltr\">Akıllı Telefonların Tarihsel Gelişimi</h2>\r\n<p dir=\"ltr\">Akıllı telefonların k&ouml;keni, 1992\'de IBM\'in tanıttığı Simon Personal Communicator\'a kadar uzanır. Bu cihaz, telefon g&ouml;r&uuml;şmelerinin &ouml;tesinde e-posta g&ouml;nderme ve takvim y&ouml;netimi gibi &ouml;zellikler sunuyordu. Ancak, akıllı telefonların pop&uuml;lerleşmesi 2007\'de Apple\'ın iPhone\'u piyasaya s&uuml;rmesiyle başladı. iPhone, dokunmatik ekran, kullanıcı dostu aray&uuml;z ve uygulama ekosistemiyle &ccedil;ığır a&ccedil;tı. Ardından Android işletim sisteminin y&uuml;kselişi, Samsung, Xiaomi ve diğer markaların rekabete katılmasıyla pazar &ccedil;eşitlendi.</p>\r\n<p dir=\"ltr\">2010\'larda 4G bağlantısı, daha g&uuml;&ccedil;l&uuml; işlemciler ve y&uuml;ksek &ccedil;&ouml;z&uuml;n&uuml;rl&uuml;kl&uuml; kameralar akıllı telefonları birer mini bilgisayara d&ouml;n&uuml;şt&uuml;rd&uuml;. 2020\'lere gelindiğinde ise 5G, katlanabilir ekranlar ve yapay zeka entegrasyonu gibi yenilikler standart haline geldi.</p>\r\n<h2 dir=\"ltr\">G&uuml;n&uuml;m&uuml;zdeki Durum</h2>\r\n<p dir=\"ltr\">2025 itibarıyla akıllı telefonlar, iletişimden &ccedil;ok daha fazlasını sunuyor:</p>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>&Ccedil;ok Y&ouml;nl&uuml; Kullanım</strong>: Telefonlar, sosyal medya, oyun, iş y&ouml;netimi, fotoğraf&ccedil;ılık ve hatta sağlık takibi i&ccedil;in kullanılıyor. &Ouml;rneğin, bazı cihazlar kan oksijen seviyesini &ouml;l&ccedil;ebiliyor.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Yapay Zeka Entegrasyonu</strong>: AI, sesli asistanlardan (Siri, Google Assistant) kamera optimizasyonuna kadar her alanda rol oynuyor. Fotoğraf d&uuml;zenleme ve ger&ccedil;ek zamanlı &ccedil;eviri gibi &ouml;zellikler artık sıradan.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>S&uuml;rd&uuml;r&uuml;lebilirlik &Ccedil;abaları</strong>: Apple ve Samsung gibi şirketler, geri d&ouml;n&uuml;şt&uuml;r&uuml;lebilir malzemeler kullanarak &ccedil;evre dostu telefonlar &uuml;retmeye odaklanıyor.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Katlanabilir Telefonlar</strong>: Samsung Galaxy Z Fold ve Flip serileri gibi katlanabilir cihazlar, taşınabilirlik ve ekran boyutu arasında denge sağlıyor.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">Gelecekteki Potansiyel</h2>\r\n<p dir=\"ltr\">Akıllı telefonların geleceği, teknolojik yeniliklerle şekillenecek:</p>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>Artırılmış Ger&ccedil;eklik (AR) ve Sanal Ger&ccedil;eklik (VR)</strong>: Telefonlar, AR g&ouml;zl&uuml;kleriyle entegre olarak oyun, eğitim ve iş d&uuml;nyasında immersive deneyimler sunabilir.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Enerji Verimliliği</strong>: Yeni batarya teknolojileri, &ouml;rneğin grafen bazlı piller, şarj s&uuml;relerini kısaltabilir ve cihazların g&uuml;nlerce &ccedil;alışmasını sağlayabilir.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Mod&uuml;ler Tasarım</strong>: Telefonların par&ccedil;alarının (kamera, batarya) kolayca değiştirilebilir olması, hem maliyetleri d&uuml;ş&uuml;rebilir hem de elektronik atığı azaltabilir.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Biyometrik Gelişmeler</strong>: Y&uuml;z tanıma ve parmak izi tarayıcılarının &ouml;tesinde, kan akışı veya beyin dalgaları gibi yeni biyometrik sistemler g&uuml;venlik standartlarını y&uuml;kseltebilir.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>6G ve &Ouml;tesi</strong>: 6G ağlarının 2030\'larda devreye girmesiyle, ultra d&uuml;ş&uuml;k gecikme s&uuml;releri ve holografik iletişim gibi yenilikler m&uuml;mk&uuml;n olabilir.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">Zorluklar</h2>\r\n<p dir=\"ltr\">Akıllı telefon sekt&ouml;r&uuml;n&uuml;n karşılaştığı bazı engeller şunlardır:</p>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>Gizlilik ve G&uuml;venlik</strong>: Kullanıcı verilerinin korunması, &ouml;zellikle AI ve bulut tabanlı hizmetlerin artmasıyla kritik bir sorun.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Elektronik Atık</strong>: Her yıl milyonlarca telefon atık haline geliyor. Geri d&ouml;n&uuml;ş&uuml;m s&uuml;re&ccedil;lerinin geliştirilmesi gerekiyor.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Pazar Doygunluğu</strong>: Yenilik&ccedil;i &ouml;zelliklerin azalması, t&uuml;keticilerin telefon değiştirme sıklığını d&uuml;ş&uuml;r&uuml;yor.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Bağımlılık</strong>: Akıllı telefonların aşırı kullanımı, &ouml;zellikle gen&ccedil;lerde dikkat dağınıklığı ve sosyal izolasyon gibi sorunlara yol a&ccedil;ıyor.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">Sonu&ccedil;</h2>\r\n<p dir=\"ltr\">Akıllı telefonlar, iletişimden eğlenceye, sağlıktan eğitime kadar hayatımızın her alanında devrim yarattı. 2025&rsquo;te, bu cihazlar teknolojik yeniliklerin &ouml;n saflarında yer alıyor ve gelecekte daha da entegre, s&uuml;rd&uuml;r&uuml;lebilir ve kullanıcı odaklı hale gelmesi bekleniyor. Ancak, gizlilik, &ccedil;evre ve toplumsal etkiler gibi konularda dikkatli adımlar atılması, bu teknolojinin s&uuml;rd&uuml;r&uuml;lebilir bir şekilde ilerlemesi i&ccedil;in şart.</p>', 'Akıllı telefonlar, modern yaşamın vazgeçilmez bir parçası haline gelmiştir. İlk olarak 1990&amp;#039;larda ortaya çıkan basit cep telefonlarından, günümüzde yapay zeka destekli, çok işlevli cihazlara kadar uzanan bu yolculuk, teknolojinin insan hayatındaki dönüştürücü gücünü gözler önüne seriyor.', 'https://www.innova.com.tr/medias/akilli-telefonlarin-gelecegi.jpg', 15, 1, 1, 0, 0, 0, 0, 'published', 2, 'Akıllı Telefonlar', 'Akıllı Telefonlar', NULL, NULL, '2025-07-10 19:18:11', '2025-07-14 10:29:14'),
(22, 'Sürdürülebilir Turizm ve Geleceğin Gezi Trendleri', 'surdurulebilir-turizm-ve-gelecegin-gezi-trendleri', '<p dir=\"ltr\" data-pm-slice=\"1 1 []\">Gezi, insanlık tarihinin en eski aktivitelerinden biridir ve g&uuml;n&uuml;m&uuml;zde hem bireysel hem de toplumsal d&uuml;zeyde b&uuml;y&uuml;k bir &ouml;neme sahiptir. 2025 itibarıyla, turizm sekt&ouml;r&uuml; teknolojik yenilikler, &ccedil;evresel farkındalık ve değişen t&uuml;ketici alışkanlıklarıyla yeniden şekilleniyor. Bu makale, s&uuml;rd&uuml;r&uuml;lebilir turizmin y&uuml;kselişini, gezi alışkanlıklarındaki d&ouml;n&uuml;ş&uuml;m&uuml; ve gelecekteki trendleri ele alıyor.</p>\r\n<h2 dir=\"ltr\">&nbsp;</h2>\r\n<h1 dir=\"ltr\">S&uuml;rd&uuml;r&uuml;lebilir Turizmin Y&uuml;kselişi</h1>\r\n<p dir=\"ltr\">S&uuml;rd&uuml;r&uuml;lebilir turizm, &ccedil;evresel, k&uuml;lt&uuml;rel ve ekonomik kaynakları korurken gezginlere otantik deneyimler sunmayı hedefler. İklim değişikliği ve doğal kaynakların t&uuml;kenmesi gibi sorunlar, turizm sekt&ouml;r&uuml;n&uuml; daha sorumlu bir yaklaşıma zorluyor. 2025&rsquo;te s&uuml;rd&uuml;r&uuml;lebilir turizm, aşağıdaki şekillerde kendini g&ouml;steriyor:</p>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>Eko-Dostu Konaklama</strong>: Karbon n&ouml;tr oteller, geri d&ouml;n&uuml;şt&uuml;r&uuml;lebilir malzemelerle inşa edilen tatil k&ouml;yleri ve enerji verimli tesisler pop&uuml;lerlik kazanıyor.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Yerel K&uuml;lt&uuml;rlerin Korunması</strong>: Gezginler, yerel topluluklarla etkileşime girerek onların geleneklerini ve ekonomilerini destekleyen turları tercih ediyor.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>D&uuml;ş&uuml;k Karbonlu Ulaşım</strong>: Elektrikli trenler, bisiklet turları ve paylaşımlı ulaşım se&ccedil;enekleri, u&ccedil;ak gibi y&uuml;ksek karbon salınımlı alternatiflere kıyasla daha &ccedil;ok rağbet g&ouml;r&uuml;yor.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">&nbsp;</h2>\r\n<h1 dir=\"ltr\">G&uuml;n&uuml;m&uuml;z Gezi Trendleri</h1>\r\n<p dir=\"ltr\">2025&rsquo;te gezi alışkanlıkları, teknoloji ve bireysel ihtiya&ccedil;lar doğrultusunda evrilmeye devam ediyor:</p>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>Dijital G&ouml;&ccedil;ebelik</strong>: Uzaktan &ccedil;alışma k&uuml;lt&uuml;r&uuml;n&uuml;n yaygınlaşmasıyla, gezginler uzun s&uuml;reli konaklamalar i&ccedil;in &ldquo;&ccedil;alışma tatilleri&rdquo; d&uuml;zenliyor. Bali, Lizbon ve Tiflis gibi destinasyonlar, dijital g&ouml;&ccedil;ebeler i&ccedil;in pop&uuml;ler merkezler haline geldi.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Macera ve Wellness Turizmi</strong>: Yoga kampları, dağ tırmanışları ve meditasyon inzivaları gibi hem fiziksel hem de zihinsel sağlığı destekleyen geziler reva&ccedil;ta.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Kişiselleştirilmiş Deneyimler</strong>: Yapay zeka destekli seyahat uygulamaları, gezginlerin ilgi alanlarına g&ouml;re &ouml;zelleştirilmiş rotalar ve aktiviteler sunuyor.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Kısa Mesafeli Seyahatler</strong>: Pandemi sonrası d&ouml;nemde başlayan &ldquo;yakın destinasyon&rdquo; trendi devam ediyor. İnsanlar, kendi &uuml;lkelerindeki veya komşu b&ouml;lgelerdeki gizli kalmış yerleri keşfetmeyi tercih ediyor.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">&nbsp;</h2>\r\n<h1 dir=\"ltr\">Geleceğin Gezi Trendleri</h1>\r\n<p dir=\"ltr\">Turizm sekt&ouml;r&uuml;, &ouml;n&uuml;m&uuml;zdeki yıllarda teknolojik ve toplumsal değişimlerden etkilenmeye devam edecek:</p>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>Sanal ve Artırılmış Ger&ccedil;eklik (VR/AR)</strong>: VR turları, fiziksel seyahat &ouml;ncesi destinasyonları deneyimleme imkanı sunacak. &Ouml;rneğin, bir gezgin Machu Picchu&rsquo;yu sanal olarak gezebilir ve ardından fiziksel ziyareti planlayabilir.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Uzay Turizmi</strong>: SpaceX ve Blue Origin gibi şirketlerin &ccedil;alışmaları sayesinde, uzay gezileri 2030&rsquo;lara doğru daha erişilebilir hale gelebilir. Ay veya d&uuml;ş&uuml;k y&ouml;r&uuml;nge turları, ultra zengin gezginler i&ccedil;in bir se&ccedil;enek olacak.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Akıllı Destinasyonlar</strong>: Nesnelerin İnterneti (IoT) ve 5G teknolojileri, şehirlerin turist akışını y&ouml;netmesini ve kişiselleştirilmiş hizmetler sunmasını sağlayacak. &Ouml;rneğin, akıllı şehirlerdeki sens&ouml;rler, kalabalık b&ouml;lgeler hakkında ger&ccedil;ek zamanlı bilgi verebilir.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>İklim Odaklı Seyahat</strong>: Gezginler, karbon ayak izlerini dengelemek i&ccedil;in &ldquo;karbon ofset&rdquo; programlarına katılacak veya yalnızca &ccedil;evre dostu destinasyonları se&ccedil;ecek.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">&nbsp;</h2>\r\n<h1 dir=\"ltr\">Zorluklar</h1>\r\n<p dir=\"ltr\">S&uuml;rd&uuml;r&uuml;lebilir turizmin ve modern gezi trendlerinin yaygınlaşması bazı engellerle karşı karşıya:</p>\r\n<ul class=\"tight\" dir=\"ltr\" data-tight=\"true\">\r\n<li>\r\n<p dir=\"ltr\"><strong>Aşırı Turizm</strong>: Venedik, Barselona gibi pop&uuml;ler destinasyonlarda yerel halk, turist kalabalığından rahatsız. Bu, daha az bilinen b&ouml;lgelere y&ouml;nelimi artırıyor.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Maliyet</strong>: S&uuml;rd&uuml;r&uuml;lebilir seyahat se&ccedil;enekleri, &ouml;zellikle eko-dostu tesisler veya karbon n&ouml;tr u&ccedil;uşlar, genellikle daha pahalı.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Eğitim Eksikliği</strong>: Gezginlerin s&uuml;rd&uuml;r&uuml;lebilirlik konusunda bilin&ccedil;lenmesi i&ccedil;in daha fazla farkındalık kampanyasına ihtiya&ccedil; var.</p>\r\n</li>\r\n<li>\r\n<p dir=\"ltr\"><strong>Erişim Eşitsizliği</strong>: Teknolojik yenilikler, d&uuml;ş&uuml;k gelirli bireyler veya gelişmekte olan &uuml;lkeler i&ccedil;in her zaman erişilebilir olmayabilir.</p>\r\n</li>\r\n</ul>\r\n<h2 dir=\"ltr\">Sonu&ccedil;</h2>\r\n<p dir=\"ltr\">Gezi, hem bireylerin d&uuml;nyayı keşfetme arzusunu tatmin eden hem de k&uuml;lt&uuml;rler arası k&ouml;pr&uuml;ler kuran g&uuml;&ccedil;l&uuml; bir ara&ccedil;tır. 2025&rsquo;te, s&uuml;rd&uuml;r&uuml;lebilirlik ve teknoloji, turizm sekt&ouml;r&uuml;n&uuml;n ana itici g&uuml;&ccedil;leri haline gelmiştir. Gelecekte, gezginlerin daha bilin&ccedil;li, &ccedil;evre dostu ve kişiselleştirilmiş deneyimler arayışı, sekt&ouml;r&uuml; yeniden tanımlayacak. Ancak, bu d&ouml;n&uuml;ş&uuml;m&uuml;n adil ve kapsayıcı olması i&ccedil;in hem gezginlerin hem de end&uuml;strinin sorumluluk alması gerekiyor.</p>', 'Gezi, insanlık tarihinin en eski aktivitelerinden biridir ve günümüzde hem bireysel hem de toplumsal düzeyde büyük bir öneme sahiptir. 2025 itibarıyla, turizm sektörü teknolojik yenilikler, çevresel farkındalık ve değişen tüketici alışkanlıklarıyla yeniden şekilleniyor', 'https://www.speakeragency.com.tr/media/2inpupvy/690x460-4.jpg', 16, 1, 1, 1, 0, 0, 0, 'published', 11, 'Turizm', 'Turizm', NULL, NULL, '2025-07-10 19:20:43', '2025-07-26 16:16:29'),
(51, 'Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler', 'saglikli-yasam-icin-pratik-ipuclari-ve-onemli-bilgiler', '<figure class=\"image featured-image\"><img src=\"https://images.unsplash.com/photo-1592580715317-19adca36288e?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwyfHxTYSVDNCU5RmwlQzQlQjFrbCVDNCVCMSUyMFlhJUM1JTlGYW0lMjAlQzQlQjAlQzMlQTdpbiUyMFByYXRpayUyMCVDNCVCMHB1JUMzJUE3bGFyJUM0JUIxJTIwdmUlMjAlQzMlOTZuZW1saSUyMEJpbGdpbGVyfGVufDF8MHx8fDE3NTM1NjU4MzB8MA&ixlib=rb-4.1.0&q=80&w=1080\" alt=\"Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler - Kapak Görseli\" title=\"Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler\"><figcaption>Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler - Kapak Görseli</figcaption></figure>\n\n```html\r\n<!DOCTYPE html>\r\n<html lang=\"tr\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <meta name=\"description\" content=\"Sağlıklı bir yaşam için pratik ipuçları ve bilgiler. Beslenme, egzersiz ve zihinsel sağlık üzerine uzman görüşleri.\">\r\n    <meta name=\"keywords\" content=\"sağlık, sağlıklı yaşam, beslenme, egzersiz, zihinsel sağlık, wellness, sağlıklı beslenme, spor, fitness\">\r\n    <title>Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler</title>\r\n</head>\r\n<body>\r\n\r\n    \r\n\r\n    <p>Sağlıklı bir yaşam sürmek, sadece hastalıklardan uzak olmak anlamına gelmez.  Fiziksel, zihinsel ve duygusal iyiliğin bir araya gelmesiyle oluşan bütüncül bir durumdur.  Günümüzün yoğun temposunda sağlıklı kalmak zor olabilir, ancak küçük değişikliklerle büyük farklar yaratabilirsiniz.  Bu makalede, sağlıklı bir yaşam için pratik ipuçları ve önemli bilgileri bulabilirsiniz.</p>\r\n\r\n    <h2>Beslenme: Vücudunuzun Yakıtı</h2>\r\n\r\n    <h3>Dengeli Beslenme:</h3>\r\n    <p>Sağlıklı beslenmenin temeli, dengeli bir beslenme planıdır.  Meyve, sebze, tam tahıllar, yağsız protein kaynakları ve sağlıklı yağlar tüketmeye özen gösterin. İşlenmiş gıdalar, şekerli içecekler ve aşırı doymuş yağlardan uzak durun.</p>\r\n\r\n    <h3>Suyun Önemi:</h3>\r\n    <p>Vücudunuzun büyük bir bölümünü su oluşturur.  Yeterince su içmek, vücut fonksiyonlarının düzgün çalışması için hayati önem taşır. Günlük su tüketiminizi artırmak için yanınızda su şişesi taşıyabilir ve düzenli aralıklarla su içebilirsiniz.</p>\r\n\r\n    <h2>Egzersiz: Hareketin Gücü</h2>\r\n\r\n    <p>Düzenli egzersiz, fiziksel ve zihinsel sağlığınız için olmazsa olmazdır. Haftada en az 150 dakika orta şiddette aerobik egzersiz yapmaya çalışın.  Yürüyüş, koşu, yüzme veya bisiklet sürme gibi aktiviteler tercih edebilirsiniz.  Ayrıca, haftada en az iki gün kas güçlendirme egzersizleri yapmanız da önemlidir.\n\n<figure class=\"image content-image\"><img src=\"https://images.unsplash.com/photo-1605606437828-598340dfaeb7?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwxfHxTYSVDNCU5RmwlQzQlQjFrbCVDNCVCMSUyMFlhJUM1JTlGYW0lMjAlQzQlQjAlQzMlQTdpbiUyMFByYXRpayUyMCVDNCVCMHB1JUMzJUE3bGFyJUM0JUIxJTIwdmUlMjAlQzMlOTZuZW1saSUyMEJpbGdpbGVyfGVufDF8MHx8fDE3NTM1NjU4MzB8MA&ixlib=rb-4.1.0&q=80&w=1080\" alt=\"Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler - İçerik Görseli 1\" title=\"Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler\"><figcaption>Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler - İçerik Görseli 1</figcaption></figure></p>\r\n\r\n    <h2>Zihinsel Sağlık: Unutulmamalı Bir Önem</h2>\r\n\r\n    <p>Fiziksel sağlığınız kadar zihinsel sağlığınız da önemlidir.  Stres yönetimi teknikleri öğrenmek, yeterli uyku almak, hobiler edinmek ve sosyal ilişkilerinizi güçlendirmek zihinsel sağlığınızı korumanıza yardımcı olur.  İhtiyaç duyduğunuzda profesyonel yardım almaktan çekinmeyin.</p>\r\n\r\n    <h2>Sağlıklı Yaşam Yolculuğunuzda İlk Adım</h2>\r\n\r\n    <p>Sağlıklı bir yaşam tarzı benimsemek, bir gecede gerçekleşen bir olay değildir.  Küçük adımlar atarak başlayabilir ve zamanla alışkanlıklarınızı değiştirebilirsiniz.  Önemli olan, kendinize uygun bir plan oluşturmak ve bu plana bağlı kalmaktır.  Unutmayın, sağlıklı bir yaşam, uzun ve kaliteli bir yaşamın anahtarıdır.</p>\r\n\r\n    <p><strong>Bugünden itibaren daha sağlıklı bir yaşam için ilk adımı atın!  Beslenme alışkanlıklarınızı gözden geçirin, düzenli egzersiz yapmaya başlayın ve zihinsel sağlığınıza özen gösterin.</strong>\n\n<figure class=\"image content-image\"><img src=\"https://images.unsplash.com/photo-1592580715317-19adca36288e?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwyfHxTYSVDNCU5RmwlQzQlQjFrbCVDNCVCMSUyMFlhJUM1JTlGYW0lMjAlQzQlQjAlQzMlQTdpbiUyMFByYXRpayUyMCVDNCVCMHB1JUMzJUE3bGFyJUM0JUIxJTIwdmUlMjAlQzMlOTZuZW1saSUyMEJpbGdpbGVyfGVufDF8MHx8fDE3NTM1NjU4MzB8MA&ixlib=rb-4.1.0&q=80&w=1080\" alt=\"Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler - İçerik Görseli 2\" title=\"Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler\"><figcaption>Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler - İçerik Görseli 2</figcaption></figure></p>\r\n\r\n</body>\r\n</html>\r\n```', 'Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler - Kapak Görseli ```html Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler Sağlıklı bir yaşam sürmek, sadece hastalıklardan uzak olmak anlamına gelmez. Fiziksel, zihinsel ve duygusal iyiliğin bir araya gelmesiyle oluşan...', 'https://images.unsplash.com/photo-1592580715317-19adca36288e?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwyfHxTYSVDNCU5RmwlQzQlQjFrbCVDNCVCMSUyMFlhJUM1JTlGYW0lMjAlQzQlQjAlQzMlQTdpbiUyMFByYXRpayUyMCVDNCVCMHB1JUMzJUE3bGFyJUM0JUIxJ', 14, 1, 1, 0, 0, 0, 0, 'published', 1, 'Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler', 'Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler - Kapak Görseli ```html Sağlıklı Yaşam İçin Pratik İpuçları ve Önemli Bilgiler Sağlıklı bir yaşam sürmek, sadece hastalıklardan uzak olmak anlamına gelmez. Fiziksel, zihinsel ve duygusal iyiliğin bir araya gelmesiyle oluşan...', NULL, NULL, '2025-07-26 21:37:19', '2025-07-26 21:37:27'),
(52, 'Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi', 'unutulmaz-bir-gezi-icin-tam-kapsamli-gezi-rehberi', '<figure class=\"image featured-image\"><img src=\"https://images.unsplash.com/photo-1719163893241-b36167f81a31?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwzfHxVbnV0dWxtYXolMjBCaXIlMjBHZXppJTIwJUM0JUIwJUMzJUE3aW4lMjBUYW0lMjBLYXBzYW1sJUM0JUIxJTIwR2V6aSUyMFJlaGJlcml8ZW58MXwwfHx8MTc1MzU2NTg4MXww&ixlib=rb-4.1.0&q=80&w=1080\" alt=\"Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi - Kapak Görseli\" title=\"Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi\"><figcaption>Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi - Kapak Görseli</figcaption></figure>\n\n```html\r\n<!DOCTYPE html>\r\n<html lang=\"tr\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <meta name=\"description\" content=\"Unutulmaz bir gezi planlamak için ihtiyacınız olan her şey burada! Gezi rehberimiz ile en iyi destinasyonları keşfedin, ipuçlarını öğrenin ve unutulmaz anılar biriktirin.\">\r\n    <meta name=\"keywords\" content=\"gezi rehberi, seyahat, tatil, destinasyon, ipuçları, rehber, planlama, gezilecek yerler\">\r\n    <title>Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi</title>\r\n</head>\r\n<body>\r\n\r\n    \r\n\r\n    <p>Hayallerinizdeki tatili planlamak zorlu bir iş olabilir.  Nereden başlayacağınızı bilmek, doğru bilgileri bulmak ve zamanınızı verimli kullanmak önemlidir. Bu kapsamlı gezi rehberi,  her adımda size yardımcı olmak ve unutulmaz bir deneyim yaşamanızı sağlamak için hazırlandı.</p>\r\n\r\n    <h2>Gezi Planlamanın Temel Adımları</h2>\r\n\r\n    <h3>Hedefinizi Belirleyin</h3>\r\n    <p>Öncelikle nereye gitmek istediğinizi belirleyin.  Deniz, kum ve güneş mi arıyorsunuz, yoksa tarihi yerleri keşfetmeyi mi tercih ediyorsunuz?  Bütçeniz, seyahat tarzınız ve ilgi alanlarınız hedefinizi belirlemede önemli rol oynar.  Macera dolu bir trekking mi, yoksa sakin bir plaj tatili mi istiyorsunuz?  Bu sorulara cevap vererek doğru destinasyonu seçebilirsiniz.</p>\r\n\r\n    <h3>Bütçenizi Planlayın</h3>\r\n    <p>Seyahatinizin maliyetini önceden tahmin etmek çok önemlidir. Uçak bileti, konaklama, yemek, aktiviteler ve ulaşım gibi masrafları göz önünde bulundurun.  Bütçenize uygun bir seyahat planı yaparak hayal kırıklıklarını önleyebilirsiniz.  Fırsatları değerlendirmek ve erken rezervasyon yapmak maliyetleri düşürmenize yardımcı olabilir.</p>\r\n\r\n    <h3>Konaklama Seçeneğinizi Belirleyin</h3>\r\n    <p>Oteller, pansiyonlar, Airbnb gibi farklı konaklama seçenekleri mevcuttur.  Bütçenize, seyahat tarzınıza ve tercihlerinize uygun bir seçenek seçmek önemlidir.  Konum, olanaklar ve yorumlar konaklama seçiminizi etkileyen faktörler arasındadır.\n\n<figure class=\"image content-image\"><img src=\"https://images.unsplash.com/photo-1692895591954-451050db22fd?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwxfHxVbnV0dWxtYXolMjBCaXIlMjBHZXppJTIwJUM0JUIwJUMzJUE3aW4lMjBUYW0lMjBLYXBzYW1sJUM0JUIxJTIwR2V6aSUyMFJlaGJlcml8ZW58MXwwfHx8MTc1MzU2NTg4MXww&ixlib=rb-4.1.0&q=80&w=1080\" alt=\"Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi - İçerik Görseli 1\" title=\"Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi\"><figcaption>Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi - İçerik Görseli 1</figcaption></figure></p>\r\n\r\n    <h3>Aktivitelerinizi Planlayın</h3>\r\n    <p>Ziyaret edeceğiniz yerlerde yapılacak aktiviteleri önceden araştırın.  Müzeler, tarihi yerler, doğal güzellikler, festivaller ve diğer etkinlikler hakkında bilgi edinin.  Seyahatinizin her gününü planlamak yerine, esneklik bırakmayı unutmayın.  Spontane aktivitelere de yer açın!</p>\r\n\r\n    <h2>Seyahat İpuçları</h2>\r\n    <p>Seyahatinizden önce gerekli belgeleri kontrol edin, seyahat sigortası yaptırın ve yerel kültüre saygılı olun.  Yerel halkla iletişim kurmaktan çekinmeyin, yeni deneyimlere açık olun ve anılarınızı fotoğraflarla ölümsüzleştirin.</p>\r\n\r\n\r\n    <p>Unutulmaz bir gezi için doğru planlama ve hazırlık çok önemlidir.  Bu rehber size yol gösterici olsun ve hayallerinizdeki tatili gerçekleştirmenize yardımcı olsun!\n\n<figure class=\"image content-image\"><img src=\"https://images.unsplash.com/photo-1570714436355-2556087f0912?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwyfHxVbnV0dWxtYXolMjBCaXIlMjBHZXppJTIwJUM0JUIwJUMzJUE3aW4lMjBUYW0lMjBLYXBzYW1sJUM0JUIxJTIwR2V6aSUyMFJlaGJlcml8ZW58MXwwfHx8MTc1MzU2NTg4MXww&ixlib=rb-4.1.0&q=80&w=1080\" alt=\"Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi - İçerik Görseli 2\" title=\"Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi\"><figcaption>Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi - İçerik Görseli 2</figcaption></figure></p>\r\n\r\n    <p><strong>Hemen planlamaya başlayın ve unutulmaz bir maceraya atılın!</strong></p>\r\n\r\n</body>\r\n</html>\r\n```', 'Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi - Kapak Görseli ```html Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi Hayallerinizdeki tatili planlamak zorlu bir iş olabilir. Nereden başlayacağınızı bilmek, doğru bilgileri bulmak ve zamanınızı verimli kullanmak önemlidir. Bu kapsamlı gezi...', 'https://images.unsplash.com/photo-1719163893241-b36167f81a31?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwzfHxVbnV0dWxtYXolMjBCaXIlMjBHZXppJTIwJUM0JUIwJUMzJUE3aW4lMjBUYW0lMjBLYXBzYW1sJUM0JUIxJTIwR2V6aSUyMFJlaGJlcml8ZW58MXwwfHx8M', 16, 1, 1, 0, 0, 0, 0, 'published', 1, 'Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi', 'Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi - Kapak Görseli ```html Unutulmaz Bir Gezi İçin Tam Kapsamlı Gezi Rehberi Hayallerinizdeki tatili planlamak zorlu bir iş olabilir. Nereden başlayacağınızı bilmek, doğru bilgileri bulmak ve zamanınızı verimli kullanmak önemlidir. Bu kapsamlı gezi...', NULL, NULL, '2025-07-26 21:38:17', '2025-07-26 21:38:27'),
(53, 'Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler', 'kayip-sehirlerin-fisiltisi-7-gizli-cennet-unutulmus-tarihler', '<figure class=\"image featured-image\"><img src=\"https://images.unsplash.com/photo-1690061522034-5fe90656d29b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwxfHxLYXklQzQlQjFwJTIwJUM1JTlFZWhpcmxlcmluJTIwRiVDNCVCMXMlQzQlQjFsdCVDNCVCMXMlQzQlQjElM0ElMjA3JTIwR2l6bGklMjBDZW5uZXQlMkMlMjBVbnV0dWxtdSVDNSU5RiUyMFRhcmlobGVyfGVufDF8MHx8fDE3NTM1NjY1NDR8MA&ixlib=rb-4.1.0&q=80&w=1080\" alt=\"Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler - Kapak Görseli\" title=\"Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler\"><figcaption>Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler - Kapak Görseli</figcaption></figure>\n\n```html\r\n\r\n\r\n<p>Dünyanın kalbine doğru bir yolculuğa hazır mısınız?  Gezgin ruhların, keşfedilmemiş köşelerin cazibesine kapıldıkları, tarihin unutulmuş sayfalarının fısıltılarını duydukları bir maceraya?</p>\r\n\r\n<h2>Geçmişin Gizli İzlerinde: 7 Şaşırtıcı Destinasyon</h2>\r\n\r\n<p>Sıkıcı turistik noktalardan uzaklaşın!  Bu makalede, kalabalığın uzak kaldığı, doğanın ve tarihin iç içe geçtiği yedi büyüleyici destinasyonu keşfedeceğiz.  Hazırsanız, bilinmeyenin büyülü dünyasına dalalım.</p>\r\n\r\n<h3>1. Kolombiya\'nın Gizli Vadisi: Tayrona\'nın Büyülü Kalıntıları</h3>\r\n\r\n<p>Kayıp bir uygarlığın izlerini taşıyan Tayrona Milli Parkı,  yemyeşil ormanların arasında saklı kalmış antik taş kalıntılarla dolu.  Muhteşem plajları ve  turkuaz suları ise  tarihin ve doğanın kusursuz bir buluşması.</p>\r\n\r\n<h3>2. Peru\'nun Kayıp Şehri:  Chachapoyas\'ın Bulut Ormanlarındaki Sırları</h3>\r\n\r\n<p>And Dağları\'nın eteklerinde, bulutların arasında gizlenmiş,  Kutsal Kuntur Vadisindeki  Chachapoyas şehri, yüksek irtifadaki ihtişamıyla sizi büyüleyecek.  İnkalar öncesi uygarlığın izlerini taşıyan  antik mezarlıklar ve kalıntılar sizi zamanda bir yolculuğa çıkaracak.</p>\r\n\r\n<h3>3.  İtalya\'nın Gizemli Şatosu:  Eerie Rocca Calascio\'nun Yansımaları</h3>\r\n\r\n<p>Apennin Dağları\'nın tepesinde, zamanın durduğu bir yer:  Rocca Calascio Şatosu.  Yüzlerce yıllık geçmişi,  bozulmamış güzelliği ve etrafını saran manzara,  soluğunuzu kesecek kadar etkileyici.\n\n<figure class=\"image content-image\"><img src=\"https://images.unsplash.com/photo-1690061522034-5fe90656d29b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwxfHxLYXklQzQlQjFwJTIwJUM1JTlFZWhpcmxlcmluJTIwRiVDNCVCMXMlQzQlQjFsdCVDNCVCMXMlQzQlQjElM0ElMjA3JTIwR2l6bGklMjBDZW5uZXQlMkMlMjBVbnV0dWxtdSVDNSU5RiUyMFRhcmlobGVyfGVufDF8MHx8fDE3NTM1NjY1NDR8MA&ixlib=rb-4.1.0&q=80&w=1080\" alt=\"Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler - İçerik Görseli 1\" title=\"Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler\"><figcaption>Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler - İçerik Görseli 1</figcaption></figure></p>\r\n\r\n<h3>4.  Çin\'in Kayıp Bahçeleri:  Chengde\'nin Gizli Köşkleri</h3>\r\n\r\n<p>İmparatorluk bahçelerinin gizli dünyasını keşfetmeye hazır olun!  Pekin\'in kuzeyindeki Chengde, sayısız köşk, pagoda ve tapınaklarıyla büyüleyici bir görsel şölen sunuyor.  Sessizliğin hüküm sürdüğü bu huzurlu vaha, şehrin karmaşasından kaçmak isteyenler için ideal bir sığınak.</p>\r\n\r\n<h3>5.  Yunanistan\'ın Unutulmuş Adası:  Koufonisia\'nın Masmavi Suları</h3>\r\n\r\n<p>Yunan Adaları\'nın gizli incisi Koufonisia,  kristal berraklığındaki suları ve bakir plajlarıyla masalsı bir tatil deneyimi vaat ediyor.  Kalabalıkların ulaşmadığı bu cennet köşesi,  huzur ve doğanın kucaklaştığı bir yer.</p>\r\n\r\n<h3>6.  Meksika\'nın Gizli Mağaraları:  Xilitla\'nın  Sürreal  Sırları</h3>\r\n\r\n<p>Meksika\'nın büyüleyici  Xilitla,  Edward James\'in  sürrealist mimarisinin izlerini taşıyan  gizli bahçeleri ve mağaralarıyla  fantastik bir dünyaya açılan kapı.  Beklenmedik şekiller ve  doğanın büyüsü,  bu yeri benzersiz kılıyor.</p>\r\n\r\n<h3>7.  Sri Lanka\'nın Kayıp Şelaleleri:  Ella\'nın  Nefes Kesici Güzelliği</h3>\r\n\r\n<p>Sri Lanka\'nın yemyeşil tepelerinde,  Ella kasabası  nefes kesici şelaleleri ve sonsuz çay tarlalarıyla büyüleyici bir manzara sunuyor.  Doğanın ihtişamı ve kültürel zenginliklerin harmanlanması,  unutulmaz bir deneyim yaşatacak.\n\n<figure class=\"image content-image\"><img src=\"https://images.unsplash.com/photo-1717539780863-75b1635259cb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwyfHxLYXklQzQlQjFwJTIwJUM1JTlFZWhpcmxlcmluJTIwRiVDNCVCMXMlQzQlQjFsdCVDNCVCMXMlQzQlQjElM0ElMjA3JTIwR2l6bGklMjBDZW5uZXQlMkMlMjBVbnV0dWxtdSVDNSU5RiUyMFRhcmlobGVyfGVufDF8MHx8fDE3NTM1NjY1NDR8MA&ixlib=rb-4.1.0&q=80&w=1080\" alt=\"Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler - İçerik Görseli 2\" title=\"Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler\"><figcaption>Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler - İçerik Görseli 2</figcaption></figure></p>\r\n\r\n<p>Bu eşsiz destinasyonlardan sadece birini keşfetmek bile size unutulmaz anılar bırakacak.  Macera dolu bir yolculuğa çıkmaya hazır mısınız?  Hemen planlamaya başlayın ve dünyanın gizli köşelerini keşfedin!</p>\r\n```', 'Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler - Kapak Görseli ```html Dünyanın kalbine doğru bir yolculuğa hazır mısınız? Gezgin ruhların, keşfedilmemiş köşelerin cazibesine kapıldıkları, tarihin unutulmuş sayfalarının fısıltılarını duydukları bir maceraya? Geçmişin...', 'https://images.unsplash.com/photo-1690061522034-5fe90656d29b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3ODM1Nzl8MHwxfHNlYXJjaHwxfHxLYXklQzQlQjFwJTIwJUM1JTlFZWhpcmxlcmluJTIwRiVDNCVCMXMlQzQlQjFsdCVDNCVCMXMlQzQlQjElM0ElMjA3JTIwR2l6bGklMjBDZW5uZXQlMkMlM', 16, 1, 1, 0, 0, 0, 0, 'published', 1, 'Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler', 'Kayıp Şehirlerin Fısıltısı: 7 Gizli Cennet, Unutulmuş Tarihler - Kapak Görseli ```html Dünyanın kalbine doğru bir yolculuğa hazır mısınız? Gezgin ruhların, keşfedilmemiş köşelerin cazibesine kapıldıkları, tarihin unutulmuş sayfalarının fısıltılarını duydukları bir maceraya? Geçmişin...', NULL, NULL, '2025-07-26 21:49:33', '2025-07-26 21:49:49');

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

--
-- Tablo döküm verisi `article_tags`
--

INSERT INTO `article_tags` (`article_id`, `tag_id`) VALUES
(19, 2),
(20, 3),
(21, 4),
(22, 6);

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

--
-- Tablo döküm verisi `banned_users`
--

INSERT INTO `banned_users` (`id`, `user_id`, `ip_address`, `reason`, `ban_date`, `expiry_date`, `banned_by`, `is_active`) VALUES
(5, 29, '::1', '..', '2025-07-26 20:31:25', NULL, 1, 0),
(6, 29, '::1', '..', '2025-07-26 20:36:42', NULL, 1, 0),
(7, 29, '::1', '..', '2025-07-26 20:38:41', NULL, 1, 0),
(8, 29, '::1', 'dd', '2025-07-26 21:08:45', NULL, 1, 0),
(9, 29, '::1', '22', '2025-07-26 21:46:59', '2025-07-27 20:46:59', 1, 0),
(10, 29, '::1', '22', '2025-07-26 21:51:12', '2025-07-27 20:51:12', 1, 0),
(11, 29, '::1', '22', '2025-07-26 21:55:32', '2025-07-27 20:55:32', 1, 0),
(12, 29, '::1', '11', '2025-07-27 00:59:21', NULL, 1, 0);

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

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `meta_title`, `meta_description`, `parent_id`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(13, 'Teknoloji', 'teknoloji', '', NULL, NULL, NULL, 0, 1, '2025-07-10 19:10:58', '2025-07-10 19:10:58'),
(14, 'Sağlık', 'saglik', '', NULL, NULL, NULL, 0, 1, '2025-07-10 19:13:54', '2025-07-10 19:13:54'),
(15, 'Telefon', 'telefon', '', NULL, NULL, NULL, 0, 1, '2025-07-10 19:17:08', '2025-07-10 19:17:08'),
(16, 'Gezi Rehberi', 'gezi-rehberi', '', NULL, NULL, NULL, 0, 1, '2025-07-10 19:19:29', '2025-07-10 19:19:29'),
(17, 'Yaşam', 'yasam', '', NULL, NULL, NULL, 0, 1, '2025-07-27 10:06:30', '2025-07-27 10:06:30');

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
(1, 1, '0x4AAAAAABmofqZ58JUW3xCH', '0x4AAAAAABmofvkyutqezmz5RuU-YmirtZ8', 1, 1, 1, 1, 'hard', 'auto', 'tr', '2025-07-01 10:36:21', '2025-07-26 21:46:45');

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

--
-- Tablo döküm verisi `headline_articles`
--

INSERT INTO `headline_articles` (`id`, `article_id`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(27, 22, 1, 1, '2025-07-10 21:07:12', '2025-07-10 21:07:12'),
(28, 21, 2, 1, '2025-07-10 21:07:12', '2025-07-10 21:07:12'),
(29, 20, 3, 1, '2025-07-10 21:07:12', '2025-07-10 21:07:12');

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

--
-- Tablo döküm verisi `online_users`
--

INSERT INTO `online_users` (`id`, `user_id`, `session_id`, `last_activity`, `ip_address`, `user_agent`, `created_at`) VALUES
(237, 1, 't8pgk0mfn8k9hi8ff895hu3fhk', '2025-07-27 13:07:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-27 13:07:26'),
(238, 1, 'i355uin3kkeckjgmi3t9f4b28d', '2025-07-27 13:17:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-27 13:15:57');

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

--
-- Tablo döküm verisi `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `plan_id`, `amount`, `payment_date`, `status`, `payment_method`, `transaction_id`, `created_at`) VALUES
(23, 29, 1, 0.00, '2025-07-26 20:30:19', 'completed', 'free', NULL, '2025-07-26 20:30:19'),
(24, 30, 1, 0.00, '2025-07-27 00:46:33', 'completed', 'free', NULL, '2025-07-27 00:46:33');

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
(7, 1, '170a895c16aa779317763587ebfca804845225a90cc93ba34710bd4ce25e69aa', '2025-08-26 08:22:20', '2025-07-27 09:22:20');

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
(1, '127.0.0.1', 'co', '2025-07-26 21:48:17'),
(6, '::1', 'admin', '2025-07-27 12:22:20'),
(7, '127.0.0.1', 'bv', '2025-07-27 00:57:43');

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
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_image`, `bio`, `is_approved`, `is_premium`, `premium_expires_at`, `two_factor_secret`, `is_admin`, `theme_preference`, `language_preference`, `created_at`, `updated_at`, `location`, `website`, `twitter`, `facebook`, `instagram`, `linkedin`, `tiktok`, `youtube`, `github`, `last_ip`, `last_login`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$7kIn.GMLrNlKJbtc8C5eweVO93MIw9KspKWxcpeRtnnH2ccJ.li/u', 'assets/images/profiles/6865860ac95f1_1751483914.jpg', '12', 1, 0, NULL, NULL, 1, 'light', 'tr', '2025-07-27 10:17:37', '2025-07-27 10:17:37', '12', 'http://localhost/', '1', '1', '1', '1', '1', '1', '1', '::1', '2025-07-27 12:22:20'),
(29, 'co', 'co@co.com', '$2y$10$K7NkylhBb0ue0OxBSe6OnOs/8PMZL7f/iMxj0CeFStiTGRFVBEUDa', NULL, NULL, 1, 0, NULL, NULL, 0, 'light', 'tr', '2025-07-27 10:17:37', '2025-07-27 10:17:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '127.0.0.1', '2025-07-26 21:48:17'),
(30, 'bv', 'bv@bv.com', '$2y$10$s5.V68XyR46OuGAvVXRGpezG5fp4ksewE82yfMSVI/XeuCuAc9DQa', NULL, NULL, 1, 0, NULL, NULL, 0, 'light', 'tr', '2025-07-27 10:17:37', '2025-07-27 10:17:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '127.0.0.1', '2025-07-27 00:57:43');

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
-- Tablo döküm verisi `user_subscriptions`
--

INSERT INTO `user_subscriptions` (`id`, `user_id`, `plan_id`, `payment_id`, `start_date`, `end_date`, `cancelled_at`, `status`, `created_at`, `updated_at`) VALUES
(23, 29, 1, 23, '2025-07-26 20:30:19', '2125-07-26 20:30:19', NULL, 'active', '2025-07-26 20:30:19', '2025-07-26 20:30:19'),
(24, 30, 1, 24, '2025-07-27 00:46:33', '2125-07-27 00:46:33', NULL, 'active', '2025-07-27 00:46:33', '2025-07-27 00:46:33');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- Tablo için AUTO_INCREMENT değeri `article_statistics`
--
ALTER TABLE `article_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `banned_users`
--
ALTER TABLE `banned_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `online_users`
--
ALTER TABLE `online_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=239;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `safe_ips`
--
ALTER TABLE `safe_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Tablo için AUTO_INCREMENT değeri `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
