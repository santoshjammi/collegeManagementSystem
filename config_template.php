-- =====================================================
-- TEST USER ACCOUNTS FOR COLLEGE MANAGEMENT SYSTEM
-- =====================================================
-- Run this after the main database setup
-- All test accounts use password: 'password123'

USE college_management;

-- Insert test user accounts with properly hashed passwords
-- Password hash for 'password123' using password_hash('password123', PASSWORD_DEFAULT)

INSERT INTO users (username, password_hash, role_id, is_active) VALUES
-- Admin user
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
-- Administrative Staff
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1),
-- Faculty users
('faculty1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1),
('faculty2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1),
('sarah.johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1),
-- Student users
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1),
('student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1),
('alice.johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1),
('bob.smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1);

-- Link existing faculty and students to user accounts
UPDATE faculty SET user_id = (SELECT user_id FROM users WHERE username = 'sarah.johnson') WHERE email = 'sarah.johnson@university.edu';
UPDATE faculty SET user_id = (SELECT user_id FROM users WHERE username = 'faculty1') WHERE faculty_id = 1 AND user_id IS NULL;
UPDATE faculty SET user_id = (SELECT user_id FROM users WHERE username = 'faculty2') WHERE faculty_id = 2 AND user_id IS NULL;

UPDATE students SET user_id = (SELECT user_id FROM users WHERE username = 'alice.johnson') WHERE email = 'alice.johnson@student.university.edu';
UPDATE students SET user_id = (SELECT user_id FROM users WHERE username = 'bob.smith') WHERE email = 'bob.smith@student.university.edu';
UPDATE students SET user_id = (SELECT user_id FROM users WHERE username = 'student1') WHERE student_id = 'CS2024001' AND user_id IS NULL;
UPDATE students SET user_id = (SELECT user_id FROM users WHERE username = 'student2') WHERE student_id = 'CS2024002' AND user_id IS NULL;

-- =====================================================
-- TEST LOGIN CREDENTIALS
-- =====================================================

/*
Test Accounts for Login Testing:

ADMINISTRATOR:
- Username: admin
- Password: password123
- Role: Admin (full access to all modules)

ADMINISTRATIVE STAFF:
- Username: staff1
- Password: password123
- Role: Administrative Staff (most admin functions except some restrictions)

FACULTY:
- Username: faculty1, faculty2, sarah.johnson
- Password: password123 (for all faculty accounts)
- Role: Faculty (can manage subjects, tests, grades, papers)

STUDENTS:
- Username: student1, student2, alice.johnson, bob.smith
- Password: password123 (for all student accounts)
- Role: Student (read-only access to grades, library, placements)

USAGE EXAMPLES:
1. Login as admin to test course/subject management
2. Login as faculty1 to test grade management
3. Login as student1 to test student portal features
4. Login as staff1 to test administrative functions

All accounts are active and ready for testing.
*/

-- Verify user setup
SELECT
    r.role_name,
    COUNT(u.user_id) as user_count,
    GROUP_CONCAT(u.username) as usernames
FROM users u
JOIN roles r ON u.role_id = r.role_id
WHERE u.is_active = 1
GROUP BY r.role_id, r.role_name
ORDER BY r.role_id;</content>
<parameter name="filePath">/Users/kgt/Desktop/Projects/PHP/CollegeManagementSystem/test_users_setup.sql