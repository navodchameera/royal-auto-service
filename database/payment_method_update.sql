-- Run once if your payments table already exists (phpMyAdmin > SQL).
-- Payment method becomes: cash (marked by manager/cleaner) or online (demo card payment).
ALTER TABLE payments MODIFY method ENUM('cash','card','bank_transfer','online') NOT NULL;
UPDATE payments SET method = 'online' WHERE method IN ('card','bank_transfer');
ALTER TABLE payments MODIFY method ENUM('cash','online') NOT NULL;
