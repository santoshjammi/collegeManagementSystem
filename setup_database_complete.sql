# College Management System - Complete Setup Guide

## Overview
This guide provides everything needed to set up the College Management System on a new machine for testing and development.

## Prerequisites
- PHP 7.4+ with PDO extension
- MySQL 5.7+ or MariaDB 10.0+
- Apache/Nginx web server
- Composer (optional, for dependency management)

## 1. Database Setup

### Database Configuration
Create a MySQL database and update the connection settings in `config.php`:

```php
define('DB_HOST', 'localhost');     // Your MySQL host
define('DB_NAME', 'college_management');  // Database name
define('DB_USER', 'your_username');       // MySQL username
define('DB_PASS', 'your_password');       // MySQL password
```

### Complete Database Schema

Run the following SQL script to create all tables:

```sql
-- =====================================================
-- COLLEGE MANAGEMENT SYSTEM - COMPLETE DATABASE SCHEMA
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS college_management;
USE college_management;

-- Disable foreign key checks for setup
SET FOREIGN_KEY_CHECKS = 0;

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

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
```

### Sample Data Insertion

After creating the tables, insert the following sample data:

```sql
-- Insert default roles
INSERT INTO roles (role_name) VALUES
('Admin'), ('Administrative Staff'), ('Faculty'), ('Student');

-- Insert sample course and batch
INSERT INTO courses (course_code, course_name, duration_years) VALUES
('CS', 'Computer Science', 4),
('IT', 'Information Technology', 4),
('ME', 'Mechanical Engineering', 4);

INSERT INTO batches (course_id, batch_year, batch_name) VALUES
(1, 2024, 'CS 2024 Intake'),
(2, 2024, 'IT 2024 Intake'),
(3, 2024, 'ME 2024 Intake');

-- Insert sample faculty
INSERT INTO faculty (full_name, email, department, is_active) VALUES
('Dr. Sarah Johnson', 'sarah.johnson@university.edu', 'Computer Science', 1),
('Dr. Michael Chen', 'michael.chen@university.edu', 'Information Technology', 1),
('Prof. Robert Davis', 'robert.davis@university.edu', 'Mechanical Engineering', 1);

-- Insert sample students
INSERT INTO students (student_id, full_name, email, course_id, batch_id, academic_status) VALUES
('CS2024001', 'Alice Johnson', 'alice.johnson@student.university.edu', 1, 1, 'Active'),
('CS2024002', 'Bob Smith', 'bob.smith@student.university.edu', 1, 1, 'Active'),
('IT2024001', 'Carol Davis', 'carol.davis@student.university.edu', 2, 2, 'Active'),
('ME2024001', 'David Wilson', 'david.wilson@student.university.edu', 3, 3, 'Active');

-- Insert sample subjects
INSERT INTO subjects (subject_code, subject_name, course_id, semester, credits, faculty_id, subject_type, status) VALUES
('CS101', 'Introduction to Computer Science', 1, 1, 4, 1, 'Theory', 'Active'),
('CS102', 'Data Structures and Algorithms', 1, 2, 4, 1, 'Theory', 'Active'),
('IT101', 'Information Systems', 2, 1, 3, 2, 'Theory', 'Active'),
('ME101', 'Engineering Mechanics', 3, 1, 4, 3, 'Theory', 'Active');
```

## 2. User Account Creation

Create test user accounts with the following credentials:

### Admin User
```sql
INSERT INTO users (username, password_hash, role_id, is_active) VALUES
('admin', '$2y$10$hashed_password_here', 1, 1);
```
**Login:** admin / admin123

### Faculty Users
```sql
INSERT INTO users (username, password_hash, role_id, is_active) VALUES
('faculty1', '$2y$10$hashed_password_here', 3, 1),
('faculty2', '$2y$10$hashed_password_here', 3, 1);
```
**Login:** faculty1 / faculty123

### Student Users
```sql
INSERT INTO users (username, password_hash, role_id, is_active) VALUES
('student1', '$2y$10$hashed_password_here', 4, 1),
('student2', '$2y$10$hashed_password_here', 4, 1);
```
**Login:** student1 / student123

**Note:** Replace `$2y$10$hashed_password_here` with actual bcrypt hashes. You can generate them using:
```php
echo password_hash('admin123', PASSWORD_DEFAULT);
```

## 3. Application Configuration

