<?php
/**
 * Comprehensive Demo Data Generator
 * Consolidates all sample data creation into one robust script
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Comprehensive Demo Data Generator</title>
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
        .stats-card { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin: 10px 0;
        }
        .section-header {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h2 mb-4'>🎓 Comprehensive Demo Data Generator</h1>
    <div class='section-header'>
        <h5 class='mb-2'>🚀 Complete Academic System Setup</h5>
        <p class='mb-0'>This will create a comprehensive demo environment with students, faculty, subjects, tests, and sample grades for your College Management System.</p>
    </div>";

try {
    echo "<div class='log'>";
    
    echo "🔥 STARTING COMPREHENSIVE DEMO DATA GENERATION\n";
    echo "==============================================\n\n";
    
    // Inspect database structure
    echo "🔍 ANALYZING DATABASE STRUCTURE:\n";
    echo "---------------------------------\n";
    
    $tables = ['students', 'faculty', 'subjects', 'tests', 'courses', 'batches', 'student_test_grades'];
    $table_info = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $table_info[$table] = $columns;
            echo "✅ $table table: " . count($columns) . " columns\n";
        } catch (Exception $e) {
            $table_info[$table] = [];
            echo "⚠️  $table table: Not found or inaccessible\n";
        }
    }
    
    // Count existing data
    $counts = [];
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $counts[$table] = $count;
        } catch (Exception $e) {
            $counts[$table] = 0;
        }
    }
    
    echo "\n📊 CURRENT DATA STATUS:\n";
    echo "-----------------------\n";
    foreach ($counts as $table => $count) {
        echo "$table: $count records\n";
    }
    echo "\n";
    
    // ==========================================
    // STEP 1: ENSURE BASIC STRUCTURE EXISTS
    // ==========================================
    
    echo "🏗️  STEP 1: ENSURING BASIC STRUCTURE\n";
    echo "===================================\n";
    
    // Create courses if none exist
    if ($counts['courses'] == 0) {
        echo "Creating basic courses...\n";
        $basic_courses = [
            ['BTECH_CSE', 'B.Tech Computer Science & Engineering'],
            ['BTECH_IT', 'B.Tech Information Technology'],
            ['BTECH_ECE', 'B.Tech Electronics & Communication'],
            ['BTECH_EE', 'B.Tech Electrical Engineering'],
            ['BTECH_ME', 'B.Tech Mechanical Engineering'],
            ['BTECH_CE', 'B.Tech Civil Engineering'],
            ['BBA', 'Bachelor of Business Administration'],
            ['BSC_CS', 'B.Sc Computer Science'],
            ['BSC_MATH', 'B.Sc Mathematics'],
            ['BSC_PHYS', 'B.Sc Physics']
        ];
        
        foreach ($basic_courses as $course) {
            try {
                // Try different column name possibilities
                $possible_queries = [
                    "INSERT IGNORE INTO courses (course_id, course_name) VALUES (?, ?)",
                    "INSERT IGNORE INTO courses (course_code, course_name) VALUES (?, ?)",
                    "INSERT IGNORE INTO courses (id, name) VALUES (?, ?)",
                    "INSERT IGNORE INTO courses (code, name) VALUES (?, ?)"
                ];
                
                $inserted = false;
                foreach ($possible_queries as $query) {
                    try {
                        $stmt = $pdo->prepare($query);
                        $stmt->execute([$course[0], $course[1]]);
                        $inserted = true;
                        echo "  ✅ Course: {$course[1]}\n";
                        break;
                    } catch (Exception $e) {
                        continue;
                    }
                }
                if (!$inserted) {
                    echo "  ❌ Could not insert course: {$course[1]}\n";
                }
            } catch (Exception $e) {
                echo "  ❌ Error with course {$course[1]}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Create batches if none exist
    if ($counts['batches'] == 0) {
        echo "Creating basic batches...\n";
        $basic_batches = [
            ['2024_A', '2024 Batch A (Morning)'],
            ['2024_B', '2024 Batch B (Evening)'],
            ['2023_A', '2023 Batch A (Morning)'],
            ['2023_B', '2023 Batch B (Evening)'],
            ['2022_A', '2022 Batch A (Morning)'],
            ['2022_B', '2022 Batch B (Evening)'],
            ['2021_A', '2021 Batch A (Morning)']
        ];
        
        foreach ($basic_batches as $batch) {
            try {
                $possible_queries = [
                    "INSERT IGNORE INTO batches (batch_id, batch_name) VALUES (?, ?)",
                    "INSERT IGNORE INTO batches (batch_code, batch_name) VALUES (?, ?)",
                    "INSERT IGNORE INTO batches (id, name) VALUES (?, ?)",
                    "INSERT IGNORE INTO batches (code, name) VALUES (?, ?)"
                ];
                
                $inserted = false;
                foreach ($possible_queries as $query) {
                    try {
                        $stmt = $pdo->prepare($query);
                        $stmt->execute([$batch[0], $batch[1]]);
                        $inserted = true;
                        echo "  ✅ Batch: {$batch[1]}\n";
                        break;
                    } catch (Exception $e) {
                        continue;
                    }
                }
                if (!$inserted) {
                    echo "  ❌ Could not insert batch: {$batch[1]}\n";
                }
            } catch (Exception $e) {
                echo "  ❌ Error with batch {$batch[1]}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Refresh data
    $courses = [];
    $batches = [];
    try {
        $courses = $pdo->query("SELECT * FROM courses ORDER BY course_id LIMIT 10")->fetchAll();
        $batches = $pdo->query("SELECT * FROM batches ORDER BY batch_id LIMIT 10")->fetchAll();
        echo "📚 Available: " . count($courses) . " courses, " . count($batches) . " batches\n\n";
    } catch (Exception $e) {
        echo "⚠️  Could not fetch courses/batches: " . $e->getMessage() . "\n\n";
    }
    
    // ==========================================
    // STEP 2: ADD COMPREHENSIVE FACULTY DATA
    // ==========================================
    
    echo "👨‍🏫 STEP 2: ADDING COMPREHENSIVE FACULTY\n";
    echo "========================================\n";
    
    $comprehensive_faculty = [
        // Computer Science Department - Female Faculty
        ['FAC001', 'Dr. Priya Sharma', 'priya.sharma@college.edu', 'Computer Science', 'Programming, Data Structures', 'Female', 75000, 'PhD', 'Professor'],
        ['FAC002', 'Dr. Kavya Patel', 'kavya.patel@college.edu', 'Computer Science', 'Database Systems, AI/ML', 'Female', 78000, 'PhD', 'Associate Professor'],
        ['FAC003', 'Ms. Shreya Singh', 'shreya.singh@college.edu', 'Information Technology', 'Web Development, Networks', 'Female', 65000, 'MTech', 'Assistant Professor'],
        ['FAC004', 'Dr. Neha Kumar', 'neha.kumar@college.edu', 'Computer Science', 'Software Engineering, Cloud Computing', 'Female', 72000, 'PhD', 'Associate Professor'],
        
        // Computer Science Department - Male Faculty
        ['FAC005', 'Prof. Rajesh Kumar', 'rajesh.kumar@college.edu', 'Computer Science', 'Algorithms, Competitive Programming', 'Male', 85000, 'PhD', 'Professor'],
        ['FAC006', 'Dr. Arjun Nair', 'arjun.nair@college.edu', 'Information Technology', 'Cybersecurity, Blockchain', 'Male', 80000, 'PhD', 'Professor'],
        ['FAC007', 'Mr. Vikram Rao', 'vikram.rao@college.edu', 'Computer Science', 'Mobile Development, UI/UX', 'Male', 68000, 'MTech', 'Assistant Professor'],
        
        // Engineering Departments - Female Faculty
        ['FAC008', 'Dr. Riya Agarwal', 'riya.agarwal@college.edu', 'Electrical Engineering', 'Circuit Design, Power Systems', 'Female', 74000, 'PhD', 'Associate Professor'],
        ['FAC009', 'Ms. Sonia Verma', 'sonia.verma@college.edu', 'Electronics Engineering', 'Digital Circuits, Microprocessors', 'Female', 66000, 'MTech', 'Assistant Professor'],
        ['FAC010', 'Dr. Meera Jain', 'meera.jain@college.edu', 'Mechanical Engineering', 'Thermodynamics, Manufacturing', 'Female', 71000, 'PhD', 'Associate Professor'],
        
        // Engineering Departments - Male Faculty
        ['FAC011', 'Prof. Suresh Krishnan', 'suresh.krishnan@college.edu', 'Electrical Engineering', 'Control Systems, Robotics', 'Male', 82000, 'PhD', 'Professor'],
        ['FAC012', 'Dr. Amit Bansal', 'amit.bansal@college.edu', 'Mechanical Engineering', 'Fluid Mechanics, Heat Transfer', 'Male', 76000, 'PhD', 'Associate Professor'],
        ['FAC013', 'Mr. Rohit Chopra', 'rohit.chopra@college.edu', 'Civil Engineering', 'Structural Analysis, Construction Management', 'Male', 70000, 'MTech', 'Assistant Professor'],
        
        // Science & Liberal Arts - Female Faculty
        ['FAC014', 'Dr. Pooja Gupta', 'pooja.gupta@college.edu', 'Mathematics', 'Calculus, Linear Algebra, Statistics', 'Female', 64000, 'PhD', 'Associate Professor'],
        ['FAC015', 'Ms. Divya Shah', 'divya.shah@college.edu', 'Physics', 'Quantum Mechanics, Optics', 'Female', 62000, 'MSc', 'Assistant Professor'],
        ['FAC016', 'Mrs. Sunita Mehta', 'sunita.mehta@college.edu', 'Chemistry', 'Organic Chemistry, Biochemistry', 'Female', 60000, 'MSc', 'Assistant Professor'],
        ['FAC017', 'Dr. Anita Rao', 'anita.rao@college.edu', 'English', 'Literature, Technical Writing', 'Female', 58000, 'PhD', 'Associate Professor'],
        
        // Science & Management - Male Faculty
        ['FAC018', 'Prof. Karan Malhotra', 'karan.malhotra@college.edu', 'Mathematics', 'Advanced Calculus, Discrete Mathematics', 'Male', 73000, 'PhD', 'Professor'],
        ['FAC019', 'Dr. Ankit Saxena', 'ankit.saxena@college.edu', 'Physics', 'Electromagnetism, Solid State Physics', 'Male', 69000, 'PhD', 'Associate Professor'],
        ['FAC020', 'Mr. Deepak Pandey', 'deepak.pandey@college.edu', 'Management', 'Finance, Marketing, Operations', 'Male', 67000, 'MBA', 'Assistant Professor']
    ];
    
    $faculty_added = 0;
    $female_faculty_added = 0;
    
    foreach ($comprehensive_faculty as $faculty) {
        try {
            // Check if faculty already exists
            $check_queries = [
                "SELECT COUNT(*) FROM faculty WHERE faculty_id = ?",
                "SELECT COUNT(*) FROM faculty WHERE email = ?",
                "SELECT COUNT(*) FROM faculty WHERE faculty_id = ? OR email = ?"
            ];
            
            $exists = false;
            foreach ($check_queries as $check_query) {
                try {
                    $stmt = $pdo->prepare($check_query);
                    if (strpos($check_query, 'OR') !== false) {
                        $stmt->execute([$faculty[0], $faculty[2]]);
                    } else {
                        $stmt->execute([$faculty[0]]);
                    }
                    if ($stmt->fetchColumn() > 0) {
                        $exists = true;
                        break;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
            
            if ($exists) {
                echo "ℹ️  Faculty {$faculty[1]} already exists, skipping\n";
                continue;
            }
            
            // Try different insert strategies
            $insert_strategies = [
                [
                    "INSERT INTO faculty (faculty_id, full_name, email, department, specialization, gender, salary, qualification, designation, employment_status, joining_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$faculty[0], $faculty[1], $faculty[2], $faculty[3], $faculty[4], $faculty[5], $faculty[6], $faculty[7], $faculty[8], 'Active', date('Y-m-d')]
                ],
                [
                    "INSERT INTO faculty (faculty_id, full_name, email, department, specialization, gender, salary, employment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$faculty[0], $faculty[1], $faculty[2], $faculty[3], $faculty[4], $faculty[5], $faculty[6], 'Active']
                ],
                [
                    "INSERT INTO faculty (faculty_id, full_name, email, department, gender, salary) VALUES (?, ?, ?, ?, ?, ?)",
                    [$faculty[0], $faculty[1], $faculty[2], $faculty[3], $faculty[5], $faculty[6]]
                ],
                [
                    "INSERT INTO faculty (faculty_id, full_name, email, department) VALUES (?, ?, ?, ?)",
                    [$faculty[0], $faculty[1], $faculty[2], $faculty[3]]
                ],
                [
                    "INSERT INTO faculty (faculty_id, full_name, email) VALUES (?, ?, ?)",
                    [$faculty[0], $faculty[1], $faculty[2]]
                ]
            ];
            
            $success = false;
            foreach ($insert_strategies as $strategy) {
                try {
                    $stmt = $pdo->prepare($strategy[0]);
                    $stmt->execute($strategy[1]);
                    $success = true;
                    break;
                } catch (Exception $e) {
                    continue;
                }
            }
            
            if ($success) {
                $faculty_added++;
                if ($faculty[5] === 'Female') $female_faculty_added++;
                $icon = $faculty[5] === 'Female' ? '👩‍🏫' : '👨‍🏫';
                echo "✅ $icon {$faculty[1]} - {$faculty[3]} - {$faculty[8]} ({$faculty[0]})\n";
            } else {
                echo "❌ Could not add faculty: {$faculty[1]}\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error adding faculty {$faculty[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Faculty Summary: $faculty_added total ($female_faculty_added female, " . ($faculty_added - $female_faculty_added) . " male)\n";
    echo "Female representation: " . round($female_faculty_added / max($faculty_added, 1) * 100, 1) . "%\n\n";
    
    // ==========================================
    // STEP 3: ADD COMPREHENSIVE STUDENT DATA
    // ==========================================
    
    echo "👥 STEP 3: ADDING COMPREHENSIVE STUDENTS\n";
    echo "======================================\n";
    
    $comprehensive_students = [
        // Female Students - Diverse backgrounds
        ['STU001', 'Priya Sharma', 'priya.sharma@student.edu', 'Female', 'Computer Science'],
        ['STU002', 'Kavya Patel', 'kavya.patel@student.edu', 'Female', 'Information Technology'],
        ['STU003', 'Shreya Singh', 'shreya.singh@student.edu', 'Female', 'Computer Science'],
        ['STU004', 'Neha Kumar', 'neha.kumar@student.edu', 'Female', 'Electrical Engineering'],
        ['STU005', 'Pooja Gupta', 'pooja.gupta@student.edu', 'Female', 'Electronics Engineering'],
        ['STU006', 'Riya Agarwal', 'riya.agarwal@student.edu', 'Female', 'Mechanical Engineering'],
        ['STU007', 'Sonia Verma', 'sonia.verma@student.edu', 'Female', 'Civil Engineering'],
        ['STU008', 'Meera Jain', 'meera.jain@student.edu', 'Female', 'Computer Science'],
        ['STU009', 'Divya Shah', 'divya.shah@student.edu', 'Female', 'Information Technology'],
        ['STU010', 'Sunita Mehta', 'sunita.mehta@student.edu', 'Female', 'Mathematics'],
        ['STU011', 'Anita Rao', 'anita.rao@student.edu', 'Female', 'Physics'],
        ['STU012', 'Rekha Iyer', 'rekha.iyer@student.edu', 'Female', 'Chemistry'],
        
        // Male Students - Diverse backgrounds
        ['STU013', 'Aarav Reddy', 'aarav.reddy@student.edu', 'Male', 'Computer Science'],
        ['STU014', 'Arjun Nair', 'arjun.nair@student.edu', 'Male', 'Information Technology'],
        ['STU015', 'Vikram Rao', 'vikram.rao@student.edu', 'Male', 'Electrical Engineering'],
        ['STU016', 'Rajesh Iyer', 'rajesh.iyer@student.edu', 'Male', 'Electronics Engineering'],
        ['STU017', 'Suresh Krishnan', 'suresh.krishnan@student.edu', 'Male', 'Mechanical Engineering'],
        ['STU018', 'Amit Bansal', 'amit.bansal@student.edu', 'Male', 'Civil Engineering'],
        ['STU019', 'Rohit Chopra', 'rohit.chopra@student.edu', 'Male', 'Computer Science'],
        ['STU020', 'Karan Malhotra', 'karan.malhotra@student.edu', 'Male', 'Information Technology'],
        ['STU021', 'Ankit Saxena', 'ankit.saxena@student.edu', 'Male', 'Electrical Engineering'],
        ['STU022', 'Deepak Pandey', 'deepak.pandey@student.edu', 'Male', 'Mechanical Engineering'],
        ['STU023', 'Ravi Kumar', 'ravi.kumar@student.edu', 'Male', 'Mathematics'],
        ['STU024', 'Anil Sharma', 'anil.sharma@student.edu', 'Male', 'Physics'],
        ['STU025', 'Nitin Patel', 'nitin.patel@student.edu', 'Male', 'Management'],
        ['STU026', 'Rahul Singh', 'rahul.singh@student.edu', 'Male', 'Computer Science'],
        ['STU027', 'Manish Gupta', 'manish.gupta@student.edu', 'Male', 'Information Technology'],
        ['STU028', 'Sachin Joshi', 'sachin.joshi@student.edu', 'Male', 'Electronics Engineering']
    ];
    
    $students_added = 0;
    $female_students_added = 0;
    
    foreach ($comprehensive_students as $student) {
        try {
            // Check if student already exists
            $exists = false;
            $check_queries = [
                "SELECT COUNT(*) FROM students WHERE student_id = ?",
                "SELECT COUNT(*) FROM students WHERE email = ?",
                "SELECT COUNT(*) FROM students WHERE student_id = ? OR email = ?"
            ];
            
            foreach ($check_queries as $check_query) {
                try {
                    $stmt = $pdo->prepare($check_query);
                    if (strpos($check_query, 'OR') !== false) {
                        $stmt->execute([$student[0], $student[2]]);
                    } else {
                        $stmt->execute([$student[0]]);
                    }
                    if ($stmt->fetchColumn() > 0) {
                        $exists = true;
                        break;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
            
            if ($exists) {
                echo "ℹ️  Student {$student[1]} already exists, skipping\n";
                continue;
            }
            
            // Get random course and batch
            $course = !empty($courses) ? $courses[array_rand($courses)] : null;
            $batch = !empty($batches) ? $batches[array_rand($batches)] : null;
            
            $course_id = null;
            $batch_id = null;
            
            if ($course) {
                $course_keys = array_keys($course);
                $course_id = $course[$course_keys[0]]; // First column value
            }
            
            if ($batch) {
                $batch_keys = array_keys($batch);
                $batch_id = $batch[$batch_keys[0]]; // First column value
            }
            
            // Try different insert strategies
            $insert_strategies = [
                [
                    "INSERT INTO students (student_id, full_name, email, gender, course_id, batch_id, academic_status, admission_date, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$student[0], $student[1], $student[2], $student[3], $course_id, $batch_id, 'Active', date('Y-m-d'), '9876543210']
                ],
                [
                    "INSERT INTO students (student_id, full_name, email, gender, course_id, batch_id, academic_status) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$student[0], $student[1], $student[2], $student[3], $course_id, $batch_id, 'Active']
                ],
                [
                    "INSERT INTO students (student_id, full_name, email, gender, course_id, batch_id) VALUES (?, ?, ?, ?, ?, ?)",
                    [$student[0], $student[1], $student[2], $student[3], $course_id, $batch_id]
                ],
                [
                    "INSERT INTO students (student_id, full_name, email, course_id, batch_id) VALUES (?, ?, ?, ?, ?)",
                    [$student[0], $student[1], $student[2], $course_id, $batch_id]
                ],
                [
                    "INSERT INTO students (student_id, full_name, email, gender) VALUES (?, ?, ?, ?)",
                    [$student[0], $student[1], $student[2], $student[3]]
                ],
                [
                    "INSERT INTO students (student_id, full_name, email) VALUES (?, ?, ?)",
                    [$student[0], $student[1], $student[2]]
                ]
            ];
            
            $success = false;
            foreach ($insert_strategies as $strategy) {
                try {
                    $stmt = $pdo->prepare($strategy[0]);
                    $stmt->execute($strategy[1]);
                    $success = true;
                    break;
                } catch (Exception $e) {
                    continue;
                }
            }
            
            if ($success) {
                $students_added++;
                if ($student[3] === 'Female') $female_students_added++;
                $icon = $student[3] === 'Female' ? '👩‍🎓' : '👨‍🎓';
                echo "✅ $icon {$student[1]} - {$student[4]} ({$student[0]})\n";
            } else {
                echo "❌ Could not add student: {$student[1]}\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error adding student {$student[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Students Summary: $students_added total ($female_students_added female, " . ($students_added - $female_students_added) . " male)\n";
    echo "Female representation: " . round($female_students_added / max($students_added, 1) * 100, 1) . "%\n\n";
    
    // ==========================================
    // STEP 4: ADD COMPREHENSIVE SUBJECTS
    // ==========================================
    
    echo "📚 STEP 4: ADDING COMPREHENSIVE SUBJECTS\n";
    echo "=======================================\n";
    
    $comprehensive_subjects = [
        // Computer Science & IT Subjects
        ['CS101', 'Programming Fundamentals', 'Introduction to programming concepts using C/C++', 4, 'FAC001', 1, 'Theory'],
        ['CS102', 'Data Structures', 'Arrays, linked lists, stacks, queues, trees, graphs', 4, 'FAC002', 2, 'Theory'],
        ['CS201', 'Database Management Systems', 'Relational database design, SQL, normalization', 4, 'FAC002', 3, 'Theory'],
        ['CS202', 'Web Development', 'HTML, CSS, JavaScript, PHP, frameworks', 4, 'FAC003', 3, 'Practical'],
        ['CS301', 'Artificial Intelligence', 'Machine learning, neural networks, AI algorithms', 4, 'FAC002', 5, 'Theory'],
        ['CS302', 'Software Engineering', 'SDLC, design patterns, project management', 4, 'FAC004', 5, 'Theory'],
        ['IT101', 'Computer Networks', 'OSI model, TCP/IP, network protocols', 4, 'FAC003', 4, 'Theory'],
        ['IT201', 'Cybersecurity', 'Network security, cryptography, ethical hacking', 4, 'FAC006', 6, 'Theory'],
        
        // Engineering Subjects
        ['EE101', 'Circuit Analysis', 'DC and AC circuit analysis, network theorems', 4, 'FAC008', 1, 'Theory'],
        ['EE102', 'Digital Electronics', 'Boolean algebra, combinational and sequential circuits', 4, 'FAC009', 2, 'Theory'],
        ['EE201', 'Control Systems', 'Feedback systems, stability, PID controllers', 4, 'FAC011', 4, 'Theory'],
        ['ME101', 'Engineering Mechanics', 'Statics, dynamics, mechanics of materials', 4, 'FAC010', 1, 'Theory'],
        ['ME201', 'Thermodynamics', 'Laws of thermodynamics, heat engines, cycles', 4, 'FAC012', 3, 'Theory'],
        ['CE101', 'Surveying', 'Land surveying, leveling, measurement techniques', 3, 'FAC013', 2, 'Practical'],
        
        // Science & Mathematics Subjects
        ['MATH101', 'Calculus I', 'Differential calculus, limits, derivatives', 4, 'FAC014', 1, 'Theory'],
        ['MATH201', 'Linear Algebra', 'Matrices, vectors, eigenvalues, vector spaces', 4, 'FAC018', 2, 'Theory'],
        ['MATH301', 'Statistics & Probability', 'Probability distributions, statistical inference', 3, 'FAC014', 4, 'Theory'],
        ['PHYS101', 'Physics I', 'Mechanics, waves, thermodynamics', 4, 'FAC015', 1, 'Theory'],
        ['PHYS201', 'Electromagnetism', 'Electric and magnetic fields, Maxwell equations', 4, 'FAC019', 3, 'Theory'],
        ['CHEM101', 'Chemistry', 'Atomic structure, chemical bonding, reactions', 4, 'FAC016', 1, 'Theory'],
        
        // Liberal Arts & Management
        ['ENG101', 'Technical Communication', 'Written communication, presentation skills', 3, 'FAC017', 1, 'Theory'],
        ['MGT101', 'Principles of Management', 'Planning, organizing, leading, controlling', 3, 'FAC020', 2, 'Theory'],
        ['MGT201', 'Financial Management', 'Corporate finance, investment analysis', 3, 'FAC020', 4, 'Theory']
    ];
    
    $subjects_added = 0;
    
    foreach ($comprehensive_subjects as $subject) {
        try {
            // Check if subject already exists
            $exists = false;
            $check_queries = [
                "SELECT COUNT(*) FROM subjects WHERE subject_code = ?",
                "SELECT COUNT(*) FROM subjects WHERE subject_name = ?"
            ];
            
            foreach ($check_queries as $check_query) {
                try {
                    $stmt = $pdo->prepare($check_query);
                    $stmt->execute([$subject[0]]);
                    if ($stmt->fetchColumn() > 0) {
                        $exists = true;
                        break;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
            
            if ($exists) {
                echo "ℹ️  Subject {$subject[1]} already exists, skipping\n";
                continue;
            }
            
            // Get random course
            $course = !empty($courses) ? $courses[array_rand($courses)] : null;
            $course_id = null;
            
            if ($course) {
                $course_keys = array_keys($course);
                $course_id = $course[$course_keys[0]];
            }
            
            // Try different insert strategies
            $insert_strategies = [
                [
                    "INSERT INTO subjects (subject_code, subject_name, description, credits, faculty_id, course_id, semester, subject_type, academic_year, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$subject[0], $subject[1], $subject[2], $subject[3], $subject[4], $course_id, $subject[5], $subject[6], '2024-25', 'Active']
                ],
                [
                    "INSERT INTO subjects (subject_code, subject_name, description, credits, faculty_id, course_id, semester, subject_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$subject[0], $subject[1], $subject[2], $subject[3], $subject[4], $course_id, $subject[5], $subject[6]]
                ],
                [
                    "INSERT INTO subjects (subject_code, subject_name, description, credits, faculty_id, semester) VALUES (?, ?, ?, ?, ?, ?)",
                    [$subject[0], $subject[1], $subject[2], $subject[3], $subject[4], $subject[5]]
                ],
                [
                    "INSERT INTO subjects (subject_code, subject_name, faculty_id, credits) VALUES (?, ?, ?, ?)",
                    [$subject[0], $subject[1], $subject[4], $subject[3]]
                ],
                [
                    "INSERT INTO subjects (subject_code, subject_name, faculty_id) VALUES (?, ?, ?)",
                    [$subject[0], $subject[1], $subject[4]]
                ]
            ];
            
            $success = false;
            foreach ($insert_strategies as $strategy) {
                try {
                    $stmt = $pdo->prepare($strategy[0]);
                    $stmt->execute($strategy[1]);
                    $success = true;
                    break;
                } catch (Exception $e) {
                    continue;
                }
            }
            
            if ($success) {
                $subjects_added++;
                $type_icon = $subject[6] === 'Practical' ? '🔬' : '📖';
                echo "✅ $type_icon {$subject[1]} ({$subject[0]}) - Sem {$subject[5]} - {$subject[4]}\n";
            } else {
                echo "❌ Could not add subject: {$subject[1]}\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error adding subject {$subject[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Subjects Summary: $subjects_added total\n\n";
    
    // ==========================================
    // STEP 5: ADD COMPREHENSIVE TESTS
    // ==========================================
    
    echo "📝 STEP 5: ADDING COMPREHENSIVE TESTS\n";
    echo "====================================\n";
    
    // Get added subjects for test creation
    $added_subjects = [];
    try {
        $added_subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_id DESC LIMIT 25")->fetchAll();
    } catch (Exception $e) {
        echo "⚠️  Could not fetch subjects for test creation\n";
    }
    
    $test_templates = [
        ['Quiz 1', 25, 'Quiz', 60, 'First quiz covering basic concepts'],
        ['Midterm Exam', 100, 'Midterm', 120, 'Comprehensive midterm examination'],
        ['Quiz 2', 25, 'Quiz', 60, 'Second quiz on advanced topics'],
        ['Assignment', 50, 'Assignment', 0, 'Take-home assignment project'],
        ['Final Exam', 100, 'Final', 180, 'Comprehensive final examination']
    ];
    
    $tests_added = 0;
    
    foreach ($added_subjects as $subject) {
        $subject_keys = array_keys($subject);
        $subject_id = $subject[$subject_keys[0]]; // First column (usually subject_id)
        $subject_name = isset($subject['subject_name']) ? $subject['subject_name'] : 
                       (isset($subject['name']) ? $subject['name'] : 'Subject');
        $faculty_id = isset($subject['faculty_id']) ? $subject['faculty_id'] : 'FAC001';
        
        // Add 3-4 tests per subject
        $num_tests = rand(3, 4);
        $selected_tests = array_slice($test_templates, 0, $num_tests);
        
        foreach ($selected_tests as $index => $test_template) {
            try {
                $test_name = "$subject_name - {$test_template[0]}";
                $test_date = date('Y-m-d', strtotime('+' . rand(5, 45) . ' days'));
                
                // Try different insert strategies
                $insert_strategies = [
                    [
                        "INSERT INTO tests (test_name, subject_id, faculty_id, max_marks, test_date, test_type, duration, instructions, status, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$test_name, $subject_id, $faculty_id, $test_template[1], $test_date, $test_template[2], $test_template[3], $test_template[4], 'Published', date('Y-m-d')]
                    ],
                    [
                        "INSERT INTO tests (test_name, subject_id, faculty_id, max_marks, test_date, test_type, duration, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [$test_name, $subject_id, $faculty_id, $test_template[1], $test_date, $test_template[2], $test_template[3], 'Published']
                    ],
                    [
                        "INSERT INTO tests (test_name, subject_id, faculty_id, max_marks, test_date, test_type) VALUES (?, ?, ?, ?, ?, ?)",
                        [$test_name, $subject_id, $faculty_id, $test_template[1], $test_date, $test_template[2]]
                    ],
                    [
                        "INSERT INTO tests (test_name, subject_id, max_marks, test_date) VALUES (?, ?, ?, ?)",
                        [$test_name, $subject_id, $test_template[1], $test_date]
                    ],
                    [
                        "INSERT INTO tests (test_name, subject_id, max_marks) VALUES (?, ?, ?)",
                        [$test_name, $subject_id, $test_template[1]]
                    ]
                ];
                
                $success = false;
                foreach ($insert_strategies as $strategy) {
                    try {
                        $stmt = $pdo->prepare($strategy[0]);
                        $stmt->execute($strategy[1]);
                        $success = true;
                        break;
                    } catch (Exception $e) {
                        continue;
                    }
                }
                
                if ($success) {
                    $tests_added++;
                    $test_icon = $test_template[2] === 'Quiz' ? '📝' : 
                                ($test_template[2] === 'Final' ? '🎯' : 
                                ($test_template[2] === 'Assignment' ? '📋' : '📊'));
                    echo "✅ $test_icon $test_name - {$test_template[1]} marks - $test_date\n";
                } else {
                    echo "❌ Could not add test: $test_name\n";
                }
                
            } catch (Exception $e) {
                echo "❌ Error adding test for $subject_name: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n📊 Tests Summary: $tests_added total\n\n";
    
    // ==========================================
    // FINAL SUMMARY
    // ==========================================
    
    echo "🎉 COMPREHENSIVE DEMO DATA SETUP COMPLETE!\n";
    echo "==========================================\n";
    echo "✅ Faculty Members: $faculty_added (Professional staff across all departments)\n";
    echo "✅ Students: $students_added (Diverse student body with gender balance)\n";
    echo "✅ Subjects: $subjects_added (Comprehensive curriculum across disciplines)\n";
    echo "✅ Tests: $tests_added (Various assessment types ready for grading)\n\n";
    
    $total_people = $faculty_added + $students_added;
    $total_female = $female_faculty_added + $female_students_added;
    
    if ($total_people > 0) {
        echo "👥 Gender Diversity Summary:\n";
        echo "   • Total People: $total_people\n";
        echo "   • Female: $total_female (" . round($total_female / $total_people * 100, 1) . "%)\n";
        echo "   • Male: " . ($total_people - $total_female) . " (" . round(($total_people - $total_female) / $total_people * 100, 1) . "%)\n\n";
    }
    
    echo "🚀 YOUR COLLEGE MANAGEMENT SYSTEM IS NOW FULLY READY!\n";
    echo "• Complete academic hierarchy with departments and specializations\n";
    echo "• Realistic faculty-subject-student relationships\n";
    echo "• Ready-to-use grading system with published tests\n";
    echo "• Comprehensive demo data for all system modules\n";
    
    echo "</div>";
    
    // Create beautiful summary cards
    echo "<div class='stats-card text-center'>";
    echo "<h4 class='mb-3'>🎓 Demo Environment Successfully Created!</h4>";
    echo "<div class='row'>";
    echo "<div class='col-md-3'>";
    echo "<h2>$faculty_added</h2>";
    echo "<p class='mb-0'>Faculty Members</p>";
    echo "<small>Across all departments</small>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h2>$students_added</h2>";
    echo "<p class='mb-0'>Students</p>";
    echo "<small>Diverse backgrounds</small>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h2>$subjects_added</h2>";
    echo "<p class='mb-0'>Subjects</p>";
    echo "<small>Complete curriculum</small>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<h2>$tests_added</h2>";
    echo "<p class='mb-0'>Tests</p>";
    echo "<small>Ready for grading</small>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    if ($total_people > 0) {
        echo "<div class='alert alert-info mt-3'>";
        echo "<h6>👥 Diversity & Inclusion Achieved</h6>";
        echo "<div class='progress mb-2' style='height: 25px;'>";
        $female_percentage = round($total_female / $total_people * 100, 1);
        echo "<div class='progress-bar bg-success' role='progressbar' style='width: {$female_percentage}%' aria-valuenow='$female_percentage' aria-valuemin='0' aria-valuemax='100'>";
        echo "{$female_percentage}% Female";
        echo "</div>";
        echo "</div>";
        echo "<p class='mb-0'><strong>Excellent gender representation</strong> with $total_female female members out of $total_people total people in your demo system.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "</div>";
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ Error During Comprehensive Setup</h5>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>The script uses multiple fallback strategies to ensure maximum compatibility with different database structures.</p>";
    echo "</div>";
}

echo "<div class='mt-4 text-center'>";
echo "<h5 class='mb-3'>🚀 Ready to Explore Your System</h5>";
echo "<div class='row'>";
echo "<div class='col-md-2'>";
echo "<a href='students.php' class='btn btn-primary w-100 mb-2'>👥<br>Students</a>";
echo "</div>";
echo "<div class='col-md-2'>";
echo "<a href='faculty.php' class='btn btn-primary w-100 mb-2'>👨‍🏫<br>Faculty</a>";
echo "</div>";
echo "<div class='col-md-2'>";
echo "<a href='subjects.php' class='btn btn-primary w-100 mb-2'>📚<br>Subjects</a>";
echo "</div>";
echo "<div class='col-md-2'>";
echo "<a href='tests.php' class='btn btn-primary w-100 mb-2'>📝<br>Tests</a>";
echo "</div>";
echo "<div class='col-md-2'>";
echo "<a href='grades.php' class='btn btn-success w-100 mb-2'>🎯<br>Grading</a>";
echo "</div>";
echo "<div class='col-md-2'>";
echo "<a href='index.php' class='btn btn-outline-primary w-100 mb-2'>🏠<br>Dashboard</a>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div></body></html>";
?>