-- =============================================
-- DATABASE: estatehub_db
-- Description: Complete database for EstateHub 
-- Real Estate Platform (FULLY CORRECTED)
-- =============================================

DROP DATABASE IF EXISTS estatehub_db;
CREATE DATABASE estatehub_db;
USE estatehub_db;

-- =============================================
-- TABLE: users (FIXED - Added missing columns)
-- =============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    profile_pic VARCHAR(255) DEFAULT NULL,
    bio TEXT,
    user_type ENUM('admin', 'seller', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- =============================================
-- TABLE: properties
-- =============================================
CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    property_type ENUM('House','Apartment','Plot','Commercial','Farm House','Portion','Villa','Penthouse') DEFAULT 'House',
    purpose ENUM('Sale','Rent') DEFAULT 'Sale',
    price DECIMAL(15,2) NOT NULL,
    location VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    area_size VARCHAR(50),
    bedrooms INT DEFAULT 0,
    bathrooms INT DEFAULT 0,
    image_main VARCHAR(255),
    images TEXT,
    status ENUM('Active','Pending','Sold','Rented') DEFAULT 'Active',
    views INT DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- TABLE: wishlist (FIXED - Single definition)
-- =============================================
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (user_id, property_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- =============================================
-- TABLE: messages
-- =============================================
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    property_id INT,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
);

-- =============================================
-- TABLE: inquiries
-- =============================================
CREATE TABLE inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_replied BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABLE: property_views
-- =============================================
CREATE TABLE property_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45),
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- =============================================
-- TABLE: admin_settings
-- =============================================
CREATE TABLE admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- TABLE: settings
-- =============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- INSERT ADMIN USER (Password: admin123)
-- =============================================
INSERT INTO users (id, full_name, email, password, phone, user_type, status) 
VALUES (1, 'Admin User', 'admin@estatehub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+92 300 1234567', 'admin', 'active')
ON DUPLICATE KEY UPDATE id=id;

-- =============================================
-- INSERT SELLER USERS (Password: password123)
-- =============================================
INSERT INTO users (id, full_name, email, password, phone, bio, user_type, status) 
VALUES 
(2, 'Ali Raza', 'ali@estatehub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+92 300 1234567', 'Real estate agent and property dealer', 'seller', 'active'),
(3, 'Ahmed Malik', 'ahmed@estatehub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+92 300 9876543', 'Real estate developer', 'seller', 'active')
ON DUPLICATE KEY UPDATE id=id;

-- =============================================
-- INSERT BUYER/USER (Password: password123)
-- =============================================
INSERT INTO users (id, full_name, email, password, phone, bio, user_type, status) 
VALUES 
(4, 'Sara Khan', 'sara@estatehub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+92 300 7654321', 'Property investor and consultant', 'user', 'active'),
(5, 'John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+92 300 1111111', 'First time home buyer', 'user', 'active')
ON DUPLICATE KEY UPDATE id=id;

