-- BayanTap Water Utility Billing System
-- Database Schema
-- Run this file to initialize the database

CREATE DATABASE IF NOT EXISTS bayantap_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bayantap_db;

-- ============================================================
-- USERS TABLE (Treasurer login)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role ENUM('treasurer', 'admin') DEFAULT 'treasurer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- HOUSEHOLDS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS households (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_no VARCHAR(50) NOT NULL UNIQUE,  -- e.g. "Blk 9 Lot 2"
    block VARCHAR(20),
    lot VARCHAR(20),
    address TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- RESIDENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS residents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(20),
    email VARCHAR(150),
    is_primary TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
);

-- ============================================================
-- BILLS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    billing_month DATE NOT NULL,              -- e.g. 2026-01-01 means January 2026
    prev_reading DECIMAL(10,2) NOT NULL,      -- cubic meters
    curr_reading DECIMAL(10,2) NOT NULL,      -- cubic meters
    consumption DECIMAL(10,2) GENERATED ALWAYS AS (curr_reading - prev_reading) STORED,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('paid', 'unpaid', 'overdue') DEFAULT 'unpaid',
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
);

-- ============================================================
-- PAYMENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    receipt_no VARCHAR(50) NOT NULL UNIQUE,   -- e.g. MV-2026-0156
    payment_date DATE NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    received_by VARCHAR(150),                 -- treasurer name
    resident_signature VARCHAR(150),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED DATA: Default Admin User
-- Password: admin123 (bcrypt hash)
-- ============================================================
INSERT INTO users (username, password, full_name, role) VALUES
('treasurer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carmen Santos', 'treasurer'),
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin');

-- ============================================================
-- SEED DATA: Households
-- ============================================================
INSERT INTO households (household_no, block, lot, address) VALUES
('Blk 9 Lot 2',  '9',  '2',  'Block 9 Lot 2, Marcos Village, Dagupan City'),
('Blk 08 Lot 3', '8',  '3',  'Block 8 Lot 3, Marcos Village, Dagupan City'),
('Blk 5 Lot 6',  '5',  '6',  'Block 5 Lot 6, Marcos Village, Dagupan City'),
('Blk 15 Lot 2', '15', '2',  'Block 15 Lot 2, Marcos Village, Dagupan City'),
('Blk 15 Lot 7', '15', '7',  'Block 15 Lot 7, Marcos Village, Dagupan City'),
('Blk 4 Lot 1',  '4',  '1',  'Block 4 Lot 1, Marcos Village, Dagupan City'),
('Blk 15 Lot 8', '15', '8',  'Block 15 Lot 8, Marcos Village, Dagupan City'),
('Blk 3 Lot 4',  '3',  '4',  'Block 3 Lot 4, Marcos Village, Dagupan City'),
('Blk 7 Lot 5',  '7',  '5',  'Block 7 Lot 5, Marcos Village, Dagupan City'),
('Blk 11 Lot 9', '11', '9',  'Block 11 Lot 9, Marcos Village, Dagupan City'),
('Blk 2 Lot 3',  '2',  '3',  'Block 2 Lot 3, Marcos Village, Dagupan City'),
('Blk 6 Lot 7',  '6',  '7',  'Block 6 Lot 7, Marcos Village, Dagupan City');

-- ============================================================
-- SEED DATA: Residents
-- ============================================================
INSERT INTO residents (household_id, full_name, contact_number, is_primary) VALUES
(1,  'Justin Basco',           '09171234567', 1),
(2,  'James Tanglao',          '09281234567', 1),
(3,  'Ian Patrick Reyes',      '09391234567', 1),
(4,  'Janella Ashley S. Gomez','09401234567', 1),
(5,  'Ronald Barangay',        '09511234567', 1),
(6,  'Aliyah Macapagal',       '09621234567', 1),
(7,  'Rafael Laquian',         '09731234567', 1),
(8,  'Maria Santos',           '09841234567', 1),
(9,  'Eduardo Cruz',           '09951234567', 1),
(10, 'Liza Reyes',             '09061234567', 1),
(11, 'Roberto Dela Cruz',      '09171111111', 1),
(12, 'Ana Gonzales',           '09282222222', 1);

-- ============================================================
-- SEED DATA: Bills (January 2026)
-- ============================================================
INSERT INTO bills (household_id, billing_month, prev_reading, curr_reading, amount, status, due_date) VALUES
(1,  '2026-01-01', 1245, 1268, 575.00,  'paid',    '2026-01-25'),
(2,  '2026-01-01', 2156, 2189, 885.00,  'paid',    '2026-01-25'),
(3,  '2026-01-01', 987,  1015, 700.00,  'unpaid',  '2026-01-25'),
(4,  '2026-01-01', 2246, 2268, 960.00,  'paid',    '2026-01-25'),
(5,  '2026-01-01', 1825, 1768, 650.00,  'paid',    '2026-01-25'),
(6,  '2026-01-01', 1245, 1268, 320.00,  'paid',    '2026-01-25'),
(7,  '2026-01-01', 1245, 1268, 600.00,  'unpaid',  '2026-01-25'),
(8,  '2026-01-01', 1100, 1134, 850.00,  'paid',    '2026-01-25'),
(9,  '2026-01-01', 560,  598,  950.00,  'overdue', '2026-01-10'),
(10, '2026-01-01', 2310, 2345, 875.00,  'overdue', '2026-01-10'),
(11, '2026-01-01', 780,  812,  800.00,  'paid',    '2026-01-25'),
(12, '2026-01-01', 1450, 1478, 700.00,  'overdue', '2026-01-10');

-- Bills for December 2025 (previous month)
INSERT INTO bills (household_id, billing_month, prev_reading, curr_reading, amount, status, due_date) VALUES
(1,  '2025-12-01', 1220, 1245, 625.00,  'paid',    '2025-12-25'),
(2,  '2025-12-01', 2130, 2156, 650.00,  'paid',    '2025-12-25'),
(3,  '2025-12-01', 960,  987,  675.00,  'paid',    '2025-12-25'),
(4,  '2025-12-01', 2218, 2246, 700.00,  'paid',    '2025-12-25'),
(5,  '2025-12-01', 1800, 1825, 625.00,  'paid',    '2025-12-25'),
(6,  '2025-12-01', 1220, 1245, 625.00,  'paid',    '2025-12-25');

-- ============================================================
-- SEED DATA: Payments (for paid bills)
-- ============================================================
INSERT INTO payments (bill_id, receipt_no, payment_date, amount_paid, received_by, resident_signature) VALUES
(1,  'MV-2026-0151', '2026-01-15', 575.00, 'Carmen Santos', 'Justin Basco'),
(2,  'MV-2026-0152', '2026-01-11', 885.00, 'Carmen Santos', 'James Tanglao'),
(4,  'MV-2026-0153', '2026-01-19', 960.00, 'Carmen Santos', 'Janella Ashley S. Gomez'),
(5,  'MV-2026-0154', '2026-01-15', 650.00, 'Carmen Santos', 'Ronald Barangay'),
(6,  'MV-2026-0155', '2026-01-15', 320.00, 'Carmen Santos', 'Aliyah Macapagal'),
(8,  'MV-2026-0156', '2026-01-20', 850.00, 'Carmen Santos', 'Maria Santos'),
(11, 'MV-2026-0157', '2026-01-18', 800.00, 'Carmen Santos', 'Roberto Dela Cruz');
