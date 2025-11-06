-- =====================================================
-- DATABASE SCHEMA UPDATE - Guardian Details & Address Fields
-- =====================================================
-- This script adds guardian details, address fields, and date of joining
-- to existing students and faculty tables
--
-- Run this on existing database installations to add new fields
-- Generated: November 6, 2025
-- =====================================================

USE college_management;

-- =====================================================
-- STUDENTS TABLE UPDATES
-- =====================================================

-- Add contact number if not exists
ALTER TABLE students 
ADD COLUMN IF NOT EXISTS contact_number VARCHAR(20) AFTER email;

-- Add anticipated_graduation_year if not exists
ALTER TABLE students 
ADD COLUMN IF NOT EXISTS anticipated_graduation_year INT NULL AFTER academic_status;

-- Add date fields if not exists
ALTER TABLE students 
ADD COLUMN IF NOT EXISTS date_of_birth DATE AFTER gender,
ADD COLUMN IF NOT EXISTS date_of_joining DATE NOT NULL DEFAULT (CURRENT_DATE) AFTER date_of_birth;

-- Add address fields for students
ALTER TABLE students 
ADD COLUMN IF NOT EXISTS address_line1 VARCHAR(255) AFTER date_of_joining,
ADD COLUMN IF NOT EXISTS address_line2 VARCHAR(255) AFTER address_line1,
ADD COLUMN IF NOT EXISTS city VARCHAR(100) AFTER address_line2,
ADD COLUMN IF NOT EXISTS state VARCHAR(100) AFTER city,
ADD COLUMN IF NOT EXISTS postal_code VARCHAR(20) AFTER state,
ADD COLUMN IF NOT EXISTS country VARCHAR(100) DEFAULT 'India' AFTER postal_code;

-- Add guardian details for students
ALTER TABLE students 
ADD COLUMN IF NOT EXISTS guardian_name VARCHAR(200) AFTER country,
ADD COLUMN IF NOT EXISTS guardian_relation VARCHAR(50) AFTER guardian_name,
ADD COLUMN IF NOT EXISTS guardian_contact_number VARCHAR(20) AFTER guardian_relation,
ADD COLUMN IF NOT EXISTS guardian_email VARCHAR(100) AFTER guardian_contact_number,
ADD COLUMN IF NOT EXISTS guardian_occupation VARCHAR(100) AFTER guardian_email,
ADD COLUMN IF NOT EXISTS guardian_address_line1 VARCHAR(255) AFTER guardian_occupation,
ADD COLUMN IF NOT EXISTS guardian_address_line2 VARCHAR(255) AFTER guardian_address_line1,
ADD COLUMN IF NOT EXISTS guardian_city VARCHAR(100) AFTER guardian_address_line2,
ADD COLUMN IF NOT EXISTS guardian_state VARCHAR(100) AFTER guardian_city,
ADD COLUMN IF NOT EXISTS guardian_postal_code VARCHAR(20) AFTER guardian_state,
ADD COLUMN IF NOT EXISTS guardian_country VARCHAR(100) DEFAULT 'India' AFTER guardian_postal_code;

-- =====================================================
-- FACULTY TABLE UPDATES
-- =====================================================

-- Add date fields if not exists
ALTER TABLE faculty 
ADD COLUMN IF NOT EXISTS date_of_birth DATE AFTER profile_picture,
ADD COLUMN IF NOT EXISTS date_of_joining DATE NOT NULL DEFAULT (CURRENT_DATE) AFTER date_of_birth;

-- Add address fields for faculty
ALTER TABLE faculty 
ADD COLUMN IF NOT EXISTS address_line1 VARCHAR(255) AFTER date_of_joining,
ADD COLUMN IF NOT EXISTS address_line2 VARCHAR(255) AFTER address_line1,
ADD COLUMN IF NOT EXISTS city VARCHAR(100) AFTER address_line2,
ADD COLUMN IF NOT EXISTS state VARCHAR(100) AFTER city,
ADD COLUMN IF NOT EXISTS postal_code VARCHAR(20) AFTER state,
ADD COLUMN IF NOT EXISTS country VARCHAR(100) DEFAULT 'India' AFTER postal_code;

-- =====================================================
-- UPDATE DEFAULT VALUES FOR EXISTING RECORDS
-- =====================================================

-- Set default date_of_joining for existing students (academic year start)
UPDATE students 
SET date_of_joining = CASE 
    WHEN batch_id IS NOT NULL THEN 
        MAKEDATE(YEAR(created_at), 213) -- August 1st (day 213 of year)
    ELSE 
        DATE(created_at)
END 
WHERE date_of_joining IS NULL OR date_of_joining = '0000-00-00';

-- Set default date_of_joining for existing faculty
UPDATE faculty 
SET date_of_joining = DATE(created_at)
WHERE date_of_joining IS NULL OR date_of_joining = '0000-00-00';

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================

-- Add indexes for better query performance
ALTER TABLE students 
ADD INDEX idx_student_guardian_contact (guardian_contact_number),
ADD INDEX idx_student_city (city),
ADD INDEX idx_student_date_joining (date_of_joining);

ALTER TABLE faculty 
ADD INDEX idx_faculty_city (city),
ADD INDEX idx_faculty_date_joining (date_of_joining);

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Verify students table structure
DESCRIBE students;

-- Verify faculty table structure  
DESCRIBE faculty;

-- Check sample data
SELECT student_id, full_name, date_of_joining, city, guardian_name, guardian_relation 
FROM students LIMIT 3;

SELECT full_name, department, date_of_joining, city 
FROM faculty LIMIT 3;

-- =====================================================
-- ROLLBACK SCRIPT (UNCOMMENT IF NEEDED)
-- =====================================================
/*
-- CAUTION: This will remove all the new columns and data
-- Only run this if you need to rollback the changes

ALTER TABLE students 
DROP COLUMN IF EXISTS contact_number,
DROP COLUMN IF EXISTS date_of_birth,
DROP COLUMN IF EXISTS date_of_joining,
DROP COLUMN IF EXISTS address_line1,
DROP COLUMN IF EXISTS address_line2,
DROP COLUMN IF EXISTS city,
DROP COLUMN IF EXISTS state,
DROP COLUMN IF EXISTS postal_code,
DROP COLUMN IF EXISTS country,
DROP COLUMN IF EXISTS guardian_name,
DROP COLUMN IF EXISTS guardian_relation,
DROP COLUMN IF EXISTS guardian_contact_number,
DROP COLUMN IF EXISTS guardian_email,
DROP COLUMN IF EXISTS guardian_occupation,
DROP COLUMN IF EXISTS guardian_address_line1,
DROP COLUMN IF EXISTS guardian_address_line2,
DROP COLUMN IF EXISTS guardian_city,
DROP COLUMN IF EXISTS guardian_state,
DROP COLUMN IF EXISTS guardian_postal_code,
DROP COLUMN IF EXISTS guardian_country;

ALTER TABLE faculty 
DROP COLUMN IF EXISTS date_of_birth,
DROP COLUMN IF EXISTS date_of_joining,
DROP COLUMN IF EXISTS address_line1,
DROP COLUMN IF EXISTS address_line2,
DROP COLUMN IF EXISTS city,
DROP COLUMN IF EXISTS state,
DROP COLUMN IF EXISTS postal_code,
DROP COLUMN IF EXISTS country;
*/

COMMIT;