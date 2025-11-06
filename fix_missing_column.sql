-- =====================================================
-- FIX MISSING COLUMN - Anticipated Graduation Year
-- =====================================================
-- This script adds the missing anticipated_graduation_year column
-- to the students table that the PHP code expects
--
-- Run this on your existing database
-- =====================================================

USE college_management;

-- Add the missing anticipated_graduation_year column
ALTER TABLE students
ADD COLUMN anticipated_graduation_year INT NULL AFTER academic_status;

-- Update existing records with a reasonable default (current year + 4)
UPDATE students
SET anticipated_graduation_year = YEAR(CURDATE()) + 4
WHERE anticipated_graduation_year IS NULL;

COMMIT;