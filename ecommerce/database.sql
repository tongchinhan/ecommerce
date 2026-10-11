-- NORD Home / ecommerce
-- Import this file in phpMyAdmin (or mysql CLI) after starting MySQL in XAMPP.
-- Charset: utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS `ecommerce`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ecommerce`;

DROP TABLE IF EXISTS `coupon_usage`;
DROP TABLE IF EXISTS `wishlist_items`;
DROP TABLE IF EXISTS `wishlists`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `carts`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `posts`;
DROP TABLE IF EXISTS `post_categories`;
DROP TABLE IF EXISTS `addresses`;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `banners`;
DROP TABLE IF EXISTS `coupons`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  `status` ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active',
  `email_verified_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  KEY `idx_categories_active` (`is_active`),
  CONSTRAINT `fk_categories_parent`
    FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `brands` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_brands_slug` (`slug`),
  KEY `idx_brands_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `brand_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `sku` VARCHAR(64) NOT NULL,
  `short_description` VARCHAR(500) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `compare_price` DECIMAL(12,2) DEFAULT NULL,
  `cost` DECIMAL(12,2) DEFAULT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `weight` DECIMAL(8,2) DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  UNIQUE KEY `uq_products_sku` (`sku`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_brand` (`brand_id`),
  KEY `idx_products_featured` (`is_featured`),
  KEY `idx_products_active_price` (`is_active`, `price`),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_products_brand`
    FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `alt_text` VARCHAR(200) DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product` (`product_id`),
  CONSTRAINT `fk_product_images_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `carts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `session_id` VARCHAR(128) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_carts_user` (`user_id`),
  KEY `idx_carts_session` (`session_id`),
  CONSTRAINT `fk_carts_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cart_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cart_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_items_cart_product` (`cart_id`, `product_id`),
  KEY `idx_cart_items_product` (`product_id`),
  CONSTRAINT `fk_cart_items_cart`
    FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cart_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `type` ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
  `value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `min_order` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `max_discount` DECIMAL(12,2) DEFAULT NULL,
  `usage_limit` INT UNSIGNED DEFAULT NULL,
  `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `starts_at` DATETIME DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coupons_code` (`code`),
  KEY `idx_coupons_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `addresses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `address_line` VARCHAR(255) NOT NULL,
  `ward` VARCHAR(120) DEFAULT NULL,
  `district` VARCHAR(120) DEFAULT NULL,
  `city` VARCHAR(120) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_addresses_user` (`user_id`),
  CONSTRAINT `fk_addresses_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `coupon_id` INT UNSIGNED DEFAULT NULL,
  `order_number` VARCHAR(32) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('unpaid', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'unpaid',
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `shipping_name` VARCHAR(120) NOT NULL,
  `shipping_phone` VARCHAR(30) NOT NULL,
  `shipping_address` VARCHAR(255) NOT NULL,
  `shipping_city` VARCHAR(120) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_number` (`order_number`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_coupon` (`coupon_id`),
  KEY `idx_orders_status` (`status`),
  CONSTRAINT `fk_orders_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_coupon`
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `sku` VARCHAR(64) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `method` VARCHAR(40) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('unpaid', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'unpaid',
  `transaction_id` VARCHAR(120) DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_order` (`order_id`),
  CONSTRAINT `fk_payments_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED DEFAULT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `comment` TEXT DEFAULT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_product` (`product_id`),
  KEY `idx_reviews_user` (`user_id`),
  KEY `idx_reviews_order` (`order_id`),
  CONSTRAINT `fk_reviews_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wishlists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wishlists_user` (`user_id`),
  CONSTRAINT `fk_wishlists_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wishlist_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wishlist_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wishlist_items` (`wishlist_id`, `product_id`),
  KEY `idx_wishlist_items_product` (`product_id`),
  CONSTRAINT `fk_wishlist_items_wishlist`
    FOREIGN KEY (`wishlist_id`) REFERENCES `wishlists` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wishlist_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `post_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_post_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `post_category_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(220) NOT NULL,
  `slug` VARCHAR(240) NOT NULL,
  `excerpt` VARCHAR(500) DEFAULT NULL,
  `content` TEXT DEFAULT NULL,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  `published_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_posts_slug` (`slug`),
  KEY `idx_posts_author` (`user_id`),
  KEY `idx_posts_category` (`post_category_id`),
  KEY `idx_posts_published` (`is_published`, `published_at`),
  CONSTRAINT `fk_posts_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_posts_category`
    FOREIGN KEY (`post_category_id`) REFERENCES `post_categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `coupon_usage` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED DEFAULT NULL,
  `used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_coupon_usage_coupon` (`coupon_id`),
  KEY `idx_coupon_usage_user` (`user_id`),
  KEY `idx_coupon_usage_order` (`order_id`),
  CONSTRAINT `fk_coupon_usage_coupon`
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_coupon_usage_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_coupon_usage_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `banners` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(160) NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `position` VARCHAR(40) NOT NULL DEFAULT 'home_hero',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_banners_position` (`position`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contacts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `subject` VARCHAR(180) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('new', 'read', 'replied') NOT NULL DEFAULT 'new',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(80) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo passwords are hashed with PHP password_hash() (bcrypt).
-- The literal hash strings below are valid bcrypt hashes generated by PHP
-- for the documented demo credentials and work with password_verify().
-- They are seeded for convenience so a fresh database install already has
-- working demo logins. To regenerate (recommended for non-local setups),
-- run: php _fix_users.php
--
-- Admin:    admin@example.com    / Admin@123
-- Customer: customer@example.com / Customer@123
-- Customer: minh@example.com     / Customer@123

INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`, `email_verified_at`) VALUES
('NORD Admin', 'admin@example.com',    '$2y$10$2AmGbiHdIjKliTMDIG/feOyXe6Vlj7CHMFXb.oFhobsGiqriu.bTe', '0901000001', 'admin',    'active', NOW()),
('Mai Nguyen', 'customer@example.com', '$2y$10$N/0a3gqPotzaEZyv5QBH1uWgs7UsFWdi4E9AVtJBPzbasTgksAZge', '0902000002', 'customer', 'active', NOW()),
('Tran Minh',  'minh@example.com',     '$2y$10$N/0a3gqPotzaEZyv5QBH1uWgs7UsFWdi4E9AVtJBPzbasTgksAZge', '0903000003', 'customer', 'active', NOW());

INSERT INTO `categories` (`parent_id`, `name`, `slug`, `description`, `sort_order`, `is_active`) VALUES
(NULL, 'Living Room', 'living-room', 'Sofas, tables, and storage for everyday living.', 1, 1),
(NULL, 'Bedroom', 'bedroom', 'Beds, nightstands, and calm lighting.', 2, 1),
(NULL, 'Lighting', 'lighting', 'Floor lamps, pendants, and table lamps.', 3, 1),
(NULL, 'Kitchen & Dining', 'kitchen-dining', 'Dining tables, chairs, and serving pieces.', 4, 1),
(NULL, 'Decor', 'decor', 'Textiles, vases, and objects with presence.', 5, 1),
(NULL, 'Outdoor', 'outdoor', 'Weather-ready seating and planters.', 6, 1);

INSERT INTO `brands` (`name`, `slug`, `description`, `is_active`) VALUES
('NORD', 'nord', 'House brand. Quiet forms, durable materials.', 1),
('Lumen Atelier', 'lumen-atelier', 'Lighting designed in Ho Chi Minh City.', 1),
('Casa Forma', 'casa-forma', 'Solid wood furniture with honest joinery.', 1),
('Verde Studio', 'verde-studio', 'Outdoor and botanical living.', 1),
('Atelier Kite', 'atelier-kite', 'Textiles and small objects.', 1);

INSERT INTO `products` (`category_id`, `brand_id`, `name`, `slug`, `sku`, `short_description`, `description`, `price`, `compare_price`, `stock`, `is_featured`, `is_active`) VALUES
(1, 3, 'Oslo Linen Sofa', 'oslo-linen-sofa', 'NORD-SF-001', 'Three-seat sofa in natural linen with oak legs.', 'A low, deep sofa with removable linen covers and solid oak legs. Built for daily use without looking bulky.', 18990000, 21990000, 12, 1, 1),
(1, 3, 'Hanoi Oak Coffee Table', 'hanoi-oak-coffee-table', 'NORD-TB-014', 'Solid oak table with a rounded rectangular top.', 'Hand-finished oak with a matte oil. The rounded corners keep the living room calm and family-safe.', 4590000, NULL, 20, 1, 1),
(1, 1, 'Line Media Console', 'line-media-console', 'NORD-ST-008', 'Low walnut-veneer console with cable management.', 'Two soft-close drawers and an open bay for devices. Rear cutouts keep cables out of sight.', 6290000, 6990000, 8, 0, 1),
(2, 3, 'Kyoto Platform Bed', 'kyoto-platform-bed', 'NORD-BD-003', 'Low oak platform bed, queen size.', 'A quiet bedroom piece with a slatted base. Pair with the Kyoto nightstand.', 12990000, NULL, 6, 1, 1),
(2, 1, 'Kyoto Nightstand', 'kyoto-nightstand', 'NORD-BD-004', 'Single-drawer oak nightstand.', 'Matches the Kyoto bed. Soft-close drawer and a hidden cable slot for a lamp.', 2190000, NULL, 18, 0, 1),
(3, 2, 'Arc Floor Lamp', 'arc-floor-lamp', 'LUM-LP-021', 'Brushed steel arc lamp with linen shade.', 'Over-sofa lighting with a weighted marble base. Dimmer compatible.', 3890000, 4290000, 15, 1, 1),
(3, 2, 'Paper Pendant 45', 'paper-pendant-45', 'LUM-PN-045', 'Handmade paper pendant, 45cm.', 'Warm, even light for dining. Uses an E27 LED bulb (not included).', 1590000, NULL, 25, 1, 1),
(4, 3, 'River Dining Table', 'river-dining-table', 'CASA-DT-010', 'Seats six. Solid oak with a live-edge-inspired profile.', 'A dining table meant to age. Oil finish, not lacquer. Extendable leaf sold separately in a later drop.', 15990000, NULL, 4, 1, 1),
(4, 1, 'Spline Dining Chair', 'spline-dining-chair', 'NORD-CH-022', 'Oak frame with woven paper-cord seat.', 'Sold as a single chair. Stacks two high. Designed for the River table.', 1890000, NULL, 40, 0, 1),
(5, 5, 'Clay Vessel Set', 'clay-vessel-set', 'KITE-DC-007', 'Three stoneware vessels in oat glaze.', 'Unglazed interiors. Use as vases or purely as objects.', 890000, 990000, 30, 0, 1),
(5, 5, 'Wool Throw — Fog', 'wool-throw-fog', 'KITE-TX-012', 'Merino throw in fog grey, 130x180cm.', 'Light enough for HCMC evenings, warm enough for Da Lat mornings.', 1290000, NULL, 22, 1, 1),
(6, 4, 'Terrace Lounge Chair', 'terrace-lounge-chair', 'VERD-OD-002', 'Teak lounge chair with weather-ready canvas.', 'Quick-dry canvas. Teak left untreated so it greys honestly outdoors.', 5490000, NULL, 10, 1, 1);

INSERT INTO `product_images` (`product_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`) VALUES
(1, 'products/oslo-linen-sofa.jpg', 'Oslo linen sofa', 1, 1),
(2, 'products/hanoi-oak-coffee-table.jpg', 'Hanoi oak coffee table', 1, 1),
(3, 'products/line-media-console.jpg', 'Line media console', 1, 1),
(4, 'products/kyoto-platform-bed.jpg', 'Kyoto platform bed', 1, 1),
(5, 'products/kyoto-nightstand.jpg', 'Kyoto nightstand', 1, 1),
(6, 'products/arc-floor-lamp.jpg', 'Arc floor lamp', 1, 1),
(7, 'products/paper-pendant-45.jpg', 'Paper pendant 45', 1, 1),
(8, 'products/river-dining-table.jpg', 'River dining table', 1, 1),
(9, 'products/spline-dining-chair.jpg', 'Spline dining chair', 1, 1),
(10, 'products/clay-vessel-set.jpg', 'Clay vessel set', 1, 1),
(11, 'products/wool-throw-fog.jpg', 'Wool throw fog', 1, 1),
(12, 'products/terrace-lounge-chair.jpg', 'Terrace lounge chair', 1, 1);

INSERT INTO `coupons` (`code`, `type`, `value`, `min_order`, `max_discount`, `usage_limit`, `used_count`, `starts_at`, `expires_at`, `is_active`) VALUES
('WELCOME10', 'percent', 10.00, 1000000.00, 500000.00, 200, 0, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 1),
('NORD500K', 'fixed', 500000.00, 5000000.00, NULL, 50, 0, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY), 1),
('LIGHTING15', 'percent', 15.00, 2000000.00, 800000.00, 80, 0, NOW(), DATE_ADD(NOW(), INTERVAL 45 DAY), 1);

