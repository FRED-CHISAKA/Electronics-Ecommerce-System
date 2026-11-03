-- Stage 4.2 catalogue extension. Run once against the existing electronics_store database.
-- categories.parent_id already supports parent/child/grandchild categories.
ALTER TABLE products ADD COLUMN parent_product_id BIGINT UNSIGNED NULL AFTER category_id;
ALTER TABLE products ADD CONSTRAINT fk_products_parent_product FOREIGN KEY (parent_product_id) REFERENCES products(id) ON DELETE SET NULL;
CREATE INDEX idx_products_parent_product ON products(parent_product_id);

CREATE TABLE page_views (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 page_path VARCHAR(255) NOT NULL,
 session_hash CHAR(64) NULL,
 viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_page_views_date (viewed_at)
) ENGINE=InnoDB;
