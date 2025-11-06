<?php
/**
 * Database Schema Inspector
 * This will show us the actual column structure of your tables
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Schema Inspector</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h3 mb-4'>Database Schema Inspector</h1>";

try {
    // Check courses table structure
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'><h5>Courses Table Structure</h5></div>";
    echo "<div class='card-body'>";
    
    $stmt = $pdo->query("DESCRIBE courses");
    $courses_columns = $stmt->fetchAll();
    
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
    echo "<tbody>";
    foreach ($courses_columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div></div>";
    
    // Check batches table structure
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'><h5>Batches Table Structure</h5></div>";
    echo "<div class='card-body'>";
    
    $stmt = $pdo->query("DESCRIBE batches");
    $batches_columns = $stmt->fetchAll();
    
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
    echo "<tbody>";
    foreach ($batches_columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div></div>";
    
    // Show existing data
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<div class='card'>";
    echo "<div class='card-header'><h5>Existing Courses</h5></div>";
    echo "<div class='card-body'>";
    
    $stmt = $pdo->query("SELECT * FROM courses");
    $courses = $stmt->fetchAll();
    
    if (empty($courses)) {
        echo "<p class='text-muted'>No courses found</p>";
    } else {
        echo "<ul>";
        foreach ($courses as $course) {
            echo "<li>{$course['course_name']} ({$course['course_code']})</li>";
        }
        echo "</ul>";
    }
    echo "</div></div>";
    echo "</div>";
    
    echo "<div class='col-md-6'>";
    echo "<div class='card'>";
    echo "<div class='card-header'><h5>Existing Batches</h5></div>";
    echo "<div class='card-body'>";
    
    $stmt = $pdo->query("SELECT * FROM batches");
    $batches = $stmt->fetchAll();
    
    if (empty($batches)) {
        echo "<p class='text-muted'>No batches found</p>";
    } else {
        echo "<ul>";
        foreach ($batches as $batch) {
            echo "<li>{$batch['batch_name']} (Year: {$batch['batch_year']})</li>";
        }
        echo "</ul>";
    }
    echo "</div></div>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h5>Error</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='mt-3'>";
echo "<a href='index.php' class='btn btn-outline-primary'>🏠 Back to Dashboard</a>";
echo "</div>";

echo "</div></body></html>";
?>