<?php
// EMERGENCY DATABASE SETUP - MINIMAL VERSION
// Run this to fix the missing tables immediately

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Emergency Database Setup</h2>";
echo "<p>Creating missing tables...</p>";

try {
    echo "<pre>";
    
    // 1. Create subjects table
    echo "Creating subjects table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
        subject_id INT PRIMARY KEY AUTO_INCREMENT,
        subject_code VARCHAR(20) NOT NULL UNIQUE,
        subject_name VARCHAR(150) NOT NULL,
        course_id INT NOT NULL,
        semester INT NOT NULL,
        credits INT DEFAULT 3,
        description TEXT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE RESTRICT
    )");
    echo "✓ subjects table created\n";

    // 2. Create subject_faculty table
    echo "Creating subject_faculty table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS subject_faculty (
        assignment_id INT PRIMARY KEY AUTO_INCREMENT,
        subject_id INT NOT NULL,
        faculty_id INT NOT NULL,
        batch_id INT NOT NULL,
        academic_year YEAR NOT NULL,
        is_primary_faculty TINYINT(1) DEFAULT 0,
        assigned_date DATE DEFAULT (CURRENT_DATE),
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
        FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE,
        FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE
    )");
    echo "✓ subject_faculty table created\n";

    // 3. Create test_types table
    echo "Creating test_types table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_types (
        type_id INT PRIMARY KEY AUTO_INCREMENT,
        type_name VARCHAR(50) NOT NULL UNIQUE,
        weight_percentage DECIMAL(5,2) DEFAULT 100.00,
        description TEXT NULL
    )");
    echo "✓ test_types table created\n";

    // 4. Insert test types
    echo "Inserting test types...\n";
    $pdo->exec("INSERT IGNORE INTO test_types (type_name, weight_percentage, description) VALUES
        ('Quiz', 10.00, 'Short assessment covering recent topics'),
        ('Assignment', 15.00, 'Take-home assignments and projects'),
        ('Mid-term Exam', 35.00, 'Mid-semester examination'),
        ('Final Exam', 40.00, 'Comprehensive final examination')");
    echo "✓ test types inserted\n";

    // 5. Create tests table
    echo "Creating tests table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS tests (
        test_id INT PRIMARY KEY AUTO_INCREMENT,
        test_title VARCHAR(150) NOT NULL,
        subject_id INT NOT NULL,
        batch_id INT NOT NULL,
        faculty_id INT NOT NULL,
        test_type_id INT NOT NULL,
        test_date DATE NOT NULL,
        total_marks INT NOT NULL DEFAULT 100,
        passing_marks INT NOT NULL DEFAULT 40,
        duration_minutes INT DEFAULT 60,
        instructions TEXT NULL,
        is_published TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
        FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
        FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE,
        FOREIGN KEY (test_type_id) REFERENCES test_types(type_id) ON DELETE RESTRICT
    )");
    echo "✓ tests table created\n";

    // 6. Create student_test_grades table (the one that was missing!)
    echo "Creating student_test_grades table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_test_grades (
        grade_id INT PRIMARY KEY AUTO_INCREMENT,
        test_id INT NOT NULL,
        student_id INT NOT NULL,
        marks_obtained INT NULL,
        is_absent TINYINT(1) DEFAULT 0,
        percentage DECIMAL(5,2) NULL,
        grade_label VARCHAR(5) NULL,
        remarks TEXT NULL,
        graded_at TIMESTAMP NULL,
        graded_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (test_id) REFERENCES tests(test_id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE,
        FOREIGN KEY (graded_by) REFERENCES faculty(faculty_id) ON DELETE SET NULL,
        UNIQUE KEY unique_student_test (test_id, student_id)
    )");
    echo "✓ student_test_grades table created\n";

    // 7. Create grade_ranges table
    echo "Creating grade_ranges table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS grade_ranges (
        range_id INT PRIMARY KEY AUTO_INCREMENT,
        scale_id INT DEFAULT 1,
        grade_label VARCHAR(5) NOT NULL,
        min_percentage DECIMAL(5,2) NOT NULL,
        max_percentage DECIMAL(5,2) NOT NULL,
        grade_points DECIMAL(3,2) NOT NULL,
        description VARCHAR(100) NULL
    )");
    echo "✓ grade_ranges table created\n";

    // 8. Insert grade ranges
    echo "Inserting grade ranges...\n";
    $pdo->exec("INSERT IGNORE INTO grade_ranges (scale_id, grade_label, min_percentage, max_percentage, grade_points, description) VALUES
        (1, 'A+', 90.00, 100.00, 4.00, 'Exceptional'),
        (1, 'A', 85.00, 89.99, 3.75, 'Excellent'),
        (1, 'B+', 80.00, 84.99, 3.50, 'Very Good'),
        (1, 'B', 75.00, 79.99, 3.25, 'Good'),
        (1, 'C+', 70.00, 74.99, 3.00, 'Above Average'),
        (1, 'C', 65.00, 69.99, 2.75, 'Average'),
        (1, 'D+', 60.00, 64.99, 2.50, 'Below Average'),
        (1, 'D', 50.00, 59.99, 2.00, 'Pass'),
        (1, 'F', 0.00, 49.99, 0.00, 'Fail')");
    echo "✓ grade ranges inserted\n";

    // Verify all tables exist
    echo "\nVerifying tables...\n";
    $tables = ['subjects', 'subject_faculty', 'test_types', 'tests', 'student_test_grades', 'grade_ranges'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✓ $table: $count records\n";
    }
    
    echo "\n🎉 SUCCESS! All grading system tables created!\n";
    echo "\nYou can now access:\n";
    echo "- Subjects: <a href='subjects.php'>subjects.php</a>\n";
    echo "- Tests: <a href='tests.php'>tests.php</a>\n";
    echo "- Grades: <a href='grades.php'>grades.php</a>\n";
    echo "- Student Grades: <a href='student-grades.php'>student-grades.php</a>\n";
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and existing table structure.</p>";
}

echo "<p><a href='index.php'>← Back to Dashboard</a></p>";
?>