-- College Management System Database Setup
-- Run this script to create all necessary tables

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS college_management;
USE college_management;

-- 1. ROLES Table
CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., Admin, Staff, Faculty, Student'
) ENGINE=InnoDB;

-- 2. USERS Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL COMMENT 'Store securely hashed password using PHP password_hash()',
    role_id INT NOT NULL,
    linked_entity_id INT NULL COMMENT 'ID linking to student_id or faculty_id. Null for Admin/General Staff.',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT
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
    batch_year YEAR NOT NULL COMMENT 'The intake year, e.g., 2024',
    batch_name VARCHAR(50) NOT NULL COMMENT 'e.g., CS 2024 Intake',
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 5. STUDENTS Table
CREATE TABLE IF NOT EXISTS students (
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

-- 6. FACULTY Table
CREATE TABLE IF NOT EXISTS faculty (
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

-- Insert default roles
INSERT INTO roles (role_name) VALUES
('Admin'),
('Administrative Staff'),
('Faculty'),
('Student')
ON DUPLICATE KEY UPDATE role_name = role_name;

-- Insert sample course
INSERT INTO courses (course_code, course_name, duration_years) VALUES
('CS', 'Computer Science', 4)
ON DUPLICATE KEY UPDATE course_name = course_name;

-- Insert sample batch
INSERT INTO batches (course_id, batch_year, batch_name) VALUES
(1, 2024, 'CS 2024 Intake')
ON DUPLICATE KEY UPDATE batch_name = batch_name;