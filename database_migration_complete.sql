-- =====================================================
-- COLLEGE MANAGEMENT SYSTEM - COMPLETE DATABASE MIGRATION
-- =====================================================
-- This file contains the complete database schema and sample data
-- for migrating the College Management System to any MySQL/MariaDB server
--
-- Generated: November 6, 2025
-- Version: Complete Migration Package
--
-- TABLES INCLUDED:
-- - Core: roles, users, courses, batches, students, faculty
-- - Academic: subjects, tests, grades, announcements
-- - Administrative: fee_payments, library_transactions, placements
-- - Research: publications
--
-- Run this script on your target MySQL/MariaDB server
-- =====================================================

-- Create database with proper charset
CREATE DATABASE IF NOT EXISTS college_management
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE college_management;

-- Disable foreign key checks for setup
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- CORE SYSTEM TABLES
-- =====================================================

-- 1. ROLES Table
CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 2. USERS Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

-- 3. COURSES Table
CREATE TABLE IF NOT EXISTS courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(10) NOT NULL UNIQUE,
    course_name VARCHAR(150) NOT NULL,
    duration_years INT
) ENGINE=InnoDB;

-- 4. BATCHES Table
CREATE TABLE IF NOT EXISTS batches (
    batch_id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    batch_year YEAR NOT NULL,
    batch_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
) ENGINE=InnoDB;

-- 5. STUDENTS Table
CREATE TABLE IF NOT EXISTS students (
    student_pk_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NULL UNIQUE,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(100) UNIQUE,
    course_id INT NOT NULL,
    batch_id INT NOT NULL,
    academic_status ENUM('Active', 'On Leave', 'Suspended', 'Graduated') DEFAULT 'Active',
    profile_picture VARCHAR(255) NULL,
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id)
) ENGINE=InnoDB;

-- 6. FACULTY Table
CREATE TABLE IF NOT EXISTS faculty (
    faculty_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL UNIQUE,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(100) UNIQUE,
    contact_number VARCHAR(20),
    department VARCHAR(100),
    employment_role VARCHAR(100),
    qualification VARCHAR(200),
    experience_years INT,
    profile_picture VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- =====================================================
-- ACADEMIC TABLES
-- =====================================================

-- 7. SUBJECTS Table
CREATE TABLE IF NOT EXISTS subjects (
    subject_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(10) NOT NULL UNIQUE,
    subject_name VARCHAR(150) NOT NULL,
    course_id INT NOT NULL,
    semester INT NOT NULL,
    credits INT DEFAULT 3,
    faculty_id INT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id)
) ENGINE=InnoDB;

-- 8. TESTS Table
CREATE TABLE IF NOT EXISTS tests (
    test_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    test_type ENUM('Quiz', 'Midterm', 'Final', 'Assignment', 'Project') DEFAULT 'Quiz',
    total_marks DECIMAL(5,2) NOT NULL,
    test_date DATE NOT NULL,
    faculty_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id)
) ENGINE=InnoDB;

-- 9. GRADES Table
CREATE TABLE IF NOT EXISTS grades (
    grade_id INT PRIMARY KEY AUTO_INCREMENT,
    test_id INT NOT NULL,
    student_id INT NOT NULL,
    marks_obtained DECIMAL(5,2) NOT NULL,
    grade_letter CHAR(2),
    remarks TEXT,
    graded_by INT NOT NULL,
    graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(test_id),
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id),
    FOREIGN KEY (graded_by) REFERENCES faculty(faculty_id)
) ENGINE=InnoDB;

-- =====================================================
-- ADMINISTRATIVE TABLES
-- =====================================================

