-- users tablosuna last_ip ve last_login sütunlarını ekle
ALTER TABLE users ADD COLUMN last_ip VARCHAR(45) DEFAULT NULL;
ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL;
