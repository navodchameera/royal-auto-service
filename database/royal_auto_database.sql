-- Royal Auto Service: full database with ON DELETE CASCADE / ON UPDATE CASCADE
-- WARNING: this drops the existing tables first, so all current data is deleted.

-- Local XAMPP only: create and open the database.
-- On AeonFree (or any host that gives you a fixed database name) skip these two
-- lines, select your database on the left of phpMyAdmin, then run the rest.
CREATE DATABASE IF NOT EXISTS royal_auto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE royal_auto;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS photos, reviews, payments, jobs, quotations, bookings, vehicles, packages, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------- USER
CREATE TABLE users (
  user_id    INT AUTO_INCREMENT PRIMARY KEY,
  full_name  VARCHAR(100) NOT NULL,
  username   VARCHAR(30)  NOT NULL UNIQUE,
  email      VARCHAR(100) NOT NULL UNIQUE,
  phone      VARCHAR(15)  NOT NULL,
  password   VARCHAR(255) NOT NULL,
  address    VARCHAR(255) NOT NULL,
  role       ENUM('customer','manager','cleaner') NOT NULL DEFAULT 'customer',
  status     ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------- PACKAGE
CREATE TABLE packages (
  package_id   INT AUTO_INCREMENT PRIMARY KEY,
  package_name VARCHAR(50)  NOT NULL,
  description  VARCHAR(255) NULL,
  min_price    DECIMAL(10,2) NOT NULL,
  max_price    DECIMAL(10,2) NOT NULL,
  is_active    TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO packages (package_name, description, min_price, max_price) VALUES
('Basic',   'Exterior wash',                2500,  3500),
('Combo',   'Wash and interior vacuum',     4000,  6000),
('Premium', 'Combo, wax and odor removal',  7000, 10000);

-- ------------------------------------------------------------- VEHICLE
-- User 1 --- m Vehicle (owns)
CREATE TABLE vehicles (
  vehicle_id   INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  vehicle_type VARCHAR(30)  NOT NULL,
  make_model   VARCHAR(100) NOT NULL,
  plate_no     VARCHAR(20)  NOT NULL UNIQUE,
  colour       VARCHAR(30)  NULL,
  CONSTRAINT fk_vehicles_user FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------- BOOKING
-- User 1 --- m Booking (creates), Vehicle 1 --- m Booking (has), Package 1 --- m Booking (select)
CREATE TABLE bookings (
  booking_id       INT AUTO_INCREMENT PRIMARY KEY,
  user_id          INT NOT NULL,
  vehicle_id       INT NOT NULL,
  package_id       INT NOT NULL,
  location_type    ENUM('garage','customer_site') NOT NULL,
  job_address      VARCHAR(255) NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  status           ENUM('requested','quoted','accepted','assigned','in_progress','completed','finished','cancelled')
                   NOT NULL DEFAULT 'requested',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bookings_user    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_bookings_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_bookings_package FOREIGN KEY (package_id) REFERENCES packages(package_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------- QUOTATION
-- Booking 1 --- 1 Quotation (belongs to)
CREATE TABLE quotations (
  quotation_id    INT AUTO_INCREMENT PRIMARY KEY,
  booking_id      INT NOT NULL UNIQUE,
  quoted_price    DECIMAL(10,2) NOT NULL,
  condition_level ENUM('light','moderate','heavy') NULL,
  remarks         TEXT NULL,
  status          ENUM('sent','accepted','rejected') NOT NULL DEFAULT 'sent',
  quotation_date  DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_quotations_booking FOREIGN KEY (booking_id)
    REFERENCES bookings(booking_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------- JOB
-- Booking 1 --- 1 Job (has), Cleaner (User) 1 --- m Job (done by)
CREATE TABLE jobs (
  job_id       INT AUTO_INCREMENT PRIMARY KEY,
  booking_id   INT NOT NULL UNIQUE,
  cleaner_id   INT NOT NULL,
  job_status   ENUM('assigned','on_the_way','arrived','in_progress','completed') NOT NULL DEFAULT 'assigned',
  arrived_at   DATETIME NULL,
  completed_at DATETIME NULL,
  CONSTRAINT fk_jobs_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_jobs_cleaner FOREIGN KEY (cleaner_id) REFERENCES users(user_id)       ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------- PAYMENT
-- Booking 1 --- m Payment (has)
CREATE TABLE payments (
  payment_id     INT AUTO_INCREMENT PRIMARY KEY,
  booking_id     INT NOT NULL,
  amount         DECIMAL(10,2) NOT NULL,
  method         ENUM('cash','online') NOT NULL,
  payment_status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  payment_date   DATETIME NULL,
  CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id)
    REFERENCES bookings(booking_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------- REVIEW
-- User 1 --- m Review (writes), Booking 1 --- 1 Review (belongs to)
CREATE TABLE reviews (
  review_id   INT AUTO_INCREMENT PRIMARY KEY,
  booking_id  INT NOT NULL UNIQUE,
  user_id     INT NOT NULL,
  rating      TINYINT NOT NULL,
  comment     TEXT NULL,
  review_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT fk_reviews_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_user    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------- PHOTO
-- Booking 1 --- m Photo (has)
CREATE TABLE photos (
  photo_id    INT AUTO_INCREMENT PRIMARY KEY,
  booking_id  INT NOT NULL,
  file_path   VARCHAR(255) NOT NULL,   -- file name inside /uploads/booking_photos
  photo_area  VARCHAR(30)  NULL,       -- front, rear, left, interior_front, rear_seats, boot
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_photos_booking FOREIGN KEY (booking_id)
    REFERENCES bookings(booking_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
