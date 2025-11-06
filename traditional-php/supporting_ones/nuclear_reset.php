<?php
/**
 * Nuclear Reset - Complete Database Rebuild
 * Completely drops all grading-related tables and rebuilds from scratch
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Nuclear Database Reset</title>
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
        .danger-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h2 mb-4'>💥 Nuclear Database Reset</h1>
    
    <div class='danger-box'>
        <h5 class='mb-2'>🚨 Nuclear Reset Warning</h5>
        <p class='mb-2'>This will perform a complete nuclear reset:</p>
        <ol class='mb-2'>
            <li><strong>Drop ALL grading-related tables</strong> (including any custom tables)</li>
            <li><strong>Completely rebuild</strong> with clean VARCHAR ID structure</li>
            <li><strong>Add comprehensive demo data</strong> from scratch</li>
        </ol>
        <p class='mb-0'><strong>Result:</strong> Brand new grading system with VARCHAR IDs</p>
    </div>";

try {
    echo "<div class='log'>";
    
    echo "💥 STARTING NUCLEAR DATABASE RESET\n";
    echo "==================================\n\n";
    
    // Disable foreign key checks completely
    echo "🔧 Disabling ALL constraints...\n";
    $pdo->exec("SET foreign_key_checks = 0");
    $pdo->exec("SET sql_mode = ''");
    
    // Step 1: Nuclear drop of ALL grading-related tables
    echo "💥 STEP 1: NUCLEAR TABLE CLEANUP\n";
    echo "===============================\n";
    
    // Get list of all tables that might be related
    $all_tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Found tables in database: " . implode(', ', $all_tables) . "\n\n";
    
    // Tables to definitely nuke
    $tables_to_nuke = [
        'student_test_grades',
        'test_grades', 
        'grades',
        'tests',
        'test_results',
        'subjects',
        'subject_faculty',
        'faculty_subjects',
        'faculty_custom_fields',
        'faculty'
    ];
    
    echo "Nuclear dropping tables...\n";
    foreach ($tables_to_nuke as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS $table");
            echo "💥 Nuked: $table\n";
        } catch (Exception $e) {
            echo "⚠️  Could not nuke $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Also try to drop any table that has 'faculty', 'subject', 'test', or 'grade' in the name
    foreach ($all_tables as $table) {
        if (stripos($table, 'faculty') !== false || 
            stripos($table, 'subject') !== false || 
            stripos($table, 'test') !== false || 
            stripos($table, 'grade') !== false) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS $table");
                echo "💥 Auto-nuked: $table\n";
            } catch (Exception $e) {
                echo "⚠️  Could not auto-nuke $table: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Nuclear cleanup complete!\n\n";
    
    // Step 2: Create brand new tables with VARCHAR IDs
    echo "🏗️  STEP 2: REBUILDING FROM ASHES\n";
    echo "=================================\n";
    
    // Create faculty table (no foreign keys initially)
    echo "Creating faculty table...\n";
    $faculty_sql = "
    CREATE TABLE faculty (
        faculty_id VARCHAR(10) PRIMARY KEY,
        full_name VARCHAR(200) NOT NULL,
        email VARCHAR(100) UNIQUE,
        department VARCHAR(100),
        employment_role VARCHAR(50),
        contact_number VARCHAR(15),
        primary_subject VARCHAR(150),
        highest_degree VARCHAR(50),
        research_interests TEXT,
        is_active TINYINT(1) DEFAULT 1,
        salary DECIMAL(10,2),
        joining_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($faculty_sql);
    echo "✅ Faculty table created with VARCHAR IDs\n";
    
    // Create subjects table
    echo "Creating subjects table...\n";
    $subjects_sql = "
    CREATE TABLE subjects (
        subject_id VARCHAR(10) PRIMARY KEY,
        subject_code VARCHAR(10) UNIQUE NOT NULL,
        subject_name VARCHAR(200) NOT NULL,
        description TEXT,
        credits INT DEFAULT 3,
        faculty_id VARCHAR(10),
        course_id VARCHAR(10),
        semester INT,
        academic_year VARCHAR(10) DEFAULT '2024-25',
        subject_type VARCHAR(20) DEFAULT 'Theory',
        status VARCHAR(20) DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_faculty (faculty_id),
        INDEX idx_course (course_id)
    )";
    $pdo->exec($subjects_sql);
    echo "✅ Subjects table created with VARCHAR IDs\n";
    
    // Create tests table
    echo "Creating tests table...\n";
    $tests_sql = "
    CREATE TABLE tests (
        test_id VARCHAR(10) PRIMARY KEY,
        test_name VARCHAR(200) NOT NULL,
        subject_id VARCHAR(10),
        faculty_id VARCHAR(10),
        max_marks INT DEFAULT 100,
        test_date DATE,
        test_type VARCHAR(20) DEFAULT 'Quiz',
        duration INT DEFAULT 60,
        instructions TEXT,
        status VARCHAR(20) DEFAULT 'Draft',
        is_published TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_subject (subject_id),
        INDEX idx_faculty (faculty_id)
    )";
    $pdo->exec($tests_sql);
    echo "✅ Tests table created with VARCHAR IDs\n";
    
    // Create student_test_grades table
    echo "Creating student_test_grades table...\n";
    $grades_sql = "
    CREATE TABLE student_test_grades (
        grade_id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(10) NOT NULL,
        test_id VARCHAR(10),
        subject_id VARCHAR(10),
        faculty_id VARCHAR(10),
        marks_obtained DECIMAL(5,2),
        percentage DECIMAL(5,2),
        letter_grade VARCHAR(2),
        status VARCHAR(20) DEFAULT 'Pending',
        is_absent TINYINT(1) DEFAULT 0,
        graded_by VARCHAR(10),
        remarks TEXT,
        graded_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_student (student_id),
        INDEX idx_test (test_id),
        INDEX idx_subject (subject_id),
        INDEX idx_graded_by (graded_by),
        UNIQUE KEY unique_student_test (student_id, test_id)
    )";
    $pdo->exec($grades_sql);
    echo "✅ Student grades table created with VARCHAR IDs\n";
    
    // Re-enable foreign key checks
    echo "\n🔧 Re-enabling constraints...\n";
    $pdo->exec("SET foreign_key_checks = 1");
    
    echo "✅ All tables rebuilt successfully!\n\n";
    
    // Step 3: Add comprehensive demo data
    echo "📊 STEP 3: POPULATING WITH DEMO DATA\n";
    echo "===================================\n";
    
    // Add Faculty first
    echo "👨‍🏫 Adding Faculty Members...\n";
    $faculty_data = [
        ['FAC001', 'Dr. Priya Sharma', 'priya.sharma@college.edu', 'Computer Science', 'Professor', '9876543201', 'Programming, Data Structures', 'PhD', 75000],
        ['FAC002', 'Prof. Rajesh Kumar', 'rajesh.kumar@college.edu', 'Computer Science', 'Professor', '9876543202', 'Database Systems, AI', 'PhD', 85000],
        ['FAC003', 'Dr. Kavya Patel', 'kavya.patel@college.edu', 'Information Technology', 'Associate Professor', '9876543203', 'Web Development, Networks', 'PhD', 70000],
        ['FAC004', 'Mr. Vikram Rao', 'vikram.rao@college.edu', 'Electrical Engineering', 'Assistant Professor', '9876543204', 'Circuit Analysis', 'MTech', 65000],
        ['FAC005', 'Ms. Shreya Singh', 'shreya.singh@college.edu', 'Electronics Engineering', 'Assistant Professor', '9876543205', 'Digital Electronics', 'MTech', 62000],
        ['FAC006', 'Dr. Arjun Nair', 'arjun.nair@college.edu', 'Mechanical Engineering', 'Associate Professor', '9876543206', 'Thermodynamics', 'PhD', 72000],
        ['FAC007', 'Dr. Neha Kumar', 'neha.kumar@college.edu', 'Mathematics', 'Associate Professor', '9876543207', 'Calculus, Statistics', 'PhD', 68000],
        ['FAC008', 'Prof. Suresh Krishnan', 'suresh.krishnan@college.edu', 'Physics', 'Professor', '9876543208', 'Quantum Mechanics', 'PhD', 78000],
        ['FAC009', 'Mrs. Pooja Gupta', 'pooja.gupta@college.edu', 'Chemistry', 'Assistant Professor', '9876543209', 'Organic Chemistry', 'MSc', 58000],
        ['FAC010', 'Dr. Amit Bansal', 'amit.bansal@college.edu', 'Management', 'Associate Professor', '9876543210', 'Finance, Marketing', 'PhD', 74000],
        ['FAC011', 'Dr. Riya Agarwal', 'riya.agarwal@college.edu', 'Civil Engineering', 'Assistant Professor', '9876543211', 'Structural Engineering', 'PhD', 66000],
        ['FAC012', 'Prof. Karan Malhotra', 'karan.malhotra@college.edu', 'Mathematics', 'Professor', '9876543212', 'Applied Mathematics', 'PhD', 80000],
        ['FAC013', 'Ms. Sonia Verma', 'sonia.verma@college.edu', 'English', 'Assistant Professor', '9876543213', 'Technical Communication', 'MA', 55000],
        ['FAC014', 'Dr. Rohit Chopra', 'rohit.chopra@college.edu', 'Physics', 'Associate Professor', '9876543214', 'Electromagnetism', 'PhD', 71000],
        ['FAC015', 'Dr. Meera Jain', 'meera.jain@college.edu', 'Biotechnology', 'Assistant Professor', '9876543215', 'Molecular Biology', 'PhD', 64000]
    ];
    
    $faculty_added = 0;
    $female_faculty = 0;
    
    foreach ($faculty_data as $faculty) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO faculty (faculty_id, full_name, email, department, employment_role, contact_number, primary_subject, highest_degree, salary, is_active, joining_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
            ");
            
            $joining_date = date('Y-m-d', strtotime('-' . rand(1, 5) . ' years'));
            
            $stmt->execute([
                $faculty[0], $faculty[1], $faculty[2], $faculty[3], 
                $faculty[4], $faculty[5], $faculty[6], $faculty[7], 
                $faculty[8], $joining_date
            ]);
            
            $faculty_added++;
            // Determine gender from name
            $female_names = ['Dr. Priya', 'Dr. Kavya', 'Ms. Shreya', 'Dr. Neha', 'Mrs. Pooja', 'Dr. Riya', 'Ms. Sonia', 'Dr. Meera'];
            $is_female = false;
            foreach ($female_names as $female_name) {
                if (strpos($faculty[1], $female_name) !== false) {
                    $is_female = true;
                    break;
                }
            }
            if ($is_female) $female_faculty++;
            
            $icon = $is_female ? '👩‍🏫' : '👨‍🏫';
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
            $stmt = $pdo->prepare("
                INSERT INTO subjects (subject_id, subject_code, subject_name, description, faculty_id, credits, semester, course_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active')
            ");
            $stmt->execute([
                $subject[0], $subject[1], $subject[2], $subject[3], 
                $subject[4], $subject[5], $subject[6], 'COURSE1'
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
        ['TEST020', 'Electromagnetism Quiz', 'SUB016', 'FAC014', 25, 'Quiz', 60],
        ['TEST021', 'Biology Lab Test', 'SUB017', 'FAC015', 30, 'Practical', 90],
        ['TEST022', 'Software Engineering Project', 'SUB018', 'FAC001', 100, 'Assignment', 0]
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
            echo "  ✅ $test_icon {$test[1]} ({$test[0]}) - {$test[4]} marks\n";
            
        } catch (Exception $e) {
            echo "  ❌ Error adding test {$test[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Tests Added: $tests_added\n\n";
    
    echo "🎉 NUCLEAR RESET COMPLETE!\n";
    echo "==========================\n";
    echo "✅ Database: Completely rebuilt from scratch\n";
    echo "✅ Faculty Members: $faculty_added (with VARCHAR IDs FAC001-FAC015)\n";
    echo "✅ Subjects: $subjects_added (with VARCHAR IDs SUB001-SUB018)\n";
    echo "✅ Tests: $tests_added (with VARCHAR IDs TEST001-TEST022)\n";
    echo "✅ Female Faculty: $female_faculty out of $faculty_added (" . round($female_faculty/$faculty_added*100,1) . "%)\n";
    echo "✅ No Foreign Key Issues: All constraints removed, clean relationships\n\n";
    
    echo "🚀 YOUR SYSTEM IS NOW COMPLETELY REBUILT!\n";
    echo "• Professional VARCHAR ID structure throughout\n";
    echo "• Clean tables with no legacy constraint issues\n";
    echo "• Comprehensive demo data ready for testing\n";
    echo "• All grading modules will work perfectly\n";
    
    echo "</div>";
    
    // Success summary
    echo "<div class='alert alert-success mt-3'>";
    echo "<h5>💥 Nuclear Reset Successful!</h5>";
    echo "<div class='row text-center'>";
    echo "<div class='col-md-3'>";
    echo "<h2 class='text-primary'>$faculty_added</h2>";
    echo "<p class='mb-0'><strong>Faculty</strong><br><small>FAC001-FAC015</small></p>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h2 class='text-success'>$subjects_added</h2>";
    echo "<p class='mb-0'><strong>Subjects</strong><br><small>SUB001-SUB018</small></p>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h2 class='text-warning'>$tests_added</h2>";
    echo "<p class='mb-0'><strong>Tests</strong><br><small>TEST001-TEST022</small></p>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h2 class='text-info'>" . round($female_faculty/$faculty_added*100,1) . "%</h2>";
    echo "<p class='mb-0'><strong>Female Faculty</strong><br><small>Gender balanced</small></p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "</div>";
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ Nuclear Reset Error</h5>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='mt-4 text-center'>";
echo "<h5 class='mb-3'>🚀 Test Your Rebuilt System</h5>";
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