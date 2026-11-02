-- Apply once to the existing electronics_store database. Do not re-import schema.sql.
ALTER TABLE users ADD COLUMN profile_image VARCHAR(500) NULL AFTER phone;

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
