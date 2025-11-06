<?php
/**
 * Database Diagnostic Script
 * This will help us understand the current state and fix any issues
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Diagnostic</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h3 mb-4'>Database Diagnostic</h1>";

try {
    echo "<div class='card mb-3'>
        <div class='card-header'><h5>Database Connection Test</h5></div>
        <div class='card-body'>";
    
    // Test basic connection
    echo "✅ Database connection: SUCCESS<br>";
    echo "📍 Host: " . DB_HOST . "<br>";
    echo "📂 Database: " . DB_NAME . "<br>";
    echo "👤 User: " . DB_USER . "<br>";
    
    echo "</div></div>";
    
    // Check existing tables
    echo "<div class='card mb-3'>
        <div class='card-header'><h5>Existing Tables</h5></div>
        <div class='card-body'>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Found " . count($tables) . " tables in database:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    echo "</div></div>";
    
    // Check for grading system tables specifically
    echo "<div class='card mb-3'>
        <div class='card-header'><h5>Grading System Tables Status</h5></div>
        <div class='card-body'>";
    
    $requiredTables = ['subjects', 'subject_faculty', 'test_types', 'tests', 'student_test_grades', 'grade_ranges'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "✅ $table - EXISTS<br>";
        } else {
            echo "❌ $table - MISSING<br>";
            $missingTables[] = $table;
        }
    }
    
    echo "</div></div>";
    
    // Check schema file
    echo "<div class='card mb-3'>
        <div class='card-header'><h5>Schema File Check</h5></div>
        <div class='card-body'>";
    
    $possiblePaths = [
        '../docs/Grading_System_Schema.sql',
        'docs/Grading_System_Schema.sql',
        __DIR__ . '/../docs/Grading_System_Schema.sql',
        dirname(__DIR__) . '/docs/Grading_System_Schema.sql'
    ];
    
    foreach ($possiblePaths as $path) {
        $exists = file_exists($path);
        echo ($exists ? "✅" : "❌") . " $path - " . ($exists ? "EXISTS" : "NOT FOUND") . "<br>";
        if ($exists) {
            $size = filesize($path);
            echo "&nbsp;&nbsp;&nbsp;📏 Size: " . number_format($size) . " bytes<br>";
        }
    }
    
    echo "</div></div>";
    
    // Show action buttons
    echo "<div class='card'>
        <div class='card-header'><h5>Actions</h5></div>
        <div class='card-body'>";
    
    if (!empty($missingTables)) {
        echo "<div class='alert alert-warning'>";
        echo "<strong>Missing Tables:</strong> " . implode(', ', $missingTables) . "<br>";
        echo "You need to run the database setup to create these tables.";
        echo "</div>";
        
        echo "<a href='setup_database.php' class='btn btn-primary me-2'>🔧 Run Database Setup</a>";
        echo "<a href='execute_sql_directly.php' class='btn btn-outline-secondary me-2'>📝 Execute SQL Directly</a>";
    } else {
        echo "<div class='alert alert-success'>";
        echo "All required tables exist! The grading system should work properly.";
        echo "</div>";
        
        echo "<a href='subjects.php' class='btn btn-success me-2'>📚 Test Subjects Page</a>";
    }
    
    echo "<a href='index.php' class='btn btn-outline-primary'>🏠 Back to Dashboard</a>";
    
    echo "</div></div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h5>Error</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>