-- Run once in phpMyAdmin (database royal_auto > SQL tab).
-- Adds the final booking status "finished": the customer rated the service and completed the booking.

ALTER TABLE bookings
  MODIFY status ENUM('requested','quoted','accepted','assigned','in_progress','completed','finished','cancelled')
  NOT NULL DEFAULT 'requested';

-- Optional: bookings that were already "completed" before this change were never reviewed.
-- To treat them as fully completed instead of asking customers to review them, run:
-- UPDATE bookings SET status = 'finished' WHERE status = 'completed';
