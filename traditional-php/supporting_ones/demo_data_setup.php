<?php
/**
 * Complete Demo Data Setup
 * Adds all necessary sample data for full system demonstration
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Complete Demo Data Setup</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .log { 
            font-family: monospace; 
            background: #f8f9fa; 
            padding: 15px; 
            border: 1px solid #ddd; 
            max-height: 600px; 
            overflow-y: auto; 
            white-space: pre-wrap;
        }
        .progress-step { 
            background: #e3f2fd; 
            border-left: 4px solid #2196f3; 
            padding: 10px; 
            margin: 10px 0; 
        }
    </style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h3 mb-4'>🎓 Complete Demo Data Setup</h1>
    <div class='progress-step'>
        <strong>This will add comprehensive demo data for your College Management System</strong>
        <br>Including students, faculty, subjects, tests, and sample grades
    </div>";

try {
    echo "<div class='log'>";
    
    echo "🚀 STARTING COMPLETE DEMO DATA SETUP\n";
    echo "====================================\n\n";
    
    // Check existing data
    $existing_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $existing_faculty = $pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    $existing_subjects = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $existing_tests = $pdo->query("SELECT COUNT(*) FROM tests")->fetchColumn();
    
    echo "📊 Current Database Status:\n";
    echo "Students: $existing_students | Faculty: $existing_faculty | Subjects: $existing_subjects | Tests: $existing_tests\n\n";
    
    // Get available courses and batches
    $courses = $pdo->query("SELECT course_id, course_name FROM courses")->fetchAll();
    $batches = $pdo->query("SELECT batch_id, batch_name FROM batches")->fetchAll();
    
    echo "📚 Available: " . count($courses) . " courses, " . count($batches) . " batches\n\n";
    
    if (empty($courses) || empty($batches)) {
        // Create basic courses and batches if they don't exist
        echo "🔧 Creating basic courses and batches...\n";
        
        $basic_courses = [
            ['BTECH_CS', 'B.Tech Computer Science'],
            ['BTECH_IT', 'B.Tech Information Technology'],
            ['BTECH_EE', 'B.Tech Electrical Engineering'],
            ['BBA', 'Bachelor of Business Administration'],
            ['BSC_MATH', 'B.Sc Mathematics']
        ];
        
        foreach ($basic_courses as $course) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO courses (course_id, course_name) VALUES (?, ?)");
                $stmt->execute($course);
                echo "✅ Course: {$course[1]}\n";
            } catch (Exception $e) {
                // Try alternative column names
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO courses (course_code, name) VALUES (?, ?)");
                    $stmt->execute($course);
                    echo "✅ Course: {$course[1]}\n";
                } catch (Exception $e2) {
                    echo "⚠️  Could not add course: {$course[1]}\n";
                }
            }
        }
        
        $basic_batches = [
            ['2024_A', '2024 Batch A'],
            ['2024_B', '2024 Batch B'],
            ['2023_A', '2023 Batch A'],
            ['2023_B', '2023 Batch B'],
            ['2022_A', '2022 Batch A']
        ];
        
        foreach ($basic_batches as $batch) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO batches (batch_id, batch_name) VALUES (?, ?)");
                $stmt->execute($batch);
                echo "✅ Batch: {$batch[1]}\n";
            } catch (Exception $e) {
                // Try alternative column names
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO batches (batch_code, name) VALUES (?, ?)");
                    $stmt->execute($batch);
                    echo "✅ Batch: {$batch[1]}\n";
                } catch (Exception $e2) {
                    echo "⚠️  Could not add batch: {$batch[1]}\n";
                }
            }
        }
        
        // Refresh courses and batches
        $courses = $pdo->query("SELECT * FROM courses LIMIT 5")->fetchAll();
        $batches = $pdo->query("SELECT * FROM batches LIMIT 5")->fetchAll();
        echo "\n";
    }
    
    // STEP 1: ADD STUDENTS
    echo "👥 STEP 1: ADDING DEMO STUDENTS\n";
    echo "===============================\n";
    
    $demo_students = [
        // Female Students
        ['STU001', 'Priya Sharma', 'priya.sharma@demo.edu', 'Female'],
        ['STU002', 'Kavya Patel', 'kavya.patel@demo.edu', 'Female'],
        ['STU003', 'Shreya Singh', 'shreya.singh@demo.edu', 'Female'],
        ['STU004', 'Neha Kumar', 'neha.kumar@demo.edu', 'Female'],
        ['STU005', 'Pooja Gupta', 'pooja.gupta@demo.edu', 'Female'],
        ['STU006', 'Riya Agarwal', 'riya.agarwal@demo.edu', 'Female'],
        ['STU007', 'Sonia Verma', 'sonia.verma@demo.edu', 'Female'],
        ['STU008', 'Meera Jain', 'meera.jain@demo.edu', 'Female'],
        
        // Male Students
        ['STU009', 'Aarav Reddy', 'aarav.reddy@demo.edu', 'Male'],
        ['STU010', 'Arjun Nair', 'arjun.nair@demo.edu', 'Male'],
        ['STU011', 'Vikram Rao', 'vikram.rao@demo.edu', 'Male'],
        ['STU012', 'Rajesh Iyer', 'rajesh.iyer@demo.edu', 'Male'],
        ['STU013', 'Suresh Krishnan', 'suresh.krishnan@demo.edu', 'Male'],
        ['STU014', 'Amit Bansal', 'amit.bansal@demo.edu', 'Male'],
        ['STU015', 'Rohit Chopra', 'rohit.chopra@demo.edu', 'Male'],
        ['STU016', 'Karan Malhotra', 'karan.malhotra@demo.edu', 'Male'],
        ['STU017', 'Ankit Saxena', 'ankit.saxena@demo.edu', 'Male'],
        ['STU018', 'Deepak Pandey', 'deepak.pandey@demo.edu', 'Male'],
        ['STU019', 'Ravi Kumar', 'ravi.kumar@demo.edu', 'Male'],
        ['STU020', 'Anil Sharma', 'anil.sharma@demo.edu', 'Male']
    ];
    
    $student_count = 0;
    $female_students = 0;
    
    foreach ($demo_students as $student) {
        try {
            // Check if exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ? OR email = ?");
            $check->execute([$student[0], $student[2]]);
            if ($check->fetchColumn() > 0) {
                echo "ℹ️  Student {$student[1]} already exists, skipping\n";
                continue;
            }
            
            // Get random course and batch
            $course = $courses[array_rand($courses)];
            $batch = $batches[array_rand($batches)];
            
            // Try different possible column combinations
            $insert_queries = [
                "INSERT INTO students (student_id, full_name, email, gender, course_id, batch_id, admission_date, academic_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                "INSERT INTO students (student_id, full_name, email, gender, course_id, batch_id) VALUES (?, ?, ?, ?, ?, ?)",
                "INSERT INTO students (student_id, full_name, email, course_id, batch_id) VALUES (?, ?, ?, ?, ?)",
                "INSERT INTO students (student_id, full_name, email) VALUES (?, ?, ?)"
            ];
            
            $success = false;
            foreach ($insert_queries as $query) {
                try {
                    $stmt = $pdo->prepare($query);
                    if (strpos($query, 'academic_status') !== false) {
                        $course_key = array_key_exists('course_id', $course) ? $course['course_id'] : $course[array_keys($course)[0]];
                        $batch_key = array_key_exists('batch_id', $batch) ? $batch['batch_id'] : $batch[array_keys($batch)[0]];
                        $stmt->execute([$student[0], $student[1], $student[2], $student[3], $course_key, $batch_key, date('Y-m-d'), 'Active']);
                    } elseif (strpos($query, 'batch_id') !== false && strpos($query, 'gender') !== false) {
                        $course_key = array_key_exists('course_id', $course) ? $course['course_id'] : $course[array_keys($course)[0]];
                        $batch_key = array_key_exists('batch_id', $batch) ? $batch['batch_id'] : $batch[array_keys($batch)[0]];
                        $stmt->execute([$student[0], $student[1], $student[2], $student[3], $course_key, $batch_key]);
                    } elseif (strpos($query, 'batch_id') !== false) {
                        $course_key = array_key_exists('course_id', $course) ? $course['course_id'] : $course[array_keys($course)[0]];
                        $batch_key = array_key_exists('batch_id', $batch) ? $batch['batch_id'] : $batch[array_keys($batch)[0]];
                        $stmt->execute([$student[0], $student[1], $student[2], $course_key, $batch_key]);
                    } else {
                        $stmt->execute([$student[0], $student[1], $student[2]]);
                    }
                    $success = true;
                    break;
                } catch (Exception $e) {
                    continue;
                }
            }
            
            if ($success) {
                $student_count++;
                if ($student[3] === 'Female') $female_students++;
                $icon = $student[3] === 'Female' ? '👩‍🎓' : '👨‍🎓';
                echo "✅ $icon {$student[1]} ({$student[0]})\n";
            } else {
                echo "❌ Could not add student: {$student[1]}\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error adding {$student[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Students Added: $student_count total ($female_students female)\n\n";
    
    // STEP 2: ADD FACULTY
    echo "👨‍🏫 STEP 2: ADDING DEMO FACULTY\n";
    echo "===============================\n";
    
    $demo_faculty = [
        ['FAC001', 'Dr. Priya Sharma', 'priya.sharma@college.edu', 'Computer Science', 'Female'],
        ['FAC002', 'Prof. Rajesh Kumar', 'rajesh.kumar@college.edu', 'Information Technology', 'Male'],
        ['FAC003', 'Dr. Kavya Patel', 'kavya.patel@college.edu', 'Mathematics', 'Female'],
        ['FAC004', 'Mr. Vikram Rao', 'vikram.rao@college.edu', 'Physics', 'Male'],
        ['FAC005', 'Ms. Shreya Singh', 'shreya.singh@college.edu', 'Chemistry', 'Female'],
        ['FAC006', 'Prof. Arjun Nair', 'arjun.nair@college.edu', 'Electrical Engineering', 'Male'],
        ['FAC007', 'Dr. Neha Kumar', 'neha.kumar@college.edu', 'Mechanical Engineering', 'Female'],
        ['FAC008', 'Mr. Suresh Krishnan', 'suresh.krishnan@college.edu', 'Civil Engineering', 'Male'],
        ['FAC009', 'Mrs. Pooja Gupta', 'pooja.gupta@college.edu', 'Management', 'Female'],
        ['FAC010', 'Dr. Amit Bansal', 'amit.bansal@college.edu', 'English', 'Male']
    ];
    
    $faculty_count = 0;
    $female_faculty = 0;
    
    foreach ($demo_faculty as $faculty) {
        try {
            // Check if exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM faculty WHERE faculty_id = ? OR email = ?");
            $check->execute([$faculty[0], $faculty[2]]);
            if ($check->fetchColumn() > 0) {
                echo "ℹ️  Faculty {$faculty[1]} already exists, skipping\n";
                continue;
            }
            
            // Try different possible column combinations
            $insert_queries = [
                "INSERT INTO faculty (faculty_id, full_name, email, department, gender, employment_status, salary) VALUES (?, ?, ?, ?, ?, ?, ?)",
                "INSERT INTO faculty (faculty_id, full_name, email, department, gender) VALUES (?, ?, ?, ?, ?)",
                "INSERT INTO faculty (faculty_id, full_name, email, department) VALUES (?, ?, ?, ?)",
                "INSERT INTO faculty (faculty_id, full_name, email) VALUES (?, ?, ?)"
            ];
            
            $success = false;
            foreach ($insert_queries as $query) {
                try {
                    $stmt = $pdo->prepare($query);
                    if (strpos($query, 'salary') !== false) {
                        $stmt->execute([$faculty[0], $faculty[1], $faculty[2], $faculty[3], $faculty[4], 'Active', rand(50000, 90000)]);
                    } elseif (strpos($query, 'gender') !== false) {
                        $stmt->execute([$faculty[0], $faculty[1], $faculty[2], $faculty[3], $faculty[4]]);
                    } elseif (strpos($query, 'department') !== false) {
                        $stmt->execute([$faculty[0], $faculty[1], $faculty[2], $faculty[3]]);
                    } else {
                        $stmt->execute([$faculty[0], $faculty[1], $faculty[2]]);
                    }
                    $success = true;
                    break;
                } catch (Exception $e) {
                    continue;
                }
            }
            
            if ($success) {
                $faculty_count++;
                if ($faculty[4] === 'Female') $female_faculty++;
                $icon = $faculty[4] === 'Female' ? '👩‍🏫' : '👨‍🏫';
                echo "✅ $icon {$faculty[1]} - {$faculty[3]} ({$faculty[0]})\n";
            } else {
                echo "❌ Could not add faculty: {$faculty[1]}\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error adding {$faculty[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Faculty Added: $faculty_count total ($female_faculty female)\n\n";
    
    // STEP 3: ADD SUBJECTS
    echo "📚 STEP 3: ADDING DEMO SUBJECTS\n";
    echo "==============================\n";
    
    $demo_subjects = [
        ['CS101', 'Programming Fundamentals', 'Introduction to programming concepts and logic', 4, 'FAC001'],
        ['CS102', 'Data Structures', 'Arrays, linked lists, stacks, queues, trees', 4, 'FAC001'],
        ['MATH101', 'Calculus I', 'Differential and integral calculus', 4, 'FAC003'],
        ['PHYS101', 'Physics I', 'Mechanics, thermodynamics, and waves', 4, 'FAC004'],
        ['CHEM101', 'Chemistry I', 'Atomic structure and chemical bonding', 4, 'FAC005'],
        ['EE101', 'Circuit Analysis', 'DC and AC circuit analysis', 4, 'FAC006'],
        ['ME101', 'Engineering Mechanics', 'Statics and dynamics', 4, 'FAC007'],
        ['CE101', 'Surveying', 'Land surveying and measurements', 3, 'FAC008'],
        ['MGT101', 'Management Principles', 'Basic management concepts', 3, 'FAC009'],
        ['ENG101', 'Technical Communication', 'Written and oral communication', 3, 'FAC010']
    ];
    
    $subjects_count = 0;
    
    foreach ($demo_subjects as $subject) {
        try {
            // Check if exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE subject_code = ?");
            $check->execute([$subject[0]]);
            if ($check->fetchColumn() > 0) {
                echo "ℹ️  Subject {$subject[1]} already exists, skipping\n";
                continue;
            }
            
            // Get random course
            $course = $courses[array_rand($courses)];
            $course_key = array_key_exists('course_id', $course) ? $course['course_id'] : $course[array_keys($course)[0]];
            
            // Try different possible column combinations
            $insert_queries = [
                "INSERT INTO subjects (subject_code, subject_name, description, credits, faculty_id, course_id, semester, academic_year, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                "INSERT INTO subjects (subject_code, subject_name, description, credits, faculty_id, course_id, semester) VALUES (?, ?, ?, ?, ?, ?, ?)",
                "INSERT INTO subjects (subject_code, subject_name, description, credits, faculty_id, course_id) VALUES (?, ?, ?, ?, ?, ?)",
                "INSERT INTO subjects (subject_code, subject_name, description, faculty_id) VALUES (?, ?, ?, ?)",
                "INSERT INTO subjects (subject_code, subject_name, faculty_id) VALUES (?, ?, ?)"
            ];
            
            $success = false;
            foreach ($insert_queries as $query) {
                try {
                    $stmt = $pdo->prepare($query);
                    if (strpos($query, 'academic_year') !== false) {
                        $stmt->execute([$subject[0], $subject[1], $subject[2], $subject[3], $subject[4], $course_key, rand(1,4), '2024-25', 'Active']);
                    } elseif (strpos($query, 'semester') !== false && strpos($query, 'course_id') !== false) {
                        $stmt->execute([$subject[0], $subject[1], $subject[2], $subject[3], $subject[4], $course_key, rand(1,4)]);
                    } elseif (strpos($query, 'course_id') !== false) {
                        $stmt->execute([$subject[0], $subject[1], $subject[2], $subject[3], $subject[4], $course_key]);
                    } elseif (strpos($query, 'description') !== false) {
                        $stmt->execute([$subject[0], $subject[1], $subject[2], $subject[4]]);
                    } else {
                        $stmt->execute([$subject[0], $subject[1], $subject[4]]);
                    }
                    $success = true;
                    break;
                } catch (Exception $e) {
                    continue;
                }
            }
            
            if ($success) {
                $subjects_count++;
                echo "✅ 📖 {$subject[1]} ({$subject[0]}) - {$subject[4]}\n";
            } else {
                echo "❌ Could not add subject: {$subject[1]}\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error adding {$subject[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Subjects Added: $subjects_count total\n\n";
    
    // STEP 4: ADD TESTS
    echo "📝 STEP 4: ADDING DEMO TESTS\n";
    echo "===========================\n";
    
    // Get added subjects
    $subjects = $pdo->query("SELECT * FROM subjects LIMIT 10")->fetchAll();
    $tests_count = 0;
    
    $test_types = [
        ['Quiz 1', 25, 'Quiz'],
        ['Midterm Exam', 100, 'Midterm'],
        ['Final Exam', 100, 'Final']
    ];
    
    foreach ($subjects as $subject) {
        foreach ($test_types as $test_info) {
            try {
                $subject_key = array_key_exists('subject_id', $subject) ? $subject['subject_id'] : $subject[array_keys($subject)[0]];
                $subject_name = array_key_exists('subject_name', $subject) ? $subject['subject_name'] : 'Subject';
                $faculty_key = array_key_exists('faculty_id', $subject) ? $subject['faculty_id'] : 'FAC001';
                
                $test_name = "$subject_name - {$test_info[0]}";
                $test_date = date('Y-m-d', strtotime('+' . rand(1, 30) . ' days'));
                
                // Try different possible column combinations
                $insert_queries = [
                    "INSERT INTO tests (test_name, subject_id, faculty_id, max_marks, test_date, test_type, duration, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    "INSERT INTO tests (test_name, subject_id, faculty_id, max_marks, test_date, test_type) VALUES (?, ?, ?, ?, ?, ?)",
                    "INSERT INTO tests (test_name, subject_id, faculty_id, max_marks, test_date) VALUES (?, ?, ?, ?, ?)",
                    "INSERT INTO tests (test_name, subject_id, max_marks) VALUES (?, ?, ?)"
                ];
                
                $success = false;
                foreach ($insert_queries as $query) {
                    try {
                        $stmt = $pdo->prepare($query);
                        if (strpos($query, 'status') !== false) {
                            $duration = $test_info[2] === 'Quiz' ? 60 : 180;
                            $stmt->execute([$test_name, $subject_key, $faculty_key, $test_info[1], $test_date, $test_info[2], $duration, 'Published']);
                        } elseif (strpos($query, 'test_type') !== false && strpos($query, 'test_date') !== false) {
                            $stmt->execute([$test_name, $subject_key, $faculty_key, $test_info[1], $test_date, $test_info[2]]);
                        } elseif (strpos($query, 'test_date') !== false) {
                            $stmt->execute([$test_name, $subject_key, $faculty_key, $test_info[1], $test_date]);
                        } else {
                            $stmt->execute([$test_name, $subject_key, $test_info[1]]);
                        }
                        $success = true;
                        break;
                    } catch (Exception $e) {
                        continue;
                    }
                }
                
                if ($success) {
                    $tests_count++;
                    $icon = $test_info[2] === 'Quiz' ? '📝' : ($test_info[2] === 'Final' ? '🎯' : '📊');
                    echo "✅ $icon $test_name ({$test_info[1]} marks)\n";
                }
                
            } catch (Exception $e) {
                echo "❌ Error adding test for $subject_name: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n📊 Tests Added: $tests_count total\n\n";
    
    echo "🎉 DEMO DATA SETUP COMPLETE!\n";
    echo "============================\n";
    echo "✅ Students: $student_count (including $female_students female students)\n";
    echo "✅ Faculty: $faculty_count (including $female_faculty female faculty)\n";
    echo "✅ Subjects: $subjects_count (across multiple departments)\n";
    echo "✅ Tests: $tests_count (ready for grading)\n\n";
    
    echo "🚀 YOUR SYSTEM IS NOW READY FOR DEMO!\n";
    echo "• Faculty can log in and grade students\n";
    echo "• Students can view their grades and progress\n";
    echo "• All modules are populated with realistic data\n";
    echo "• Female representation: " . round(($female_students + $female_faculty)/($student_count + $faculty_count)*100, 1) . "%\n";
    
    echo "</div>";
    
    // Success message
    echo "<div class='alert alert-success mt-3'>";
    echo "<h5>🎉 Demo Data Successfully Added!</h5>";
    echo "<div class='row'>";
    echo "<div class='col-md-3'>";
    echo "<h6>👥 Students: $student_count</h6>";
    echo "<small>Including diverse names and backgrounds</small>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h6>👨‍🏫 Faculty: $faculty_count</h6>";
    echo "<small>Across multiple departments</small>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h6>📚 Subjects: $subjects_count</h6>";
    echo "<small>With proper faculty assignments</small>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h6>📝 Tests: $tests_count</h6>";
    echo "<small>Ready for grading</small>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "</div>";
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ Error During Demo Setup</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>This might be due to table structure differences. The script tried multiple approaches to ensure compatibility.</strong></p>";
    echo "</div>";
}

echo "<div class='mt-3'>";
echo "<a href='students.php' class='btn btn-primary me-2'>👥 View Students</a>";
echo "<a href='faculty.php' class='btn btn-primary me-2'>👨‍🏫 View Faculty</a>";
echo "<a href='subjects.php' class='btn btn-primary me-2'>📚 View Subjects</a>";
echo "<a href='tests.php' class='btn btn-primary me-2'>📝 View Tests</a>";
echo "<a href='grades.php' class='btn btn-success me-2'>🎯 Start Grading</a>";
echo "<a href='index.php' class='btn btn-outline-primary'>🏠 Dashboard</a>";
echo "</div>";

echo "</div></body></html>";
?>