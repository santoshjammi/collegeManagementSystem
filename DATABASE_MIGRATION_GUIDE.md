# College Management System - Database Migration Guide

## Overview
This guide provides complete database schema and sample data for migrating the College Management System to any MySQL/MariaDB server.

## Files Included

### 1. `database_migration_complete.sql` (Recommended)
- **Complete migration package** with schema + data
- **Size**: ~25KB with comprehensive sample data
- **Use case**: Fresh database setup on new server

### 2. `database_data_only.sql`
- **Data only migration** (no schema creation)
- **Size**: ~15KB sample data only
- **Use case**: Existing schema, just need sample data

### 3. Individual Files (For Reference)
- `setup_database_complete.sql` - Original schema with data
- `test_users_setup.sql` - User accounts only
- `additional_tables.sql` - Enhanced features tables

## Migration Options

### Option 1: Complete Migration (Recommended)

```bash
# 1. Create database on target server
mysql -u root -p -e "CREATE DATABASE college_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Import complete schema and data
mysql -u root -p college_management < database_migration_complete.sql

# 3. Verify import
mysql -u root -p college_management -e "SHOW TABLES;"
mysql -u root -p college_management -e "SELECT COUNT(*) as users FROM users;"
```

### Option 2: Data Only Migration

```bash
# If you already have the schema set up
mysql -u root -p college_management < database_data_only.sql
```

### Option 3: Using phpMyAdmin

1. **Create Database**:
   - Go to phpMyAdmin
   - Create new database: `college_management`
   - Set charset: `utf8mb4_unicode_ci`

2. **Import SQL File**:
   - Select the database
   - Go to "Import" tab
   - Choose `database_migration_complete.sql`
   - Click "Go"

## Database Structure

### Core Tables (16 total)
- **System**: `roles`, `users`
- **Academic**: `courses`, `batches`, `students`, `faculty`, `subjects`, `tests`, `grades`
- **Administrative**: `announcements`, `fee_payments`, `library_transactions`, `placements`
- **Research**: `publications`
- **Enhanced**: `admissions`, `library_books`

### Sample Data Included
- **4 User Roles**: Admin, Staff, Faculty, Student
- **5 Courses**: CS, IT, ME, EE, CE
- **7 Batches**: 2023-2024 intakes
- **5 Students**: Across different courses
- **2 Faculty**: With publications and subjects
- **10 Subjects**: Distributed across courses
- **5 Tests**: Various types with grades
- **3 Announcements**: Different types and audiences
- **5 Fee Payments**: Various payment methods
- **4 Library Transactions**: Books issued/returned
- **3 Placements**: Job offers and acceptances
- **4 Publications**: Faculty and student research
- **3 Admission Applications**: Different statuses
- **5 Library Books**: Various categories

## User Accounts

After migration, use these accounts to test:

| Role | Username | Password | Access Level |
|------|----------|----------|--------------|
| **Admin** | `admin` | `password123` | Full system access |
| **Staff** | `staff1` | `password123` | Administrative functions |
| **Faculty** | `faculty1` | `password123` | Teaching & grading |
| **Faculty** | `sarah.johnson` | `password123` | Teaching & research |
| **Student** | `alice.johnson` | `password123` | Student portal |
| **Student** | `bob.smith` | `password123` | Student portal |

*Note: All passwords are bcrypt-hashed for security*

## Database Configuration

Update your application config to match the database:

```php
// config.php
define('DB_HOST', 'your-server-host');     // e.g., 'localhost' or 'db-server.com'
define('DB_NAME', 'college_management');  // Database name
define('DB_USER', 'your_db_username');     // Database user
define('DB_PASS', 'your_db_password');     // Database password
```

## Verification Steps

After migration, verify everything works:

```sql
-- Check table counts
SELECT 'roles' as table_name, COUNT(*) as count FROM roles
UNION ALL
SELECT 'users', COUNT(*) FROM users
UNION ALL
SELECT 'courses', COUNT(*) FROM courses
UNION ALL
SELECT 'students', COUNT(*) FROM students
UNION ALL
SELECT 'faculty', COUNT(*) FROM faculty;

-- Test relationships
SELECT s.full_name, c.course_name, b.batch_name
FROM students s
JOIN courses c ON s.course_id = c.course_id
JOIN batches b ON s.batch_id = b.batch_id
LIMIT 5;

-- Test user login
SELECT u.username, r.role_name, u.is_active
FROM users u
JOIN roles r ON u.role_id = r.role_id;
```

## Performance Optimization

The migration includes these indexes for better performance:

```sql
-- User-related indexes
CREATE INDEX idx_users_role_id ON users(role_id);
CREATE INDEX idx_users_username ON users(username);

-- Student-related indexes
CREATE INDEX idx_students_user_id ON students(user_id);
CREATE INDEX idx_students_course_id ON students(course_id);
CREATE INDEX idx_students_batch_id ON students(batch_id);

-- Academic indexes
CREATE INDEX idx_subjects_course_id ON subjects(course_id);
CREATE INDEX idx_tests_subject_id ON tests(subject_id);
CREATE INDEX idx_grades_test_id ON grades(test_id);

-- And more... (see migration file for complete list)
```

## Backup Recommendations

Before migration:

```bash
# Create backup of existing database (if any)
mysqldump -u root -p existing_database > backup_$(date +%Y%m%d).sql

# Backup application files
tar -czf app_backup_$(date +%Y%m%d).tar.gz /path/to/application/
```

## Troubleshooting

### Common Issues

1. **Foreign Key Errors**:
   ```sql
   -- Disable foreign keys temporarily
   SET FOREIGN_KEY_CHECKS = 0;
   -- Import data
   -- Re-enable foreign keys
   SET FOREIGN_KEY_CHECKS = 1;
   ```

2. **Duplicate Key Errors**:
   - The migration uses `ON DUPLICATE KEY UPDATE` to handle existing data
   - For clean migration, use empty database

3. **Character Set Issues**:
   ```sql
   -- Ensure proper charset
   ALTER DATABASE college_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **Permission Errors**:
   - Ensure database user has CREATE, INSERT, UPDATE, DELETE privileges
   - Grant all privileges: `GRANT ALL PRIVILEGES ON college_management.* TO 'user'@'host';`

### Verification Queries

```sql
-- Check all tables exist
SHOW TABLES;

-- Verify data integrity
SELECT
    (SELECT COUNT(*) FROM users) as users,
    (SELECT COUNT(*) FROM students) as students,
    (SELECT COUNT(*) FROM faculty) as faculty,
    (SELECT COUNT(*) FROM courses) as courses,
    (SELECT COUNT(*) FROM subjects) as subjects;

-- Test foreign key relationships
SELECT 'Testing relationships...' as status;
SELECT COUNT(*) as valid_students FROM students s
JOIN courses c ON s.course_id = c.course_id
JOIN batches b ON s.batch_id = b.batch_id;
```

## Migration Checklist

- [ ] Backup existing database (if applicable)
- [ ] Create new database with proper charset
- [ ] Run migration script
- [ ] Verify table creation
- [ ] Verify data insertion
- [ ] Test user logins
- [ ] Update application configuration
- [ ] Test application functionality
- [ ] Set up automated backups

## Support

If you encounter issues during migration:

1. Check the MySQL/MariaDB error logs
2. Verify database user permissions
3. Ensure target server meets requirements (MySQL 5.7+ or MariaDB 10.0+)
4. Test with a small subset of data first

---

**Migration completed successfully!** 🎉

Your College Management System database is now ready for use.