-- 10. ANNOUNCEMENTS Table
CREATE TABLE IF NOT EXISTS announcements (
    announcement_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    announcement_type ENUM('General', 'Academic', 'Administrative', 'Urgent') DEFAULT 'General',
    target_audience ENUM('All', 'Students', 'Faculty', 'Staff') DEFAULT 'All',
    created_by INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATE NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 11. FEE_PAYMENTS Table
CREATE TABLE IF NOT EXISTS fee_payments (
    fee_payment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    status ENUM('Paid', 'Pending', 'Failed') DEFAULT 'Paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id)
) ENGINE=InnoDB;

-- 12. LIBRARY_TRANSACTIONS Table
CREATE TABLE IF NOT EXISTS library_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    book_title VARCHAR(255) NOT NULL,
    author VARCHAR(150),
    isbn VARCHAR(30),
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('Issued', 'Returned', 'Overdue') DEFAULT 'Issued',
    fine_amount DECIMAL(8,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id)
) ENGINE=InnoDB;

-- 13. PLACEMENTS Table
CREATE TABLE IF NOT EXISTS placements (
    placement_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    company_name VARCHAR(150) NOT NULL,
    job_title VARCHAR(100) NOT NULL,
    salary DECIMAL(12,2),
    placement_date DATE NOT NULL,
    status ENUM('Offered', 'Accepted', 'Rejected', 'Joined') DEFAULT 'Offered',
    contact_person VARCHAR(100),
    contact_email VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id)
) ENGINE=InnoDB;

-- =====================================================
-- RESEARCH TABLES
-- =====================================================

-- 14. PUBLICATIONS Table
CREATE TABLE IF NOT EXISTS publications (
    publication_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(300) NOT NULL,
    authors TEXT NOT NULL,
    publication_type ENUM('Journal', 'Conference', 'Book', 'Thesis', 'Other') DEFAULT 'Journal',
    journal_name VARCHAR(200),
    publication_date DATE,
    doi VARCHAR(100),
    abstract TEXT,
    keywords VARCHAR(255),
    faculty_id INT NULL,
    student_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id)
) ENGINE=InnoDB;

-- =====================================================
-- ADDITIONAL TABLES (Enhanced Features)
-- =====================================================

-- 15. ADMISSIONS Table
CREATE TABLE IF NOT EXISTS admissions (
    admission_pk_id INT PRIMARY KEY AUTO_INCREMENT,
    application_number VARCHAR(50) NOT NULL UNIQUE,
    applicant_name VARCHAR(200) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    course_applied VARCHAR(150),
    application_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('pending', 'approved', 'rejected', 'waitlisted') DEFAULT 'pending',
    entrance_score DECIMAL(5,2) NULL,
    interview_score DECIMAL(5,2) NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 16. LIBRARY_BOOKS Table
CREATE TABLE IF NOT EXISTS library_books (
    book_pk_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150),
    isbn VARCHAR(30),
    category VARCHAR(100),
    publisher VARCHAR(150),
    publication_year YEAR,
    pages INT,
    copies_total INT DEFAULT 1,
    status ENUM('available', 'issued', 'damaged', 'lost') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

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

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Create indexes for better performance
CREATE INDEX idx_users_role_id ON users(role_id);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_students_user_id ON students(user_id);
CREATE INDEX idx_students_course_id ON students(course_id);
CREATE INDEX idx_students_batch_id ON students(batch_id);
CREATE INDEX idx_faculty_user_id ON faculty(user_id);
CREATE INDEX idx_subjects_course_id ON subjects(course_id);
CREATE INDEX idx_subjects_faculty_id ON subjects(faculty_id);
CREATE INDEX idx_tests_subject_id ON tests(subject_id);
CREATE INDEX idx_tests_faculty_id ON tests(faculty_id);
CREATE INDEX idx_grades_test_id ON grades(test_id);
CREATE INDEX idx_grades_student_id ON grades(student_id);
CREATE INDEX idx_announcements_created_by ON announcements(created_by);
CREATE INDEX idx_fee_payments_student_id ON fee_payments(student_id);
CREATE INDEX idx_library_transactions_student_id ON library_transactions(student_id);
CREATE INDEX idx_placements_student_id ON placements(student_id);
CREATE INDEX idx_publications_faculty_id ON publications(faculty_id);
CREATE INDEX idx_publications_student_id ON publications(student_id);

-- =====================================================
-- ENABLE FOREIGN KEY CHECKS
-- =====================================================

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

SELECT 'College Management System database migration completed successfully!' AS status;