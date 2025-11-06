<?php
/**
 * Simple Student & Faculty Data Adder
 * This assumes you already have some courses and batches in your database
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Add Students & Faculty</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>.log { font-family: monospace; background: #f8f9fa; padding: 15px; border: 1px solid #ddd; max-height: 500px; overflow-y: auto; }</style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h3 mb-4'>Add Sample Students & Faculty</h1>";

try {
    echo "<div class='log'>";
    
    // Check if we have existing courses and batches
    $stmt = $pdo->query("SELECT COUNT(*) FROM courses");
    $courseCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM batches");
    $batchCount = $stmt->fetchColumn();
    
    echo "📋 CHECKING EXISTING DATA:\n";
    echo "=========================\n";
    echo "Existing courses: $courseCount\n";
    echo "Existing batches: $batchCount\n\n";
    
    if ($courseCount == 0) {
        echo "❌ No courses found. Adding basic courses first...\n\n";
        
        // Add minimal courses
        $basicCourses = [
            ['CS', 'Computer Science'],
            ['IT', 'Information Technology'],
            ['EE', 'Electrical Engineering']
        ];
        
        foreach ($basicCourses as $course) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO courses (course_code, course_name) VALUES (?, ?)");
                $stmt->execute($course);
                if ($stmt->rowCount() > 0) {
                    echo "✅ Added course: {$course[1]}\n";
                }
            } catch (Exception $e) {
                echo "❌ Error adding course: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }
    
    if ($batchCount == 0) {
        echo "❌ No batches found. Adding basic batches...\n\n";
        
        // Get courses and add batches
        $stmt = $pdo->query("SELECT course_id, course_code FROM courses LIMIT 3");
        $courses = $stmt->fetchAll();
        
        foreach ($courses as $course) {
            try {
                $batchName = $course['course_code'] . '_2024';
                $stmt = $pdo->prepare("INSERT IGNORE INTO batches (batch_name, course_id, batch_year) VALUES (?, ?, 2024)");
                $stmt->execute([$batchName, $course['course_id']]);
                if ($stmt->rowCount() > 0) {
                    echo "✅ Added batch: $batchName\n";
                }
            } catch (Exception $e) {
                echo "❌ Error adding batch: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }
    
    // Now get available courses and batches
    $stmt = $pdo->query("SELECT course_id, course_name FROM courses");
    $courses = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT batch_id, batch_name FROM batches");
    $batches = $stmt->fetchAll();
    
    if (empty($courses) || empty($batches)) {
        throw new Exception("No courses or batches available for student creation");
    }
    
    echo "📚 Available: " . count($courses) . " courses, " . count($batches) . " batches\n\n";
    
    // Sample data
    $students_data = [
        // Female students (10)
        ['STU001', 'Priya Sharma', 'priya.sharma@student.edu', '9876543210', 'Female'],
        ['STU002', 'Kavya Patel', 'kavya.patel@student.edu', '9876543211', 'Female'],
        ['STU003', 'Shreya Singh', 'shreya.singh@student.edu', '9876543212', 'Female'],
        ['STU004', 'Neha Kumar', 'neha.kumar@student.edu', '9876543213', 'Female'],
        ['STU005', 'Pooja Gupta', 'pooja.gupta@student.edu', '9876543214', 'Female'],
        ['STU006', 'Riya Agarwal', 'riya.agarwal@student.edu', '9876543215', 'Female'],
        ['STU007', 'Sonia Verma', 'sonia.verma@student.edu', '9876543216', 'Female'],
        ['STU008', 'Meera Jain', 'meera.jain@student.edu', '9876543217', 'Female'],
        ['STU009', 'Divya Shah', 'divya.shah@student.edu', '9876543218', 'Female'],
        ['STU010', 'Sunita Mehta', 'sunita.mehta@student.edu', '9876543219', 'Female'],
        
        // Male students (15)
        ['STU011', 'Aarav Reddy', 'aarav.reddy@student.edu', '9876543220', 'Male'],
        ['STU012', 'Arjun Nair', 'arjun.nair@student.edu', '9876543221', 'Male'],
        ['STU013', 'Vikram Rao', 'vikram.rao@student.edu', '9876543222', 'Male'],
        ['STU014', 'Rajesh Iyer', 'rajesh.iyer@student.edu', '9876543223', 'Male'],
        ['STU015', 'Suresh Krishnan', 'suresh.krishnan@student.edu', '9876543224', 'Male'],
        ['STU016', 'Amit Bansal', 'amit.bansal@student.edu', '9876543225', 'Male'],
        ['STU017', 'Rohit Chopra', 'rohit.chopra@student.edu', '9876543226', 'Male'],
        ['STU018', 'Karan Malhotra', 'karan.malhotra@student.edu', '9876543227', 'Male'],
        ['STU019', 'Ankit Saxena', 'ankit.saxena@student.edu', '9876543228', 'Male'],
        ['STU020', 'Deepak Pandey', 'deepak.pandey@student.edu', '9876543229', 'Male'],
        ['STU021', 'Ravi Kumar', 'ravi.kumar@student.edu', '9876543230', 'Male'],
        ['STU022', 'Anil Sharma', 'anil.sharma@student.edu', '9876543231', 'Male'],
        ['STU023', 'Nitin Patel', 'nitin.patel@student.edu', '9876543232', 'Male'],
        ['STU024', 'Rahul Singh', 'rahul.singh@student.edu', '9876543233', 'Male'],
        ['STU025', 'Manish Gupta', 'manish.gupta@student.edu', '9876543234', 'Male']
    ];
    
    echo "👥 ADDING 25 STUDENTS:\n";
    echo "=====================\n";
    
    $studentCount = 0;
    $femaleCount = 0;
    
    foreach ($students_data as $student) {
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
            
            // Insert with minimal required fields
            $stmt = $pdo->prepare("
                INSERT INTO students (student_id, full_name, email, phone, course_id, batch_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $student[0], $student[1], $student[2], $student[3], 
                $course['course_id'], $batch['batch_id']
            ]);
            
            $studentCount++;
            if ($student[4] === 'Female') $femaleCount++;
            
            $genderIcon = $student[4] === 'Female' ? '👩' : '👨';
            echo "✅ $genderIcon Student $studentCount: {$student[1]} ({$student[0]}) - {$course['course_name']}\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to add student {$student[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Students Added: $studentCount total ($femaleCount female, " . ($studentCount - $femaleCount) . " male)\n\n";
    
    // Faculty data
    $faculty_data = [
        // Female faculty (5)
        ['FAC001', 'Dr. Priya Sharma', 'priya.sharma@college.edu', '9876540001', 'Computer Science', 'Female'],
        ['FAC002', 'Ms. Kavya Patel', 'kavya.patel@college.edu', '9876540002', 'Information Technology', 'Female'],
        ['FAC003', 'Mrs. Shreya Singh', 'shreya.singh@college.edu', '9876540003', 'Mathematics', 'Female'],
        ['FAC004', 'Dr. Neha Kumar', 'neha.kumar@college.edu', '9876540004', 'Physics', 'Female'],
        ['FAC005', 'Ms. Pooja Gupta', 'pooja.gupta@college.edu', '9876540005', 'Chemistry', 'Female'],
        
        // Male faculty (10)
        ['FAC006', 'Dr. Aarav Reddy', 'aarav.reddy@college.edu', '9876540006', 'Computer Science', 'Male'],
        ['FAC007', 'Prof. Arjun Nair', 'arjun.nair@college.edu', '9876540007', 'Electrical Engineering', 'Male'],
        ['FAC008', 'Dr. Vikram Rao', 'vikram.rao@college.edu', '9876540008', 'Mechanical Engineering', 'Male'],
        ['FAC009', 'Mr. Rajesh Iyer', 'rajesh.iyer@college.edu', '9876540009', 'Civil Engineering', 'Male'],
        ['FAC010', 'Prof. Suresh Krishnan', 'suresh.krishnan@college.edu', '9876540010', 'Electronics', 'Male'],
        ['FAC011', 'Dr. Amit Bansal', 'amit.bansal@college.edu', '9876540011', 'Mathematics', 'Male'],
        ['FAC012', 'Mr. Rohit Chopra', 'rohit.chopra@college.edu', '9876540012', 'Physics', 'Male'],
        ['FAC013', 'Prof. Karan Malhotra', 'karan.malhotra@college.edu', '9876540013', 'Chemistry', 'Male'],
        ['FAC014', 'Dr. Ankit Saxena', 'ankit.saxena@college.edu', '9876540014', 'English', 'Male'],
        ['FAC015', 'Mr. Deepak Pandey', 'deepak.pandey@college.edu', '9876540015', 'Management', 'Male']
    ];
    
    echo "👨‍🏫 ADDING 15 FACULTY:\n";
    echo "======================\n";
    
    $facultyCount = 0;
    $femaleFacultyCount = 0;
    
    foreach ($faculty_data as $faculty) {
        try {
            // Check if faculty already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM faculty WHERE faculty_id = ?");
            $checkStmt->execute([$faculty[0]]);
            if ($checkStmt->fetchColumn() > 0) {
                echo "ℹ️  Faculty {$faculty[0]} already exists, skipping\n";
                continue;
            }
            
            // Insert with minimal required fields
            $stmt = $pdo->prepare("
                INSERT INTO faculty (faculty_id, full_name, email, phone, department) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $faculty[0], $faculty[1], $faculty[2], $faculty[3], $faculty[4]
            ]);
            
            $facultyCount++;
            if ($faculty[5] === 'Female') $femaleFacultyCount++;
            
            $genderIcon = $faculty[5] === 'Female' ? '👩‍🏫' : '👨‍🏫';
            echo "✅ $genderIcon Faculty $facultyCount: {$faculty[1]} ({$faculty[0]}) - {$faculty[4]}\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to add faculty {$faculty[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Faculty Added: $facultyCount total ($femaleFacultyCount female, " . ($facultyCount - $femaleFacultyCount) . " male)\n\n";
    
    echo "🎉 DATA ADDITION COMPLETE!\n";
    echo "=========================\n";
    echo "✅ Added $studentCount students\n";
    echo "✅ Added $facultyCount faculty members\n";
    
    $totalFemale = $femaleCount + $femaleFacultyCount;
    $totalPeople = $studentCount + $facultyCount;
    if ($totalPeople > 0) {
        echo "✅ Female representation: " . round($totalFemale / $totalPeople * 100, 1) . "% ($totalFemale out of $totalPeople)\n";
    }
    
    echo "</div>";
    
    // Success summary
    echo "<div class='alert alert-success mt-3'>";
    echo "<h5>🎉 Sample Data Added Successfully!</h5>";
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<h6>👥 Students Added: $studentCount</h6>";
    echo "<ul class='mb-0'>";
    echo "<li>👩 Female: $femaleCount (Priya, Kavya, Shreya, etc.)</li>";
    echo "<li>👨 Male: " . ($studentCount - $femaleCount) . " (Aarav, Arjun, Vikram, etc.)</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<h6>👨‍🏫 Faculty Added: $facultyCount</h6>";
    echo "<ul class='mb-0'>";
    echo "<li>👩‍🏫 Female: $femaleFacultyCount (Dr. Priya, Ms. Kavya, etc.)</li>";
    echo "<li>👨‍🏫 Male: " . ($facultyCount - $femaleFacultyCount) . " (Dr. Aarav, Prof. Arjun, etc.)</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "</div>";
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ Error During Setup</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please ensure your database connection is working and tables exist.</p>";
    echo "</div>";
}

echo "<div class='mt-3'>";
echo "<a href='students.php' class='btn btn-primary me-2'>👥 View Students</a>";
echo "<a href='faculty.php' class='btn btn-primary me-2'>👨‍🏫 View Faculty</a>";
echo "<a href='index.php' class='btn btn-outline-primary'>🏠 Dashboard</a>";
echo "</div>";

echo "</div></body></html>";
?>