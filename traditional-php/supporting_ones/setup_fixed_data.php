<?php
/**
 * Fixed Sample Data Setup
 * This version uses only the columns that exist in your database
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fixed Sample Data Setup</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>.log { font-family: monospace; background: #f8f9fa; padding: 15px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto; }</style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h3 mb-4'>Fixed Sample Data Setup</h1>";

try {
    echo "<div class='log'>";
    
    // First, let's check what columns exist in courses and batches tables
    $stmt = $pdo->query("DESCRIBE courses");
    $courses_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->query("DESCRIBE batches");
    $batches_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 CHECKING TABLE STRUCTURES:\n";
    echo "============================\n";
    echo "Courses columns: " . implode(', ', $courses_columns) . "\n";
    echo "Batches columns: " . implode(', ', $batches_columns) . "\n\n";
    
    // 1. Add courses using only existing columns
    echo "📚 SETTING UP COURSES:\n";
    echo "=====================\n";
    
    $courses_data = [
        ['BTech_CS', 'Bachelor of Technology - Computer Science'],
        ['BTech_IT', 'Bachelor of Technology - Information Technology'], 
        ['BTech_EE', 'Bachelor of Technology - Electrical Engineering'],
        ['BTech_ME', 'Bachelor of Technology - Mechanical Engineering'],
        ['BCA', 'Bachelor of Computer Applications']
    ];
    
    foreach ($courses_data as $course) {
        try {
            // Use only the basic columns that should exist
            if (in_array('course_type', $courses_columns)) {
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO courses (course_code, course_name, course_type) 
                    VALUES (?, ?, 'Undergraduate')
                ");
            } else {
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO courses (course_code, course_name) 
                    VALUES (?, ?)
                ");
            }
            $stmt->execute([$course[0], $course[1]]);
            
            if ($stmt->rowCount() > 0) {
                echo "✅ Added course: {$course[1]}\n";
            } else {
                echo "ℹ️  Course exists: {$course[1]}\n";
            }
        } catch (Exception $e) {
            echo "❌ Error adding course {$course[1]}: " . $e->getMessage() . "\n";
        }
    }
    
    // 2. Add batches using only existing columns
    echo "\n📅 SETTING UP BATCHES:\n";
    echo "=====================\n";
    
    // Get course IDs
    $stmt = $pdo->query("SELECT course_id, course_code FROM courses");
    $courses = $stmt->fetchAll();
    
    $batch_years = [2021, 2022, 2023, 2024];
    
    foreach ($courses as $course) {
        foreach ($batch_years as $year) {
            $batch_name = $course['course_code'] . '_' . $year;
            
            try {
                // Use only basic columns that should exist
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO batches (batch_name, course_id, batch_year) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$batch_name, $course['course_id'], $year]);
                
                if ($stmt->rowCount() > 0) {
                    echo "✅ Added batch: $batch_name\n";
                } else {
                    echo "ℹ️  Batch exists: $batch_name\n";
                }
            } catch (Exception $e) {
                echo "❌ Error adding batch $batch_name: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Prerequisites setup complete!\n\n";
    
    // Now add the students and faculty with realistic names
    echo "👥 ADDING 25 STUDENTS:\n";
    echo "=====================\n";
    
    $maleFirstNames = [
        'Aarav', 'Arjun', 'Vikram', 'Rajesh', 'Suresh', 'Amit', 'Rohit', 'Karan', 'Ankit', 'Deepak',
        'Ravi', 'Anil', 'Nitin', 'Rahul', 'Manish', 'Sanjay', 'Ajay', 'Vijay', 'Ashish', 'Gopal'
    ];

    $femaleFirstNames = [
        'Priya', 'Anita', 'Kavya', 'Shreya', 'Neha', 'Pooja', 'Riya', 'Sonia', 'Meera', 'Divya',
        'Sunita', 'Geeta', 'Radha', 'Smita', 'Rekha', 'Vandana', 'Jyoti', 'Mamta'
    ];

    $lastNames = [
        'Sharma', 'Patel', 'Singh', 'Kumar', 'Gupta', 'Agarwal', 'Verma', 'Jain', 'Shah', 'Mehta',
        'Reddy', 'Nair', 'Rao', 'Iyer', 'Krishnan', 'Bansal', 'Chopra', 'Malhotra', 'Saxena', 'Pandey'
    ];

    $cities = [
        'Mumbai', 'Delhi', 'Bangalore', 'Chennai', 'Kolkata', 'Hyderabad', 'Pune', 'Ahmedabad', 
        'Jaipur', 'Lucknow', 'Kanpur', 'Nagpur', 'Indore', 'Bhopal', 'Visakhapatnam'
    ];

    // Get fresh course and batch data
    $stmt = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_id");
    $courses = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT batch_id, batch_name FROM batches ORDER BY batch_id");
    $batches = $stmt->fetchAll();

    if (empty($courses) || empty($batches)) {
        echo "❌ ERROR: No courses or batches available. Cannot proceed with student creation.\n";
    } else {
        $studentCount = 0;
        $femaleCount = 0;
        
        for ($i = 1; $i <= 25; $i++) {
            // Ensure good female representation (about 40%)
            $isFemale = ($i % 5 == 0 || $i % 7 == 0 || $i % 11 == 0) && $femaleCount < 10;
            if ($isFemale) $femaleCount++;
            
            $firstName = $isFemale ? 
                $femaleFirstNames[array_rand($femaleFirstNames)] : 
                $maleFirstNames[array_rand($maleFirstNames)];
            
            $lastName = $lastNames[array_rand($lastNames)];
            $fullName = $firstName . ' ' . $lastName;
            
            // Generate unique student ID
            $studentId = 'STU' . str_pad($i, 4, '0', STR_PAD_LEFT);
            
            $course = $courses[array_rand($courses)];
            $batch = $batches[array_rand($batches)];
            
            $email = strtolower(str_replace(' ', '.', $fullName)) . '@student.college.edu';
            $city = $cities[array_rand($cities)];
            $phone = '9' . str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
            
            $birthYear = date('Y') - rand(18, 22);
            $birthMonth = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
            $birthDay = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
            $dateOfBirth = "$birthYear-$birthMonth-$birthDay";
            
            try {
                // Check if student ID already exists
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
                $checkStmt->execute([$studentId]);
                if ($checkStmt->fetchColumn() > 0) {
                    echo "ℹ️  Student ID $studentId already exists, skipping\n";
                    continue;
                }
                
                // Check what columns exist in students table
                $stmt = $pdo->query("DESCRIBE students");
                $students_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Build INSERT query based on available columns
                $columns = ['student_id', 'full_name', 'email', 'phone', 'course_id', 'batch_id'];
                $values = [$studentId, $fullName, $email, $phone, $course['course_id'], $batch['batch_id']];
                $placeholders = ['?', '?', '?', '?', '?', '?'];
                
                // Add optional columns if they exist
                if (in_array('date_of_birth', $students_columns)) {
                    $columns[] = 'date_of_birth';
                    $values[] = $dateOfBirth;
                    $placeholders[] = '?';
                }
                if (in_array('city', $students_columns)) {
                    $columns[] = 'city';
                    $values[] = $city;
                    $placeholders[] = '?';
                }
                if (in_array('state', $students_columns)) {
                    $columns[] = 'state';
                    $values[] = 'Maharashtra';
                    $placeholders[] = '?';
                }
                if (in_array('gender', $students_columns)) {
                    $columns[] = 'gender';
                    $values[] = $isFemale ? 'Female' : 'Male';
                    $placeholders[] = '?';
                }
                if (in_array('academic_status', $students_columns)) {
                    $columns[] = 'academic_status';
                    $values[] = 'Active';
                    $placeholders[] = '?';
                }
                if (in_array('admission_date', $students_columns)) {
                    $columns[] = 'admission_date';
                    $values[] = date('Y-m-d', strtotime('-' . rand(30, 365) . ' days'));
                    $placeholders[] = '?';
                }
                
                $sql = "INSERT INTO students (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
                
                $studentCount++;
                $genderIcon = $isFemale ? '👩' : '👨';
                echo "✅ $genderIcon Student $studentCount: $fullName ($studentId) - {$course['course_name']}\n";
                
            } catch (Exception $e) {
                echo "❌ Failed to add student $fullName: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n📊 Students Added: $studentCount total ($femaleCount female, " . ($studentCount - $femaleCount) . " male)\n\n";
    }

    // Add 15 Faculty
    echo "👨‍🏫 ADDING 15 FACULTY:\n";
    echo "======================\n";
    
    $departments = [
        'Computer Science', 'Information Technology', 'Electrical Engineering', 'Mechanical Engineering',
        'Civil Engineering', 'Electronics', 'Mathematics', 'Physics', 'Chemistry', 'English'
    ];
    
    $facultyCount = 0;
    $femaleFacultyCount = 0;
    
    // Check what columns exist in faculty table
    $stmt = $pdo->query("DESCRIBE faculty");
    $faculty_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    for ($i = 1; $i <= 15; $i++) {
        // 30% female faculty
        $isFemale = ($i % 4 == 0 || $i % 7 == 0) && $femaleFacultyCount < 5;
        if ($isFemale) $femaleFacultyCount++;
        
        $firstName = $isFemale ? 
            $femaleFirstNames[array_rand($femaleFirstNames)] : 
            $maleFirstNames[array_rand($maleFirstNames)];
        
        $lastName = $lastNames[array_rand($lastNames)];
        
        // Add appropriate title
        if ($isFemale) {
            $title = ($i <= 5) ? 'Dr.' : (rand(0, 1) ? 'Ms.' : 'Mrs.');
        } else {
            $title = ($i <= 7) ? 'Dr.' : (rand(0, 1) ? 'Prof.' : 'Mr.');
        }
        
        $fullName = $title . ' ' . $firstName . ' ' . $lastName;
        
        $facultyId = 'FAC' . str_pad($i, 3, '0', STR_PAD_LEFT);
        $emailName = strtolower($firstName . '.' . $lastName);
        $email = $emailName . '@college.edu';
        $department = $departments[array_rand($departments)];
        $experience = rand(2, 25);
        
        $baseSalary = 35000;
        $experienceBonus = $experience * 2000;
        $titleBonus = (strpos($title, 'Dr.') !== false) ? 15000 : 
                     ((strpos($title, 'Prof.') !== false) ? 10000 : 0);
        $salary = $baseSalary + $experienceBonus + $titleBonus + rand(-5000, 10000);
        
        $phone = '9' . str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        
        $qualification = (strpos($title, 'Dr.') !== false) ? 'Ph.D' : 
                        (['M.Tech', 'M.Sc', 'M.E', 'MBA'][array_rand(['M.Tech', 'M.Sc', 'M.E', 'MBA'])]);
        
        try {
            // Check if faculty ID already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM faculty WHERE faculty_id = ?");
            $checkStmt->execute([$facultyId]);
            if ($checkStmt->fetchColumn() > 0) {
                echo "ℹ️  Faculty ID $facultyId already exists, skipping\n";
                continue;
            }
            
            // Build INSERT query based on available columns
            $columns = ['faculty_id', 'full_name', 'email', 'phone'];
            $values = [$facultyId, $fullName, $email, $phone];
            $placeholders = ['?', '?', '?', '?'];
            
            // Add optional columns if they exist
            if (in_array('department', $faculty_columns)) {
                $columns[] = 'department';
                $values[] = $department;
                $placeholders[] = '?';
            }
            if (in_array('qualification', $faculty_columns)) {
                $columns[] = 'qualification';
                $values[] = $qualification;
                $placeholders[] = '?';
            }
            if (in_array('experience_years', $faculty_columns)) {
                $columns[] = 'experience_years';
                $values[] = $experience;
                $placeholders[] = '?';
            }
            if (in_array('salary', $faculty_columns)) {
                $columns[] = 'salary';
                $values[] = $salary;
                $placeholders[] = '?';
            }
            if (in_array('gender', $faculty_columns)) {
                $columns[] = 'gender';
                $values[] = $isFemale ? 'Female' : 'Male';
                $placeholders[] = '?';
            }
            if (in_array('employment_status', $faculty_columns)) {
                $columns[] = 'employment_status';
                $values[] = 'Active';
                $placeholders[] = '?';
            }
            if (in_array('joining_date', $faculty_columns)) {
                $columns[] = 'joining_date';
                $values[] = date('Y-m-d', strtotime('-' . rand(30, $experience * 365) . ' days'));
                $placeholders[] = '?';
            }
            
            $sql = "INSERT INTO faculty (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            $facultyCount++;
            $genderIcon = $isFemale ? '👩‍🏫' : '👨‍🏫';
            echo "✅ $genderIcon Faculty $facultyCount: $fullName ($facultyId) - $department\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to add faculty $fullName: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Faculty Added: $facultyCount total ($femaleFacultyCount female, " . ($facultyCount - $femaleFacultyCount) . " male)\n\n";

    echo "🎉 SETUP COMPLETE!\n";
    echo "=================\n";
    echo "✅ Added $studentCount students\n";
    echo "✅ Added $facultyCount faculty members\n";
    
    $totalFemale = $femaleCount + $femaleFacultyCount;
    $totalPeople = $studentCount + $facultyCount;
    if ($totalPeople > 0) {
        echo "✅ Female representation: " . round($totalFemale / $totalPeople * 100, 1) . "% ($totalFemale out of $totalPeople)\n";
    }

    echo "</div>";
    
    echo "<div class='alert alert-success mt-3'>";
    echo "<h5>🎉 Sample Data Setup Complete!</h5>";
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<h6>👥 Students: $studentCount</h6>";
    echo "<ul>";
    echo "<li>👩 Female: $femaleCount</li>";
    echo "<li>👨 Male: " . ($studentCount - $femaleCount) . "</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<h6>👨‍🏫 Faculty: $facultyCount</h6>";
    echo "<ul>";
    echo "<li>👩‍🏫 Female: $femaleFacultyCount</li>";
    echo "<li>👨‍🏫 Male: " . ($facultyCount - $femaleFacultyCount) . "</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
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
echo "<a href='inspect_schema.php' class='btn btn-outline-info me-2'>🔍 Inspect Schema</a>";
echo "<a href='index.php' class='btn btn-outline-primary'>🏠 Dashboard</a>";
echo "</div>";

echo "</div></body></html>";
?>