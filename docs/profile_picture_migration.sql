-- Database Migration: Add Gender Support (Profile Picture Already Exists)
-- Date: 2025-11-02
-- Description: Add gender columns to students and faculty tables (profile_picture columns already exist)

-- Add gender column to students table (profile_picture already exists)
ALTER TABLE students
ADD COLUMN gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other' COMMENT 'Gender for avatar selection' AFTER profile_picture;

-- Add gender column to faculty table (profile_picture already exists)
ALTER TABLE faculty
ADD COLUMN gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other' COMMENT 'Gender for avatar selection' AFTER profile_picture;