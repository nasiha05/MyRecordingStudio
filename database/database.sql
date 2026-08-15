-- =========================================================
-- MyRecordingStudio - Database 
-- ISIT307 Assignment 2
--Name: Nasihafarhin Abulhasan
--UOWID:9090447

-- =========================================================

DROP DATABASE IF EXISTS myrecordingstudio;
CREATE DATABASE myrecordingstudio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE myrecordingstudio;

-- ---------------------------------------------------------
-- Table: users
-- Stores both Administrator and Client accounts.
-- ---------------------------------------------------------
CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    phone         VARCHAR(20)  NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    user_type     ENUM('admin','client') NOT NULL DEFAULT 'client',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: locations
-- A location contains a pool of identical recording studios.
-- ---------------------------------------------------------
CREATE TABLE locations (
    location_id    INT AUTO_INCREMENT PRIMARY KEY,
    description    VARCHAR(255) NOT NULL,
    num_studios    INT NOT NULL,
    cost_per_hour  DECIMAL(8,2) NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_num_studios CHECK (num_studios > 0),
    CONSTRAINT chk_cost CHECK (cost_per_hour > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: bookings
-- One row represents one client's session at one location.
-- ---------------------------------------------------------
CREATE TABLE bookings (
    booking_id     INT AUTO_INCREMENT PRIMARY KEY,
    client_id      INT NOT NULL,
    location_id    INT NOT NULL,
    booking_date   DATE NOT NULL,
    start_time     TIME NOT NULL,
    duration_hours INT NOT NULL,
    end_time       TIME NOT NULL,
    total_cost     DECIMAL(8,2) NOT NULL,
    status         ENUM('active','cancelled') NOT NULL DEFAULT 'active',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_client
        FOREIGN KEY (client_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_booking_location
        FOREIGN KEY (location_id) REFERENCES locations(location_id)
        ON DELETE CASCADE,
    CONSTRAINT chk_duration CHECK (duration_hours BETWEEN 1 AND 12)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Sample Administrator account
-- Email: admin@myrecordingstudio.com
-- Password: Admin123!
-- ---------------------------------------------------------
INSERT INTO users (name, phone, email, password_hash, user_type) VALUES
('Studio Administrator', '61230001', 'admin@myrecordingstudio.com',
 '$2y$12$KfMTJE1fhptWHrzoMidaCOTGIOXPdfQP.C3EU4Tk6WDbdoYVRF.dC', 'admin');

-- ---------------------------------------------------------
-- Sample Client accounts
-- Password for all three: Client123!
-- ---------------------------------------------------------
INSERT INTO users (name, phone, email, password_hash, user_type) VALUES
('Aisha Tan', '81230001', 'client1@example.com',
 '$2y$12$Wy2tjrszu915tfv/2f6zCuWGqR8n1cK18OWnblU2.xxalxB/UDbei', 'client'),
('Daniel Lim', '81230002', 'client2@example.com',
 '$2y$12$Wy2tjrszu915tfv/2f6zCuWGqR8n1cK18OWnblU2.xxalxB/UDbei', 'client'),
('Mei Wong', '81230003', 'client3@example.com',
 '$2y$12$Wy2tjrszu915tfv/2f6zCuWGqR8n1cK18OWnblU2.xxalxB/UDbei', 'client');

-- ---------------------------------------------------------
-- Four simple Singapore-based sample locations.
-- Studio counts are intentionally kept between 2 and 4 so
-- capacity/availability testing is easy to understand.
-- ---------------------------------------------------------
INSERT INTO locations (description, num_studios, cost_per_hour) VALUES
('Orchard Road Studio', 4, 55.00),
('Bugis Studio', 3, 48.00),
('Tampines Studio', 2, 42.00),
('Jurong East Studio', 3, 45.00);

-- =========================================================
-- Sample bookings
-- =========================================================
-- These records intentionally cover different testing situations.
-- Relative future dates keep the sample data useful after re-importing.
--
-- 1) Client 1 and Client 2 overlap at Orchard, but the location has
--    four studios, so another client can still book during part of it.
-- 2) Client 3 has a later Orchard session.
-- 3) Bugis has overlapping sessions that demonstrate capacity.
-- 4) One cancelled record demonstrates cancellation history.
-- 5) One booking on the current date is included for status testing.
-- =========================================================

-- Current-date booking for status testing: 2:00 PM - 4:00 PM.
INSERT INTO bookings
(client_id, location_id, booking_date, start_time, duration_hours, end_time, total_cost, status)
VALUES
(1, 1, CURDATE(), '14:00:00', 2, '16:00:00', 110.00, 'active');

-- Future overlapping bookings at Orchard Road Studio (4 studios).
INSERT INTO bookings
(client_id, location_id, booking_date, start_time, duration_hours, end_time, total_cost, status)
VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 5, '15:00:00', 275.00, 'active'),
(2, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '12:00:00', 4, '16:00:00', 220.00, 'active'),
(3, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:00:00', 4, '22:00:00', 220.00, 'active');

-- Bugis has 3 studios. These three bookings overlap at 2:00-4:00 PM,
-- so that period is fully booked and is useful for testing capacity.
INSERT INTO bookings
(client_id, location_id, booking_date, start_time, duration_hours, end_time, total_cost, status)
VALUES
(1, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', 6, '16:00:00', 288.00, 'active'),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '12:00:00', 4, '16:00:00', 192.00, 'active'),
(3, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', 4, '18:00:00', 192.00, 'active');

-- Cancelled booking: useful for testing that cancelled sessions cannot
-- be modified/cancelled again and no longer consume studio capacity.
INSERT INTO bookings
(client_id, location_id, booking_date, start_time, duration_hours, end_time, total_cost, status)
VALUES
(2, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00:00', 2, '12:00:00', 84.00, 'cancelled');

