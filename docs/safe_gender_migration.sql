-- Safe Database Migration: Add Gender Support with Column Checks
-- Date: 2025-11-02
-- Description: Safely add gender columns only if they don't already exist

-- Add gender column to students table if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'students'
     AND COLUMN_NAME = 'gender') = 0,
    'ALTER TABLE students ADD COLUMN gender ENUM(\'Male\', \'Female\', \'Other\') DEFAULT \'Other\' COMMENT \'Gender for avatar selection\' AFTER profile_picture;',
    'SELECT "Gender column already exists in students table";'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add gender column to faculty table if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'faculty'
     AND COLUMN_NAME = 'gender') = 0,
    'ALTER TABLE faculty ADD COLUMN gender ENUM(\'Male\', \'Female\', \'Other\') DEFAULT \'Other\' COMMENT \'Gender for avatar selection\' AFTER profile_picture;',
    'SELECT "Gender column already exists in faculty table";'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;