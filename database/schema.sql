-- BlueCircuit Electronics demo store schema
-- Import this file into MySQL/phpMyAdmin before opening the website.
CREATE DATABASE IF NOT EXISTS electronics_store
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE electronics_store;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS product_reviews;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS addresses;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  profile_image VARCHAR(500) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  email_verified_at DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB;

CREATE TABLE addresses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  recipient_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  county VARCHAR(100) NOT NULL,
  town VARCHAR(100) NOT NULL,
  address_line VARCHAR(255) NOT NULL,
  delivery_notes VARCHAR(500) NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_addresses_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE brands (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(120) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id BIGINT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  description TEXT NULL,
  image_path VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  show_on_home TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_categories_active (is_active),
  INDEX idx_categories_home_active (is_active, show_on_home)
) ENGINE=InnoDB;

CREATE TABLE products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NULL,
  brand_id BIGINT UNSIGNED NULL,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  sku VARCHAR(80) NOT NULL UNIQUE,
  short_description VARCHAR(500) NULL,
  description MEDIUMTEXT NULL,
  specifications JSON NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  compare_at_price DECIMAL(12,2) NULL,
  cost_price DECIMAL(12,2) NULL,
  stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  low_stock_threshold INT UNSIGNED NOT NULL DEFAULT 5,
  image_path VARCHAR(500) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
  INDEX idx_products_active_featured (is_active, is_featured),
  INDEX idx_products_category (category_id),
  INDEX idx_products_name (name),
  CHECK (price >= 0),
  CHECK (stock_quantity >= 0)
) ENGINE=InnoDB;

CREATE TABLE product_images (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  image_path VARCHAR(500) NOT NULL,
  alt_text VARCHAR(200) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_product_images_sort (product_id, sort_order)
) ENGINE=InnoDB;

CREATE TABLE product_reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  title VARCHAR(160) NULL,
  review TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_product_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_product_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_product_review_user (product_id,user_id),
  INDEX idx_product_reviews_public (product_id,status,created_at),
  CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE carts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  session_token CHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_cart_session (session_token),
  INDEX idx_carts_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE cart_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY uq_cart_product (cart_id, product_id),
  CHECK (quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(32) NOT NULL UNIQUE,
  user_id BIGINT UNSIGNED NULL,
  customer_name VARCHAR(150) NOT NULL,
  customer_email VARCHAR(190) NULL,
  customer_phone VARCHAR(30) NOT NULL,
  county VARCHAR(100) NOT NULL,
  town VARCHAR(100) NOT NULL,
  delivery_address VARCHAR(255) NOT NULL,
  delivery_notes VARCHAR(500) NULL,
  order_channel ENUM('website','whatsapp','admin') NOT NULL DEFAULT 'website',
  status ENUM('pending','confirmed','processing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  payment_method ENUM('mpesa','cash_on_delivery','bank_transfer','whatsapp_arrangement') NOT NULL DEFAULT 'cash_on_delivery',
  payment_status ENUM('unpaid','pending','paid','failed','refunded') NOT NULL DEFAULT 'unpaid',
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  delivery_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  customer_notes TEXT NULL,
  placed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_orders_status_date (status, placed_at),
  INDEX idx_orders_customer_phone (customer_phone),
  INDEX idx_orders_payment_status (payment_status),
  CHECK (subtotal >= 0 AND delivery_fee >= 0 AND discount_amount >= 0 AND total_amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NULL,
  product_name VARCHAR(200) NOT NULL,
  sku VARCHAR(80) NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  INDEX idx_order_items_product (product_id),
  CHECK (unit_price >= 0 AND quantity > 0 AND line_total >= 0)
) ENGINE=InnoDB;

CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  provider ENUM('mpesa','cash','bank_transfer','other') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('initiated','pending','successful','failed','reversed') NOT NULL DEFAULT 'initiated',
  transaction_reference VARCHAR(100) NULL,
  provider_request_id VARCHAR(150) NULL,
  provider_receipt VARCHAR(150) NULL,
  raw_callback JSON NULL,
  paid_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  INDEX idx_payments_order_status (order_id, status),
  INDEX idx_payments_reference (transaction_reference)
) ENGINE=InnoDB;

CREATE TABLE settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO categories (name, slug, description) VALUES
('Laptops', 'laptops', 'Laptops for work, study and creativity.'),
('Phones & Tablets', 'phones-tablets', 'Smartphones, tablets and mobile accessories.'),
('Audio', 'audio', 'Headphones, speakers and audio gear.'),
('Accessories', 'accessories', 'Chargers, cables, keyboards and everyday essentials.');

INSERT INTO brands (name, slug) VALUES
('TechNova', 'technova'),
('SoundPeak', 'soundpeak'),
('CoreMobile', 'coremobile');

INSERT INTO products
(category_id, brand_id, name, slug, sku, short_description, description, price, compare_at_price, stock_quantity, image_path, is_featured, is_active)
VALUES
(1, 1, 'NovaBook 14 Laptop', 'novabook-14-laptop', 'BC-LAP-001', 'A lightweight everyday laptop for work and study.', 'Demo product. Replace specifications, images and availability with verified product details before launch.', 64999.00, 69999.00, 8, NULL, 1, 1),
(2, 3, 'CoreMobile X Smartphone', 'coremobile-x-smartphone', 'BC-PHN-001', 'A modern smartphone for daily use.', 'Demo product. Replace specifications, images and availability with verified product details before launch.', 32999.00, NULL, 12, NULL, 1, 1),
(3, 2, 'SoundPeak Wireless Headphones', 'soundpeak-wireless-headphones', 'BC-AUD-001', 'Wireless audio for work, travel and downtime.', 'Demo product. Replace specifications, images and availability with verified product details before launch.', 5499.00, 6999.00, 20, NULL, 1, 1),
(4, 1, 'USB-C 65W Fast Charger', 'usb-c-65w-fast-charger', 'BC-ACC-001', 'Compact USB-C charger for compatible devices.', 'Demo product. Confirm device compatibility and safety certifications before selling.', 2499.00, NULL, 30, NULL, 1, 1);

INSERT INTO settings (setting_key, setting_value) VALUES
('store_name', 'BlueCircuit Electronics'),
('store_currency', 'KES'),
('whatsapp_number', ''),
('default_delivery_fee', '0.00');
