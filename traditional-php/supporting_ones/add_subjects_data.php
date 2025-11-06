<?php
/**
 * Add Subjects Data - Direct insertion from nuclear_reset.php
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Add Subjects Data</title>
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
    </style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h2 mb-4'>📚 Add Subjects Data</h1>";

try {
    echo "<div class='log'>";

    echo "📚 ADDING SUBJECTS FROM NUCLEAR_RESET.PHP\n";
    echo "=========================================\n\n";

    // Clear existing subjects first
    $pdo->exec("DELETE FROM subjects");
    echo "🧹 Cleared existing subjects\n\n";

    // Subjects data from nuclear_reset.php
    $subjects_data = [
        ['SUB001', 'CS101', 'Programming Fundamentals', 'Introduction to programming concepts using C/C++', 4, 1, 'Theory'],
        ['SUB002', 'CS102', 'Data Structures', 'Arrays, linked lists, trees, graphs, algorithms', 4, 2, 'Theory'],
        ['SUB003', 'CS201', 'Database Systems', 'Relational databases, SQL, normalization, transactions', 4, 3, 'Theory'],
        ['SUB004', 'IT101', 'Web Development', 'HTML, CSS, JavaScript, PHP, frameworks', 4, 3, 'Practical'],
        ['SUB005', 'CS301', 'Artificial Intelligence', 'Machine learning, neural networks, expert systems', 4, 5, 'Theory'],
        ['SUB006', 'EE101', 'Circuit Analysis', 'DC and AC circuits, network theorems, analysis', 4, 1, 'Theory'],
        ['SUB007', 'EC101', 'Digital Electronics', 'Boolean algebra, logic gates, combinational circuits', 4, 2, 'Theory'],
        ['SUB008', 'ME201', 'Thermodynamics', 'Laws of thermodynamics, heat engines, cycles', 4, 3, 'Theory'],
        ['SUB009', 'MATH101', 'Calculus I', 'Differential and integral calculus, limits', 4, 1, 'Theory'],
        ['SUB010', 'PHYS101', 'Physics I', 'Mechanics, waves, thermodynamics, optics', 4, 1, 'Theory'],
        ['SUB011', 'CHEM101', 'Chemistry', 'Atomic structure, chemical bonding, reactions', 4, 1, 'Theory'],
        ['SUB012', 'MGT101', 'Management Principles', 'Planning, organizing, leading, controlling', 3, 2, 'Theory'],
        ['SUB013', 'CE201', 'Structural Engineering', 'Design and analysis of structures', 4, 4, 'Theory'],
        ['SUB014', 'MATH201', 'Linear Algebra', 'Matrices, vectors, eigenvalues, transformations', 3, 2, 'Theory'],
        ['SUB015', 'ENG101', 'Technical Communication', 'Written and oral communication skills', 3, 1, 'Theory'],
        ['SUB016', 'PHYS201', 'Electromagnetism', 'Electric and magnetic fields, Maxwell equations', 4, 3, 'Theory'],
        ['SUB017', 'BIO101', 'Molecular Biology', 'DNA, RNA, protein synthesis, genetics', 4, 2, 'Theory'],
        ['SUB018', 'CS202', 'Software Engineering', 'SDLC, design patterns, project management', 4, 4, 'Theory']
    ];

    $subjects_added = 0;
    foreach ($subjects_data as $subject) {
        try {
            // Get random course and faculty for each subject
            $course = $pdo->query("SELECT course_id FROM courses ORDER BY RAND() LIMIT 1")->fetchColumn();
            $faculty = $pdo->query("SELECT faculty_id FROM faculty WHERE is_active = 1 ORDER BY RAND() LIMIT 1")->fetchColumn();

            $stmt = $pdo->prepare("
                INSERT INTO subjects (subject_code, subject_name, description, credits, semester, subject_type, course_id, faculty_id, status, academic_year)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active', '2024-25')
            ");
            $stmt->execute([
                $subject[1], // subject_code
                $subject[2], // subject_name
                $subject[3], // description
                $subject[4], // credits
                $subject[5], // semester
                $subject[6], // subject_type
                $course,     // course_id
                $faculty     // faculty_id
            ]);

            $subjects_added++;
            echo "✅ Added: {$subject[2]} ({$subject[1]}) - Semester {$subject[5]} - {$subject[6]}\n";

        } catch (Exception $e) {
            echo "❌ Error adding {$subject[2]}: " . $e->getMessage() . "\n";
        }
    }

    echo "\n📊 Total subjects added: $subjects_added\n\n";

    // Verify the data
    $total_subjects = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $active_subjects = $pdo->query("SELECT COUNT(*) FROM subjects WHERE status = 'Active'")->fetchColumn();

    echo "📋 VERIFICATION:\n";
    echo "===============\n";
    echo "Total subjects in database: $total_subjects\n";
    echo "Active subjects: $active_subjects\n\n";

    echo "🎉 SUBJECTS DATA ADDED SUCCESSFULLY!\n";
    echo "===================================\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</div></body></html>";
?>