-- =============================================
-- INSERT BASE PROPERTIES (IDs 1-17)
-- =============================================
INSERT INTO properties (id, user_id, title, description, property_type, purpose, price, location, city, area_size, bedrooms, bathrooms, status, views, featured) VALUES
(1, 2, 'Modern 10 Marla House', 'Beautiful modern family home located in the heart of DHA Lahore. Contemporary design, spacious layout.', 'House', 'Sale', 5200000, 'DHA Phase 6', 'Lahore', '10 Marla', 5, 6, 'Active', 320, TRUE),
(2, 2, 'Luxury Apartment', 'Luxury apartment in Bahria Town with all modern amenities', 'Apartment', 'Rent', 120000, 'Bahria Town', 'Karachi', '2000 Sqft', 3, 3, 'Active', 270, TRUE),
(3, 3, 'Designer Villa', 'Premium designer villa in F-10 sector with stunning views', 'Villa', 'Sale', 7800000, 'F-10', 'Islamabad', '1 Kanal', 6, 7, 'Active', 140, TRUE),
(4, 2, 'Double Kitchen House', 'Beautiful house with double kitchen setup. Perfect for joint family system.', 'House', 'Sale', 3450000, 'Model Town', 'Lahore', '8 Marla', 4, 4, 'Active', 98, FALSE),
(5, 3, 'Luxury Penthouse', 'Luxury penthouse with breathtaking sea views. Premium location with all modern amenities.', 'Penthouse', 'Rent', 250000, 'Clifton', 'Karachi', '3500 Sqft', 4, 5, 'Active', 120, FALSE),
(6, 2, 'Modern Farm House', 'Beautiful farm house with swimming pool and lush garden.', 'Farm House', 'Sale', 9500000, 'Gulberg Greens', 'Islamabad', '2 Kanal', 5, 6, 'Active', 85, FALSE),
(7, 3, 'Commercial Plaza', 'Prime commercial plaza in the heart of Blue Area. Perfect for business investment.', 'Commercial', 'Sale', 25500000, 'Blue Area', 'Islamabad', '5000 Sqft', 0, 0, 'Active', 62, FALSE),
(8, 2, 'Cozy Studio Apartment', 'Cozy studio apartment, fully furnished and ready to move in.', 'Apartment', 'Rent', 45000, 'Gulberg', 'Lahore', '1200 Sqft', 2, 2, 'Active', 45, FALSE),
(9, 2, 'Bahria Town Luxury Home', 'Stunning 1 Kanal house in Bahria Town Lahore with modern amenities', 'House', 'Sale', 8500000, 'Bahria Town', 'Lahore', '1 Kanal', 5, 6, 'Active', 245, TRUE),
(10, 3, 'Johar Town Apartment', 'Affordable apartment in Johar Town, ideal for families', 'Apartment', 'Rent', 65000, 'Johar Town', 'Lahore', '1500 Sqft', 3, 2, 'Active', 180, FALSE),
(11, 2, 'DHA Commercial Plaza', 'Prime commercial property in DHA Phase 5', 'Commercial', 'Sale', 18000000, 'DHA Phase 5', 'Lahore', '3000 Sqft', 0, 2, 'Active', 95, FALSE),
(12, 3, 'Clifton Sea View Apartment', 'Luxury apartment with stunning sea views in Clifton', 'Apartment', 'Sale', 12000000, 'Clifton', 'Karachi', '2500 Sqft', 4, 4, 'Active', 310, TRUE),
(13, 2, 'Gulshan-e-Iqbal House', 'Well-maintained house in peaceful neighborhood', 'House', 'Rent', 85000, 'Gulshan-e-Iqbal', 'Karachi', '1800 Sqft', 4, 3, 'Active', 165, FALSE),
(14, 3, 'DHA Karachi Villa', 'Premium villa with private pool in DHA', 'Villa', 'Sale', 22000000, 'DHA Phase 8', 'Karachi', '2 Kanal', 6, 7, 'Active', 280, TRUE),
(15, 2, 'F-7 Sector House', 'Diplomatic enclave house with beautiful garden', 'House', 'Sale', 15000000, 'F-7', 'Islamabad', '1 Kanal', 5, 6, 'Active', 340, TRUE),
(16, 3, 'E-11 Apartment', 'Modern apartment near Centaurus Mall', 'Apartment', 'Rent', 95000, 'E-11', 'Islamabad', '2000 Sqft', 3, 3, 'Active', 200, FALSE),
(17, 2, 'Gulberg Greens Farm', 'Beautiful farmhouse with orchard', 'Farm House', 'Sale', 12000000, 'Gulberg Greens', 'Islamabad', '4 Kanal', 6, 7, 'Active', 150, FALSE);

-- =============================================
-- INSERT ADDITIONAL PROPERTIES (More cities)
-- =============================================
INSERT INTO properties (user_id, title, description, property_type, purpose, price, location, city, area_size, bedrooms, bathrooms, status, views, featured) VALUES

-- Rawalpindi
(3, 'Saddar Commercial Shop', 'Prime location shop in Saddar Bazaar', 'Commercial', 'Sale', 5500000, 'Saddar', 'Rawalpindi', '500 Sqft', 0, 1, 'Active', 120, FALSE),
(2, 'Bahria Town Phase 8 House', 'Beautiful 10 Marla house in Bahria Town', 'House', 'Sale', 7200000, 'Bahria Town Phase 8', 'Rawalpindi', '10 Marla', 4, 4, 'Active', 190, FALSE),

-- Faisalabad
(3, 'Canal Road House', 'Spacious house near Canal Road', 'House', 'Rent', 55000, 'Canal Road', 'Faisalabad', '10 Marla', 4, 3, 'Active', 110, FALSE),
(2, 'People Colony Plot', 'Residential plot in prime location', 'Plot', 'Sale', 3500000, 'People Colony', 'Faisalabad', '1 Kanal', 0, 0, 'Active', 75, FALSE),

