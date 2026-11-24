-- Optional demo data for the manager dashboard.
-- Run in phpMyAdmin (database royal_auto). Needs the tables from royal_auto_database.sql, including `jobs`.
-- Sample passwords (change them!):  manager / Manager123    cleaner_a, cleaner_b, cleaner_c / Cleaner123
USE royal_auto;

INSERT INTO users (full_name, username, email, phone, password, address, role) VALUES
('Manager',   'manager',   'manager@example.com',   '0771000000', '$2y$10$wPhpYpJ3tdSBG4cITzhe7uBbRsrEQ3fFc2f6OAhc55jGHZdqxRdYq', 'Royal Auto Service, Kandy', 'manager'),
('Cleaner A', 'cleaner_a', 'cleaner.a@example.com', '0771000001', '$2y$10$EnEtVr72XzGE8p4LWkUPXOXRK/Q0tVhEUbm0CmzBWe7pBzxK3hwv.', 'Kandy', 'cleaner'),
('Cleaner B', 'cleaner_b', 'cleaner.b@example.com', '0771000002', '$2y$10$EnEtVr72XzGE8p4LWkUPXOXRK/Q0tVhEUbm0CmzBWe7pBzxK3hwv.', 'Kandy', 'cleaner'),
('Cleaner C', 'cleaner_c', 'cleaner.c@example.com', '0771000003', '$2y$10$EnEtVr72XzGE8p4LWkUPXOXRK/Q0tVhEUbm0CmzBWe7pBzxK3hwv.', 'Kandy', 'cleaner');

-- Optional: put today's work on the dashboard using a customer you already registered.
-- Change 'your_username', and run sample_data.sql first so that customer has a vehicle.
SET @uid = (SELECT user_id FROM users WHERE username = 'your_username');
SET @v1  = (SELECT MIN(vehicle_id) FROM vehicles WHERE user_id = @uid);
SET @ca  = (SELECT user_id FROM users WHERE username = 'cleaner_a');
SET @cb  = (SELECT user_id FROM users WHERE username = 'cleaner_b');

-- In progress now (Cleaner A)
INSERT INTO bookings (user_id, vehicle_id, package_id, location_type, job_address, appointment_date, appointment_time, status)
VALUES (@uid, @v1, 2, 'customer_site', 'Sample address, Kandy', CURDATE(), '10:00:00', 'in_progress');
INSERT INTO jobs (booking_id, cleaner_id, job_status, arrived_at) VALUES (LAST_INSERT_ID(), @ca, 'in_progress', NOW());

-- Assigned later today (Cleaner B)
INSERT INTO bookings (user_id, vehicle_id, package_id, location_type, appointment_date, appointment_time, status)
VALUES (@uid, @v1, 3, 'garage', CURDATE(), '13:30:00', 'assigned');
INSERT INTO jobs (booking_id, cleaner_id, job_status) VALUES (LAST_INSERT_ID(), @cb, 'assigned');

-- Accepted but no cleaner yet
INSERT INTO bookings (user_id, vehicle_id, package_id, location_type, job_address, appointment_date, appointment_time, status)
VALUES (@uid, @v1, 3, 'customer_site', 'Sample address, Kandy', CURDATE(), '16:00:00', 'accepted');

-- A new request waiting for a quotation
INSERT INTO bookings (user_id, vehicle_id, package_id, location_type, appointment_date, appointment_time, status)
VALUES (@uid, @v1, 1, 'garage', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00', 'requested');
