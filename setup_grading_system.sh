#!/bin/bash

# Database Setup Script for Grading System
# Run this script to set up the grading system tables

echo "=== College Management System - Grading System Setup ==="
echo ""

# Database connection details (modify these according to your setup)
DB_HOST="localhost"
DB_NAME="college_management"
DB_USER="root"  # Change this to your MySQL username
DB_PASS=""      # Change this to your MySQL password (leave empty if no password)

# Check if MySQL client is available
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL client not found. Please install MySQL client first."
    exit 1
fi

echo "🔧 Setting up grading system tables..."
echo "Database: $DB_NAME"
echo "Host: $DB_HOST"
echo ""

# Execute the SQL schema
if [ -z "$DB_PASS" ]; then
    mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" < "$(dirname "$0")/docs/Grading_System_Schema.sql"
else
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$(dirname "$0")/docs/Grading_System_Schema.sql"
fi

# Check if the command was successful
if [ $? -eq 0 ]; then
    echo "✅ Grading system tables created successfully!"
    echo ""
    echo "📋 Tables created:"
    echo "   - subjects"
    echo "   - subject_faculty"
    echo "   - test_types"
    echo "   - tests"
    echo "   - student_test_grades"
    echo "   - grade_ranges"
    echo "   - student_semester_grades"
    echo ""
    echo "🎯 You can now use the grading system modules:"
    echo "   - Subjects Management (subjects.php)"
    echo "   - Tests Management (tests.php)"
    echo "   - Grading Interface (grades.php)"
    echo "   - Student Grade View (student-grades.php)"
else
    echo "❌ Error occurred while setting up the database."
    echo "Please check your database connection details and try again."
    exit 1
fi