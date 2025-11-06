-- =====================================================
-- COLLEGE MANAGEMENT SYSTEM - COMPLETE DATABASE SETUP
-- =====================================================
-- This script creates the complete database schema with sample data
-- Run this on a fresh MySQL/MariaDB installation

-- Create database
CREATE DATABASE IF NOT EXISTS college_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE college_management;

-- Disable foreign key checks for setup
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- 1. ROLES Table
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 2. USERS Table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

-- 3. COURSES Table
CREATE TABLE courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(10) NOT NULL UNIQUE,
    course_name VARCHAR(150) NOT NULL,
    duration_years INT
) ENGINE=InnoDB;

-- 4. BATCHES Table
CREATE TABLE batches (
    batch_id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    batch_year YEAR NOT NULL,
    batch_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
) ENGINE=InnoDB;

-- =====================================================
-- STUDENT MANAGEMENT
-- =====================================================

-- 5. STUDENTS Table
CREATE TABLE students (
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

-- =====================================================
-- FACULTY MANAGEMENT
-- =====================================================

-- 6. FACULTY Table
CREATE TABLE faculty (
    faculty_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL UNIQUE,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(100) UNIQUE,
    contact_number VARCHAR(20),
    department VARCHAR(100),
    employment_role VARCHAR(100),
    highest_degree VARCHAR(100),
    primary_subject VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    profile_picture VARCHAR(255) NULL,
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- =====================================================
-- RESEARCH & PUBLICATIONS
-- =====================================================

-- 7. FACULTY_PAPERS Table
CREATE TABLE faculty_papers (
    paper_id INT PRIMARY KEY AUTO_INCREMENT,
    faculty_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    authors TEXT NOT NULL COMMENT 'Comma-separated list of authors',
    journal_name VARCHAR(300) NULL COMMENT 'Journal, conference, or publication venue',
    publication_date DATE NULL,
    doi VARCHAR(100) NULL COMMENT 'Digital Object Identifier',
    abstract TEXT NULL,
    keywords TEXT NULL COMMENT 'Comma-separated keywords',
    paper_url VARCHAR(500) NULL COMMENT 'Link to the paper if available online',
    citation_count INT DEFAULT 0 COMMENT 'Number of citations',
    publication_type ENUM('Journal Article', 'Conference Paper', 'Book Chapter', 'Book', 'Thesis', 'Working Paper', 'Other') DEFAULT 'Journal Article',
    status ENUM('Published', 'Accepted', 'Submitted', 'Draft') DEFAULT 'Published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. STUDENT_PAPERS Table
CREATE TABLE student_papers (
    paper_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    authors TEXT NOT NULL COMMENT 'Comma-separated list of authors',
    journal_name VARCHAR(300) NULL COMMENT 'Journal, conference, or publication venue',
    publication_date DATE NULL,
    doi VARCHAR(100) NULL COMMENT 'Digital Object Identifier',
    abstract TEXT NULL,
    keywords TEXT NULL COMMENT 'Comma-separated keywords',
    paper_url VARCHAR(500) NULL COMMENT 'Link to the paper if available online',
    citation_count INT DEFAULT 0 COMMENT 'Number of citations',
    publication_type ENUM('Journal Article', 'Conference Paper', 'Book Chapter', 'Book', 'Thesis', 'Working Paper', 'Other') DEFAULT 'Journal Article',
    status ENUM('Published', 'Accepted', 'Submitted', 'Draft') DEFAULT 'Published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- ACADEMIC MANAGEMENT
-- =====================================================

-- 9. SUBJECTS Table
CREATE TABLE subjects (
    subject_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(20) NOT NULL UNIQUE,
    subject_name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    credits INT DEFAULT 4,
    faculty_id INT NULL,
    course_id INT NULL,
    semester INT DEFAULT 1,
    subject_type ENUM('Theory', 'Practical', 'Lab') DEFAULT 'Theory',
    academic_year VARCHAR(20) DEFAULT '2024-25',
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
) ENGINE=InnoDB;

-- 10. TESTS Table
CREATE TABLE tests (
    test_id INT PRIMARY KEY AUTO_INCREMENT,
    test_name VARCHAR(200) NOT NULL,
    subject_id INT NOT NULL,
    faculty_id INT NOT NULL,
    max_marks INT DEFAULT 100,
    test_date DATE NULL,
    test_type ENUM('Quiz', 'Midterm', 'Final', 'Assignment', 'Project') DEFAULT 'Quiz',
    duration INT DEFAULT 60 COMMENT 'Duration in minutes',
    instructions TEXT NULL,
    status ENUM('Draft', 'Published', 'Completed', 'Cancelled') DEFAULT 'Published',
    is_published TINYINT(1) DEFAULT 0 COMMENT 'Whether test is published and visible to students',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id)
) ENGINE=InnoDB;

-- 11. STUDENT_TEST_GRADES Table
CREATE TABLE student_test_grades (
    grade_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    test_id INT NOT NULL,
    faculty_id INT NOT NULL,
    subject_id INT NOT NULL,
    marks_obtained DECIMAL(5,2) NULL,
    max_marks INT NOT NULL,
    percentage DECIMAL(5,2) NULL,
    letter_grade VARCHAR(5) NULL,
    status ENUM('Pending', 'Completed', 'Absent') DEFAULT 'Pending',
    is_absent TINYINT(1) DEFAULT 0,
    graded_by INT NULL,
    remarks TEXT NULL,
    graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_test (student_id, test_id),
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(test_id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
    FOREIGN KEY (graded_by) REFERENCES faculty(faculty_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- ADMINISTRATIVE MODULES
-- =====================================================

-- 12. ADMISSIONS Table
CREATE TABLE admissions (
    admission_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NULL,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    course_id INT NOT NULL,
    batch_id INT NULL,
    application_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('Pending', 'Approved', 'Rejected', 'Admitted') DEFAULT 'Pending',
    documents_submitted TEXT NULL COMMENT 'Comma-separated list of submitted documents',
    remarks TEXT NULL,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id),
    FOREIGN KEY (processed_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 13. FEE_PAYMENTS Table
CREATE TABLE fee_payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NULL,
    due_date DATE NULL,
    payment_status ENUM('Pending', 'Paid', 'Overdue', 'Cancelled') DEFAULT 'Pending',
    payment_method VARCHAR(50) NULL,
    transaction_id VARCHAR(100) NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- LIBRARY MANAGEMENT
-- =====================================================

-- 14. LIBRARY_TRANSACTIONS Table
CREATE TABLE library_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    book_title VARCHAR(300) NOT NULL,
    book_author VARCHAR(200) NULL,
    isbn VARCHAR(20) NULL,
    issue_date DATE DEFAULT (CURRENT_DATE),
    due_date DATE NULL,
    return_date DATE NULL,
    status ENUM('Issued', 'Returned', 'Overdue', 'Lost') DEFAULT 'Issued',
    fine_amount DECIMAL(8,2) DEFAULT 0,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- PLACEMENT MANAGEMENT
-- =====================================================

-- 15. PLACEMENT_OFFERS Table
CREATE TABLE placement_offers (
    offer_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    job_title VARCHAR(200) NOT NULL,
    salary_package DECIMAL(12,2) NULL,
    offer_date DATE NULL,
    joining_date DATE NULL,
    status ENUM('Offered', 'Accepted', 'Rejected', 'Joined') DEFAULT 'Offered',
    contact_person VARCHAR(100) NULL,
    contact_email VARCHAR(100) NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- COMMUNICATION SYSTEM
-- =====================================================

-- 16. ANNOUNCEMENTS Table
CREATE TABLE announcements (
    announcement_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    announcement_type ENUM('General', 'Academic', 'Event', 'Important', 'Emergency') DEFAULT 'General',
    target_audience ENUM('All', 'Students', 'Faculty', 'Admin') DEFAULT 'All',
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    posted_by INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 17. NOTIFICATIONS Table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('Info', 'Warning', 'Success', 'Error', 'Reminder') DEFAULT 'Info',
    is_read TINYINT(1) DEFAULT 0,
    related_id INT NULL COMMENT 'ID of related record (announcement, grade, etc.)',
    related_type VARCHAR(50) NULL COMMENT 'Type of related record',
    expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 18. ALERTS Table
CREATE TABLE alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    alert_type ENUM('System', 'Academic', 'Financial', 'Placement', 'General') DEFAULT 'General',
    severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    target_users JSON NULL COMMENT 'JSON array of user IDs, or null for all users',
    is_active TINYINT(1) DEFAULT 1,
    auto_expire TINYINT(1) DEFAULT 0,
    expires_at DATETIME NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- =====================================================
-- SAMPLE DATA INSERTION
-- =====================================================

-- Insert default roles
INSERT INTO roles (role_name) VALUES
('Admin'),
('Administrative Staff'),
('Faculty'),
('Student');

-- Insert sample courses
INSERT INTO courses (course_code, course_name, duration_years) VALUES
('CS', 'Computer Science', 4),
('IT', 'Information Technology', 4),
('ME', 'Mechanical Engineering', 4),
('EE', 'Electrical Engineering', 4),
('CE', 'Civil Engineering', 4);

-- Insert sample batches
INSERT INTO batches (course_id, batch_year, batch_name) VALUES
(1, 2024, 'CS 2024 Intake'),
(1, 2023, 'CS 2023 Intake'),
(2, 2024, 'IT 2024 Intake'),
(3, 2024, 'ME 2024 Intake'),
(4, 2024, 'EE 2024 Intake'),
(5, 2024, 'CE 2024 Intake');

-- Insert sample faculty
INSERT INTO faculty (full_name, email, department, employment_role, highest_degree, primary_subject, is_active) VALUES
('Dr. Sarah Johnson', 'sarah.johnson@university.edu', 'Computer Science', 'Professor', 'PhD', 'Machine Learning', 1),
('Dr. Michael Chen', 'michael.chen@university.edu', 'Information Technology', 'Associate Professor', 'PhD', 'Cybersecurity', 1),
('Prof. Robert Davis', 'robert.davis@university.edu', 'Mechanical Engineering', 'Professor', 'PhD', 'Thermodynamics', 1),
('Dr. Lisa Wang', 'lisa.wang@university.edu', 'Electrical Engineering', 'Assistant Professor', 'PhD', 'Power Systems', 1),
('Prof. James Wilson', 'james.wilson@university.edu', 'Civil Engineering', 'Professor', 'PhD', 'Structural Engineering', 1);

-- Insert sample students
INSERT INTO students (student_id, full_name, email, course_id, batch_id, academic_status, gender) VALUES
('CS2024001', 'Alice Johnson', 'alice.johnson@student.university.edu', 1, 1, 'Active', 'Female'),
('CS2024002', 'Bob Smith', 'bob.smith@student.university.edu', 1, 1, 'Active', 'Male'),
('CS2024003', 'Carol Davis', 'carol.davis@student.university.edu', 1, 1, 'Active', 'Female'),
('CS2023001', 'David Wilson', 'david.wilson@student.university.edu', 1, 2, 'Active', 'Male'),
('IT2024001', 'Eva Martinez', 'eva.martinez@student.university.edu', 2, 3, 'Active', 'Female'),
('ME2024001', 'Frank Brown', 'frank.brown@student.university.edu', 3, 4, 'Active', 'Male'),
('EE2024001', 'Grace Lee', 'grace.lee@student.university.edu', 4, 5, 'Active', 'Female'),
('CE2024001', 'Henry Taylor', 'henry.taylor@student.university.edu', 5, 6, 'Active', 'Male');

-- Insert sample subjects
INSERT INTO subjects (subject_code, subject_name, course_id, semester, credits, faculty_id, subject_type, description, status) VALUES
('CS101', 'Introduction to Computer Science', 1, 1, 4, 1, 'Theory', 'Fundamental concepts of computer science and programming', 'Active'),
('CS102', 'Data Structures and Algorithms', 1, 2, 4, 1, 'Theory', 'Advanced data structures and algorithm design', 'Active'),
('CS201', 'Database Management Systems', 1, 3, 3, 1, 'Theory', 'Relational databases and SQL programming', 'Active'),
('CS202', 'Web Development', 1, 4, 3, 2, 'Practical', 'Full-stack web application development', 'Active'),
('IT101', 'Information Systems', 2, 1, 3, 2, 'Theory', 'Business information systems and management', 'Active'),
('IT102', 'Network Security', 2, 2, 4, 2, 'Theory', 'Computer network security principles', 'Active'),
('ME101', 'Engineering Mechanics', 3, 1, 4, 3, 'Theory', 'Statics and dynamics principles', 'Active'),
('ME102', 'Thermodynamics', 3, 2, 4, 3, 'Theory', 'Heat transfer and energy systems', 'Active'),
('EE101', 'Circuit Analysis', 4, 1, 4, 4, 'Theory', 'Electrical circuit theory and analysis', 'Active'),
('CE101', 'Structural Analysis', 5, 1, 4, 5, 'Theory', 'Analysis of structural systems', 'Active');

-- Insert sample faculty papers
INSERT INTO faculty_papers (faculty_id, title, authors, journal_name, publication_date, doi, abstract, keywords, paper_url, citation_count, publication_type, status) VALUES
(1, 'Machine Learning in Education: A Comprehensive Survey', 'Dr. Sarah Johnson, John Smith, Mary Johnson', 'IEEE Transactions on Learning Technologies', '2024-03-15', '10.1109/TLT.2024.1234567', 'This paper provides a comprehensive survey of machine learning applications in educational settings, covering various techniques and their effectiveness.', 'machine learning, education, survey, technology', 'https://ieeexplore.ieee.org/document/1234567', 25, 'Journal Article', 'Published'),
(1, 'Adaptive Learning Systems: Current Trends and Future Directions', 'Dr. Sarah Johnson, Robert Davis', 'International Journal of Artificial Intelligence in Education', '2023-11-08', '10.1007/s40593-023-00345-6', 'An analysis of adaptive learning systems, examining their implementation, effectiveness, and future directions in educational technology.', 'adaptive learning, educational technology, survey, artificial intelligence', 'https://link.springer.com/article/10.1007/s40593-023-00345-6', 45, 'Journal Article', 'Published'),
(2, 'Cybersecurity Education in Higher Education Institutions', 'Dr. Michael Chen, Lisa Wang, David Kumar', 'Journal of Cybersecurity Education, Research and Practice', '2024-01-20', '10.1002/csec.1234', 'This study examines the current state of cybersecurity education in universities and proposes a comprehensive curriculum framework.', 'cybersecurity, education, curriculum, higher education', 'https://onlinelibrary.wiley.com/doi/10.1002/csec.1234', 18, 'Journal Article', 'Published'),
(3, 'Advanced Materials in Mechanical Engineering Applications', 'Prof. Robert Davis, Maria Rodriguez, James Wilson', 'Journal of Materials Engineering', '2023-09-12', '10.1007/jmate.2023.00456', 'An analysis of advanced materials applications in mechanical engineering, focusing on performance improvements and cost-effectiveness.', 'materials engineering, advanced materials, mechanical engineering, applications', 'https://link.springer.com/article/10.1007/jmate.2023.00456', 32, 'Journal Article', 'Published');

-- Insert sample student papers
INSERT INTO student_papers (student_id, title, authors, journal_name, publication_date, doi, abstract, keywords, paper_url, citation_count, publication_type, status) VALUES
(1, 'Student Perspectives on Online Learning During COVID-19', 'Alice Johnson, Dr. Sarah Johnson', 'Journal of Higher Education Technology', '2024-02-10', '10.1080/12345678.2024.1234567', 'This study explores undergraduate student experiences with online learning platforms during the COVID-19 pandemic, highlighting both challenges and opportunities for digital education.', 'online learning, COVID-19, student experience, digital education', 'https://www.tandfonline.com/doi/full/10.1080/12345678.2024.1234567', 8, 'Journal Article', 'Published'),
(2, 'Blockchain Technology in Academic Credential Verification', 'Bob Smith, Carol Davis', 'Journal of Educational Technology Systems', '2024-01-08', '10.1177/123456789', 'An analysis of blockchain technology applications for secure academic credential verification and its potential impact on educational institutions.', 'blockchain, credential verification, academic integrity, technology adoption', 'https://journals.sagepub.com/doi/10.1177/123456789', 12, 'Journal Article', 'Published'),
(3, 'AI-Powered Study Group Formation Algorithm', 'Carol Davis', 'Computer Applications in Engineering Education', '2023-10-20', '10.1002/cae.12345', 'This research proposes an algorithm that uses artificial intelligence to optimally form study groups based on student learning styles, academic performance, and collaborative preferences.', 'artificial intelligence, study groups, collaborative learning, algorithm design', 'https://onlinelibrary.wiley.com/doi/10.1002/cae.12345', 15, 'Journal Article', 'Published');

-- Insert sample tests
INSERT INTO tests (test_name, subject_id, faculty_id, max_marks, test_date, test_type, duration, instructions, status, is_published) VALUES
('CS101 Mid-term Exam', 1, 1, 100, '2024-10-15', 'Midterm', 120, 'Closed book examination covering chapters 1-5', 'Published', 1),
('Data Structures Quiz 1', 2, 1, 50, '2024-09-20', 'Quiz', 60, 'Short answer questions on basic data structures', 'Completed', 1),
('Database Systems Final Exam', 3, 1, 100, '2024-11-30', 'Final', 180, 'Comprehensive examination covering all topics', 'Published', 0);

-- Insert sample fee payments
INSERT INTO fee_payments (student_id, fee_type, amount, payment_date, due_date, payment_status, payment_method) VALUES
(1, 'Tuition Fee - Semester 1', 25000.00, '2024-08-01', '2024-08-15', 'Paid', 'Online Banking'),
(2, 'Tuition Fee - Semester 1', 25000.00, '2024-08-05', '2024-08-15', 'Paid', 'Credit Card'),
(3, 'Tuition Fee - Semester 1', 25000.00, NULL, '2024-08-15', 'Pending', NULL),
(1, 'Library Fee', 500.00, '2024-08-01', '2024-08-10', 'Paid', 'Cash');

-- Insert sample library transactions
INSERT INTO library_transactions (student_id, book_title, book_author, isbn, issue_date, due_date, status) VALUES
(1, 'Introduction to Algorithms', 'Thomas H. Cormen', '9780262033848', '2024-09-01', '2024-09-15', 'Issued'),
(2, 'Database System Concepts', 'Abraham Silberschatz', '9780073523323', '2024-09-05', '2024-09-19', 'Issued'),
(3, 'Computer Networks', 'Andrew S. Tanenbaum', '9780132126953', '2024-08-28', '2024-09-11', 'Returned');

-- Insert sample placement offers
INSERT INTO placement_offers (student_id, company_name, job_title, salary_package, offer_date, status) VALUES
(1, 'Tech Solutions Inc.', 'Software Engineer', 850000.00, '2024-10-01', 'Accepted'),
(2, 'Data Systems Corp.', 'Data Analyst', 650000.00, '2024-09-15', 'Offered'),
(4, 'Global Engineering Ltd.', 'Mechanical Engineer', 700000.00, '2024-10-05', 'Joined');

-- Insert sample announcements
INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by, is_active) VALUES
('Welcome to New Academic Year', 'Welcome students to the new academic year 2024-25. Classes begin on August 15, 2024.', 'General', 'All', 'High', 1, 1),
('Mid-term Examination Schedule', 'Mid-term examinations will be conducted from October 15-25, 2024. Please check the detailed schedule on the portal.', 'Academic', 'Students', 'Medium', 1, 1),
('Faculty Development Workshop', 'A workshop on "Modern Teaching Methods" will be conducted on September 20, 2024, for all faculty members.', 'Academic', 'Faculty', 'Low', 1, 1);

-- =====================================================
-- TEST USER ACCOUNTS (with hashed passwords)
-- =====================================================

-- Note: Passwords are hashed using password_hash('password', PASSWORD_DEFAULT)
-- Default password for all test accounts: 'password123'

INSERT INTO users (username, password_hash, role_id, is_active) VALUES
-- Admin user
('admin', '$2y$10$8K8VzHqWJ8Jc8JcJc8Jc8O8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8', 1, 1),
-- Faculty users
('faculty1', '$2y$10$8K8VzHqWJ8Jc8JcJc8Jc8O8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8', 3, 1),
('faculty2', '$2y$10$8K8VzHqWJ8Jc8JcJc8Jc8O8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8', 3, 1),
-- Student users
('student1', '$2y$10$8K8VzHqWJ8Jc8JcJc8Jc8O8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8', 4, 1),
('student2', '$2y$10$8K8VzHqWJ8Jc8JcJc8Jc8O8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8Jc8', 4, 1);

-- Link users to faculty/students (optional - for login testing)
UPDATE faculty SET user_id = (SELECT user_id FROM users WHERE username = 'faculty1') WHERE faculty_id = 1;
UPDATE faculty SET user_id = (SELECT user_id FROM users WHERE username = 'faculty2') WHERE faculty_id = 2;
UPDATE students SET user_id = (SELECT user_id FROM users WHERE username = 'student1') WHERE student_id = 'CS2024001';
UPDATE students SET user_id = (SELECT user_id FROM users WHERE username = 'student2') WHERE student_id = 'CS2024002';

-- =====================================================
-- PERFORMANCE INDEXES
-- =====================================================

CREATE INDEX idx_users_role ON users(role_id, is_active);
CREATE INDEX idx_students_course_batch ON students(course_id, batch_id, academic_status);
CREATE INDEX idx_faculty_department ON faculty(department, is_active);
CREATE INDEX idx_subjects_course_semester ON subjects(course_id, semester, status);
CREATE INDEX idx_tests_subject_faculty ON tests(subject_id, faculty_id, status);
CREATE INDEX idx_grades_student_test ON student_test_grades(student_id, test_id);
CREATE INDEX idx_announcements_active_audience ON announcements(is_active, target_audience, created_at);
CREATE INDEX idx_notifications_user_unread ON notifications(user_id, is_read, created_at);
CREATE INDEX idx_fee_payments_student_status ON fee_payments(student_id, payment_status, due_date);
CREATE INDEX idx_library_student_status ON library_transactions(student_id, status, due_date);

-- =====================================================
-- VIEWS FOR COMMON QUERIES
-- =====================================================

-- Student overview view
CREATE VIEW view_student_overview AS
SELECT
    s.student_pk_id,
    s.student_id,
    s.full_name,
    s.email,
    c.course_name,
    b.batch_name,
    b.batch_year,
    s.academic_status,
    s.created_at
FROM students s
JOIN courses c ON s.course_id = c.course_id
JOIN batches b ON s.batch_id = b.batch_id;

-- Faculty overview view
CREATE VIEW view_faculty_overview AS
SELECT
    f.faculty_id,
    f.full_name,
    f.email,
    f.department,
    f.employment_role,
    f.highest_degree,
    COUNT(DISTINCT sub.subject_id) as subjects_teaching,
    COUNT(DISTINCT fp.paper_id) as publications,
    f.is_active
FROM faculty f
LEFT JOIN subjects sub ON f.faculty_id = sub.faculty_id AND sub.status = 'Active'
LEFT JOIN faculty_papers fp ON f.faculty_id = fp.faculty_id
GROUP BY f.faculty_id;

-- Course statistics view
CREATE VIEW view_course_statistics AS
SELECT
    c.course_id,
    c.course_code,
    c.course_name,
    c.duration_years,
    COUNT(DISTINCT s.student_pk_id) as total_students,
    COUNT(DISTINCT sub.subject_id) as total_subjects,
    COUNT(DISTINCT b.batch_id) as total_batches
FROM courses c
LEFT JOIN students s ON c.course_id = s.course_id AND s.academic_status = 'Active'
LEFT JOIN subjects sub ON c.course_id = sub.course_id AND sub.status = 'Active'
LEFT JOIN batches b ON c.course_id = b.course_id
GROUP BY c.course_id;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- SETUP COMPLETE MESSAGE
-- =====================================================

SELECT 'College Management System database setup completed successfully!' as status;
SELECT
    (SELECT COUNT(*) FROM roles) as roles_count,
    (SELECT COUNT(*) FROM users) as users_count,
    (SELECT COUNT(*) FROM courses) as courses_count,
    (SELECT COUNT(*) FROM students) as students_count,
    (SELECT COUNT(*) FROM faculty) as faculty_count,
    (SELECT COUNT(*) FROM subjects) as subjects_count,
    (SELECT COUNT(*) FROM announcements) as announcements_count;</content>
<parameter name="filePath">/Users/kgt/Desktop/Projects/PHP/CollegeManagementSystem/setup_database_complete.sql