-- Multan
(3, 'Bosan Road Apartment', 'Newly built apartment near BZU', 'Apartment', 'Rent', 35000, 'Bosan Road', 'Multan', '1200 Sqft', 2, 2, 'Active', 85, FALSE),
(2, 'Cantt Area House', 'Beautiful house in Multan Cantt', 'House', 'Sale', 4800000, 'Cantt', 'Multan', '8 Marla', 3, 3, 'Active', 95, FALSE),

-- Peshawar
(3, 'Hayatabad Phase 5 House', 'Modern house in secure gated community', 'House', 'Sale', 6500000, 'Hayatabad', 'Peshawar', '1 Kanal', 5, 5, 'Active', 140, FALSE),
(2, 'University Town Apartment', 'Affordable apartment near University', 'Apartment', 'Rent', 30000, 'University Town', 'Peshawar', '900 Sqft', 2, 1, 'Active', 70, FALSE),

-- Quetta
(3, 'Jinnah Road Shop', 'Commercial shop in busy area', 'Commercial', 'Sale', 4200000, 'Jinnah Road', 'Quetta', '600 Sqft', 0, 1, 'Active', 55, FALSE),
(2, 'Cantt Area Plot', 'Residential plot in secure area', 'Plot', 'Sale', 2800000, 'Cantt', 'Quetta', '10 Marla', 0, 0, 'Active', 40, FALSE);

-- =============================================
-- ADD ALL PROPERTIES TO WISHLIST FOR TESTING
-- =============================================

-- First, clear existing wishlist data (optional - uncomment if needed)
-- DELETE FROM wishlist;

-- =============================================
-- For User ID 4 (Sara Khan) - Add ALL 17 base properties
-- =============================================
INSERT INTO wishlist (user_id, property_id, created_at) VALUES
(4, 1, NOW()),
(4, 2, NOW()),
(4, 3, NOW()),
(4, 4, NOW()),
(4, 5, NOW()),
(4, 6, NOW()),
(4, 7, NOW()),
(4, 8, NOW()),
(4, 9, NOW()),
(4, 10, NOW()),
(4, 11, NOW()),
(4, 12, NOW()),
(4, 13, NOW()),
(4, 14, NOW()),
(4, 15, NOW()),
(4, 16, NOW()),
(4, 17, NOW())
ON DUPLICATE KEY UPDATE created_at = NOW();

-- =============================================
-- For User ID 5 (John Doe) - Add ALL 17 base properties
-- =============================================
INSERT INTO wishlist (user_id, property_id, created_at) VALUES
(5, 1, NOW()),
(5, 2, NOW()),
(5, 3, NOW()),
(5, 4, NOW()),
(5, 5, NOW()),
(5, 6, NOW()),
(5, 7, NOW()),
(5, 8, NOW()),
(5, 9, NOW()),
(5, 10, NOW()),
(5, 11, NOW()),
(5, 12, NOW()),
(5, 13, NOW()),
(5, 14, NOW()),
(5, 15, NOW()),
(5, 16, NOW()),
(5, 17, NOW())
ON DUPLICATE KEY UPDATE created_at = NOW();

-- =============================================
-- For User ID 2 (Ali Raza - Seller)
-- =============================================
INSERT INTO wishlist (user_id, property_id, created_at) VALUES
(2, 1, NOW()),
(2, 2, NOW()),
(2, 3, NOW()),
(2, 4, NOW()),
(2, 5, NOW()),
(2, 6, NOW()),
(2, 7, NOW()),
(2, 8, NOW()),
(2, 9, NOW()),
(2, 10, NOW()),
(2, 11, NOW()),
(2, 12, NOW()),
(2, 13, NOW()),
(2, 14, NOW()),
(2, 15, NOW()),
(2, 16, NOW()),
(2, 17, NOW())
ON DUPLICATE KEY UPDATE created_at = NOW();

-- =============================================
-- For User ID 3 (Ahmed Malik - Seller)
-- =============================================
INSERT INTO wishlist (user_id, property_id, created_at) VALUES
(3, 1, NOW()),
(3, 2, NOW()),
(3, 3, NOW()),
(3, 4, NOW()),
(3, 5, NOW()),
(3, 6, NOW()),
(3, 7, NOW()),
(3, 8, NOW()),
(3, 9, NOW()),
(3, 10, NOW()),
(3, 11, NOW()),
(3, 12, NOW()),
(3, 13, NOW()),
(3, 14, NOW()),
(3, 15, NOW()),
(3, 16, NOW()),
(3, 17, NOW())
ON DUPLICATE KEY UPDATE created_at = NOW();

