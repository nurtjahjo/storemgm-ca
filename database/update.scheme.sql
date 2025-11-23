ALTER TABLE `storemgm_products`
ADD COLUMN `source_file_path` VARCHAR(255) DEFAULT NULL COMMENT 'Path ke file master .epub (untuk ebook) atau .zip (untuk audiobook)' AFTER `profile_audio_path`;