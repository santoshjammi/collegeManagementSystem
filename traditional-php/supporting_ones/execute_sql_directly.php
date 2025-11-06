<?php
/**
 * Direct SQL Executor for Grading System
 * This will execute the SQL commands directly without file path issues
 */

require_once 'config.php';

// Get the SQL content directly embedded in the script
$sqlStatements = [
    // SUBJECTS Table
    "CREATE TABLE IF NOT EXISTS subjects (
        subject_id INT PRIMARY KEY AUTO_INCREMENT,
        subject_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g., CS101, MATH201',
        subject_name VARCHAR(150) NOT NULL COMMENT 'e.g., Introduction to Computer Science',
        course_id INT NOT NULL COMMENT 'Which degree program this subject belongs to',
        semester INT NOT NULL COMMENT 'Which semester (1-8 typically)',
        credits INT DEFAULT 3 COMMENT 'Credit hours for this subject',
        description TEXT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE RESTRICT,
        UNIQUE KEY unique_subject_course_semester (subject_code, course_id, semester)
    ) ENGINE=InnoDB",

    // SUBJECT_FACULTY Table
    "CREATE TABLE IF NOT EXISTS subject_faculty (
        assignment_id INT PRIMARY KEY AUTO_INCREMENT,
        subject_id INT NOT NULL,
        faculty_id INT NOT NULL,
        batch_id INT NOT NULL COMMENT 'Which batch this faculty teaches',
        academic_year YEAR NOT NULL COMMENT 'e.g., 2024',
        is_primary_faculty TINYINT(1) DEFAULT 0 COMMENT 'Main teacher vs assistant',
        assigned_date DATE DEFAULT (CURRENT_DATE),
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
        FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE,
        FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
        UNIQUE KEY unique_assignment (subject_id, faculty_id, batch_id, academic_year)
    ) ENGINE=InnoDB",

    // TEST_TYPES Table
    "CREATE TABLE IF NOT EXISTS test_types (
        type_id INT PRIMARY KEY AUTO_INCREMENT,
        type_name VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., Quiz, Mid-term, Final Exam, Assignment',
        weight_percentage DECIMAL(5,2) DEFAULT 100.00 COMMENT 'Default weight for grade calculation',
        description TEXT NULL
    ) ENGINE=InnoDB",

    // Insert default test types
    "INSERT IGNORE INTO test_types (type_name, weight_percentage, description) VALUES
        ('Quiz', 10.00, 'Short assessment covering recent topics'),
        ('Assignment', 15.00, 'Take-home assignments and projects'),
        ('Mid-term Exam', 35.00, 'Mid-semester examination'),
        ('Final Exam', 40.00, 'Comprehensive final examination')",

    // TESTS Table
    "CREATE TABLE IF NOT EXISTS tests (
        test_id INT PRIMARY KEY AUTO_INCREMENT,
        test_title VARCHAR(150) NOT NULL,
        subject_id INT NOT NULL,
        batch_id INT NOT NULL COMMENT 'Which batch takes this test',
        faculty_id INT NOT NULL COMMENT 'Faculty who created/manages this test',
        test_type_id INT NOT NULL,
        test_date DATE NOT NULL,
        total_marks INT NOT NULL DEFAULT 100,
        passing_marks INT NOT NULL DEFAULT 40,
        duration_minutes INT DEFAULT 60 COMMENT 'Test duration in minutes',
        instructions TEXT NULL,
        is_published TINYINT(1) DEFAULT 0 COMMENT 'Whether results are visible to students',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
        FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
        FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE,
        FOREIGN KEY (test_type_id) REFERENCES test_types(type_id) ON DELETE RESTRICT
    ) ENGINE=InnoDB",

    // STUDENT_TEST_GRADES Table
    "CREATE TABLE IF NOT EXISTS student_test_grades (
        grade_id INT PRIMARY KEY AUTO_INCREMENT,
        test_id INT NOT NULL,
        student_id INT NOT NULL COMMENT 'References students.student_pk_id',
        marks_obtained INT NULL COMMENT 'Actual marks scored, NULL if not graded yet',
        is_absent TINYINT(1) DEFAULT 0 COMMENT 'Student was absent for the test',
        percentage DECIMAL(5,2) GENERATED ALWAYS AS (
            CASE 
                WHEN is_absent = 1 OR marks_obtained IS NULL THEN NULL 
                ELSE (marks_obtained * 100.0 / (SELECT total_marks FROM tests WHERE tests.test_id = student_test_grades.test_id))
            END
        ) STORED,
        grade_label VARCHAR(5) GENERATED ALWAYS AS (
            CASE 
                WHEN is_absent = 1 OR marks_obtained IS NULL THEN NULL
                WHEN (marks_obtained * 100.0 / (SELECT total_marks FROM tests WHERE tests.test_id = student_test_grades.test_id)) >= 90 THEN 'A+'
                WHEN (marks_obtained * 100.0 / (SELECT total_marks FROM tests WHERE tests.test_id = student_test_grades.test_id)) >= 85 THEN 'A'
                WHEN (marks_obtained * 100.0 / (SELECT total_marks FROM tests WHERE tests.test_id = student_test_grades.test_id)) >= 80 THEN 'B+'
                WHEN (marks_obtained * 100.0 / (SELECT total_marks FROM tests WHERE tests.test_id = student_test_grades.test_id)) >= 75 THEN 'B'
                WHEN (marks_obtained * 100.0 / (SELECT total_marks FROM tests WHERE tests.test_id = student_test_grades.test_id)) >= 70 THEN 'C+'
                WHEN (marks_obtained * 100.0 / (SELECT total_marks FROM tests WHERE tests.test_id = student_test_grades.test_id)) >= 65 THEN 'C'
                WHEN (marks_obtained * 100.0 / (SELECT total_marks FROM tests WHERE tests.test_id = student_test_grades.test_id)) >= 60 THEN 'D+'
                WHEN (marks_obtained * 100.0 / (SELECT total_marks FROM tests WHERE tests.test_id = student_test_grades.test_id)) >= 50 THEN 'D'
                ELSE 'F'
            END
        ) STORED,
        remarks TEXT NULL COMMENT 'Optional remarks from faculty',
        graded_at TIMESTAMP NULL COMMENT 'When the grade was assigned',
        graded_by INT NULL COMMENT 'Faculty who assigned the grade',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (test_id) REFERENCES tests(test_id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE,
        FOREIGN KEY (graded_by) REFERENCES faculty(faculty_id) ON DELETE SET NULL,
        UNIQUE KEY unique_student_test (test_id, student_id)
    ) ENGINE=InnoDB",

    // GRADE_RANGES Table
    "CREATE TABLE IF NOT EXISTS grade_ranges (
        range_id INT PRIMARY KEY AUTO_INCREMENT,
        scale_id INT DEFAULT 1 COMMENT 'For different grading scales',
        grade_label VARCHAR(5) NOT NULL,
        min_percentage DECIMAL(5,2) NOT NULL,
        max_percentage DECIMAL(5,2) NOT NULL,
        grade_points DECIMAL(3,2) NOT NULL COMMENT 'For GPA calculation',
        description VARCHAR(100) NULL
    ) ENGINE=InnoDB",

    // Insert default grade ranges
    "INSERT IGNORE INTO grade_ranges (scale_id, grade_label, min_percentage, max_percentage, grade_points, description) VALUES
        (1, 'A+', 90.00, 100.00, 4.00, 'Exceptional'),
        (1, 'A', 85.00, 89.99, 3.75, 'Excellent'),
        (1, 'B+', 80.00, 84.99, 3.50, 'Very Good'),
        (1, 'B', 75.00, 79.99, 3.25, 'Good'),
        (1, 'C+', 70.00, 74.99, 3.00, 'Above Average'),
        (1, 'C', 65.00, 69.99, 2.75, 'Average'),
        (1, 'D+', 60.00, 64.99, 2.50, 'Below Average'),
        (1, 'D', 50.00, 59.99, 2.00, 'Pass'),
        (1, 'F', 0.00, 49.99, 0.00, 'Fail')"
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Direct SQL Executor</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>.log { font-family: monospace; background: #f8f9fa; padding: 10px; border: 1px solid #ddd; }</style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h3 mb-4'>Direct SQL Executor - Grading System Setup</h1>";

try {
    echo "<div class='alert alert-info'>";
    echo "<h5>Database: " . DB_NAME . " @ " . DB_HOST . "</h5>";
    echo "<p>Executing " . count($sqlStatements) . " SQL statements...</p>";
    echo "</div>";
    
    echo "<div class='card'>";
    echo "<div class='card-header'><h5>Execution Log</h5></div>";
    echo "<div class='card-body'>";
    echo "<div class='log'>";
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($sqlStatements as $index => $sql) {
        $statementNum = $index + 1;
        echo "[$statementNum/" . count($sqlStatements) . "] ";
        
        try {
            $pdo->exec($sql);
            $success_count++;
            
            // Determine what was executed
            if (stripos($sql, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?(\w+)/i', $sql, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "✅ Created table: $tableName<br>";
            } elseif (stripos($sql, 'INSERT') !== false) {
                preg_match('/INSERT\s+(?:IGNORE\s+)?INTO\s+(\w+)/i', $sql, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "✅ Inserted data into: $tableName<br>";
            } else {
                echo "✅ Executed SQL statement<br>";
            }
            
        } catch (PDOException $e) {
            $error_count++;
            $error = $e->getMessage();
            
            // Check if it's just a "table exists" error
            if (stripos($error, 'already exists') !== false) {
                preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?(\w+)/i', $sql, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "ℹ️ Table $tableName already exists<br>";
                $error_count--; // Don't count this as a real error
            } else {
                echo "❌ ERROR: " . htmlspecialchars($error) . "<br>";
            }
        }
    }
    
    echo "</div></div></div>";
    
    // Verification
    echo "<div class='card mt-3'>";
    echo "<div class='card-header'><h5>Verification</h5></div>";
    echo "<div class='card-body'>";
    
    $tables_to_check = ['subjects', 'subject_faculty', 'test_types', 'tests', 'student_test_grades', 'grade_ranges'];
    $created_tables = [];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            $created_tables[] = $table;
            echo "✅ Table '$table' exists with $count records<br>";
        } catch (PDOException $e) {
            echo "❌ Table '$table' not accessible<br>";
        }
    }
    
    echo "</div></div>";
    
    // Final result
    if (count($created_tables) === count($tables_to_check)) {
        echo "<div class='alert alert-success mt-3'>";
        echo "<h5>🎉 SUCCESS! Grading System Setup Complete</h5>";
        echo "<p>All required tables have been created successfully.</p>";
        echo "<p><strong>You can now use:</strong></p>";
        echo "<a href='subjects.php' class='btn btn-primary me-2'>📚 Subjects Management</a>";
        echo "<a href='tests.php' class='btn btn-primary me-2'>📝 Tests Management</a>";
        echo "<a href='grades.php' class='btn btn-primary me-2'>🏆 Grading Interface</a>";
        echo "<a href='student-grades.php' class='btn btn-primary me-2'>📊 Student Grades</a>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning mt-3'>";
        echo "<h5>⚠️ Partial Success</h5>";
        echo "<p>Some tables were created but there may be issues.</p>";
        echo "<p>Created: " . implode(', ', $created_tables) . "</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ Setup Failed</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='mt-3'>";
echo "<a href='index.php' class='btn btn-outline-primary'>🏠 Back to Dashboard</a>";
echo "<a href='diagnostic.php' class='btn btn-outline-secondary'>🔍 Run Diagnostics</a>";
echo "</div>";

echo "</div></body></html>";
?>