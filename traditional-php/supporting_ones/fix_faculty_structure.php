<?php
/**
 * Faculty Table Structure Analyzer and Fixer
 * This will examine your exact faculty table structure and create appropriate faculty records
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Faculty Structure Analyzer & Fixer</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .log { 
            font-family: monospace; 
            background: #f8f9fa; 
            padding: 20px; 
            border: 1px solid #ddd; 
            max-height: 600px; 
            overflow-y: auto; 
            white-space: pre-wrap;
            border-radius: 5px;
        }
    </style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h3 mb-4'>🔧 Faculty Table Structure Analyzer & Fixer</h1>";

try {
    echo "<div class='log'>";
    
    echo "🔍 ANALYZING FACULTY TABLE STRUCTURE:\n";
    echo "=====================================\n\n";
    
    // Get detailed table structure
    $stmt = $pdo->query("DESCRIBE faculty");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Faculty table columns:\n";
    foreach ($columns as $column) {
        echo "  • {$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}\n";
    }
    echo "\n";
    
    // Check existing faculty
    $existing_faculty = $pdo->query("SELECT * FROM faculty LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "Existing faculty records: " . count($existing_faculty) . "\n";
    
    if (!empty($existing_faculty)) {
        echo "Sample existing faculty:\n";
        foreach ($existing_faculty as $faculty) {
            $keys = array_keys($faculty);
            $id = $faculty[$keys[0]]; // First column
            $name = isset($faculty['full_name']) ? $faculty['full_name'] : 
                   (isset($faculty['name']) ? $faculty['name'] : 'Unknown');
            echo "  • $id - $name\n";
        }
        echo "\n";
    }
    
    // Get column names for targeted insertion
    $column_names = array_column($columns, 'Field');
    echo "Available columns: " . implode(', ', $column_names) . "\n\n";
    
    // Create faculty data based on available columns
    echo "🎯 CREATING FACULTY WITH EXACT COLUMN MAPPING:\n";
    echo "=============================================\n";
    
    $faculty_to_add = [
        ['FAC001', 'Dr. Priya Sharma', 'priya.sharma@college.edu', 'Computer Science', 'Female'],
        ['FAC002', 'Prof. Rajesh Kumar', 'rajesh.kumar@college.edu', 'Computer Science', 'Male'],
        ['FAC003', 'Dr. Kavya Patel', 'kavya.patel@college.edu', 'Information Technology', 'Female'],
        ['FAC004', 'Mr. Vikram Rao', 'vikram.rao@college.edu', 'Electrical Engineering', 'Male'],
        ['FAC005', 'Ms. Shreya Singh', 'shreya.singh@college.edu', 'Electronics Engineering', 'Female'],
        ['FAC006', 'Dr. Arjun Nair', 'arjun.nair@college.edu', 'Mechanical Engineering', 'Male'],
        ['FAC007', 'Dr. Neha Kumar', 'neha.kumar@college.edu', 'Mathematics', 'Female'],
        ['FAC008', 'Prof. Suresh Krishnan', 'suresh.krishnan@college.edu', 'Physics', 'Male'],
        ['FAC009', 'Mrs. Pooja Gupta', 'pooja.gupta@college.edu', 'Chemistry', 'Female'],
        ['FAC010', 'Dr. Amit Bansal', 'amit.bansal@college.edu', 'Management', 'Male']
    ];
    
    $faculty_added = 0;
    $female_faculty = 0;
    
    foreach ($faculty_to_add as $faculty) {
        try {
            // Check if faculty exists
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM faculty WHERE faculty_id = ?");
            $check_stmt->execute([$faculty[0]]);
            if ($check_stmt->fetchColumn() > 0) {
                echo "ℹ️  Faculty {$faculty[0]} already exists, skipping\n";
                continue;
            }
            
            // Build insert query based on available columns
            $insert_columns = [];
            $insert_values = [];
            $placeholders = [];
            
            // Required/Primary columns
            if (in_array('faculty_id', $column_names)) {
                $insert_columns[] = 'faculty_id';
                $insert_values[] = $faculty[0];
                $placeholders[] = '?';
            }
            
            if (in_array('full_name', $column_names)) {
                $insert_columns[] = 'full_name';
                $insert_values[] = $faculty[1];
                $placeholders[] = '?';
            } elseif (in_array('name', $column_names)) {
                $insert_columns[] = 'name';
                $insert_values[] = $faculty[1];
                $placeholders[] = '?';
            }
            
            if (in_array('email', $column_names)) {
                $insert_columns[] = 'email';
                $insert_values[] = $faculty[2];
                $placeholders[] = '?';
            }
            
            // Optional columns
            if (in_array('department', $column_names)) {
                $insert_columns[] = 'department';
                $insert_values[] = $faculty[3];
                $placeholders[] = '?';
            }
            
            if (in_array('gender', $column_names)) {
                $insert_columns[] = 'gender';
                $insert_values[] = $faculty[4];
                $placeholders[] = '?';
            }
            
            if (in_array('employment_status', $column_names)) {
                $insert_columns[] = 'employment_status';
                $insert_values[] = 'Active';
                $placeholders[] = '?';
            } elseif (in_array('status', $column_names)) {
                $insert_columns[] = 'status';
                $insert_values[] = 'Active';
                $placeholders[] = '?';
            }
            
            if (in_array('salary', $column_names)) {
                $insert_columns[] = 'salary';
                $insert_values[] = rand(50000, 90000);
                $placeholders[] = '?';
            }
            
            if (in_array('joining_date', $column_names)) {
                $insert_columns[] = 'joining_date';
                $insert_values[] = date('Y-m-d', strtotime('-' . rand(1, 5) . ' years'));
                $placeholders[] = '?';
            } elseif (in_array('hire_date', $column_names)) {
                $insert_columns[] = 'hire_date';
                $insert_values[] = date('Y-m-d', strtotime('-' . rand(1, 5) . ' years'));
                $placeholders[] = '?';
            }
            
            if (in_array('qualification', $column_names)) {
                $qualifications = ['PhD', 'MTech', 'MSc', 'MBA'];
                $insert_columns[] = 'qualification';
                $insert_values[] = $qualifications[array_rand($qualifications)];
                $placeholders[] = '?';
            }
            
            if (in_array('designation', $column_names)) {
                $designations = ['Professor', 'Associate Professor', 'Assistant Professor'];
                $insert_columns[] = 'designation';
                $insert_values[] = $designations[array_rand($designations)];
                $placeholders[] = '?';
            }
            
            if (in_array('phone', $column_names)) {
                $insert_columns[] = 'phone';
                $insert_values[] = '98765432' . str_pad(rand(10, 99), 2, '0');
                $placeholders[] = '?';
            }
            
            if (empty($insert_columns)) {
                echo "❌ No compatible columns found for faculty insertion\n";
                break;
            }
            
            // Execute insert
            $sql = "INSERT INTO faculty (" . implode(', ', $insert_columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            echo "Trying SQL: $sql\n";
            echo "With values: " . implode(', ', $insert_values) . "\n";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($insert_values);
            
            $faculty_added++;
            if ($faculty[4] === 'Female') $female_faculty++;
            
            $icon = $faculty[4] === 'Female' ? '👩‍🏫' : '👨‍🏫';
            echo "✅ $icon {$faculty[1]} - {$faculty[3]} ({$faculty[0]})\n";
            
        } catch (Exception $e) {
            echo "❌ Error adding {$faculty[1]}: " . $e->getMessage() . "\n";
            echo "   Columns used: " . implode(', ', $insert_columns ?? []) . "\n";
        }
    }
    
    echo "\n📊 Faculty Addition Results:\n";
    echo "   • Total added: $faculty_added\n";
    echo "   • Female: $female_faculty\n";
    echo "   • Male: " . ($faculty_added - $female_faculty) . "\n\n";
    
    // Now let's try to add subjects with the added faculty
    if ($faculty_added > 0) {
        echo "📚 ADDING SUBJECTS WITH NEW FACULTY:\n";
        echo "===================================\n";
        
        // Get available faculty IDs
        $available_faculty = $pdo->query("SELECT faculty_id FROM faculty LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
        echo "Available faculty IDs: " . implode(', ', $available_faculty) . "\n\n";
        
        $subjects_to_add = [
            ['CS101', 'Programming Fundamentals', 'Introduction to programming concepts', 4],
            ['CS102', 'Data Structures', 'Arrays, linked lists, stacks, queues', 4],
            ['MATH101', 'Calculus I', 'Differential and integral calculus', 4],
            ['PHYS101', 'Physics I', 'Mechanics and thermodynamics', 4],
            ['EE101', 'Circuit Analysis', 'DC and AC circuits', 4],
            ['MGT101', 'Management Principles', 'Basic management concepts', 3]
        ];
        
        // Get subject table structure
        $subject_columns = $pdo->query("DESCRIBE subjects")->fetchAll(PDO::FETCH_COLUMN);
        echo "Subject table columns: " . implode(', ', $subject_columns) . "\n\n";
        
        $subjects_added = 0;
        foreach ($subjects_to_add as $index => $subject) {
            try {
                // Check if subject exists
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE subject_code = ?");
                $check_stmt->execute([$subject[0]]);
                if ($check_stmt->fetchColumn() > 0) {
                    echo "ℹ️  Subject {$subject[0]} already exists, skipping\n";
                    continue;
                }
                
                // Get random faculty ID from available ones
                $faculty_id = $available_faculty[array_rand($available_faculty)];
                
                // Build subject insert
                $sub_columns = [];
                $sub_values = [];
                $sub_placeholders = [];
                
                if (in_array('subject_code', $subject_columns)) {
                    $sub_columns[] = 'subject_code';
                    $sub_values[] = $subject[0];
                    $sub_placeholders[] = '?';
                }
                
                if (in_array('subject_name', $subject_columns)) {
                    $sub_columns[] = 'subject_name';
                    $sub_values[] = $subject[1];
                    $sub_placeholders[] = '?';
                } elseif (in_array('name', $subject_columns)) {
                    $sub_columns[] = 'name';
                    $sub_values[] = $subject[1];
                    $sub_placeholders[] = '?';
                }
                
                if (in_array('description', $subject_columns)) {
                    $sub_columns[] = 'description';
                    $sub_values[] = $subject[2];
                    $sub_placeholders[] = '?';
                }
                
                if (in_array('credits', $subject_columns)) {
                    $sub_columns[] = 'credits';
                    $sub_values[] = $subject[3];
                    $sub_placeholders[] = '?';
                }
                
                if (in_array('faculty_id', $subject_columns)) {
                    $sub_columns[] = 'faculty_id';
                    $sub_values[] = $faculty_id;
                    $sub_placeholders[] = '?';
                }
                
                if (in_array('semester', $subject_columns)) {
                    $sub_columns[] = 'semester';
                    $sub_values[] = rand(1, 4);
                    $sub_placeholders[] = '?';
                }
                
                if (in_array('academic_year', $subject_columns)) {
                    $sub_columns[] = 'academic_year';
                    $sub_values[] = '2024-25';
                    $sub_placeholders[] = '?';
                }
                
                if (in_array('status', $subject_columns)) {
                    $sub_columns[] = 'status';
                    $sub_values[] = 'Active';
                    $sub_placeholders[] = '?';
                }
                
                // Get random course ID
                $courses = $pdo->query("SELECT * FROM courses LIMIT 5")->fetchAll();
                if (!empty($courses)) {
                    $course = $courses[array_rand($courses)];
                    $course_keys = array_keys($course);
                    $course_id = $course[$course_keys[0]];
                    
                    if (in_array('course_id', $subject_columns)) {
                        $sub_columns[] = 'course_id';
                        $sub_values[] = $course_id;
                        $sub_placeholders[] = '?';
                    }
                }
                
                if (!empty($sub_columns)) {
                    $sub_sql = "INSERT INTO subjects (" . implode(', ', $sub_columns) . ") VALUES (" . implode(', ', $sub_placeholders) . ")";
                    $sub_stmt = $pdo->prepare($sub_sql);
                    $sub_stmt->execute($sub_values);
                    
                    $subjects_added++;
                    echo "✅ 📖 {$subject[1]} ({$subject[0]}) - assigned to $faculty_id\n";
                }
                
            } catch (Exception $e) {
                echo "❌ Error adding subject {$subject[1]}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n📊 Subjects added: $subjects_added\n\n";
    }
    
    echo "🎉 FACULTY STRUCTURE FIX COMPLETE!\n";
    echo "==================================\n";
    echo "✅ Faculty members added: $faculty_added\n";
    echo "✅ This should resolve the faculty insertion issues\n";
    echo "✅ You can now run the comprehensive demo data script again\n";
    
    echo "</div>";
    
    if ($faculty_added > 0) {
        echo "<div class='alert alert-success mt-3'>";
        echo "<h5>✅ Faculty Addition Successful!</h5>";
        echo "<p>Successfully added <strong>$faculty_added faculty members</strong> using your exact table structure.</p>";
        echo "<p>The comprehensive demo data script should now work properly.</p>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning mt-3'>";
        echo "<h5>⚠️ Faculty Addition Issues</h5>";
        echo "<p>Could not add faculty members. This might be due to:</p>";
        echo "<ul>";
        echo "<li>Required columns that we haven't identified</li>";
        echo "<li>Foreign key constraints</li>";
        echo "<li>Unique constraints on columns</li>";
        echo "</ul>";
        echo "<p>Check the detailed log above for specific error messages.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "</div>";
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ Analysis Error</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='mt-3'>";
echo "<a href='comprehensive_demo_data.php' class='btn btn-primary me-2'>🔄 Run Demo Data Again</a>";
echo "<a href='faculty.php' class='btn btn-success me-2'>👨‍🏫 Check Faculty</a>";
echo "<a href='index.php' class='btn btn-outline-primary'>🏠 Dashboard</a>";
echo "</div>";

echo "</div></body></html>";
?>