INSERT INTO `banners` (`title`, `image`, `link`, `position`, `sort_order`, `is_active`) VALUES
('Autumn Living Edit', 'banners/hero-living.jpg', '/products.php?featured=1', 'home_hero', 1, 1),
('Lighting, quietly', 'banners/hero-lighting.jpg', '/category.php?slug=lighting', 'home_hero', 2, 1),
('Outdoor weekend', 'banners/promo-outdoor.jpg', '/category.php?slug=outdoor', 'home_promo', 1, 1);

INSERT INTO `post_categories` (`name`, `slug`) VALUES
('Journal', 'journal'),
('Materials', 'materials'),
('Guides', 'guides');

INSERT INTO `posts` (`user_id`, `post_category_id`, `title`, `slug`, `excerpt`, `content`, `thumbnail`, `is_published`, `published_at`) VALUES
(1, 1, 'How we choose oak', 'how-we-choose-oak', 'Not all oak furniture is built the same. Here is what we look for.', 'We source kiln-dried oak with a stable moisture content and visible grain, then oil the surface instead of hiding it under thick lacquer. This journal note explains why that matters in a humid climate.', 'posts/oak.jpg', 1, NOW()),
(1, 3, 'Lighting a small apartment', 'lighting-a-small-apartment', 'One overhead light is rarely enough. A simple three-layer plan.', 'Ambient, task, and accent. Start with a dimmable pendant, add a floor lamp for evenings, and keep table lamps low. This guide maps that idea onto a typical 60m2 apartment.', 'posts/lighting.jpg', 1, NOW()),
(1, 2, 'Linen that lasts', 'linen-that-lasts', 'Why we prefer removable covers and stonewashed linen.', 'Covers should come off. Stonewashed linen softens without looking worn-out on week one. We also talk through colour: oat, fog, and ink.', 'posts/linen.jpg', 1, NOW());

INSERT INTO `addresses` (`user_id`, `full_name`, `phone`, `address_line`, `ward`, `district`, `city`, `is_default`) VALUES
(2, 'Mai Nguyen', '0902000002', '12 Nguyen Hue', 'Ben Nghe', 'District 1', 'Ho Chi Minh City', 1);

INSERT INTO `wishlists` (`user_id`) VALUES (2);

INSERT INTO `wishlist_items` (`wishlist_id`, `product_id`) VALUES (1, 1), (1, 6);

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('store_name', 'NORD Home'),
('store_email', 'hello@nordhome.local'),
('store_phone', '0901000001'),
('store_address', 'District 1, Ho Chi Minh City'),
('currency', 'VND'),
('free_shipping_min', '2000000');
