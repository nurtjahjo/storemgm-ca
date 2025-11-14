-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 15, 2025 at 12:00 PM
-- Server version: 10.6.22-MariaDB-0ubuntu0.22.04.1
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `storemgm_db`
--

-- --------------------------------------------------------

--
-- Struktur tabel untuk `storemgm_categories`
-- Setiap kategori spesifik untuk satu bahasa.
--
CREATE TABLE IF NOT EXISTS `storemgm_categories` (
  `id` char(36) NOT NULL COMMENT 'UUID',
  `language` enum('en','id') NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL COMMENT 'Untuk URL-friendly identifier, unik per bahasa',
  `description` text DEFAULT NULL,
  `display_order` int(3) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `lang_slug_unique` (`language`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur tabel untuk `storemgm_products`
-- Setiap baris adalah produk unik dalam satu bahasa spesifik.
--
CREATE TABLE IF NOT EXISTS `storemgm_products` (
  `id` char(36) NOT NULL COMMENT 'UUID',
  `category_id` char(36) NOT NULL COMMENT 'FK ke storemgm_categories',
  `language` enum('en','id') NOT NULL,
  `type` enum('ebook','audiobook') NOT NULL,
  `title` varchar(255) NOT NULL,
  `synopsis` text DEFAULT NULL,
  `author_id` char(36) NOT NULL COMMENT 'FK ke users.id di usermgm',
  `narrator_id` char(36) DEFAULT NULL COMMENT 'FK ke users.id di usermgm, nullable untuk ebook',
  `cover_image_path` varchar(255) DEFAULT NULL COMMENT 'Path relatif ke file gambar sampul',
  `profile_audio_path` varchar(255) DEFAULT NULL COMMENT 'Path ke file audio profil/sampel buku',
  `price_usd` decimal(10,2) NOT NULL COMMENT 'Harga dalam USD sebagai sumber kebenaran',
  `tags` varchar(512) DEFAULT NULL,
  `status` enum('draft','in_review','published','rejected','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `published_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `narrator_id` (`narrator_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur tabel untuk `storemgm_product_contents`
-- Konten per bab dari sebuah produk.
--
CREATE TABLE IF NOT EXISTS `storemgm_product_contents` (
  `id` char(36) NOT NULL COMMENT 'UUID',
  `product_id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Judul Bab',
  `chapter_order` int(5) NOT NULL DEFAULT 0,
  `content_text_path` varchar(255) DEFAULT NULL COMMENT 'Path ke file naskah (md/txt/html)',
  `content_audio_path` varchar(255) DEFAULT NULL COMMENT 'Path ke file audio bab',
  `word_count` int(10) UNSIGNED DEFAULT 0,
  `duration_seconds` int(10) UNSIGNED DEFAULT 0,
  `status` enum('draft','pending_review','approved','revision_needed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur tabel untuk `storemgm_customer_profiles`
-- Menyimpan data spesifik pelanggan yang hanya relevan untuk domain toko.
--
CREATE TABLE IF NOT EXISTS `storemgm_customer_profiles` (
  `user_id` char(36) NOT NULL COMMENT 'PK dan FK ke usermgm.users.id',
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur tabel untuk `storemgm_carts`
-- Wadah untuk keranjang belanja, baik milik guest maupun user terdaftar.
--
CREATE TABLE IF NOT EXISTS `storemgm_carts` (
  `id` char(36) NOT NULL COMMENT 'UUID untuk keranjang',
  `user_id` char(36) DEFAULT NULL COMMENT 'FK ke usermgm.users.id, NULL untuk guest',
  `guest_cart_id` char(36) DEFAULT NULL COMMENT 'Identifier untuk guest cart',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `guest_cart_id` (`guest_cart_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur tabel untuk `storemgm_cart_items`
-- Item-item yang ada di dalam sebuah keranjang belanja.
--
CREATE TABLE IF NOT EXISTS `storemgm_cart_items` (
  `id` char(36) NOT NULL,
  `cart_id` char(36) NOT NULL,
  `product_id` char(36) NOT NULL,
  `quantity` int(5) UNSIGNED NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_product_unique` (`cart_id`, `product_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur tabel untuk `storemgm_orders`
-- Mencatat semua transaksi/pesanan yang telah dibuat.
--
CREATE TABLE IF NOT EXISTS `storemgm_orders` (
  `id` char(36) NOT NULL COMMENT 'UUID',
  `user_id` char(36) NOT NULL COMMENT 'FK ke usermgm.users.id',
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

--
-- Struktur tabel untuk `storemgm_order_items`
-- Detail item untuk setiap pesanan. Menyimpan harga saat pembelian untuk akurasi historis.
--
CREATE TABLE IF NOT EXISTS `storemgm_order_items` (
  `id` char(36) NOT NULL,
  `order_id` char(36) NOT NULL,
  `product_id` char(36) NOT NULL,
  `quantity` int(5) UNSIGNED NOT NULL,
  `price_usd_at_purchase` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
--
-- Constraints for dumped tables
--
-- --------------------------------------------------------


ALTER TABLE `storemgm_products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `storemgm_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `storemgm_product_contents`
  ADD CONSTRAINT `fk_content_product` FOREIGN KEY (`product_id`) REFERENCES `storemgm_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `storemgm_cart_items`
  ADD CONSTRAINT `fk_item_cart` FOREIGN KEY (`cart_id`) REFERENCES `storemgm_carts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `storemgm_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `storemgm_order_items`
  ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `storemgm_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`product_id`) REFERENCES `storemgm_products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

COMMIT;