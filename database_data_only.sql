-- =====================================================
-- COLLEGE MANAGEMENT SYSTEM - DATA ONLY MIGRATION
-- =====================================================
-- This file contains ONLY the sample data for migration
-- Use this if you already have the database schema set up
--
-- Generated: November 6, 2025
-- Version: Data Only Migration Package
--
-- Run this AFTER creating the database schema
-- =====================================================

USE college_management;

-- Disable foreign key checks for data insertion
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- SAMPLE DATA INSERTION
-- =====================================================

-- Insert roles
INSERT INTO roles (role_name) VALUES
('Admin'),
('Staff'),
('Faculty'),
('Student')
ON DUPLICATE KEY UPDATE role_name = VALUES(role_name);

-- Insert courses
INSERT INTO courses (course_code, course_name, duration_years) VALUES
('CS', 'Computer Science', 4),
('IT', 'Information Technology', 4),
('ME', 'Mechanical Engineering', 4),
('EE', 'Electrical Engineering', 4),
('CE', 'Civil Engineering', 4)
ON DUPLICATE KEY UPDATE course_name = VALUES(course_name), duration_years = VALUES(duration_years);

-- Insert batches
INSERT INTO batches (course_id, batch_year, batch_name) VALUES
(1, 2023, '2023-2027 CS Batch'),
(1, 2024, '2024-2028 CS Batch'),
(2, 2023, '2023-2027 IT Batch'),
(2, 2024, '2024-2028 IT Batch'),
(3, 2023, '2023-2027 ME Batch'),
(4, 2023, '2023-2027 EE Batch'),
(5, 2023, '2023-2027 CE Batch')
ON DUPLICATE KEY UPDATE batch_name = VALUES(batch_name);

-- Insert users with bcrypt-hashed passwords
INSERT INTO users (username, password_hash, role_id, is_active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1), -- password: password123
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1), -- password: password123
('faculty1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1), -- password: password123
('sarah.johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1), -- password: password123
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1), -- password: password123
('alice.johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1), -- password: password123
('bob.smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1), -- password: password123
('charlie.brown', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1), -- password: password123
('diana.prince', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1), -- password: password123
('eve.adams', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1) -- password: password123
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role_id = VALUES(role_id), is_active = VALUES(is_active);

-- Insert faculty
INSERT INTO faculty (user_id, full_name, email, contact_number, department, employment_role, qualification, experience_years) VALUES
(3, 'Dr. John Smith', 'faculty1@university.edu', '+1234567890', 'Computer Science', 'Associate Professor', 'PhD in Computer Science', 8),
(4, 'Dr. Sarah Johnson', 'sarah.johnson@university.edu', '+1234567891', 'Information Technology', 'Professor', 'PhD in Information Systems', 12)
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), email = VALUES(email), department = VALUES(department);

-- Insert students
INSERT INTO students (student_id, user_id, full_name, email, course_id, batch_id, academic_status, gender) VALUES
('CS2023001', 5, 'Alice Johnson', 'alice.johnson@university.edu', 1, 1, 'Active', 'Female'),
('CS2023002', 6, 'Bob Smith', 'bob.smith@university.edu', 1, 1, 'Active', 'Male'),
('IT2023001', 7, 'Charlie Brown', 'charlie.brown@university.edu', 2, 3, 'Active', 'Male'),
('ME2023001', 8, 'Diana Prince', 'diana.prince@university.edu', 3, 5, 'Active', 'Female'),
('EE2023001', 9, 'Eve Adams', 'eve.adams@university.edu', 4, 6, 'Active', 'Female')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), email = VALUES(email), course_id = VALUES(course_id), batch_id = VALUES(batch_id);

-- Insert subjects
INSERT INTO subjects (subject_code, subject_name, course_id, semester, credits, faculty_id, description) VALUES
('CS101', 'Introduction to Programming', 1, 1, 4, 1, 'Basic programming concepts using C and Python'),
('CS201', 'Data Structures', 1, 3, 4, 1, 'Fundamental data structures and algorithms'),
('IT101', 'Database Management Systems', 2, 1, 3, 2, 'Relational databases and SQL'),
('IT201', 'Web Development', 2, 3, 3, 2, 'Frontend and backend web development'),
('ME101', 'Engineering Mechanics', 3, 1, 4, NULL, 'Statics and dynamics principles'),
('EE101', 'Circuit Analysis', 4, 1, 4, NULL, 'Basic electrical circuit theory'),
('CE101', 'Structural Engineering', 5, 1, 4, NULL, 'Introduction to structural analysis')
ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name), credits = VALUES(credits), faculty_id = VALUES(faculty_id);

