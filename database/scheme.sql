-- phpMyAdmin SQL Dump (UPDATED FOR RENT & LIBRARY)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Tabel Kategori
CREATE TABLE IF NOT EXISTS `storemgm_categories` (
  `id` char(36) NOT NULL COMMENT 'UUID',
  `language` enum('en','id') NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(3) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `lang_slug_unique` (`language`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel Produk (Update: Ada opsi sewa)
CREATE TABLE IF NOT EXISTS `storemgm_products` (
  `id` char(36) NOT NULL COMMENT 'UUID',
  `category_id` char(36) NOT NULL,
  `language` enum('en','id') NOT NULL,
  `type` enum('ebook','audiobook') NOT NULL,
  `title` varchar(255) NOT NULL,
  `synopsis` text DEFAULT NULL,
  `author_id` char(36) NOT NULL,
  `narrator_id` char(36) DEFAULT NULL,
  `cover_image_path` varchar(255) DEFAULT NULL,
  `profile_audio_path` varchar(255) DEFAULT NULL,
  `source_file_path` varchar(255) DEFAULT NULL COMMENT 'Master file EPUB/ZIP',
  
  -- Harga Beli
  `price_usd` decimal(10,2) NOT NULL,
  
  -- Opsi Sewa
  `can_rent` tinyint(1) NOT NULL DEFAULT 0,
  `rental_price_usd` decimal(10,2) DEFAULT NULL,
  `rental_duration_days` int unsigned DEFAULT NULL,

  `tags` varchar(512) DEFAULT NULL,
  `status` enum('draft','in_review','published','rejected','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `published_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel Konten (Bab)
CREATE TABLE IF NOT EXISTS `storemgm_product_contents` (
  `id` char(36) NOT NULL,
  `product_id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `chapter_order` int(5) NOT NULL DEFAULT 0,
  `content_text_path` varchar(255) DEFAULT NULL,
  `content_audio_path` varchar(255) DEFAULT NULL,
  `word_count` int(10) UNSIGNED DEFAULT 0,
  `duration_seconds` int(10) UNSIGNED DEFAULT 0,
  `status` enum('draft','pending_review','approved','revision_needed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel Customer Profiles
CREATE TABLE IF NOT EXISTS `storemgm_customer_profiles` (
  `user_id` char(36) NOT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel Keranjang (Cart)
CREATE TABLE IF NOT EXISTS `storemgm_carts` (
  `id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `guest_cart_id` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `guest_cart_id` (`guest_cart_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel Item Keranjang (Update: Ada purchase_type)
CREATE TABLE IF NOT EXISTS `storemgm_cart_items` (
  `id` char(36) NOT NULL,
  `cart_id` char(36) NOT NULL,
  `product_id` char(36) NOT NULL,
  `quantity` int(5) UNSIGNED NOT NULL DEFAULT 1,
  `purchase_type` enum('buy','rent') NOT NULL DEFAULT 'buy',
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_prod_type_unique` (`cart_id`, `product_id`, `purchase_type`),
  CONSTRAINT `fk_item_cart` FOREIGN KEY (`cart_id`) REFERENCES `storemgm_carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `storemgm_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel Pesanan (Orders)
CREATE TABLE IF NOT EXISTS `storemgm_orders` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `total_price_usd` decimal(10,2) NOT NULL,
  `total_price_idr` decimal(15,2) DEFAULT NULL,
  `exchange_rate` decimal(15,6) DEFAULT NULL,
  `status` enum('pending','awaiting_payment','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `payment_gateway_transaction_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel Item Pesanan (Update: Ada purchase_type)
CREATE TABLE IF NOT EXISTS `storemgm_order_items` (
  `id` char(36) NOT NULL,
  `order_id` char(36) NOT NULL,
  `product_id` char(36) NOT NULL,
  `quantity` int(5) UNSIGNED NOT NULL,
  `price_usd_at_purchase` decimal(10,2) NOT NULL,
  `purchase_type` enum('buy','rent') NOT NULL DEFAULT 'buy',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `storemgm_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`product_id`) REFERENCES `storemgm_products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel User Library (BARU: Tempat Cek Akses/Otorisasi)
CREATE TABLE IF NOT EXISTS `storemgm_user_library` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `product_id` char(36) NOT NULL,
  `source_order_id` char(36) NOT NULL,
  `access_type` enum('owned', 'rented') NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'NULL = Seumur hidup, Ada Tanggal = Sewa',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `access_check` (`user_id`, `product_id`, `expires_at`),
  CONSTRAINT `fk_lib_prod` FOREIGN KEY (`product_id`) REFERENCES `storemgm_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lib_ord` FOREIGN KEY (`source_order_id`) REFERENCES `storemgm_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