### config.php Settings
```php
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'college_management');
define('DB_USER', 'your_mysql_username');
define('DB_PASS', 'your_mysql_password');

// Application configuration
define('BASE_URL', 'http://localhost/your_project_path');
define('SITE_NAME', 'College Management System');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.cookie_samesite', 'Lax');
session_start();
```

### Directory Structure
Ensure your project has this structure:
```
college-management-system/
├── traditional-php/
│   ├── config.php
│   ├── index.php
│   ├── login.php
│   ├── dashboard.php
│   ├── courses.php
│   ├── subjects.php
│   ├── students.php
│   ├── faculty.php
│   ├── ajax/
│   ├── includes/
│   │   └── navbar.php
│   └── uploads/
├── docs/
└── README.md
```

## 4. Web Server Configuration

### Apache (.htaccess)
```apache
RewriteEngine On
RewriteBase /college-management-system/traditional-php/

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
<FilesMatch "\.(php|html|js|css)$">
    Header set X-Content-Type-Options nosniff
</FilesMatch>

# Deny access to sensitive files
<FilesMatch "(config\.php|\.sql)$">
    Order deny,allow
    Deny from all
</FilesMatch>
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/college-management-system/traditional-php;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

## 5. Required PHP Extensions

Ensure these PHP extensions are enabled:
- `pdo`
- `pdo_mysql`
- `mbstring`
- `session`
- `fileinfo`
- `gd` (for image processing)

## 6. File Permissions

Set appropriate permissions:
```bash
chmod 755 /path/to/project
chmod 644 /path/to/project/traditional-php/*.php
chmod 755 /path/to/project/traditional-php/uploads/
```

## 7. Testing the Setup

### Quick Test Script
Create `test_setup.php` in your project root:

```php
<?php
require_once 'config.php';

echo "<h1>College Management System - Setup Test</h1>";

// Test database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Database connection: OK (" . $result['count'] . " users)</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection: FAILED - " . $e->getMessage() . "</p>";
}

// Test sample data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as courses FROM courses");
    $courses = $stmt->fetch(PDO::FETCH_ASSOC)['courses'];

    $stmt = $pdo->query("SELECT COUNT(*) as students FROM students");
    $students = $stmt->fetch(PDO::FETCH_ASSOC)['students'];

    echo "<p>✅ Sample data: $courses courses, $students students</p>";
} catch (Exception $e) {
    echo "<p>❌ Sample data check: FAILED - " . $e->getMessage() . "</p>";
}

echo "<p><strong>Setup Status:</strong> " . (strpos(ob_get_contents(), '❌') === false ? 'COMPLETE' : 'INCOMPLETE') . "</p>";
```

### Test User Logins
- **Admin:** admin / admin123
- **Faculty:** faculty1 / faculty123
- **Student:** student1 / student123

## 8. Troubleshooting

### Common Issues:

1. **Database Connection Failed**
   - Check MySQL credentials in `config.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Permission Errors**
   - Check file permissions (755 for directories, 644 for files)
   - Ensure web server user can write to uploads directory

3. **PHP Errors**
   - Enable required PHP extensions
   - Check PHP error logs
   - Verify PHP version (7.4+)

4. **Session Issues**
   - Check session save path permissions
   - Verify session configuration in `config.php`

## 9. Additional Setup Scripts

### Automated Setup Script (setup_complete.php)
```php
<?php
// Complete setup script - run once after manual setup
require_once 'config.php';

echo "Running complete setup...<br>";

// Create additional indexes for performance
$indexes = [
    "CREATE INDEX idx_announcements_active ON announcements(is_active, created_at)",
    "CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read)",
    "CREATE INDEX idx_subjects_course_semester ON subjects(course_id, semester)",
    "CREATE INDEX idx_tests_subject_date ON tests(subject_id, test_date)"
];

foreach ($indexes as $index) {
    try {
        $pdo->exec($index);
        echo "✅ Created index<br>";
    } catch (Exception $e) {
        echo "⚠️ Index creation skipped (may already exist)<br>";
    }
}

echo "<br>Setup complete! You can now access the system.";
```

## 10. Backup and Maintenance

### Database Backup
```bash
mysqldump -u username -p college_management > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Regular Maintenance
- Monitor database size and performance
- Clean up old notifications and logs
- Update user passwords periodically
- Backup regularly

---

**Note:** This setup guide provides everything needed to deploy the College Management System on a new machine. Follow the steps in order for a successful installation.</content>
<parameter name="filePath">/Users/kgt/Desktop/Projects/PHP/CollegeManagementSystem/COMPLETE_SETUP_GUIDE.md