-- Insert tests
INSERT INTO tests (subject_id, test_name, test_type, total_marks, test_date, faculty_id) VALUES
(1, 'Programming Fundamentals Quiz', 'Quiz', 20.00, '2024-09-15', 1),
(1, 'Programming Midterm', 'Midterm', 100.00, '2024-10-20', 1),
(2, 'Data Structures Final', 'Final', 100.00, '2024-11-30', 1),
(3, 'Database Design Assignment', 'Assignment', 50.00, '2024-09-25', 2),
(4, 'Web Project', 'Project', 100.00, '2024-11-15', 2)
ON DUPLICATE KEY UPDATE total_marks = VALUES(total_marks), test_date = VALUES(test_date);

-- Insert grades
INSERT INTO grades (test_id, student_id, marks_obtained, grade_letter, remarks, graded_by) VALUES
(1, 1, 18.50, 'A', 'Excellent understanding of basics', 1),
(1, 2, 16.00, 'B+', 'Good work', 1),
(2, 1, 85.00, 'A-', 'Well done', 1),
(3, 1, 92.00, 'A', 'Outstanding performance', 1),
(4, 3, 45.00, 'A', 'Excellent database design', 2),
(5, 3, 88.00, 'A-', 'Good web application', 2)
ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained), grade_letter = VALUES(grade_letter);

-- Insert announcements
INSERT INTO announcements (title, content, announcement_type, target_audience, created_by, expires_at) VALUES
('Welcome to New Academic Year', 'Welcome students and faculty to the new academic year 2024-2025. Classes begin on September 1st.', 'General', 'All', 1, '2024-09-30'),
('Library Hours Extended', 'The library will remain open until 10 PM during exam week for additional study time.', 'Academic', 'Students', 2, '2024-12-15'),
('Faculty Development Workshop', 'Professional development workshop on modern teaching methodologies scheduled for October 15th.', 'Administrative', 'Faculty', 1, '2024-10-20')
ON DUPLICATE KEY UPDATE content = VALUES(content), expires_at = VALUES(expires_at);

-- Insert fee payments
INSERT INTO fee_payments (student_id, fee_type, amount, payment_date, payment_method, transaction_id, status) VALUES
(1, 'Tuition Fee - Semester 1', 25000.00, '2024-08-15', 'Online Banking', 'TXN20240815001', 'Paid'),
(1, 'Hostel Fee - Semester 1', 15000.00, '2024-08-15', 'Online Banking', 'TXN20240815002', 'Paid'),
(2, 'Tuition Fee - Semester 1', 25000.00, '2024-08-20', 'Cash', 'TXN20240820001', 'Paid'),
(3, 'Tuition Fee - Semester 1', 24000.00, '2024-08-18', 'Online Banking', 'TXN20240818001', 'Paid'),
(4, 'Tuition Fee - Semester 1', 26000.00, '2024-08-22', 'Cash', 'TXN20240822001', 'Paid')
ON DUPLICATE KEY UPDATE amount = VALUES(amount), status = VALUES(status);

