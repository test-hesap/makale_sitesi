-- AI ile üretilen makaleler için tablo
CREATE TABLE `ai_articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `ai_type` enum('gemini', 'chatgpt', 'grok') NOT NULL,
  `prompt` text DEFAULT NULL,
  `word_count` int(11) DEFAULT 0,
  `processing_time` float DEFAULT 0,
  `cover_image_url` varchar(255) DEFAULT NULL,
  `image1_url` varchar(255) DEFAULT NULL,
  `image2_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`),
  CONSTRAINT `ai_articles_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
