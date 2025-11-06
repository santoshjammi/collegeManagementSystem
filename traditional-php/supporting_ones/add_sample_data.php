<?php
/**
 * Sample Data Seeder for College Management System
 * This will add 25 students (including female students) and 15 faculty members
 */

require_once 'config.php';

// Sample data arrays
$maleFirstNames = [
    'Aarav', 'Arjun', 'Vikram', 'Rajesh', 'Suresh', 'Amit', 'Rohit', 'Karan', 'Ankit', 'Deepak',
    'Ravi', 'Anil', 'Nitin', 'Rahul', 'Manish'
];

$femaleFirstNames = [
    'Priya', 'Anita', 'Kavya', 'Shreya', 'Neha', 'Pooja', 'Riya', 'Sonia', 'Meera', 'Divya'
];

$lastNames = [
    'Sharma', 'Patel', 'Singh', 'Kumar', 'Gupta', 'Agarwal', 'Verma', 'Jain', 'Shah', 'Mehta',
    'Reddy', 'Nair', 'Rao', 'Iyer', 'Krishnan', 'Bansal', 'Chopra', 'Malhotra', 'Saxena', 'Pandey'
];

$cities = [
    'Mumbai', 'Delhi', 'Bangalore', 'Chennai', 'Kolkata', 'Hyderabad', 'Pune', 'Ahmedabad', 
    'Jaipur', 'Lucknow', 'Kanpur', 'Nagpur', 'Indore', 'Bhopal', 'Visakhapatnam'
];