-- Insert library transactions
INSERT INTO library_transactions (student_id, book_title, author, isbn, issue_date, due_date, return_date, status) VALUES
(1, 'Introduction to Algorithms', 'Cormen et al.', '9780262033848', '2024-09-01', '2024-09-15', '2024-09-12', 'Returned'),
(1, 'Clean Code', 'Robert C. Martin', '9780132350884', '2024-09-05', '2024-09-19', NULL, 'Issued'),
(2, 'Database System Concepts', 'Silberschatz et al.', '9780073523323', '2024-09-03', '2024-09-17', '2024-09-16', 'Returned'),
(3, 'Web Development with HTML, CSS, JavaScript', 'Jon Duckett', '9781118907443', '2024-09-07', '2024-09-21', NULL, 'Issued')
ON DUPLICATE KEY UPDATE return_date = VALUES(return_date), status = VALUES(status);

-- Insert placements
INSERT INTO placements (student_id, company_name, job_title, salary, placement_date, status, contact_person) VALUES
(1, 'Tech Solutions Inc.', 'Software Engineer', 75000.00, '2024-08-30', 'Accepted', 'HR Manager'),
(2, 'DataCorp', 'Data Analyst', 65000.00, '2024-09-05', 'Joined', 'Recruitment Team'),
(3, 'WebTech Solutions', 'Full Stack Developer', 70000.00, '2024-09-10', 'Offered', 'Technical Lead')
ON DUPLICATE KEY UPDATE salary = VALUES(salary), status = VALUES(status);

-- Insert publications
INSERT INTO publications (title, authors, publication_type, journal_name, publication_date, doi, faculty_id, student_id) VALUES
('Machine Learning Applications in Healthcare', 'Dr. John Smith, Dr. Sarah Johnson', 'Journal', 'Journal of Medical Informatics', '2024-06-15', '10.1016/j.jmedinf.2024.06.001', 1, NULL),
('Big Data Analytics for Business Intelligence', 'Dr. Sarah Johnson', 'Conference', 'International Conference on Data Science', '2024-07-20', '10.1109/ICDS.2024.00123', 2, NULL),
('IoT-based Smart Campus Solution', 'Alice Johnson, Bob Smith, Dr. John Smith', 'Conference', 'IEEE International Conference on IoT', '2024-08-10', '10.1109/IoT.2024.00234', 1, 1),
('Sustainable Engineering Practices', 'Charlie Brown', 'Journal', 'Journal of Sustainable Engineering', '2024-05-22', '10.1016/j.susteng.2024.05.001', NULL, 3)
ON DUPLICATE KEY UPDATE publication_date = VALUES(publication_date), doi = VALUES(doi);

-- Insert admissions
INSERT INTO admissions (application_number, applicant_name, email, phone, course_applied, status, entrance_score) VALUES
('APP2024001', 'Frank Miller', 'frank.miller@email.com', '+1234567892', 'Computer Science', 'approved', 85.50),
('APP2024002', 'Grace Lee', 'grace.lee@email.com', '+1234567893', 'Information Technology', 'pending', 78.20),
('APP2024003', 'Henry Wilson', 'henry.wilson@email.com', '+1234567894', 'Mechanical Engineering', 'approved', 82.10)
ON DUPLICATE KEY UPDATE status = VALUES(status), entrance_score = VALUES(entrance_score);

-- Insert library books
INSERT INTO library_books (title, author, isbn, category, publisher, publication_year, pages, copies_total, status) VALUES
('Introduction to Algorithms', 'Thomas H. Cormen', '9780262033848', 'Computer Science', 'MIT Press', 2009, 1312, 3, 'available'),
('Clean Code', 'Robert C. Martin', '9780132350884', 'Programming', 'Prentice Hall', 2008, 464, 2, 'issued'),
('Database System Concepts', 'Abraham Silberschatz', '9780073523323', 'Databases', 'McGraw-Hill', 2010, 1376, 2, 'available'),
('Web Development with HTML, CSS, JavaScript', 'Jon Duckett', '9781118907443', 'Web Development', 'Wiley', 2014, 672, 4, 'issued'),
('Engineering Mechanics: Statics', 'J.L. Meriam', '9781119723625', 'Engineering', 'Wiley', 2019, 576, 2, 'available')
ON DUPLICATE KEY UPDATE copies_total = VALUES(copies_total), status = VALUES(status);

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Migration complete message
SELECT 'Sample data migration completed successfully!' AS status;