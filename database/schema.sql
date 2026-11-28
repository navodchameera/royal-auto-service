CREATE DATABASE IF NOT EXISTS royal_auto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE royal_auto;

CREATE TABLE IF NOT EXISTS users (
  user_id    INT AUTO_INCREMENT PRIMARY KEY,
  full_name  VARCHAR(100) NOT NULL,
  username   VARCHAR(30)  NOT NULL UNIQUE,
  email      VARCHAR(100) NOT NULL UNIQUE,
  phone      VARCHAR(15)  NOT NULL,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('customer','manager','cleaner') NOT NULL DEFAULT 'customer',
  status     ENUM('active','blocked') NOT NULL DEFAULT 'active',
  address    VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- If you already created the table from the first version, run this instead:
-- ALTER TABLE users ADD COLUMN username VARCHAR(30) NOT NULL UNIQUE AFTER full_name;


-- ============ Added for the customer home page ============
CREATE TABLE IF NOT EXISTS packages (
  package_id   INT AUTO_INCREMENT PRIMARY KEY,
  package_name VARCHAR(50)  NOT NULL,
  description  VARCHAR(255) NULL,
  min_price    DECIMAL(10,2) NOT NULL,
  max_price    DECIMAL(10,2) NOT NULL,
  is_active    TINYINT(1) NOT NULL DEFAULT 1
);

INSERT INTO packages (package_name, description, min_price, max_price) VALUES
('Basic',   'Exterior wash',                      2500, 3500),
('Combo',   'Wash and interior vacuum',           4000, 6000),
('Premium', 'Combo, wax and odor removal',        7000, 10000);

CREATE TABLE IF NOT EXISTS vehicles (
  vehicle_id   INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  vehicle_type VARCHAR(30)  NOT NULL,
  make_model   VARCHAR(100) NOT NULL,
  plate_no     VARCHAR(20)  NOT NULL UNIQUE,
  colour       VARCHAR(30)  NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS bookings (
  booking_id       INT AUTO_INCREMENT PRIMARY KEY,
  user_id          INT NOT NULL,
  vehicle_id       INT NOT NULL,
  package_id       INT NOT NULL,
  location_type    ENUM('garage','customer_site') NOT NULL,
  job_address      VARCHAR(255) NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  status           ENUM('requested','quoted','accepted','assigned','in_progress','completed','finished','cancelled') NOT NULL DEFAULT 'requested',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)    REFERENCES users(user_id),
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id),
  FOREIGN KEY (package_id) REFERENCES packages(package_id)
);

CREATE TABLE IF NOT EXISTS quotations (
  quotation_id    INT AUTO_INCREMENT PRIMARY KEY,
  booking_id      INT NOT NULL UNIQUE,
  quoted_price    DECIMAL(10,2) NOT NULL,
  condition_level ENUM('light','moderate','heavy') NULL,
  remarks         TEXT NULL,
  status          ENUM('sent','accepted','rejected') NOT NULL DEFAULT 'sent',
  quotation_date  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

CREATE TABLE IF NOT EXISTS payments (
  payment_id     INT AUTO_INCREMENT PRIMARY KEY,
  booking_id     INT NOT NULL,
  amount         DECIMAL(10,2) NOT NULL,
  method         ENUM('cash','online') NOT NULL,
  payment_status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  payment_date   DATETIME NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

CREATE TABLE IF NOT EXISTS reviews (
  review_id   INT AUTO_INCREMENT PRIMARY KEY,
  booking_id  INT NOT NULL UNIQUE,
  user_id     INT NOT NULL,
  rating      TINYINT NOT NULL,
  comment     TEXT NULL,
  review_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
  FOREIGN KEY (user_id)    REFERENCES users(user_id)
);

-- ============ Added for Book a service ============
CREATE TABLE IF NOT EXISTS photos (
  photo_id    INT AUTO_INCREMENT PRIMARY KEY,
  booking_id  INT NOT NULL,
  file_path   VARCHAR(255) NOT NULL,   -- file name inside /uploads/booking_photos
  photo_area  VARCHAR(30) NULL,        -- front, rear, left, interior_front, rear_seats, boot
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);
