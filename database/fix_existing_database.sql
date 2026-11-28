-- Run in phpMyAdmin (SQL tab, database royal_auto) if you do NOT want to re-import.
ALTER TABLE bookings MODIFY status ENUM('requested','quoted','accepted','assigned','in_progress','completed','finished','cancelled') NOT NULL DEFAULT 'requested';
-- bookings with a blank status that already have a review are finished
UPDATE bookings b SET b.status='finished' WHERE b.status='' AND EXISTS (SELECT 1 FROM reviews r WHERE r.booking_id=b.booking_id);
UPDATE bookings SET status='completed' WHERE status='';
ALTER TABLE photos DROP COLUMN description;
