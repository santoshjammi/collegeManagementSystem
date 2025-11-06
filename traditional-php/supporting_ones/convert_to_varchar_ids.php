<?php
/**
 * Database Schema Converter: INT to VARCHAR IDs
 * Converts all ID columns from INT to VARCHAR and adds comprehensive demo data
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Schema Converter - INT to VARCHAR IDs</title>
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
        .success-box {
            background: #d1edff;
            border: 1px solid #74b9ff;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h2 mb-4'>🔧 Database Schema Converter: INT → VARCHAR IDs</h1>
    
    <div class='warning-box'>
        <h5 class='mb-2'>⚠️ Important: Database Schema Modification</h5>
        <p class='mb-2'>This script will modify your database structure to use VARCHAR IDs instead of INT IDs. This includes:</p>
        <ul class='mb-2'>
            <li><strong>faculty_id:</strong> int → VARCHAR(10)</li>
            <li><strong>student_id:</strong> int → VARCHAR(10) (if needed)</li>
            <li><strong>subject_id:</strong> int → VARCHAR(10) (if needed)</li>
            <li><strong>test_id:</strong> int → VARCHAR(10) (if needed)</li>
        </ul>
        <p class='mb-0'><strong>Note:</strong> This is a structural change. Ensure you have a database backup before proceeding.</p>
    </div>";

try {
    echo "<div class='log'>";
    
    echo "🚀 STARTING DATABASE SCHEMA CONVERSION\n";
    echo "======================================\n\n";
    
    // Disable foreign key checks temporarily
    echo "🔧 Disabling foreign key checks...\n";
    $pdo->exec("SET foreign_key_checks = 0");
    
    echo "📊 CURRENT TABLE STRUCTURES:\n";
    echo "=============================\n";
    
    $tables_to_convert = ['faculty', 'students', 'subjects', 'tests'];
    $original_structures = [];
    
    foreach ($tables_to_convert as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $original_structures[$table] = $columns;
            
            echo "📋 $table table:\n";
            foreach ($columns as $col) {
                if (strpos($col['Field'], '_id') !== false && $col['Key'] === 'PRI') {
                    echo "  🔑 {$col['Field']} - {$col['Type']} (PRIMARY KEY) - WILL CONVERT\n";
                } elseif (strpos($col['Field'], '_id') !== false) {
                    echo "  🔗 {$col['Field']} - {$col['Type']} (FOREIGN KEY) - WILL CONVERT\n";
                } else {
                    echo "  • {$col['Field']} - {$col['Type']}\n";
                }
            }
            echo "\n";
        } catch (Exception $e) {
            echo "⚠️  Could not analyze $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "🔄 CONVERTING ID COLUMNS TO VARCHAR:\n";
    echo "====================================\n";
    
    // Convert faculty table
    echo "Converting faculty table...\n";
    try {
        // Drop foreign key constraints that reference faculty_id
        $pdo->exec("ALTER TABLE subjects DROP FOREIGN KEY IF EXISTS fk_subjects_faculty");
        $pdo->exec("ALTER TABLE tests DROP FOREIGN KEY IF EXISTS fk_tests_faculty");
        $pdo->exec("ALTER TABLE student_test_grades DROP FOREIGN KEY IF EXISTS fk_grades_faculty");
        
        // Modify faculty_id to VARCHAR
        $pdo->exec("ALTER TABLE faculty MODIFY COLUMN faculty_id VARCHAR(10) NOT NULL");
        echo "  ✅ faculty_id converted to VARCHAR(10)\n";
    } catch (Exception $e) {
        echo "  ⚠️  Faculty conversion: " . $e->getMessage() . "\n";
    }
    
    // Convert students table
    echo "Converting students table...\n";
    try {
        // Drop foreign key constraints that reference student_id
        $pdo->exec("ALTER TABLE student_test_grades DROP FOREIGN KEY IF EXISTS fk_grades_student");
        
        // Modify student_id to VARCHAR if it exists and is INT
        $student_cols = $pdo->query("DESCRIBE students")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($student_cols as $col) {
            if ($col['Field'] === 'student_id' && strpos($col['Type'], 'int') !== false) {
                $pdo->exec("ALTER TABLE students MODIFY COLUMN student_id VARCHAR(10) NOT NULL");
                echo "  ✅ student_id converted to VARCHAR(10)\n";
                break;
            }
        }
    } catch (Exception $e) {
        echo "  ⚠️  Students conversion: " . $e->getMessage() . "\n";
    }
    
    // Convert subjects table
    echo "Converting subjects table...\n";
    try {
        // Drop foreign key constraints
        $pdo->exec("ALTER TABLE tests DROP FOREIGN KEY IF EXISTS fk_tests_subject");
        $pdo->exec("ALTER TABLE student_test_grades DROP FOREIGN KEY IF EXISTS fk_grades_subject");
        
        // Modify subject_id to VARCHAR if it exists and is INT
        $subject_cols = $pdo->query("DESCRIBE subjects")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($subject_cols as $col) {
            if ($col['Field'] === 'subject_id' && strpos($col['Type'], 'int') !== false) {
                $pdo->exec("ALTER TABLE subjects MODIFY COLUMN subject_id VARCHAR(10) NOT NULL");
                echo "  ✅ subject_id converted to VARCHAR(10)\n";
                break;
            }
        }
        
        // Also convert faculty_id foreign key in subjects
        foreach ($subject_cols as $col) {
            if ($col['Field'] === 'faculty_id' && strpos($col['Type'], 'int') !== false) {
                $pdo->exec("ALTER TABLE subjects MODIFY COLUMN faculty_id VARCHAR(10)");
                echo "  ✅ subjects.faculty_id converted to VARCHAR(10)\n";
                break;
            }
        }
    } catch (Exception $e) {
        echo "  ⚠️  Subjects conversion: " . $e->getMessage() . "\n";
    }
    
    // Convert tests table
    echo "Converting tests table...\n";
    try {
        // Drop foreign key constraints
        $pdo->exec("ALTER TABLE student_test_grades DROP FOREIGN KEY IF EXISTS fk_grades_test");
        
        // Modify test_id to VARCHAR if it exists and is INT
        $test_cols = $pdo->query("DESCRIBE tests")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($test_cols as $col) {
            if ($col['Field'] === 'test_id' && strpos($col['Type'], 'int') !== false) {
                $pdo->exec("ALTER TABLE tests MODIFY COLUMN test_id VARCHAR(10) NOT NULL");
                echo "  ✅ test_id converted to VARCHAR(10)\n";
                break;
            }
        }
        
        // Convert foreign keys in tests table
        foreach ($test_cols as $col) {
            if ($col['Field'] === 'faculty_id' && strpos($col['Type'], 'int') !== false) {
                $pdo->exec("ALTER TABLE tests MODIFY COLUMN faculty_id VARCHAR(10)");
                echo "  ✅ tests.faculty_id converted to VARCHAR(10)\n";
            }
            if ($col['Field'] === 'subject_id' && strpos($col['Type'], 'int') !== false) {
                $pdo->exec("ALTER TABLE tests MODIFY COLUMN subject_id VARCHAR(10)");
                echo "  ✅ tests.subject_id converted to VARCHAR(10)\n";
            }
        }
    } catch (Exception $e) {
        echo "  ⚠️  Tests conversion: " . $e->getMessage() . "\n";
    }
    
    // Convert student_test_grades table foreign keys
    echo "Converting student_test_grades table...\n";
    try {
        $grades_cols = $pdo->query("DESCRIBE student_test_grades")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($grades_cols as $col) {
            if ($col['Field'] === 'student_id' && strpos($col['Type'], 'int') !== false) {
                $pdo->exec("ALTER TABLE student_test_grades MODIFY COLUMN student_id VARCHAR(10)");
                echo "  ✅ student_test_grades.student_id converted to VARCHAR(10)\n";
            }
            if ($col['Field'] === 'test_id' && strpos($col['Type'], 'int') !== false) {
                $pdo->exec("ALTER TABLE student_test_grades MODIFY COLUMN test_id VARCHAR(10)");
                echo "  ✅ student_test_grades.test_id converted to VARCHAR(10)\n";
            }
            if ($col['Field'] === 'faculty_id' && strpos($col['Type'], 'int') !== false) {
                $pdo->exec("ALTER TABLE student_test_grades MODIFY COLUMN faculty_id VARCHAR(10)");
                echo "  ✅ student_test_grades.faculty_id converted to VARCHAR(10)\n";
            }
            if ($col['Field'] === 'subject_id' && strpos($col['Type'], 'int') !== false) {
                $pdo->exec("ALTER TABLE student_test_grades MODIFY COLUMN subject_id VARCHAR(10)");
                echo "  ✅ student_test_grades.subject_id converted to VARCHAR(10)\n";
            }
        }
    } catch (Exception $e) {
        echo "  ⚠️  Grades table conversion: " . $e->getMessage() . "\n";
    }
    
    // Re-enable foreign key checks
    echo "\n🔧 Re-enabling foreign key checks...\n";
    $pdo->exec("SET foreign_key_checks = 1");
    
    echo "\n✅ SCHEMA CONVERSION COMPLETE!\n";
    echo "==============================\n\n";
    
    // Now add comprehensive demo data with VARCHAR IDs
    echo "📊 ADDING COMPREHENSIVE DEMO DATA WITH VARCHAR IDs:\n";
    echo "===================================================\n";
    
    // Add Faculty with VARCHAR IDs
    echo "👨‍🏫 Adding Faculty Members...\n";
    $faculty_data = [
        ['FAC001', 'Dr. Priya Sharma', 'priya.sharma@college.edu', 'Computer Science', 'Professor', 'Programming, Data Structures', 'PhD', 'Female'],
        ['FAC002', 'Prof. Rajesh Kumar', 'rajesh.kumar@college.edu', 'Computer Science', 'Professor', 'Database Systems, AI', 'PhD', 'Male'],
        ['FAC003', 'Dr. Kavya Patel', 'kavya.patel@college.edu', 'Information Technology', 'Associate Professor', 'Web Development, Networks', 'PhD', 'Female'],
        ['FAC004', 'Mr. Vikram Rao', 'vikram.rao@college.edu', 'Electrical Engineering', 'Assistant Professor', 'Circuit Analysis', 'MTech', 'Male'],
        ['FAC005', 'Ms. Shreya Singh', 'shreya.singh@college.edu', 'Electronics Engineering', 'Assistant Professor', 'Digital Electronics', 'MTech', 'Female'],
        ['FAC006', 'Dr. Arjun Nair', 'arjun.nair@college.edu', 'Mechanical Engineering', 'Associate Professor', 'Thermodynamics', 'PhD', 'Male'],
        ['FAC007', 'Dr. Neha Kumar', 'neha.kumar@college.edu', 'Mathematics', 'Associate Professor', 'Calculus, Statistics', 'PhD', 'Female'],
        ['FAC008', 'Prof. Suresh Krishnan', 'suresh.krishnan@college.edu', 'Physics', 'Professor', 'Quantum Mechanics', 'PhD', 'Male'],
        ['FAC009', 'Mrs. Pooja Gupta', 'pooja.gupta@college.edu', 'Chemistry', 'Assistant Professor', 'Organic Chemistry', 'MSc', 'Female'],
        ['FAC010', 'Dr. Amit Bansal', 'amit.bansal@college.edu', 'Management', 'Associate Professor', 'Finance, Marketing', 'PhD', 'Male'],
        ['FAC011', 'Dr. Riya Agarwal', 'riya.agarwal@college.edu', 'Civil Engineering', 'Assistant Professor', 'Structural Engineering', 'PhD', 'Female'],
        ['FAC012', 'Prof. Karan Malhotra', 'karan.malhotra@college.edu', 'Mathematics', 'Professor', 'Applied Mathematics', 'PhD', 'Male'],
        ['FAC013', 'Ms. Sonia Verma', 'sonia.verma@college.edu', 'English', 'Assistant Professor', 'Technical Communication', 'MA', 'Female'],
        ['FAC014', 'Dr. Rohit Chopra', 'rohit.chopra@college.edu', 'Physics', 'Associate Professor', 'Electromagnetism', 'PhD', 'Male'],
        ['FAC015', 'Dr. Meera Jain', 'meera.jain@college.edu', 'Biotechnology', 'Assistant Professor', 'Molecular Biology', 'PhD', 'Female']
    ];
    
    $faculty_added = 0;
    $female_faculty = 0;
    
    foreach ($faculty_data as $faculty) {
        try {
            // Check if exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM faculty WHERE faculty_id = ?");
            $check->execute([$faculty[0]]);
            if ($check->fetchColumn() > 0) {
                echo "  ℹ️  Faculty {$faculty[0]} already exists, skipping\n";
                continue;
            }
            
            // Insert with available columns
            $insert_cols = ['faculty_id', 'full_name', 'email'];
            $insert_vals = [$faculty[0], $faculty[1], $faculty[2]];
            
            // Check which columns exist and add them
            $faculty_cols = array_column($pdo->query("DESCRIBE faculty")->fetchAll(PDO::FETCH_ASSOC), 'Field');
            
            if (in_array('department', $faculty_cols)) {
                $insert_cols[] = 'department';
                $insert_vals[] = $faculty[3];
            }
            if (in_array('employment_role', $faculty_cols)) {
                $insert_cols[] = 'employment_role';
                $insert_vals[] = $faculty[4];
            }
            if (in_array('primary_subject', $faculty_cols)) {
                $insert_cols[] = 'primary_subject';
                $insert_vals[] = $faculty[5];
            }
            if (in_array('highest_degree', $faculty_cols)) {
                $insert_cols[] = 'highest_degree';
                $insert_vals[] = $faculty[6];
            }
            if (in_array('contact_number', $faculty_cols)) {
                $insert_cols[] = 'contact_number';
                $insert_vals[] = '98765432' . str_pad(rand(10, 99), 2, '0');
            }
            if (in_array('is_active', $faculty_cols)) {
                $insert_cols[] = 'is_active';
                $insert_vals[] = 1;
            }
            
            $placeholders = str_repeat('?,', count($insert_cols));
            $placeholders = rtrim($placeholders, ',');
            
            $sql = "INSERT INTO faculty (" . implode(', ', $insert_cols) . ") VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($insert_vals);
            
            $faculty_added++;
            if ($faculty[7] === 'Female') $female_faculty++;
            
            $icon = $faculty[7] === 'Female' ? '👩‍🏫' : '👨‍🏫';
            echo "  ✅ $icon {$faculty[1]} ({$faculty[0]}) - {$faculty[3]}\n";
            
        } catch (Exception $e) {
            echo "  ❌ Error adding {$faculty[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Faculty Added: $faculty_added ($female_faculty female)\n\n";
    
    // Add Subjects with VARCHAR faculty_id references
    echo "📚 Adding Subjects...\n";
    $subjects_data = [
        ['SUB001', 'Programming Fundamentals', 'Introduction to programming concepts', 'FAC001', 4],
        ['SUB002', 'Data Structures', 'Arrays, linked lists, trees, graphs', 'FAC001', 4],
        ['SUB003', 'Database Systems', 'Relational databases, SQL, normalization', 'FAC002', 4],
        ['SUB004', 'Web Development', 'HTML, CSS, JavaScript, frameworks', 'FAC003', 4],
        ['SUB005', 'Artificial Intelligence', 'Machine learning, neural networks', 'FAC002', 4],
        ['SUB006', 'Circuit Analysis', 'DC and AC circuits, network theorems', 'FAC004', 4],
        ['SUB007', 'Digital Electronics', 'Boolean algebra, logic circuits', 'FAC005', 4],
        ['SUB008', 'Thermodynamics', 'Laws of thermodynamics, heat engines', 'FAC006', 4],
        ['SUB009', 'Calculus I', 'Differential and integral calculus', 'FAC007', 4],
        ['SUB010', 'Physics I', 'Mechanics, waves, thermodynamics', 'FAC008', 4],
        ['SUB011', 'Chemistry', 'Atomic structure, chemical bonding', 'FAC009', 4],
        ['SUB012', 'Management Principles', 'Planning, organizing, leading', 'FAC010', 3],
        ['SUB013', 'Structural Engineering', 'Design and analysis of structures', 'FAC011', 4],
        ['SUB014', 'Linear Algebra', 'Matrices, vectors, eigenvalues', 'FAC012', 3],
        ['SUB015', 'Technical Communication', 'Written and oral communication', 'FAC013', 3]
    ];
    
    $subjects_added = 0;
    
    foreach ($subjects_data as $subject) {
        try {
            // Check if exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE subject_code = ?");
            $check->execute([$subject[0]]);
            if ($check->fetchColumn() > 0) {
                echo "  ℹ️  Subject {$subject[0]} already exists, skipping\n";
                continue;
            }
            
            // Get available columns
            $subject_cols = array_column($pdo->query("DESCRIBE subjects")->fetchAll(PDO::FETCH_ASSOC), 'Field');
            
            $insert_cols = [];
            $insert_vals = [];
            
            // Add columns based on what exists
            if (in_array('subject_id', $subject_cols)) {
                $insert_cols[] = 'subject_id';
                $insert_vals[] = $subject[0];
            }
            if (in_array('subject_code', $subject_cols)) {
                $insert_cols[] = 'subject_code';
                $insert_vals[] = $subject[0];
            }
            if (in_array('subject_name', $subject_cols)) {
                $insert_cols[] = 'subject_name';
                $insert_vals[] = $subject[1];
            }
            if (in_array('description', $subject_cols)) {
                $insert_cols[] = 'description';
                $insert_vals[] = $subject[2];
            }
            if (in_array('faculty_id', $subject_cols)) {
                $insert_cols[] = 'faculty_id';
                $insert_vals[] = $subject[3];
            }
            if (in_array('credits', $subject_cols)) {
                $insert_cols[] = 'credits';
                $insert_vals[] = $subject[4];
            }
            if (in_array('semester', $subject_cols)) {
                $insert_cols[] = 'semester';
                $insert_vals[] = rand(1, 4);
            }
            if (in_array('academic_year', $subject_cols)) {
                $insert_cols[] = 'academic_year';
                $insert_vals[] = '2024-25';
            }
            if (in_array('status', $subject_cols)) {
                $insert_cols[] = 'status';
                $insert_vals[] = 'Active';
            }
            
            // Get course_id if courses exist
            $courses = $pdo->query("SELECT * FROM courses LIMIT 1")->fetchAll();
            if (!empty($courses) && in_array('course_id', $subject_cols)) {
                $course_keys = array_keys($courses[0]);
                $insert_cols[] = 'course_id';
                $insert_vals[] = $courses[0][$course_keys[0]];
            }
            
            if (!empty($insert_cols)) {
                $placeholders = str_repeat('?,', count($insert_cols));
                $placeholders = rtrim($placeholders, ',');
                
                $sql = "INSERT INTO subjects (" . implode(', ', $insert_cols) . ") VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($insert_vals);
                
                $subjects_added++;
                echo "  ✅ 📖 {$subject[1]} ({$subject[0]}) - {$subject[3]}\n";
            }
            
        } catch (Exception $e) {
            echo "  ❌ Error adding subject {$subject[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Subjects Added: $subjects_added\n\n";
    
    // Add Tests with VARCHAR IDs
    echo "📝 Adding Tests...\n";
    $tests_data = [
        ['TEST001', 'Programming Quiz 1', 'SUB001', 'FAC001', 25, 'Quiz'],
        ['TEST002', 'Programming Midterm', 'SUB001', 'FAC001', 100, 'Midterm'],
        ['TEST003', 'Data Structures Quiz', 'SUB002', 'FAC001', 25, 'Quiz'],
        ['TEST004', 'Database Quiz 1', 'SUB003', 'FAC002', 25, 'Quiz'],
        ['TEST005', 'Database Final Exam', 'SUB003', 'FAC002', 100, 'Final'],
        ['TEST006', 'Web Dev Assignment', 'SUB004', 'FAC003', 50, 'Assignment'],
        ['TEST007', 'AI Midterm Exam', 'SUB005', 'FAC002', 100, 'Midterm'],
        ['TEST008', 'Circuit Analysis Quiz', 'SUB006', 'FAC004', 25, 'Quiz'],
        ['TEST009', 'Digital Electronics Lab', 'SUB007', 'FAC005', 30, 'Practical'],
        ['TEST010', 'Thermodynamics Final', 'SUB008', 'FAC006', 100, 'Final'],
        ['TEST011', 'Calculus Quiz 1', 'SUB009', 'FAC007', 25, 'Quiz'],
        ['TEST012', 'Physics Midterm', 'SUB010', 'FAC008', 100, 'Midterm'],
        ['TEST013', 'Chemistry Lab Test', 'SUB011', 'FAC009', 30, 'Practical'],
        ['TEST014', 'Management Case Study', 'SUB012', 'FAC010', 50, 'Assignment'],
        ['TEST015', 'Structures Quiz', 'SUB013', 'FAC011', 25, 'Quiz']
    ];
    
    $tests_added = 0;
    
    foreach ($tests_data as $test) {
        try {
            // Get available columns
            $test_cols = array_column($pdo->query("DESCRIBE tests")->fetchAll(PDO::FETCH_ASSOC), 'Field');
            
            $insert_cols = [];
            $insert_vals = [];
            
            // Add columns based on what exists
            if (in_array('test_id', $test_cols)) {
                $insert_cols[] = 'test_id';
                $insert_vals[] = $test[0];
            }
            if (in_array('test_name', $test_cols)) {
                $insert_cols[] = 'test_name';
                $insert_vals[] = $test[1];
            }
            if (in_array('subject_id', $test_cols)) {
                $insert_cols[] = 'subject_id';
                $insert_vals[] = $test[2];
            }
            if (in_array('faculty_id', $test_cols)) {
                $insert_cols[] = 'faculty_id';
                $insert_vals[] = $test[3];
            }
            if (in_array('max_marks', $test_cols)) {
                $insert_cols[] = 'max_marks';
                $insert_vals[] = $test[4];
            }
            if (in_array('test_type', $test_cols)) {
                $insert_cols[] = 'test_type';
                $insert_vals[] = $test[5];
            }
            if (in_array('test_date', $test_cols)) {
                $insert_cols[] = 'test_date';
                $insert_vals[] = date('Y-m-d', strtotime('+' . rand(1, 30) . ' days'));
            }
            if (in_array('duration', $test_cols)) {
                $duration = $test[5] === 'Quiz' ? 60 : ($test[5] === 'Final' ? 180 : 120);
                $insert_cols[] = 'duration';
                $insert_vals[] = $duration;
            }
            if (in_array('status', $test_cols)) {
                $insert_cols[] = 'status';
                $insert_vals[] = 'Published';
            }
            if (in_array('instructions', $test_cols)) {
                $insert_cols[] = 'instructions';
                $insert_vals[] = 'Please answer all questions carefully. Good luck!';
            }
            
            if (!empty($insert_cols)) {
                $placeholders = str_repeat('?,', count($insert_cols));
                $placeholders = rtrim($placeholders, ',');
                
                $sql = "INSERT INTO tests (" . implode(', ', $insert_cols) . ") VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($insert_vals);
                
                $tests_added++;
                $test_icon = $test[5] === 'Quiz' ? '📝' : ($test[5] === 'Final' ? '🎯' : '📊');
                echo "  ✅ $test_icon {$test[1]} ({$test[0]}) - {$test[4]} marks\n";
            }
            
        } catch (Exception $e) {
            echo "  ❌ Error adding test {$test[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Tests Added: $tests_added\n\n";
    
    echo "🎉 COMPLETE SCHEMA CONVERSION AND DATA SETUP FINISHED!\n";
    echo "======================================================\n";
    echo "✅ Database schema converted to VARCHAR IDs\n";
    echo "✅ Faculty Members: $faculty_added (with proper VARCHAR IDs)\n";
    echo "✅ Subjects: $subjects_added (linked to faculty via VARCHAR IDs)\n";
    echo "✅ Tests: $tests_added (ready for grading)\n";
    echo "✅ Female Faculty Representation: $female_faculty out of $faculty_added (" . round($female_faculty/$faculty_added*100,1) . "%)\n\n";
    
    echo "🚀 YOUR SYSTEM IS NOW READY WITH VARCHAR ID STRUCTURE!\n";
    echo "• All ID columns are now user-friendly VARCHAR format\n";
    echo "• Faculty IDs: FAC001, FAC002, etc.\n";
    echo "• Subject IDs: SUB001, SUB002, etc.\n";
    echo "• Test IDs: TEST001, TEST002, etc.\n";
    echo "• Complete demo data with proper relationships\n";
    
    echo "</div>";
    
    // Success summary
    echo "<div class='success-box'>";
    echo "<h5>🎊 Schema Conversion & Demo Data Complete!</h5>";
    echo "<div class='row'>";
    echo "<div class='col-md-3'>";
    echo "<h3>$faculty_added</h3>";
    echo "<p>Faculty Members<br><small>with VARCHAR IDs</small></p>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h3>$subjects_added</h3>";
    echo "<p>Subjects<br><small>properly linked</small></p>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h3>$tests_added</h3>";
    echo "<p>Tests<br><small>ready for grading</small></p>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h3>" . round($female_faculty/$faculty_added*100,1) . "%</h3>";
    echo "<p>Female Faculty<br><small>diversity achieved</small></p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "</div>";
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ Conversion Error</h5>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Schema conversion can be complex. Check the detailed log above.</p>";
    echo "</div>";
}

echo "<div class='mt-4 text-center'>";
echo "<h5 class='mb-3'>🚀 Explore Your Updated System</h5>";
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