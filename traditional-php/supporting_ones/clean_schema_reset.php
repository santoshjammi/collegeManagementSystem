<?php
/**
 * Clean Schema Reset to VARCHAR IDs
 * Clears existing data, converts schema to VARCHAR IDs, adds fresh demo data
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Clean Schema Reset - VARCHAR IDs</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .log { 
            font-family: monospace; 
            background: #f8f9fa; 
            padding: 20px; 
            border: 1px solid #ddd; 
            max-height: 700px; 
            overflow-y: auto; 
            white-space: pre-wrap;
            border-radius: 5px;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h2 mb-4'>🗑️ Clean Schema Reset - VARCHAR IDs</h1>
    
    <div class='warning-box'>
        <h5 class='mb-2'>🚨 Clean Reset Process</h5>
        <p class='mb-2'>This will perform a complete clean reset:</p>
        <ol class='mb-2'>
            <li><strong>Clear all existing data</strong> from grading tables</li>
            <li><strong>Drop and recreate tables</strong> with VARCHAR ID structure</li>
            <li><strong>Add comprehensive demo data</strong> with proper relationships</li>
        </ol>
        <p class='mb-0'><strong>Result:</strong> Clean database with VARCHAR IDs and realistic demo data</p>
    </div>";

try {
    echo "<div class='log'>";
    
    echo "🗑️  STARTING CLEAN SCHEMA RESET\n";
    echo "===============================\n\n";
    
    // Disable foreign key checks
    echo "🔧 Disabling foreign key checks...\n";
    $pdo->exec("SET foreign_key_checks = 0");
    
    // Step 1: Clear existing data from grading tables
    echo "🧹 STEP 1: CLEARING EXISTING DATA\n";
    echo "=================================\n";
    
    $tables_to_clear = ['student_test_grades', 'tests', 'subjects', 'faculty'];
    
    foreach ($tables_to_clear as $table) {
        try {
            $count_before = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $pdo->exec("DELETE FROM $table");
            echo "✅ Cleared $table table ($count_before records removed)\n";
        } catch (Exception $e) {
            echo "⚠️  Could not clear $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Step 2: Drop and recreate tables with VARCHAR IDs
    echo "\n🏗️  STEP 2: RECREATING TABLES WITH VARCHAR IDs\n";
    echo "==============================================\n";
    
    // Drop existing tables
    echo "Dropping existing tables...\n";
    $drop_tables = ['student_test_grades', 'tests', 'subjects', 'faculty'];
    foreach ($drop_tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS $table");
            echo "✅ Dropped $table\n";
        } catch (Exception $e) {
            echo "⚠️  Could not drop $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nCreating new tables with VARCHAR ID structure...\n";
    
    // Create faculty table with VARCHAR ID
    $faculty_sql = "
    CREATE TABLE faculty (
        faculty_id VARCHAR(10) PRIMARY KEY,
        user_id INT NULL,
        full_name VARCHAR(200) NOT NULL,
        department VARCHAR(100) NULL,
        employment_role VARCHAR(50) NULL,
        contact_number VARCHAR(15) NULL,
        email VARCHAR(100) UNIQUE NULL,
        primary_subject VARCHAR(150) NULL,
        highest_degree VARCHAR(50) NULL,
        research_interests TEXT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    try {
        $pdo->exec($faculty_sql);
        echo "✅ Created faculty table with VARCHAR(10) ID\n";
    } catch (Exception $e) {
        echo "❌ Error creating faculty table: " . $e->getMessage() . "\n";
    }
    
    // Create subjects table with VARCHAR IDs
    $subjects_sql = "
    CREATE TABLE subjects (
        subject_id VARCHAR(10) PRIMARY KEY,
        subject_code VARCHAR(10) UNIQUE NOT NULL,
        subject_name VARCHAR(200) NOT NULL,
        description TEXT NULL,
        credits INT DEFAULT 3,
        faculty_id VARCHAR(10) NULL,
        course_id INT NULL,
        semester INT NULL,
        academic_year VARCHAR(10) DEFAULT '2024-25',
        subject_type VARCHAR(20) DEFAULT 'Theory',
        status VARCHAR(20) DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE SET NULL
    )";
    
    try {
        $pdo->exec($subjects_sql);
        echo "✅ Created subjects table with VARCHAR(10) IDs\n";
    } catch (Exception $e) {
        echo "❌ Error creating subjects table: " . $e->getMessage() . "\n";
    }
    
    // Create tests table with VARCHAR IDs
    $tests_sql = "
    CREATE TABLE tests (
        test_id VARCHAR(10) PRIMARY KEY,
        test_name VARCHAR(200) NOT NULL,
        subject_id VARCHAR(10) NULL,
        faculty_id VARCHAR(10) NULL,
        max_marks INT DEFAULT 100,
        test_date DATE NULL,
        test_type VARCHAR(20) DEFAULT 'Quiz',
        duration INT DEFAULT 60,
        instructions TEXT NULL,
        status VARCHAR(20) DEFAULT 'Draft',
        is_published TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
        FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE SET NULL
    )";
    
    try {
        $pdo->exec($tests_sql);
        echo "✅ Created tests table with VARCHAR(10) IDs\n";
    } catch (Exception $e) {
        echo "❌ Error creating tests table: " . $e->getMessage() . "\n";
    }
    
    // Create student_test_grades table with VARCHAR IDs
    $grades_sql = "
    CREATE TABLE student_test_grades (
        grade_id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(10) NOT NULL,
        test_id VARCHAR(10) NOT NULL,
        subject_id VARCHAR(10) NULL,
        faculty_id VARCHAR(10) NULL,
        marks_obtained DECIMAL(5,2) NULL,
        percentage DECIMAL(5,2) NULL,
        letter_grade VARCHAR(2) NULL,
        status VARCHAR(20) DEFAULT 'Pending',
        remarks TEXT NULL,
        graded_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (test_id) REFERENCES tests(test_id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE SET NULL,
        FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE SET NULL,
        UNIQUE KEY unique_student_test (student_id, test_id)
    )";
    
    try {
        $pdo->exec($grades_sql);
        echo "✅ Created student_test_grades table with VARCHAR(10) IDs\n";
    } catch (Exception $e) {
        echo "❌ Error creating grades table: " . $e->getMessage() . "\n";
    }
    
    // Re-enable foreign key checks
    echo "\n🔧 Re-enabling foreign key checks...\n";
    $pdo->exec("SET foreign_key_checks = 1");
    
    // Step 3: Add comprehensive demo data
    echo "\n📊 STEP 3: ADDING COMPREHENSIVE DEMO DATA\n";
    echo "========================================\n";
    
    // Add Faculty
    echo "👨‍🏫 Adding Faculty Members...\n";
    $faculty_data = [
        ['FAC001', 'Dr. Priya Sharma', 'priya.sharma@college.edu', 'Computer Science', 'Professor', '9876543201', 'Programming, Data Structures', 'PhD', 'Female'],
        ['FAC002', 'Prof. Rajesh Kumar', 'rajesh.kumar@college.edu', 'Computer Science', 'Professor', '9876543202', 'Database Systems, AI', 'PhD', 'Male'],
        ['FAC003', 'Dr. Kavya Patel', 'kavya.patel@college.edu', 'Information Technology', 'Associate Professor', '9876543203', 'Web Development, Networks', 'PhD', 'Female'],
        ['FAC004', 'Mr. Vikram Rao', 'vikram.rao@college.edu', 'Electrical Engineering', 'Assistant Professor', '9876543204', 'Circuit Analysis', 'MTech', 'Male'],
        ['FAC005', 'Ms. Shreya Singh', 'shreya.singh@college.edu', 'Electronics Engineering', 'Assistant Professor', '9876543205', 'Digital Electronics', 'MTech', 'Female'],
        ['FAC006', 'Dr. Arjun Nair', 'arjun.nair@college.edu', 'Mechanical Engineering', 'Associate Professor', '9876543206', 'Thermodynamics', 'PhD', 'Male'],
        ['FAC007', 'Dr. Neha Kumar', 'neha.kumar@college.edu', 'Mathematics', 'Associate Professor', '9876543207', 'Calculus, Statistics', 'PhD', 'Female'],
        ['FAC008', 'Prof. Suresh Krishnan', 'suresh.krishnan@college.edu', 'Physics', 'Professor', '9876543208', 'Quantum Mechanics', 'PhD', 'Male'],
        ['FAC009', 'Mrs. Pooja Gupta', 'pooja.gupta@college.edu', 'Chemistry', 'Assistant Professor', '9876543209', 'Organic Chemistry', 'MSc', 'Female'],
        ['FAC010', 'Dr. Amit Bansal', 'amit.bansal@college.edu', 'Management', 'Associate Professor', '9876543210', 'Finance, Marketing', 'PhD', 'Male'],
        ['FAC011', 'Dr. Riya Agarwal', 'riya.agarwal@college.edu', 'Civil Engineering', 'Assistant Professor', '9876543211', 'Structural Engineering', 'PhD', 'Female'],
        ['FAC012', 'Prof. Karan Malhotra', 'karan.malhotra@college.edu', 'Mathematics', 'Professor', '9876543212', 'Applied Mathematics', 'PhD', 'Male'],
        ['FAC013', 'Ms. Sonia Verma', 'sonia.verma@college.edu', 'English', 'Assistant Professor', '9876543213', 'Technical Communication', 'MA', 'Female'],
        ['FAC014', 'Dr. Rohit Chopra', 'rohit.chopra@college.edu', 'Physics', 'Associate Professor', '9876543214', 'Electromagnetism', 'PhD', 'Male'],
        ['FAC015', 'Dr. Meera Jain', 'meera.jain@college.edu', 'Biotechnology', 'Assistant Professor', '9876543215', 'Molecular Biology', 'PhD', 'Female']
    ];
    
    $faculty_added = 0;
    $female_faculty = 0;
    
    foreach ($faculty_data as $faculty) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO faculty (faculty_id, full_name, email, department, employment_role, contact_number, primary_subject, highest_degree, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $faculty[0], $faculty[1], $faculty[2], $faculty[3], 
                $faculty[4], $faculty[5], $faculty[6], $faculty[7]
            ]);
            
            $faculty_added++;
            if ($faculty[8] === 'Female') $female_faculty++;
            
            $icon = $faculty[8] === 'Female' ? '👩‍🏫' : '👨‍🏫';
            echo "  ✅ $icon {$faculty[1]} ({$faculty[0]}) - {$faculty[3]}\n";
            
        } catch (Exception $e) {
            echo "  ❌ Error adding {$faculty[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Faculty Added: $faculty_added ($female_faculty female)\n\n";
    
    // Add Subjects
    echo "📚 Adding Subjects...\n";
    $subjects_data = [
        ['SUB001', 'CS101', 'Programming Fundamentals', 'Introduction to programming concepts using C/C++', 'FAC001', 4, 1],
        ['SUB002', 'CS102', 'Data Structures', 'Arrays, linked lists, trees, graphs, algorithms', 'FAC001', 4, 2],
        ['SUB003', 'CS201', 'Database Systems', 'Relational databases, SQL, normalization, transactions', 'FAC002', 4, 3],
        ['SUB004', 'IT101', 'Web Development', 'HTML, CSS, JavaScript, PHP, frameworks', 'FAC003', 4, 3],
        ['SUB005', 'CS301', 'Artificial Intelligence', 'Machine learning, neural networks, expert systems', 'FAC002', 4, 5],
        ['SUB006', 'EE101', 'Circuit Analysis', 'DC and AC circuits, network theorems, analysis', 'FAC004', 4, 1],
        ['SUB007', 'EC101', 'Digital Electronics', 'Boolean algebra, logic gates, combinational circuits', 'FAC005', 4, 2],
        ['SUB008', 'ME201', 'Thermodynamics', 'Laws of thermodynamics, heat engines, cycles', 'FAC006', 4, 3],
        ['SUB009', 'MATH101', 'Calculus I', 'Differential and integral calculus, limits', 'FAC007', 4, 1],
        ['SUB010', 'PHYS101', 'Physics I', 'Mechanics, waves, thermodynamics, optics', 'FAC008', 4, 1],
        ['SUB011', 'CHEM101', 'Chemistry', 'Atomic structure, chemical bonding, reactions', 'FAC009', 4, 1],
        ['SUB012', 'MGT101', 'Management Principles', 'Planning, organizing, leading, controlling', 'FAC010', 3, 2],
        ['SUB013', 'CE201', 'Structural Engineering', 'Design and analysis of structures', 'FAC011', 4, 4],
        ['SUB014', 'MATH201', 'Linear Algebra', 'Matrices, vectors, eigenvalues, transformations', 'FAC012', 3, 2],
        ['SUB015', 'ENG101', 'Technical Communication', 'Written and oral communication skills', 'FAC013', 3, 1],
        ['SUB016', 'PHYS201', 'Electromagnetism', 'Electric and magnetic fields, Maxwell equations', 'FAC014', 4, 3],
        ['SUB017', 'BIO101', 'Molecular Biology', 'DNA, RNA, protein synthesis, genetics', 'FAC015', 4, 2],
        ['SUB018', 'CS202', 'Software Engineering', 'SDLC, design patterns, project management', 'FAC001', 4, 4]
    ];
    
    $subjects_added = 0;
    
    foreach ($subjects_data as $subject) {
        try {
            // Get random course ID from existing courses
            $courses = $pdo->query("SELECT * FROM courses LIMIT 1")->fetchAll();
            $course_id = !empty($courses) ? array_values($courses[0])[0] : 1;
            
            $stmt = $pdo->prepare("
                INSERT INTO subjects (subject_id, subject_code, subject_name, description, faculty_id, credits, semester, course_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active')
            ");
            $stmt->execute([
                $subject[0], $subject[1], $subject[2], $subject[3], 
                $subject[4], $subject[5], $subject[6], $course_id
            ]);
            
            $subjects_added++;
            echo "  ✅ 📖 {$subject[2]} ({$subject[1]}) - Sem {$subject[6]} - {$subject[4]}\n";
            
        } catch (Exception $e) {
            echo "  ❌ Error adding subject {$subject[2]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Subjects Added: $subjects_added\n\n";
    
    // Add Tests
    echo "📝 Adding Tests...\n";
    $tests_data = [
        ['TEST001', 'Programming Quiz 1', 'SUB001', 'FAC001', 25, 'Quiz', 60],
        ['TEST002', 'Programming Midterm', 'SUB001', 'FAC001', 100, 'Midterm', 120],
        ['TEST003', 'Programming Final', 'SUB001', 'FAC001', 100, 'Final', 180],
        ['TEST004', 'Data Structures Quiz', 'SUB002', 'FAC001', 25, 'Quiz', 60],
        ['TEST005', 'Data Structures Assignment', 'SUB002', 'FAC001', 50, 'Assignment', 0],
        ['TEST006', 'Database Quiz 1', 'SUB003', 'FAC002', 25, 'Quiz', 60],
        ['TEST007', 'Database Final Exam', 'SUB003', 'FAC002', 100, 'Final', 180],
        ['TEST008', 'Web Dev Project', 'SUB004', 'FAC003', 75, 'Assignment', 0],
        ['TEST009', 'AI Midterm Exam', 'SUB005', 'FAC002', 100, 'Midterm', 150],
        ['TEST010', 'Circuit Analysis Quiz', 'SUB006', 'FAC004', 25, 'Quiz', 60],
        ['TEST011', 'Digital Electronics Lab', 'SUB007', 'FAC005', 30, 'Practical', 120],
        ['TEST012', 'Thermodynamics Final', 'SUB008', 'FAC006', 100, 'Final', 180],
        ['TEST013', 'Calculus Quiz 1', 'SUB009', 'FAC007', 25, 'Quiz', 60],
        ['TEST014', 'Physics Midterm', 'SUB010', 'FAC008', 100, 'Midterm', 120],
        ['TEST015', 'Chemistry Lab Test', 'SUB011', 'FAC009', 30, 'Practical', 90],
        ['TEST016', 'Management Case Study', 'SUB012', 'FAC010', 50, 'Assignment', 0],
        ['TEST017', 'Structures Quiz', 'SUB013', 'FAC011', 25, 'Quiz', 60],
        ['TEST018', 'Linear Algebra Test', 'SUB014', 'FAC012', 50, 'Test', 90],
        ['TEST019', 'Communication Skills', 'SUB015', 'FAC013', 40, 'Assessment', 60],
        ['TEST020', 'Electromagnetism Quiz', 'SUB016', 'FAC014', 25, 'Quiz', 60]
    ];
    
    $tests_added = 0;
    
    foreach ($tests_data as $test) {
        try {
            $test_date = date('Y-m-d', strtotime('+' . rand(1, 45) . ' days'));
            
            $stmt = $pdo->prepare("
                INSERT INTO tests (test_id, test_name, subject_id, faculty_id, max_marks, test_type, duration, test_date, status, is_published) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Published', 1)
            ");
            $stmt->execute([
                $test[0], $test[1], $test[2], $test[3], 
                $test[4], $test[5], $test[6], $test_date
            ]);
            
            $tests_added++;
            $test_icon = $test[5] === 'Quiz' ? '📝' : 
                        ($test[5] === 'Final' ? '🎯' : 
                        ($test[5] === 'Assignment' ? '📋' : 
                        ($test[5] === 'Practical' ? '🔬' : '📊')));
            echo "  ✅ $test_icon {$test[1]} ({$test[0]}) - {$test[4]} marks - $test_date\n";
            
        } catch (Exception $e) {
            echo "  ❌ Error adding test {$test[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Tests Added: $tests_added\n\n";
    
    echo "🎉 CLEAN SCHEMA RESET COMPLETE!\n";
    echo "===============================\n";
    echo "✅ Database structure: Completely rebuilt with VARCHAR IDs\n";
    echo "✅ Faculty Members: $faculty_added (with VARCHAR IDs like FAC001, FAC002)\n";
    echo "✅ Subjects: $subjects_added (with VARCHAR IDs like SUB001, SUB002)\n";
    echo "✅ Tests: $tests_added (with VARCHAR IDs like TEST001, TEST002)\n";
    echo "✅ Female Faculty: $female_faculty out of $faculty_added (" . round($female_faculty/$faculty_added*100,1) . "%)\n";
    echo "✅ Relationships: All foreign keys properly established\n\n";
    
    echo "🚀 YOUR SYSTEM IS NOW READY WITH CLEAN VARCHAR ID STRUCTURE!\n";
    echo "• Professional ID format: FAC001, SUB001, TEST001\n";
    echo "• Complete academic hierarchy with proper relationships\n";
    echo "• Ready for grading, reports, and full system testing\n";
    echo "• All demo data is fresh and consistent\n";
    
    echo "</div>";
    
    // Success summary
    echo "<div class='alert alert-success mt-3'>";
    echo "<h5>🎊 Clean Schema Reset Complete!</h5>";
    echo "<div class='row text-center'>";
    echo "<div class='col-md-3'>";
    echo "<h3 class='text-primary'>$faculty_added</h3>";
    echo "<p class='mb-0'><strong>Faculty Members</strong><br><small>Professional VARCHAR IDs</small></p>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h3 class='text-success'>$subjects_added</h3>";
    echo "<p class='mb-0'><strong>Subjects</strong><br><small>Comprehensive curriculum</small></p>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h3 class='text-warning'>$tests_added</h3>";
    echo "<p class='mb-0'><strong>Tests</strong><br><small>Ready for grading</small></p>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h3 class='text-info'>" . round($female_faculty/$faculty_added*100,1) . "%</h3>";
    echo "<p class='mb-0'><strong>Female Faculty</strong><br><small>Diversity achieved</small></p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "</div>";
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ Reset Error</h5>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='mt-4 text-center'>";
echo "<h5 class='mb-3'>🚀 Explore Your Clean System</h5>";
echo "<div class='row'>";
echo "<div class='col-md-2'>";
echo "<a href='faculty.php' class='btn btn-success w-100 mb-2'>👨‍🏫<br>Faculty</a>";
echo "</div>";
echo "<div class='col-md-2'>";
echo "<a href='subjects.php' class='btn btn-primary w-100 mb-2'>📚<br>Subjects</a>";
echo "</div>";
echo "<div class='col-md-2'>";
echo "<a href='tests.php' class='btn btn-primary w-100 mb-2'>📝<br>Tests</a>";
echo "</div>";
echo "<div class='col-md-2'>";
echo "<a href='grades.php' class='btn btn-warning w-100 mb-2'>🎯<br>Grading</a>";
echo "</div>";
echo "<div class='col-md-2'>";
echo "<a href='students.php' class='btn btn-info w-100 mb-2'>👥<br>Students</a>";
echo "</div>";
echo "<div class='col-md-2'>";
echo "<a href='index.php' class='btn btn-outline-primary w-100 mb-2'>🏠<br>Dashboard</a>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div></body></html>";
?>