-- Run once in phpMyAdmin (SQL tab, database royal_auto).
-- Lets the manager make a package active or inactive (inactive packages are hidden from the booking form).
ALTER TABLE packages ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER max_price;
