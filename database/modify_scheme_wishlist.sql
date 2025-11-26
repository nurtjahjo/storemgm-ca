-- Tabel Wishlist
-- Menyimpan daftar keinginan per user.
-- Constraint UNIQUE memastikan satu user tidak bisa menambah produk yang sama 2x.

CREATE TABLE IF NOT EXISTS `storemgm_wishlists` (
  `id` char(36) NOT NULL COMMENT 'UUID',
  `user_id` char(36) NOT NULL COMMENT 'FK ke usermgm.users',
  `product_id` char(36) NOT NULL COMMENT 'FK ke storemgm_products',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product_unique` (`user_id`, `product_id`),
  KEY `user_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
