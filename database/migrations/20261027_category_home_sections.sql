-- Run this migration once against the existing electronics_store database.
-- It enables staff to choose which categories get their own product section on the home page.
ALTER TABLE categories
    ADD COLUMN show_on_home TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;

CREATE INDEX idx_categories_home_active ON categories (is_active, show_on_home);

-- Optional: show the existing sample categories on the home page initially.
UPDATE categories SET show_on_home = 1 WHERE slug IN ('accessories', 'audio');
