-- Add an explicit product NEW badge switch. Existing products default to not new.
ALTER TABLE products ADD COLUMN is_new TINYINT(1) NOT NULL DEFAULT 0 AFTER is_featured;
