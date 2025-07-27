-- featured_image ve cover_image_url sütunlarını TEXT olarak değiştir
ALTER TABLE `articles` MODIFY COLUMN `featured_image` TEXT DEFAULT NULL;
ALTER TABLE `ai_articles` MODIFY COLUMN `cover_image_url` TEXT DEFAULT NULL;
ALTER TABLE `ai_articles` MODIFY COLUMN `image1_url` TEXT DEFAULT NULL;
ALTER TABLE `ai_articles` MODIFY COLUMN `image2_url` TEXT DEFAULT NULL;
