-- Fix for role-based access control
-- Run this in your MySQL database to ensure proper setup

-- Ensure roles exist
INSERT INTO roles (role_name) VALUES
('Admin'),
('Administrative Staff'),
('Faculty'),
('Student')
ON DUPLICATE KEY UPDATE role_name = role_name;

-- Create test users with proper role assignments
-- Admin user
INSERT INTO users (username, password_hash, role_id, is_active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1)
ON DUPLICATE KEY UPDATE
password_hash = VALUES(password_hash),
role_id = VALUES(role_id),
is_active = VALUES(is_active);

-- Administrative Staff user (role_id should be 2 for 'Administrative Staff')
INSERT INTO users (username, password_hash, role_id, is_active) VALUES
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1)
ON DUPLICATE KEY UPDATE
password_hash = VALUES(password_hash),
role_id = VALUES(role_id),
is_active = VALUES(is_active);

-- Faculty user
INSERT INTO users (username, password_hash, role_id, is_active) VALUES
('faculty1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1)
ON DUPLICATE KEY UPDATE
password_hash = VALUES(password_hash),
role_id = VALUES(role_id),
is_active = VALUES(is_active);

-- Student user
INSERT INTO users (username, password_hash, role_id, is_active) VALUES
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1)
ON DUPLICATE KEY UPDATE
password_hash = VALUES(password_hash),
role_id = VALUES(role_id),
is_active = VALUES(is_active);

-- Note: The password hash above is for 'password123' (not the specific passwords mentioned)
-- You may need to update these with proper hashes for your desired passwords