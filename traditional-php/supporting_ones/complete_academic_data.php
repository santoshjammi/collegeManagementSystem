<?php
/**
 * Complete Academic Sample Data Generator
 * Adds faculty, subjects, and tests with realistic academic content
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Complete Academic Data Generator</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>.log { font-family: monospace; background: #f8f9fa; padding: 15px; border: 1px solid #ddd; max-height: 600px; overflow-y: auto; }</style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h3 mb-4'>🎓 Complete Academic Data Generator</h1>";

try {
    echo "<div class='log'>";
    
    // Inspect table structures
    echo "🔍 INSPECTING DATABASE STRUCTURE:\n";
    echo "=================================\n";
    
    $tables_to_check = ['faculty', 'subjects', 'tests', 'students', 'courses'];
    $table_columns = [];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $table_columns[$table] = $columns;
            echo "✅ $table: " . implode(', ', $columns) . "\n";
        } catch (Exception $e) {
            echo "❌ $table: Table not found\n";
            $table_columns[$table] = [];
        }
    }
    echo "\n";
    
    // Get existing courses for reference
    $stmt = $pdo->query("SELECT course_id, course_name FROM courses LIMIT 10");
    $courses = $stmt->fetchAll();
    echo "📚 Available courses: " . count($courses) . "\n\n";
    
    if (empty($courses)) {
        throw new Exception("No courses available. Please add courses first.");
    }
    
    // STEP 1: ADD FACULTY WITH EXPERTISE
    echo "👨‍🏫 ADDING FACULTY MEMBERS:\n";
    echo "===========================\n";
    
    $faculty_data = [
        // Computer Science Faculty
        ['FAC001', 'Dr. Priya Sharma', 'priya.sharma@college.edu', 'Computer Science', 'Programming, Algorithms', 'Female', 75000],
        ['FAC002', 'Prof. Rajesh Kumar', 'rajesh.kumar@college.edu', 'Computer Science', 'Database Systems, AI', 'Male', 85000],
        ['FAC003', 'Dr. Kavya Patel', 'kavya.patel@college.edu', 'Information Technology', 'Web Development, Networks', 'Female', 70000],
        
        // Engineering Faculty
        ['FAC004', 'Prof. Vikram Rao', 'vikram.rao@college.edu', 'Mechanical Engineering', 'Thermodynamics, Mechanics', 'Male', 80000],
        ['FAC005', 'Dr. Shreya Singh', 'shreya.singh@college.edu', 'Electrical Engineering', 'Circuit Analysis, Power Systems', 'Female', 78000],
        ['FAC006', 'Mr. Arjun Nair', 'arjun.nair@college.edu', 'Civil Engineering', 'Structural Analysis, Construction', 'Male', 72000],
        
        // Science Faculty
        ['FAC007', 'Dr. Neha Kumar', 'neha.kumar@college.edu', 'Mathematics', 'Calculus, Statistics', 'Female', 65000],
        ['FAC008', 'Prof. Suresh Krishnan', 'suresh.krishnan@college.edu', 'Physics', 'Quantum Mechanics, Optics', 'Male', 68000],
        ['FAC009', 'Ms. Pooja Gupta', 'pooja.gupta@college.edu', 'Chemistry', 'Organic Chemistry, Biochemistry', 'Female', 66000],
        
        // Management & Liberal Arts
        ['FAC010', 'Dr. Amit Bansal', 'amit.bansal@college.edu', 'Management', 'Finance, Marketing', 'Male', 82000],
        ['FAC011', 'Mrs. Riya Agarwal', 'riya.agarwal@college.edu', 'English', 'Literature, Communication Skills', 'Female', 58000],
        ['FAC012', 'Mr. Rohit Chopra', 'rohit.chopra@college.edu', 'Economics', 'Microeconomics, Business Studies', 'Male', 64000],
        
        // Additional Faculty
        ['FAC013', 'Dr. Sonia Verma', 'sonia.verma@college.edu', 'Computer Science', 'Data Structures, Software Engineering', 'Female', 76000],
        ['FAC014', 'Prof. Karan Malhotra', 'karan.malhotra@college.edu', 'Electronics', 'Digital Circuits, Microprocessors', 'Male', 74000],
        ['FAC015', 'Dr. Meera Jain', 'meera.jain@college.edu', 'Biotechnology', 'Genetics, Molecular Biology', 'Female', 71000]
    ];
    
    $faculty_count = 0;
    $female_faculty = 0;
    
    foreach ($faculty_data as $faculty) {
        try {
            // Check if exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM faculty WHERE faculty_id = ?");
            $checkStmt->execute([$faculty[0]]);
            if ($checkStmt->fetchColumn() > 0) {
                echo "ℹ️  Faculty {$faculty[0]} already exists, skipping\n";
                continue;
            }
            
            // Build dynamic insert
            $columns = ['faculty_id', 'full_name', 'email'];
            $values = [$faculty[0], $faculty[1], $faculty[2]];
            $placeholders = ['?', '?', '?'];
            
            if (in_array('department', $table_columns['faculty'])) {
                $columns[] = 'department';
                $values[] = $faculty[3];
                $placeholders[] = '?';
            }
            
            if (in_array('specialization', $table_columns['faculty'])) {
                $columns[] = 'specialization';
                $values[] = $faculty[4];
                $placeholders[] = '?';
            }
            
            if (in_array('gender', $table_columns['faculty'])) {
                $columns[] = 'gender';
                $values[] = $faculty[5];
                $placeholders[] = '?';
            }
            
            if (in_array('salary', $table_columns['faculty'])) {
                $columns[] = 'salary';
                $values[] = $faculty[6];
                $placeholders[] = '?';
            }
            
            if (in_array('employment_status', $table_columns['faculty'])) {
                $columns[] = 'employment_status';
                $values[] = 'Active';
                $placeholders[] = '?';
            }
            
            if (in_array('joining_date', $table_columns['faculty'])) {
                $columns[] = 'joining_date';
                $values[] = date('Y-m-d', strtotime('-' . rand(1, 5) . ' years'));
                $placeholders[] = '?';
            }
            
            $sql = "INSERT INTO faculty (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            $faculty_count++;
            if ($faculty[5] === 'Female') $female_faculty++;
            
            $icon = $faculty[5] === 'Female' ? '👩‍🏫' : '👨‍🏫';
            echo "✅ $icon {$faculty[1]} - {$faculty[3]} ({$faculty[0]})\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to add faculty {$faculty[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Faculty Added: $faculty_count total ($female_faculty female)\n\n";
    
    // STEP 2: ADD SUBJECTS
    echo "📚 ADDING ACADEMIC SUBJECTS:\n";
    echo "===========================\n";
    
    $subjects_data = [
        // Computer Science Subjects
        ['CS101', 'Programming Fundamentals', 'Introduction to programming concepts', 4, 'FAC001', 1, 'Theory'],
        ['CS102', 'Data Structures', 'Advanced data organization techniques', 4, 'FAC013', 1, 'Theory'],
        ['CS201', 'Database Management Systems', 'Relational database design and SQL', 4, 'FAC002', 2, 'Theory'],
        ['CS202', 'Web Development', 'HTML, CSS, JavaScript, PHP basics', 3, 'FAC003', 2, 'Practical'],
        ['CS301', 'Artificial Intelligence', 'Machine learning and AI fundamentals', 4, 'FAC002', 3, 'Theory'],
        
        // Mathematics Subjects
        ['MATH101', 'Calculus I', 'Differential and integral calculus', 4, 'FAC007', 1, 'Theory'],
        ['MATH201', 'Linear Algebra', 'Matrices, vectors, and linear transformations', 3, 'FAC007', 2, 'Theory'],
        ['MATH301', 'Statistics', 'Probability and statistical analysis', 3, 'FAC007', 3, 'Theory'],
        
        // Physics Subjects
        ['PHY101', 'Physics I', 'Mechanics and thermodynamics', 4, 'FAC008', 1, 'Theory'],
        ['PHY102', 'Physics Lab', 'Experimental physics and measurements', 2, 'FAC008', 1, 'Practical'],
        ['PHY201', 'Electromagnetism', 'Electric and magnetic field theory', 4, 'FAC008', 2, 'Theory'],
        
        // Engineering Subjects
        ['ME101', 'Engineering Mechanics', 'Statics and dynamics fundamentals', 4, 'FAC004', 1, 'Theory'],
        ['EE101', 'Circuit Analysis', 'DC and AC circuit fundamentals', 4, 'FAC005', 1, 'Theory'],
        ['CE101', 'Engineering Drawing', 'Technical drawing and CAD basics', 3, 'FAC006', 1, 'Practical'],
        
        // Liberal Arts
        ['ENG101', 'English Communication', 'Written and oral communication skills', 3, 'FAC011', 1, 'Theory'],
        ['MGT101', 'Principles of Management', 'Basic management concepts', 3, 'FAC010', 1, 'Theory'],
        
        // Chemistry & Biology
        ['CHEM101', 'General Chemistry', 'Atomic structure and chemical bonding', 4, 'FAC009', 1, 'Theory'],
        ['BIO101', 'Cell Biology', 'Cellular structure and function', 4, 'FAC015', 1, 'Theory']
    ];
    
    $subjects_count = 0;
    
    foreach ($subjects_data as $subject) {
        try {
            // Check if exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE subject_code = ?");
            $checkStmt->execute([$subject[0]]);
            if ($checkStmt->fetchColumn() > 0) {
                echo "ℹ️  Subject {$subject[0]} already exists, skipping\n";
                continue;
            }
            
            // Get a random course for assignment
            $course = $courses[array_rand($courses)];
            
            // Build dynamic insert for subjects
            $columns = ['subject_code', 'subject_name'];
            $values = [$subject[0], $subject[1]];
            $placeholders = ['?', '?'];
            
            if (in_array('description', $table_columns['subjects'])) {
                $columns[] = 'description';
                $values[] = $subject[2];
                $placeholders[] = '?';
            }
            
            if (in_array('credits', $table_columns['subjects'])) {
                $columns[] = 'credits';
                $values[] = $subject[3];
                $placeholders[] = '?';
            }
            
            if (in_array('faculty_id', $table_columns['subjects'])) {
                $columns[] = 'faculty_id';
                $values[] = $subject[4];
                $placeholders[] = '?';
            }
            
            if (in_array('course_id', $table_columns['subjects'])) {
                $columns[] = 'course_id';
                $values[] = $course['course_id'];
                $placeholders[] = '?';
            }
            
            if (in_array('semester', $table_columns['subjects'])) {
                $columns[] = 'semester';
                $values[] = $subject[5];
                $placeholders[] = '?';
            }
            
            if (in_array('subject_type', $table_columns['subjects'])) {
                $columns[] = 'subject_type';
                $values[] = $subject[6];
                $placeholders[] = '?';
            }
            
            if (in_array('academic_year', $table_columns['subjects'])) {
                $columns[] = 'academic_year';
                $values[] = '2024-25';
                $placeholders[] = '?';
            }
            
            if (in_array('status', $table_columns['subjects'])) {
                $columns[] = 'status';
                $values[] = 'Active';
                $placeholders[] = '?';
            }
            
            $sql = "INSERT INTO subjects (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            $subjects_count++;
            $type_icon = $subject[6] === 'Practical' ? '🔬' : '📖';
            echo "✅ $type_icon {$subject[1]} ({$subject[0]}) - Sem {$subject[5]} - {$subject[4]}\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to add subject {$subject[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Subjects Added: $subjects_count total\n\n";
    
    // STEP 3: ADD TESTS
    echo "📝 ADDING TESTS FOR SUBJECTS:\n";
    echo "============================\n";
    
    // Get added subjects
    $stmt = $pdo->query("SELECT subject_id, subject_code, subject_name, faculty_id FROM subjects LIMIT 20");
    $subjects = $stmt->fetchAll();
    
    $test_types = [
        ['Quiz 1', 20, 'Quiz'],
        ['Midterm Exam', 100, 'Midterm'],
        ['Quiz 2', 20, 'Quiz'], 
        ['Assignment', 50, 'Assignment'],
        ['Final Exam', 100, 'Final'],
        ['Lab Test', 30, 'Practical']
    ];
    
    $tests_count = 0;
    
    foreach ($subjects as $subject) {
        // Add 3-4 tests per subject
        $num_tests = rand(3, 4);
        $selected_tests = array_slice($test_types, 0, $num_tests);
        
        foreach ($selected_tests as $index => $test_info) {
            try {
                $test_date = date('Y-m-d', strtotime('+' . rand(1, 30) . ' days'));
                $test_name = "{$subject['subject_name']} - {$test_info[0]}";
                
                // Build dynamic insert for tests
                $columns = [];
                $values = [];
                $placeholders = [];
                
                if (in_array('test_name', $table_columns['tests'])) {
                    $columns[] = 'test_name';
                    $values[] = $test_name;
                    $placeholders[] = '?';
                }
                
                if (in_array('subject_id', $table_columns['tests'])) {
                    $columns[] = 'subject_id';
                    $values[] = $subject['subject_id'];
                    $placeholders[] = '?';
                }
                
                if (in_array('faculty_id', $table_columns['tests'])) {
                    $columns[] = 'faculty_id';
                    $values[] = $subject['faculty_id'];
                    $placeholders[] = '?';
                }
                
                if (in_array('max_marks', $table_columns['tests'])) {
                    $columns[] = 'max_marks';
                    $values[] = $test_info[1];
                    $placeholders[] = '?';
                }
                
                if (in_array('test_date', $table_columns['tests'])) {
                    $columns[] = 'test_date';
                    $values[] = $test_date;
                    $placeholders[] = '?';
                }
                
                if (in_array('test_type', $table_columns['tests'])) {
                    $columns[] = 'test_type';
                    $values[] = $test_info[2];
                    $placeholders[] = '?';
                }
                
                if (in_array('duration', $table_columns['tests'])) {
                    $duration = $test_info[2] === 'Quiz' ? 30 : ($test_info[2] === 'Final' ? 180 : 120);
                    $columns[] = 'duration';
                    $values[] = $duration;
                    $placeholders[] = '?';
                }
                
                if (in_array('instructions', $table_columns['tests'])) {
                    $columns[] = 'instructions';
                    $values[] = "Please answer all questions carefully. Good luck!";
                    $placeholders[] = '?';
                }
                
                if (in_array('status', $table_columns['tests'])) {
                    $columns[] = 'status';
                    $values[] = rand(0, 1) ? 'Published' : 'Draft';
                    $placeholders[] = '?';
                }
                
                if (empty($columns)) {
                    echo "⚠️  No compatible columns for tests table\n";
                    break 2;
                }
                
                $sql = "INSERT INTO tests (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
                
                $tests_count++;
                $test_icon = $test_info[2] === 'Quiz' ? '📝' : ($test_info[2] === 'Final' ? '🎯' : '📊');
                echo "✅ $test_icon $test_name - {$test_info[1]} marks - $test_date\n";
                
            } catch (Exception $e) {
                echo "❌ Failed to add test for {$subject['subject_name']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n📊 Tests Added: $tests_count total\n\n";
    
    echo "🎉 COMPLETE ACADEMIC DATA SETUP FINISHED!\n";
    echo "========================================\n";
    echo "✅ Faculty Members: $faculty_count (with specializations)\n";
    echo "✅ Academic Subjects: $subjects_count (across multiple departments)\n";
    echo "✅ Tests Created: $tests_count (various types and schedules)\n";
    echo "✅ Female Representation: $female_faculty/$faculty_count faculty (" . round($female_faculty/$faculty_count*100,1) . "%)\n\n";
    
    echo "📋 WHAT'S BEEN ADDED:\n";
    echo "• Faculty from CS, Engineering, Science, Management departments\n";
    echo "• Subjects like Programming, Database, Physics, Mathematics, etc.\n";
    echo "• Tests including Quizzes, Midterms, Finals, Assignments\n";
    echo "• Proper faculty-subject assignments\n";
    echo "• Realistic academic schedules and credit hours\n\n";
    
    echo "🚀 READY FOR GRADING SYSTEM TESTING!\n";
    
    echo "</div>";
    
    // Success summary
    if ($faculty_count > 0 || $subjects_count > 0 || $tests_count > 0) {
        echo "<div class='alert alert-success mt-3'>";
        echo "<h5>🎓 Complete Academic Environment Ready!</h5>";
        echo "<div class='row'>";
        echo "<div class='col-md-4'>";
        echo "<h6>👨‍🏫 Faculty: $faculty_count</h6>";
        echo "<small>CS, Engineering, Science, Management departments</small>";
        echo "</div>";
        echo "<div class='col-md-4'>";
        echo "<h6>📚 Subjects: $subjects_count</h6>";
        echo "<small>Theory & Practical courses with proper credits</small>";
        echo "</div>";
        echo "<div class='col-md-4'>";
        echo "<h6>📝 Tests: $tests_count</h6>";
        echo "<small>Quizzes, Exams, Assignments ready for grading</small>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "</div>";
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ Error During Academic Setup</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='mt-3'>";
echo "<a href='subjects.php' class='btn btn-primary me-2'>📚 View Subjects</a>";
echo "<a href='tests.php' class='btn btn-primary me-2'>📝 View Tests</a>";
echo "<a href='faculty.php' class='btn btn-primary me-2'>👨‍🏫 View Faculty</a>";
echo "<a href='grades.php' class='btn btn-success me-2'>🎯 Start Grading</a>";
echo "<a href='index.php' class='btn btn-outline-primary'>🏠 Dashboard</a>";
echo "</div>";

echo "</div></body></html>";
?>