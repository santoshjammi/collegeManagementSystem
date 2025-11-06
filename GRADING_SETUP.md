# Grading System Database Setup Guide

## Quick Setup

### Option 1: Automatic Setup (Recommended)
1. Make the setup script executable:
   ```bash
   chmod +x setup_grading_system.sh
   ```

2. Edit the script to match your database credentials:
   ```bash
   nano setup_grading_system.sh
   ```
   Update these lines:
   - `DB_HOST="localhost"` (usually localhost)
   - `DB_NAME="college_management"` (your database name)
   - `DB_USER="root"` (your MySQL username)
   - `DB_PASS=""` (your MySQL password)

3. Run the setup script:
   ```bash
   ./setup_grading_system.sh
   ```

### Option 2: Manual Setup via phpMyAdmin
1. Open phpMyAdmin in your browser
2. Select your `college_management` database
3. Click on the "SQL" tab
4. Copy and paste the contents of `docs/Grading_System_Schema.sql`
5. Click "Go" to execute

### Option 3: Manual Setup via MySQL Command Line
1. Open terminal/command prompt
2. Connect to MySQL:
   ```bash
   mysql -u root -p
   ```
3. Select your database:
   ```sql
   USE college_management;
   ```
4. Execute the schema file:
   ```sql
   SOURCE /path/to/your/project/docs/Grading_System_Schema.sql;
   ```

## Verification

After running the setup, verify the tables were created:

```sql
SHOW TABLES LIKE '%subject%';
SHOW TABLES LIKE '%test%';
SHOW TABLES LIKE '%grade%';
```

You should see these new tables:
- `subjects`
- `subject_faculty`  
- `test_types`
- `tests`
- `student_test_grades`
- `grade_ranges`
- `student_semester_grades`

## Troubleshooting

### Error: "Table doesn't exist"
- Make sure you executed the SQL schema in the correct database
- Verify your database connection details in `config.php`

### Error: "Access denied" 
- Check your MySQL username and password
- Ensure your MySQL user has CREATE, INSERT permissions

### Error: "Foreign key constraint fails"
- Make sure the existing tables (courses, batches, faculty, students) exist
- Check that the table structure matches what's expected

## Next Steps

Once the database setup is complete, you can access:

1. **Subjects Management**: `http://yoursite/subjects.php`
2. **Tests Management**: `http://yoursite/tests.php`  
3. **Grading Interface**: `http://yoursite/grades.php`
4. **Student Grades**: `http://yoursite/student-grades.php`

## Support

If you encounter any issues:
1. Check the error logs in your web server
2. Verify database connection in `config.php`
3. Ensure all required tables exist with proper structure