-- =============================================
-- VERIFICATION QUERIES
-- =============================================

-- Check total wishlist items per user
SELECT '=== WISHLIST COUNT PER USER ===' as '';
SELECT 
    u.id as user_id,
    u.full_name,
    COUNT(w.id) as wishlist_count
FROM users u
LEFT JOIN wishlist w ON u.id = w.user_id
GROUP BY u.id, u.full_name
ORDER BY u.id;

-- Show all wishlist items with property details
SELECT '=== ALL WISHLIST ITEMS WITH DETAILS ===' as '';
SELECT 
    w.user_id,
    u.full_name as user_name,
    w.property_id,
    p.title as property_title,
    p.city,
    p.price,
    p.purpose,
    w.created_at
FROM wishlist w
JOIN users u ON w.user_id = u.id
JOIN properties p ON w.property_id = p.id
ORDER BY w.user_id, w.property_id;

-- Check for any missing properties in wishlist
SELECT '=== PROPERTIES NOT IN ANY WISHLIST ===' as '';
SELECT p.id, p.title, p.city
FROM properties p
WHERE p.id NOT IN (SELECT DISTINCT property_id FROM wishlist)
AND p.status = 'Active';

-- =============================================
-- OPTIONAL: Add ALL additional properties (18+)
-- =============================================
-- If you want to add ALL properties including the extra ones from Rawalpindi, Faisalabad, etc.
-- First, find the maximum property ID
SELECT '=== MAX PROPERTY ID ===' as '';
SELECT MAX(id) as max_property_id FROM properties;

-- Then add all properties (including new ones) for User 4
-- Replace 50 with your actual max property ID
INSERT INTO wishlist (user_id, property_id, created_at)
SELECT 4, id, NOW()
FROM properties
WHERE id NOT IN (SELECT property_id FROM wishlist WHERE user_id = 4)
AND status = 'Active'
ON DUPLICATE KEY UPDATE created_at = NOW();

-- =============================================
-- CLEANUP OPTIONS
-- =============================================

-- Remove duplicates (if any)
DELETE w1 FROM wishlist w1
INNER JOIN wishlist w2 
WHERE w1.id > w2.id 
AND w1.user_id = w2.user_id 
AND w1.property_id = w2.property_id;

-- Show final summary
SELECT '=== FINAL SUMMARY ===' as '';
SELECT 
    COUNT(DISTINCT user_id) as users_with_wishlist,
    COUNT(*) as total_wishlist_items,
    COUNT(DISTINCT property_id) as unique_properties_in_wishlist
FROM wishlist;

-- =============================================
-- INSERT MESSAGES
-- =============================================
INSERT INTO messages (sender_id, receiver_id, property_id, message, is_read) VALUES
(4, 2, 1, 'Is this property still available?', TRUE),
(2, 4, 1, 'Yes, it is available. Would you like to schedule a visit?', TRUE),
(4, 3, 2, 'Can we schedule a visit?', FALSE),
(3, 4, 2, 'Tomorrow at 3 PM works for me.', FALSE),
(4, 2, 3, 'What is the final price?', TRUE),
(2, 4, 3, 'The price is negotiable. Please call me to discuss.', TRUE);

-- =============================================
-- INSERT INQUIRIES
-- =============================================
INSERT INTO inquiries (name, email, subject, message) VALUES
('Umar Qureshi', 'umar@example.com', 'Property Inquiry', 'I am interested in the Modern House. Please share more details.'),
('Sana Malik', 'sana@example.com', 'Visit Request', 'Can I visit the Luxury Apartment this weekend?'),
('Hamza Ahmed', 'hamza@example.com', 'Price Negotiation', 'Is the price negotiable for Designer Villa?');

-- =============================================
-- INSERT ADMIN SETTINGS
-- =============================================
INSERT INTO admin_settings (setting_key, setting_value) VALUES
('site_name', 'EstateHub'),
('site_email', 'info@estatehub.com'),
('site_phone', '+92 300 1234567'),
('site_address', 'U2 Villa Boulevard, Lahore, Pakistan'),
('admin_email', 'admin@estatehub.com'),
('support_email', 'support@estatehub.com')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- =============================================
-- INSERT DEFAULT SETTINGS
-- =============================================
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'EstateHub'),
('admin_email', 'admin@estatehub.com'),
('currency', 'PKR'),
('timezone', 'Asia/Karachi'),
('phone', '+92 300 1234567'),
('address', '123 Real Estate Ave, Lahore, Pakistan'),
('footer_text', '© 2024 EstateHub. All rights reserved.'),
('email_notifications', 'enabled'),
('new_user_alerts', 'yes'),
('property_alerts', 'yes')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- =============================================
-- CREATE INDEXES FOR PERFORMANCE
-- =============================================
CREATE INDEX idx_properties_city_status ON properties(city, status);
CREATE INDEX idx_properties_price_purpose ON properties(price, purpose);
CREATE INDEX idx_messages_users ON messages(sender_id, receiver_id);
CREATE INDEX idx_wishlist_user ON wishlist(user_id);
CREATE INDEX idx_inquiries_created ON inquiries(created_at);
CREATE INDEX idx_users_type ON users(user_type);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_properties_user ON properties(user_id);
CREATE INDEX idx_wishlist_property ON wishlist(property_id);

