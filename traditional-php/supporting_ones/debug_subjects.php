<?php
/**
 * Debug Subjects Query
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Subjects</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h2 mb-4'>🔍 Debug Subjects Query</h1>
    <div class='card'>
        <div class='card-body'>";

try {
    // Test basic subjects count
    $count = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    echo "<h5>Basic Count: $count subjects</h5>";

    // Test the exact query from subjects.php
    $subjects_query = "
        SELECT s.*, c.course_name, c.course_code
        FROM subjects s
        LEFT JOIN courses c ON s.course_id = c.course_id
        WHERE 1=1
    ";

    $stmt = $pdo->prepare($subjects_query);
    $stmt->execute();
    $subjects = $stmt->fetchAll();

    echo "<h5>Query Result: " . count($subjects) . " subjects</h5>";

    if (count($subjects) > 0) {
        echo "<h6>Sample subjects:</h6><ul>";
        foreach (array_slice($subjects, 0, 5) as $subject) {
            echo "<li>{$subject['subject_code']}: {$subject['subject_name']} (Course: {$subject['course_name']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='text-danger'>No subjects found in query!</p>";
    }

    // Check courses
    $courses_count = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    echo "<h6>Courses available: $courses_count</h6>";

    // Check faculty
    $faculty_count = $pdo->query("SELECT COUNT(*) FROM faculty WHERE is_active = 1")->fetchColumn();
    echo "<h6>Active faculty: $faculty_count</h6>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}

echo "        </div>
    </div>
</div></body></html>";
?>