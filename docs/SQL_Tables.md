-- SIMS Apex Minimal Viable Product (MVP) Database Schema
-- Target Database: MySQL
-- Designed for PHP/MySQL implementation, focusing on core functionality and future EAV scalability.

-- --- CORE SYSTEM TABLES (User Management & Configuration) ---

-- 1. ROLES Table: Defines the different access levels in the system.
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., Admin, Staff, Faculty, Student'
) ENGINE=InnoDB;

-- 2. USERS Table: Stores login credentials. Links a user to their specific profile (Student/Faculty).
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL COMMENT 'Store securely hashed password using PHP password_hash()',
    role_id INT NOT NULL,
    linked_entity_id INT NULL COMMENT 'ID linking to student_id or faculty_id. Null for Admin/General Staff.',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 3. COURSES Table: Master list of Programs/Degrees offered.
CREATE TABLE courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(10) NOT NULL UNIQUE,
    course_name VARCHAR(150) NOT NULL,
    duration_years INT
) ENGINE=InnoDB;

-- 4. BATCHES Table: Defines a specific intake year for a course.
CREATE TABLE batches (
    batch_id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    batch_year YEAR NOT NULL COMMENT 'The intake year, e.g., 2024',
    batch_name VARCHAR(50) NOT NULL COMMENT 'e.g., CS 2024 Intake',
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE RESTRICT
) ENGINE=InnoDB;


-- --- MODULE 1: STUDENT MANAGEMENT ---

-- 5. STUDENTS Table: Core academic and demographic data for students.
CREATE TABLE students (
    student_pk_id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Primary Key for DB relations',
    student_id VARCHAR(20) NOT NULL UNIQUE COMMENT 'Unique Institutional ID (e.g., Roll Number)',
    user_id INT NULL UNIQUE COMMENT 'Links to the user login table',
    full_name VARCHAR(200) NOT NULL,
    contact_number VARCHAR(15),
    email VARCHAR(100) UNIQUE,
    course_id INT NOT NULL,
    batch_id INT NOT NULL,
    academic_status ENUM('Active', 'On Leave', 'Suspended', 'Graduated') DEFAULT 'Active',
    anticipated_graduation_year YEAR,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE RESTRICT,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 6. STUDENT_CUSTOM_FIELDS Table (EAV Pattern): For future Phase 2 customization.
CREATE TABLE student_custom_fields (
    field_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_value TEXT,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_field (student_id, field_name)
) ENGINE=InnoDB;


-- --- MODULE 2: FACULTY MANAGEMENT ---

-- 7. FACULTY Table: Core academic and profile data for faculty and staff.
CREATE TABLE faculty (
    faculty_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL UNIQUE COMMENT 'Links to the user login table',
    full_name VARCHAR(200) NOT NULL,
    department VARCHAR(100),
    employment_role VARCHAR(50) COMMENT 'e.g., Professor, Lecturer, Admin Staff, Librarian',
    contact_number VARCHAR(15),
    email VARCHAR(100) UNIQUE,
    primary_subject VARCHAR(150) COMMENT 'Core academic field/subject of expertise',
    highest_degree VARCHAR(50),
    research_interests TEXT NULL COMMENT 'Descriptive text field for expertise',
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 8. FACULTY_CUSTOM_FIELDS Table (EAV Pattern): For future Phase 2 customization.
CREATE TABLE faculty_custom_fields (
    field_id INT PRIMARY KEY AUTO_INCREMENT,
    faculty_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_value TEXT,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE,
    UNIQUE KEY unique_faculty_field (faculty_id, field_name)
) ENGINE=InnoDB;


-- --- MODULE 3: ADMISSIONS ---

-- 9. APPLICATIONS Table: Stores prospective student data submitted via the online form.
CREATE TABLE applications (
    application_id INT PRIMARY KEY AUTO_INCREMENT,
    applicant_name VARCHAR(200) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    program_applied_for VARCHAR(150) COMMENT 'Text field for course name',
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending Review', 'Approved', 'Rejected', 'Converted') DEFAULT 'Pending Review',
    reviewed_by_user_id INT NULL COMMENT 'User who last updated the status',
    FOREIGN KEY (reviewed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;


-- --- MODULE 4: FEE MANAGEMENT ---

-- 10. FEE_STRUCTURES Table: Defines standard fees per course/batch.
CREATE TABLE fee_structures (
    structure_id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    batch_id INT NULL COMMENT 'NULL for fees applying to all batches of a course',
    fee_name VARCHAR(100) NOT NULL COMMENT 'e.g., Tution Fee, Exam Fee',
    amount DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. FEE_PAYMENTS Table: Records manual payments made by students.
CREATE TABLE fee_payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    receipt_number VARCHAR(50) UNIQUE NULL,
    recorded_by_user_id INT NOT NULL COMMENT 'Staff member who recorded the transaction',
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE RESTRICT,
    FOREIGN KEY (recorded_by_user_id) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;


-- --- MODULE 5: LIBRARY MANAGEMENT ---

-- 12. BOOKS Table: Catalogue of all library assets.
CREATE TABLE books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150),
    isbn VARCHAR(30) UNIQUE,
    total_copies INT NOT NULL,
    available_copies INT NOT NULL
) ENGINE=InnoDB;

-- 13. LIBRARY_ISSUES Table: Tracks book issue and return history.
CREATE TABLE library_issues (
    issue_id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    issue_date DATE NOT NULL,
    expected_return_date DATE,
    return_date DATE NULL,
    is_returned TINYINT(1) DEFAULT 0,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE RESTRICT,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE RESTRICT
) ENGINE=InnoDB;


-- --- MODULE 6: PLACEMENT SYSTEM ---

-- 14. COMPANIES Table: Master list of companies for placement tracking.
CREATE TABLE companies (
    company_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL UNIQUE,
    contact_person VARCHAR(100),
    contact_email VARCHAR(100),
    industry VARCHAR(100)
) ENGINE=InnoDB;

-- 15. PLACEMENTS Table: Tracks the final placement status of a student.
CREATE TABLE placements (
    placement_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL UNIQUE COMMENT 'One placement record per student',
    company_id INT NULL COMMENT 'Null if placement_status is Not Placed or Interviewing',
    placement_status ENUM('Placed', 'Interviewing', 'Not Placed') NOT NULL,
    placement_date DATE NULL COMMENT 'Date the offer was finalized',
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 16. FACULTY_PAPERS Table: Stores research papers and publications by faculty members.
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