-- =============================================
-- VERIFICATION QUERIES
-- =============================================

-- Check if properties exist in database
SELECT '=== PROPERTIES CHECK ===' as '';
SELECT id, title, city, price, status FROM properties WHERE id IN (1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17);

-- Check wishlist foreign key relationship
SELECT '=== WISHLIST RELATIONSHIP CHECK ===' as '';
SELECT w.id, w.user_id, w.property_id, p.title as property_title
FROM wishlist w 
LEFT JOIN properties p ON w.property_id = p.id 
LIMIT 10;

-- Fix any missing properties in wishlist (orphan records)
DELETE FROM wishlist WHERE property_id NOT IN (SELECT id FROM properties);
SELECT '=== CLEANED WISHLIST ===' as '';
SELECT COUNT(*) as remaining_wishlist_items FROM wishlist;

-- Show all users
SELECT '=== USERS ===' as '';
SELECT id, full_name, email, user_type, status FROM users;

-- Show property count
SELECT '=== PROPERTY STATISTICS ===' as '';
SELECT COUNT(*) as total_properties FROM properties WHERE status = 'Active';
SELECT city, COUNT(*) as count FROM properties WHERE status = 'Active' GROUP BY city ORDER BY count DESC LIMIT 10;

-- Show wishlist items with property details
SELECT '=== WISHLIST WITH DETAILS ===' as '';
SELECT 
    w.id,
    u.full_name as user_name,
    p.title as property_title,
    p.city,
    p.price,
    w.created_at
FROM wishlist w
JOIN users u ON w.user_id = u.id
JOIN properties p ON w.property_id = p.id;

-- =============================================
-- LOGIN CREDENTIALS
-- =============================================
SELECT '=== LOGIN CREDENTIALS ===' as '';
SELECT 'Admin: admin@estatehub.com / admin123' as 'Admin Login';
SELECT 'Seller: ali@estatehub.com / password123' as 'Seller Login 1';
SELECT 'Seller: ahmed@estatehub.com / password123' as 'Seller Login 2';
SELECT 'Buyer: sara@estatehub.com / password123' as 'Buyer Login 1';
SELECT 'Buyer: john@example.com / password123' as 'Buyer Login 2';

-- =============================================
-- FINAL VERIFICATION
-- =============================================
SELECT '=== DATABASE SETUP COMPLETE ===' as '';
SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM properties) as total_properties,
    (SELECT COUNT(*) FROM wishlist) as total_wishlist_items,
    (SELECT COUNT(*) FROM messages) as total_messages;

    -- =========================================================
-- EstateHub — optional schema updates for User Management
-- Run only the statements you need. Nothing here deletes data.
-- =========================================================

-- 1) Add "city" and "last_login" columns if you don't already have them.
--    The admin page works fine without these (fields just show
--    "Not provided" / "Never logged in"), so this step is optional.
ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL AFTER phone;
ALTER TABLE users ADD COLUMN last_login DATETIME NULL AFTER city;

-- 2) If your "status" column is a MySQL ENUM (not VARCHAR), you must
--    widen it to allow the new 'deleted' value. Skip this if status
--    is already VARCHAR — no change needed in that case.
ALTER TABLE users MODIFY status ENUM('active','pending','blocked','deleted') NOT NULL DEFAULT 'pending';

-- 3) If your "user_type" column is ENUM('admin','seller','user'), no
--    change is required — the app already uses these exact 3 values
--    ('user' is displayed as "Buyer" in the interface).

-- 4) Recommended index to keep search fast as your users table grows.
ALTER TABLE users ADD INDEX idx_status (status);
ALTER TABLE users ADD INDEX idx_user_type (user_type);

-- =============================================
-- END OF DATABASE SQL FILE
-- =============================================