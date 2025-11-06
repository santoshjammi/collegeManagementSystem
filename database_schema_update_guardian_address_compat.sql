-- =====================================================
-- COMPATIBILITY Migration - Guardian Details & Address Fields
-- Safe for MySQL 5.7 / older MariaDB: checks information_schema before ALTER
-- Run with: mysql -u root < database_schema_update_guardian_address_compat.sql
-- =====================================================

USE college_management;

-- This script checks information_schema and only issues ALTER TABLE when a column is missing.
-- It avoids `ADD COLUMN IF NOT EXISTS` and avoids expression defaults like DEFAULT (CURRENT_DATE).

-- Set database variable
SET @db = 'college_management';

-- ---------- students table additions ----------
SET @tbl = 'students';

-- contact_number
SET @col = 'contact_number';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl AND COLUMN_NAME = @col;
SET @sql = IF(@cnt = 0,
  CONCAT('ALTER TABLE `', @db, '`.`', @tbl, '` ADD COLUMN `', @col, '` VARCHAR(20) AFTER `email`;'),
  'SELECT "column exists";'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- anticipated_graduation_year
SET @col = 'anticipated_graduation_year';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl AND COLUMN_NAME = @col;
SET @sql = IF(@cnt = 0,
  CONCAT('ALTER TABLE `', @db, '`.`', @tbl, '` ADD COLUMN `', @col, '` INT NULL AFTER `academic_status`;'),
  'SELECT "column exists";'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- date_of_birth
SET @col = 'date_of_birth';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl AND COLUMN_NAME = @col;
SET @sql = IF(@cnt = 0,
  CONCAT('ALTER TABLE `', @db, '`.`', @tbl, '` ADD COLUMN `', @col, '` DATE NULL AFTER `contact_number`;'),
  'SELECT "column exists";'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- date_of_joining
SET @col = 'date_of_joining';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl AND COLUMN_NAME = @col;
SET @sql = IF(@cnt = 0,
  CONCAT('ALTER TABLE `', @db, '`.`', @tbl, '` ADD COLUMN `', @col, '` DATE NULL AFTER `date_of_birth`;'),
  'SELECT "column exists";'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- address fields
SET @col = 'address_line1';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(255) AFTER `date_of_joining`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'address_line2';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(255) AFTER `address_line1`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'city';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(100) AFTER `address_line2`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'state';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(100) AFTER `city`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'postal_code';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(20) AFTER `state`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'country';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(100) DEFAULT ''India'' AFTER `postal_code`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- guardian fields
SET @col = 'guardian_name';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(200) AFTER `country`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'guardian_relation';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(50) AFTER `guardian_name`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'guardian_contact_number';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(20) AFTER `guardian_relation`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'guardian_email';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(100) AFTER `guardian_contact_number`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'guardian_occupation';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(100) AFTER `guardian_email`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'guardian_address_line1';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(255) AFTER `guardian_occupation`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'guardian_address_line2';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(255) AFTER `guardian_address_line1`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'guardian_city';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(100) AFTER `guardian_address_line2`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'guardian_state';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(100) AFTER `guardian_city`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'guardian_postal_code';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(20) AFTER `guardian_state`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'guardian_country';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(100) DEFAULT ''India'' AFTER `guardian_postal_code`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------- faculty table additions ----------
SET @tbl = 'faculty';

SET @col = 'date_of_birth';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` DATE NULL AFTER `profile_picture`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'date_of_joining';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` DATE NULL AFTER `date_of_birth`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'address_line1';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(255) AFTER `date_of_joining`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'address_line2';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(255) AFTER `address_line1`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'city';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(100) AFTER `address_line2`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'state';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(100) AFTER `city`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'postal_code';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(20) AFTER `state`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'country';
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@tbl AND COLUMN_NAME=@col;
SET @sql = IF(@cnt=0, CONCAT('ALTER TABLE `',@db,'`.`',@tbl,'` ADD COLUMN `',@col,'` VARCHAR(100) DEFAULT ''India'' AFTER `postal_code`;'), 'SELECT "column exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Populate date_of_joining for students and faculty using created_at fallback if available
UPDATE students
SET date_of_joining = DATE(created_at)
WHERE (date_of_joining IS NULL OR date_of_joining = '0000-00-00')
  AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='students' AND COLUMN_NAME='created_at');

UPDATE faculty
SET date_of_joining = DATE(created_at)
WHERE (date_of_joining IS NULL OR date_of_joining = '0000-00-00')
  AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='faculty' AND COLUMN_NAME='created_at');

-- Add basic indexes if they don't exist
SELECT COUNT(*) INTO @exists FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA=@db AND TABLE_NAME='students' AND INDEX_NAME='idx_student_guardian_contact';
SET @sql = IF(@exists=0, 'ALTER TABLE `college_management`.`students` ADD INDEX `idx_student_guardian_contact` (`guardian_contact_number`);', 'SELECT "index exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @exists FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA=@db AND TABLE_NAME='students' AND INDEX_NAME='idx_student_city';
SET @sql = IF(@exists=0, 'ALTER TABLE `college_management`.`students` ADD INDEX `idx_student_city` (`city`);', 'SELECT "index exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @exists FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA=@db AND TABLE_NAME='faculty' AND INDEX_NAME='idx_faculty_city';
SET @sql = IF(@exists=0, 'ALTER TABLE `college_management`.`faculty` ADD INDEX `idx_faculty_city` (`city`);', 'SELECT "index exists";');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- End of compatibility migration

COMMIT;
