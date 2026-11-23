-- Optional demo data so the customer home page is not empty.
-- 1) Register a customer on the site first.
-- 2) Change 'your_username' below, then run this file in phpMyAdmin (database royal_auto).
USE royal_auto;
SET @uid = (SELECT user_id FROM users WHERE username = 'your_username');

INSERT INTO vehicles (user_id, vehicle_type, make_model, plate_no, colour) VALUES (@uid, 'car', 'Toyota Axio', 'CAB-4821', 'Silver');
SET @v1 = LAST_INSERT_ID();
INSERT INTO vehicles (user_id, vehicle_type, make_model, plate_no, colour) VALUES (@uid, 'suv', 'Honda Vezel', 'KY-3305', 'White');
SET @v2 = LAST_INSERT_ID();

-- Upcoming, cleaner assigned
INSERT INTO bookings (user_id, vehicle_id, package_id, location_type, job_address, appointment_date, appointment_time, status)
VALUES (@uid, @v1, 2, 'customer_site', 'Sample address, Negombo', DATE_ADD(CURDATE(), INTERVAL 6 DAY), '10:00:00', 'assigned');
INSERT INTO quotations (booking_id, quoted_price, condition_level, status) VALUES (LAST_INSERT_ID(), 7000, 'moderate', 'accepted');

-- Quotation ready
INSERT INTO bookings (user_id, vehicle_id, package_id, location_type, appointment_date, appointment_time, status)
VALUES (@uid, @v2, 3, 'garage', DATE_ADD(CURDATE(), INTERVAL 10 DAY), '09:00:00', 'quoted');
INSERT INTO quotations (booking_id, quoted_price, condition_level, status) VALUES (LAST_INSERT_ID(), 8500, 'light', 'sent');

-- Completed, payment due
INSERT INTO bookings (user_id, vehicle_id, package_id, location_type, appointment_date, appointment_time, status)
VALUES (@uid, @v2, 2, 'garage', DATE_SUB(CURDATE(), INTERVAL 6 DAY), '14:00:00', 'completed');
INSERT INTO quotations (booking_id, quoted_price, status) VALUES (LAST_INSERT_ID(), 5500, 'accepted');

-- Completed, paid, waiting for a review
INSERT INTO bookings (user_id, vehicle_id, package_id, location_type, appointment_date, appointment_time, status)
VALUES (@uid, @v2, 1, 'garage', DATE_SUB(CURDATE(), INTERVAL 16 DAY), '11:00:00', 'completed');
SET @b = LAST_INSERT_ID();
INSERT INTO quotations (booking_id, quoted_price, status) VALUES (@b, 2500, 'accepted');
INSERT INTO payments (booking_id, amount, method, payment_status, payment_date) VALUES (@b, 2500, 'card', 'paid', NOW());

-- Completed, paid, reviewed
INSERT INTO bookings (user_id, vehicle_id, package_id, location_type, appointment_date, appointment_time, status)
VALUES (@uid, @v1, 3, 'customer_site', DATE_SUB(CURDATE(), INTERVAL 30 DAY), '10:00:00', 'completed');
SET @b = LAST_INSERT_ID();
INSERT INTO quotations (booking_id, quoted_price, status) VALUES (@b, 8000, 'accepted');
INSERT INTO payments (booking_id, amount, method, payment_status, payment_date) VALUES (@b, 8000, 'cash', 'paid', NOW());
INSERT INTO reviews (booking_id, user_id, rating, comment) VALUES (@b, @uid, 5, 'Sample review');
