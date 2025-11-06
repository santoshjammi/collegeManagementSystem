<?php
/**
 * Super Simple Data Adder - Works with Any Schema
 * This will check your exact table structure and use only existing columns
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Universal Data Adder</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>.log { font-family: monospace; background: #f8f9fa; padding: 15px; border: 1px solid #ddd; max-height: 500px; overflow-y: auto; }</style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h3 mb-4'>Universal Sample Data Adder</h1>";

try {
    echo "<div class='log'>";
    
    // First, inspect the actual table structures
    echo "🔍 INSPECTING YOUR DATABASE STRUCTURE:\n";
    echo "=====================================\n";
    
    $stmt = $pdo->query("DESCRIBE students");
    $students_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Students table columns: " . implode(', ', $students_columns) . "\n";
    
    $stmt = $pdo->query("DESCRIBE faculty");
    $faculty_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Faculty table columns: " . implode(', ', $faculty_columns) . "\n\n";
    
    // Get available courses and batches
    $stmt = $pdo->query("SELECT course_id, course_name FROM courses LIMIT 5");
    $courses = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT batch_id, batch_name FROM batches LIMIT 5");
    $batches = $stmt->fetchAll();
    
    echo "📚 Available: " . count($courses) . " courses, " . count($batches) . " batches\n\n";
    
    if (empty($courses) || empty($batches)) {
        throw new Exception("No courses or batches available");
    }
    
    // Student data - using only essential info
    $students_data = [
        // Female students
        ['STU001', 'Priya Sharma', 'priya.sharma@student.edu'],
        ['STU002', 'Kavya Patel', 'kavya.patel@student.edu'],
        ['STU003', 'Shreya Singh', 'shreya.singh@student.edu'],
        ['STU004', 'Neha Kumar', 'neha.kumar@student.edu'],
        ['STU005', 'Pooja Gupta', 'pooja.gupta@student.edu'],
        ['STU006', 'Riya Agarwal', 'riya.agarwal@student.edu'],
        ['STU007', 'Sonia Verma', 'sonia.verma@student.edu'],
        ['STU008', 'Meera Jain', 'meera.jain@student.edu'],
        ['STU009', 'Divya Shah', 'divya.shah@student.edu'],
        ['STU010', 'Sunita Mehta', 'sunita.mehta@student.edu'],
        
        // Male students
        ['STU011', 'Aarav Reddy', 'aarav.reddy@student.edu'],
        ['STU012', 'Arjun Nair', 'arjun.nair@student.edu'],
        ['STU013', 'Vikram Rao', 'vikram.rao@student.edu'],
        ['STU014', 'Rajesh Iyer', 'rajesh.iyer@student.edu'],
        ['STU015', 'Suresh Krishnan', 'suresh.krishnan@student.edu'],
        ['STU016', 'Amit Bansal', 'amit.bansal@student.edu'],
        ['STU017', 'Rohit Chopra', 'rohit.chopra@student.edu'],
        ['STU018', 'Karan Malhotra', 'karan.malhotra@student.edu'],
        ['STU019', 'Ankit Saxena', 'ankit.saxena@student.edu'],
        ['STU020', 'Deepak Pandey', 'deepak.pandey@student.edu'],
        ['STU021', 'Ravi Kumar', 'ravi.kumar@student.edu'],
        ['STU022', 'Anil Sharma', 'anil.sharma@student.edu'],
        ['STU023', 'Nitin Patel', 'nitin.patel@student.edu'],
        ['STU024', 'Rahul Singh', 'rahul.singh@student.edu'],
        ['STU025', 'Manish Gupta', 'manish.gupta@student.edu']
    ];
    
    echo "👥 ADDING 25 STUDENTS (using your exact table structure):\n";
    echo "========================================================\n";
    
    $studentCount = 0;
    $femaleCount = 0;
    
    foreach ($students_data as $index => $student) {
        try {
            // Check if student already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
            $checkStmt->execute([$student[0]]);
            if ($checkStmt->fetchColumn() > 0) {
                echo "ℹ️  Student {$student[0]} already exists, skipping\n";
                continue;
            }
            
            // Random course and batch
            $course = $courses[array_rand($courses)];
            $batch = $batches[array_rand($batches)];
            
            // Build INSERT query using only columns that exist
            $columns = [];
            $values = [];
            $placeholders = [];
            
            // Essential columns that should always exist
            if (in_array('student_id', $students_columns)) {
                $columns[] = 'student_id';
                $values[] = $student[0];
                $placeholders[] = '?';
            }
            
            if (in_array('full_name', $students_columns)) {
                $columns[] = 'full_name';
                $values[] = $student[1];
                $placeholders[] = '?';
            }
            
            if (in_array('email', $students_columns)) {
                $columns[] = 'email';
                $values[] = $student[2];
                $placeholders[] = '?';
            }
            
            if (in_array('course_id', $students_columns)) {
                $columns[] = 'course_id';
                $values[] = $course['course_id'];
                $placeholders[] = '?';
            }
            
            if (in_array('batch_id', $students_columns)) {
                $columns[] = 'batch_id';
                $values[] = $batch['batch_id'];
                $placeholders[] = '?';
            }
            
            // Optional columns - only add if they exist
            if (in_array('gender', $students_columns)) {
                $columns[] = 'gender';
                $values[] = $index < 10 ? 'Female' : 'Male';  // First 10 are female
                $placeholders[] = '?';
            }
            
            if (in_array('academic_status', $students_columns)) {
                $columns[] = 'academic_status';
                $values[] = 'Active';
                $placeholders[] = '?';
            }
            
            if (in_array('admission_date', $students_columns)) {
                $columns[] = 'admission_date';
                $values[] = date('Y-m-d');
                $placeholders[] = '?';
            }
            
            if (empty($columns)) {
                echo "❌ No compatible columns found for students table\n";
                break;
            }
            
            $sql = "INSERT INTO students (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            $studentCount++;
            if ($index < 10) $femaleCount++;  // First 10 are female
            
            $genderIcon = $index < 10 ? '👩' : '👨';
            echo "✅ $genderIcon Student $studentCount: {$student[1]} ({$student[0]}) - {$course['course_name']}\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to add student {$student[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Students Added: $studentCount total ($femaleCount female, " . ($studentCount - $femaleCount) . " male)\n\n";
    
    // Faculty data
    $faculty_data = [
        ['FAC001', 'Dr. Priya Sharma', 'priya.sharma@college.edu', 'Computer Science'],
        ['FAC002', 'Ms. Kavya Patel', 'kavya.patel@college.edu', 'Information Technology'],
        ['FAC003', 'Mrs. Shreya Singh', 'shreya.singh@college.edu', 'Mathematics'],
        ['FAC004', 'Dr. Neha Kumar', 'neha.kumar@college.edu', 'Physics'],
        ['FAC005', 'Ms. Pooja Gupta', 'pooja.gupta@college.edu', 'Chemistry'],
        ['FAC006', 'Dr. Aarav Reddy', 'aarav.reddy@college.edu', 'Computer Science'],
        ['FAC007', 'Prof. Arjun Nair', 'arjun.nair@college.edu', 'Electrical Engineering'],
        ['FAC008', 'Dr. Vikram Rao', 'vikram.rao@college.edu', 'Mechanical Engineering'],
        ['FAC009', 'Mr. Rajesh Iyer', 'rajesh.iyer@college.edu', 'Civil Engineering'],
        ['FAC010', 'Prof. Suresh Krishnan', 'suresh.krishnan@college.edu', 'Electronics'],
        ['FAC011', 'Dr. Amit Bansal', 'amit.bansal@college.edu', 'Mathematics'],
        ['FAC012', 'Mr. Rohit Chopra', 'rohit.chopra@college.edu', 'Physics'],
        ['FAC013', 'Prof. Karan Malhotra', 'karan.malhotra@college.edu', 'Chemistry'],
        ['FAC014', 'Dr. Ankit Saxena', 'ankit.saxena@college.edu', 'English'],
        ['FAC015', 'Mr. Deepak Pandey', 'deepak.pandey@college.edu', 'Management']
    ];
    
    echo "👨‍🏫 ADDING 15 FACULTY (using your exact table structure):\n";
    echo "======================================================\n";
    
    $facultyCount = 0;
    $femaleFacultyCount = 0;
    
    foreach ($faculty_data as $index => $faculty) {
        try {
            // Check if faculty already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM faculty WHERE faculty_id = ?");
            $checkStmt->execute([$faculty[0]]);
            if ($checkStmt->fetchColumn() > 0) {
                echo "ℹ️  Faculty {$faculty[0]} already exists, skipping\n";
                continue;
            }
            
            // Build INSERT query using only columns that exist
            $columns = [];
            $values = [];
            $placeholders = [];
            
            // Essential columns
            if (in_array('faculty_id', $faculty_columns)) {
                $columns[] = 'faculty_id';
                $values[] = $faculty[0];
                $placeholders[] = '?';
            }
            
            if (in_array('full_name', $faculty_columns)) {
                $columns[] = 'full_name';
                $values[] = $faculty[1];
                $placeholders[] = '?';
            }
            
            if (in_array('email', $faculty_columns)) {
                $columns[] = 'email';
                $values[] = $faculty[2];
                $placeholders[] = '?';
            }
            
            if (in_array('department', $faculty_columns)) {
                $columns[] = 'department';
                $values[] = $faculty[3];
                $placeholders[] = '?';
            }
            
            // Optional columns
            if (in_array('gender', $faculty_columns)) {
                $columns[] = 'gender';
                $values[] = $index < 5 ? 'Female' : 'Male';  // First 5 are female
                $placeholders[] = '?';
            }
            
            if (in_array('employment_status', $faculty_columns)) {
                $columns[] = 'employment_status';
                $values[] = 'Active';
                $placeholders[] = '?';
            }
            
            if (in_array('joining_date', $faculty_columns)) {
                $columns[] = 'joining_date';
                $values[] = date('Y-m-d');
                $placeholders[] = '?';
            }
            
            if (in_array('salary', $faculty_columns)) {
                $columns[] = 'salary';
                $values[] = rand(40000, 80000);
                $placeholders[] = '?';
            }
            
            if (empty($columns)) {
                echo "❌ No compatible columns found for faculty table\n";
                break;
            }
            
            $sql = "INSERT INTO faculty (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            $facultyCount++;
            if ($index < 5) $femaleFacultyCount++;  // First 5 are female
            
            $genderIcon = $index < 5 ? '👩‍🏫' : '👨‍🏫';
            echo "✅ $genderIcon Faculty $facultyCount: {$faculty[1]} ({$faculty[0]}) - {$faculty[3]}\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to add faculty {$faculty[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Faculty Added: $facultyCount total ($femaleFacultyCount female, " . ($facultyCount - $femaleFacultyCount) . " male)\n\n";
    
    echo "🎉 DATA ADDITION COMPLETE!\n";
    echo "=========================\n";
    echo "✅ Successfully added $studentCount students\n";
    echo "✅ Successfully added $facultyCount faculty members\n";
    
    $totalFemale = $femaleCount + $femaleFacultyCount;
    $totalPeople = $studentCount + $facultyCount;
    if ($totalPeople > 0) {
        echo "✅ Female representation: " . round($totalFemale / $totalPeople * 100, 1) . "% ($totalFemale out of $totalPeople)\n";
    }
    
    echo "</div>";
    
    if ($studentCount > 0 || $facultyCount > 0) {
        echo "<div class='alert alert-success mt-3'>";
        echo "<h5>🎉 Sample Data Added Successfully!</h5>";
        echo "<div class='row'>";
        echo "<div class='col-md-6'>";
        echo "<h6>👥 Students: $studentCount</h6>";
        echo "<p class='mb-0'>Including female students: Priya, Kavya, Shreya, Neha, Pooja, Riya, Sonia, Meera, Divya, Sunita</p>";
        echo "</div>";
        echo "<div class='col-md-6'>";
        echo "<h6>👨‍🏫 Faculty: $facultyCount</h6>";
        echo "<p class='mb-0'>Including female faculty: Dr. Priya, Ms. Kavya, Mrs. Shreya, Dr. Neha, Ms. Pooja</p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning mt-3'>";
        echo "<h5>⚠️ No Data Added</h5>";
        echo "<p>All records may already exist, or there might be table structure issues.</p>";
        echo "<p>Check the log above for details.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "</div>";
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ Error During Setup</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='mt-3'>";
echo "<a href='students.php' class='btn btn-primary me-2'>👥 View Students</a>";
echo "<a href='faculty.php' class='btn btn-primary me-2'>👨‍🏫 View Faculty</a>";
echo "<a href='index.php' class='btn btn-outline-primary'>🏠 Dashboard</a>";
echo "</div>";

echo "</div></body></html>";
?>