$departments = [
    'Computer Science', 'Information Technology', 'Electrical Engineering', 'Mechanical Engineering',
    'Civil Engineering', 'Electronics', 'Mathematics', 'Physics', 'Chemistry', 'English'
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Sample Data Seeder</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>.log { font-family: monospace; background: #f8f9fa; padding: 15px; border: 1px solid #ddd; max-height: 500px; overflow-y: auto; }</style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h3 mb-4'>Sample Data Seeder</h1>
    <div class='alert alert-info'>
        <h5>Adding Sample Data:</h5>
        <ul>
            <li>25 Students (mix of male and female)</li>
            <li>15 Faculty members</li>
        </ul>
    </div>";

try {
    echo "<div class='card'>";
    echo "<div class='card-header'><h5>Seeding Progress</h5></div>";
    echo "<div class='card-body'>";
    echo "<div class='log'>";

    // Get existing courses and batches
    $stmt = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_id LIMIT 3");
    $courses = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT batch_id, batch_name FROM batches ORDER BY batch_id LIMIT 5");
    $batches = $stmt->fetchAll();

    if (empty($courses)) {
        echo "❌ ERROR: No courses found. Please add courses first.\n";
        throw new Exception("No courses available");
    }
    
    if (empty($batches)) {
        echo "❌ ERROR: No batches found. Please add batches first.\n";
        throw new Exception("No batches available");
    }

    echo "✅ Found " . count($courses) . " courses and " . count($batches) . " batches\n\n";

    // Add 25 Students
    echo "👥 ADDING STUDENTS:\n";
    echo "==================\n";
    
    $studentCount = 0;
    $femaleCount = 0;
    
    for ($i = 1; $i <= 25; $i++) {
        // Determine gender (roughly 40% female, 60% male)
        $isFemale = ($i % 5 == 0 || $i % 7 == 0) && $femaleCount < 10;
        if ($isFemale) $femaleCount++;
        
        $firstName = $isFemale ? 
            $femaleFirstNames[array_rand($femaleFirstNames)] : 
            $maleFirstNames[array_rand($maleFirstNames)];
        
        $lastName = $lastNames[array_rand($lastNames)];
        $fullName = $firstName . ' ' . $lastName;
        
        // Generate student ID
        $studentId = 'STU' . str_pad($i, 4, '0', STR_PAD_LEFT);
        
        // Random course and batch
        $course = $courses[array_rand($courses)];
        $batch = $batches[array_rand($batches)];
        
        // Generate email
        $email = strtolower(str_replace(' ', '.', $fullName)) . '@student.college.edu';
        
        // Random city
        $city = $cities[array_rand($cities)];
        
        // Random phone
        $phone = '9' . str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        
        // Random date of birth (18-22 years old)
        $birthYear = date('Y') - rand(18, 22);
        $birthMonth = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
        $birthDay = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $dateOfBirth = "$birthYear-$birthMonth-$birthDay";
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO students (
                    student_id, full_name, email, phone, date_of_birth, 
                    address, city, state, pincode, course_id, batch_id, 
                    academic_status, gender, admission_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?)
            ");
            
            $address = rand(1, 999) . ', ' . $lastNames[array_rand($lastNames)] . ' Street';
            $state = 'Maharashtra'; // You can randomize this too
            $pincode = str_pad(rand(400000, 499999), 6, '0', STR_PAD_LEFT);
            $gender = $isFemale ? 'Female' : 'Male';
            $admissionDate = date('Y-m-d', strtotime('-' . rand(30, 365) . ' days'));
            
            $stmt->execute([
                $studentId, $fullName, $email, $phone, $dateOfBirth,
                $address, $city, $state, $pincode, $course['course_id'], $batch['batch_id'],
                $gender, $admissionDate
            ]);
            
            $studentCount++;
            $genderIcon = $isFemale ? '👩' : '👨';
            echo "✅ $genderIcon Student $studentCount: $fullName ($studentId) - {$course['course_name']}\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to add student $fullName: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Students Summary: $studentCount total ($femaleCount female, " . ($studentCount - $femaleCount) . " male)\n\n";

    // Add 15 Faculty
    echo "👨‍🏫 ADDING FACULTY:\n";
    echo "==================\n";
    
    $facultyCount = 0;
    $femaleFacultyCount = 0;
    
    // Generate some senior faculty with Dr. titles
    $titles = ['Dr.', 'Prof.', 'Mr.', 'Ms.', 'Mrs.'];
    
    for ($i = 1; $i <= 15; $i++) {
        // 30% female faculty
        $isFemale = ($i % 3 == 0 || $i % 7 == 0) && $femaleFacultyCount < 5;
        if ($isFemale) $femaleFacultyCount++;
        
        $firstName = $isFemale ? 
            $femaleFirstNames[array_rand($femaleFirstNames)] : 
            $maleFirstNames[array_rand($maleFirstNames)];
        
        $lastName = $lastNames[array_rand($lastNames)];
        
        // Add title
        if ($isFemale) {
            $title = ($i <= 5) ? 'Dr.' : (rand(0, 1) ? 'Ms.' : 'Mrs.');
        } else {
            $title = ($i <= 7) ? 'Dr.' : (rand(0, 1) ? 'Prof.' : 'Mr.');
        }
        
        $fullName = $title . ' ' . $firstName . ' ' . $lastName;
        
        // Generate faculty ID
        $facultyId = 'FAC' . str_pad($i, 3, '0', STR_PAD_LEFT);
        
        // Generate email
        $emailName = strtolower($firstName . '.' . $lastName);
        $email = $emailName . '@college.edu';
        
        // Random department
        $department = $departments[array_rand($departments)];
        
        // Random experience (2-25 years)
        $experience = rand(2, 25);
        
        // Random salary based on experience
        $baseSalary = 35000;
        $experienceBonus = $experience * 2000;
        $titleBonus = (strpos($title, 'Dr.') !== false) ? 15000 : 
                     ((strpos($title, 'Prof.') !== false) ? 10000 : 0);
        $salary = $baseSalary + $experienceBonus + $titleBonus + rand(-5000, 10000);
        
        // Random phone
        $phone = '9' . str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        
        // Random qualification
        $qualifications = ['M.Tech', 'Ph.D', 'M.Sc', 'M.E', 'MBA'];
        $qualification = $qualifications[array_rand($qualifications)];
        if (strpos($title, 'Dr.') !== false) {
            $qualification = 'Ph.D';
        }
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO faculty (
                    faculty_id, full_name, email, phone, department, 
                    qualification, experience_years, salary, joining_date, 
                    employment_status, gender
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?)
            ");
            
            $joiningDate = date('Y-m-d', strtotime('-' . rand(30, $experience * 365) . ' days'));
            $gender = $isFemale ? 'Female' : 'Male';
            
            $stmt->execute([
                $facultyId, $fullName, $email, $phone, $department,
                $qualification, $experience, $salary, $joiningDate, $gender
            ]);
            
            $facultyCount++;
            $genderIcon = $isFemale ? '👩‍🏫' : '👨‍🏫';
            echo "✅ $genderIcon Faculty $facultyCount: $fullName ($facultyId) - $department\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to add faculty $fullName: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Faculty Summary: $facultyCount total ($femaleFacultyCount female, " . ($facultyCount - $femaleFacultyCount) . " male)\n\n";

    // Final statistics
    echo "🎉 DATA SEEDING COMPLETED!\n";
    echo "=========================\n";
    echo "✅ Added $studentCount students\n";
    echo "✅ Added $facultyCount faculty members\n";
    echo "✅ Female representation: " . round(($femaleCount + $femaleFacultyCount) / ($studentCount + $facultyCount) * 100, 1) . "%\n";

    echo "</div></div></div>";
    
    // Show success message
    echo "<div class='alert alert-success mt-3'>";
    echo "<h5>🎉 Sample Data Added Successfully!</h5>";
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<h6>Students Added: $studentCount</h6>";
    echo "<ul>";
    echo "<li>Female Students: $femaleCount</li>";
    echo "<li>Male Students: " . ($studentCount - $femaleCount) . "</li>";
    echo "<li>Distributed across " . count($courses) . " courses</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<h6>Faculty Added: $facultyCount</h6>";
    echo "<ul>";
    echo "<li>Female Faculty: $femaleFacultyCount</li>";
    echo "<li>Male Faculty: " . ($facultyCount - $femaleFacultyCount) . "</li>";
    echo "<li>Across " . count(array_unique(array_column($stmt->fetchAll(), 'department'))) . " departments</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='mt-3'>";
    echo "<a href='students.php' class='btn btn-primary me-2'>👥 View Students</a>";
    echo "<a href='faculty.php' class='btn btn-primary me-2'>👨‍🏫 View Faculty</a>";
    echo "<a href='index.php' class='btn btn-outline-primary'>🏠 Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "</div></div></div>";
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ Error During Data Seeding</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please ensure you have:</p>";
    echo "<ul>";
    echo "<li>At least one course in the courses table</li>";
    echo "<li>At least one batch in the batches table</li>";
    echo "<li>Proper database permissions</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</div></body></html>";
?>