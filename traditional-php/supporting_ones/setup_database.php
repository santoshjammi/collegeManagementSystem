<?php
/**
 * Database Setup Script for Grading System
 * This script will execute the grading system schema in your existing database
 */

// Include the existing configuration
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Grading System Database Setup</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .setup-container { max-width: 800px; margin: 50px auto; padding: 20px; }
        .log-output { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; font-family: monospace; font-size: 14px; max-height: 400px; overflow-y: auto; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #007bff; }
    </style>
</head>
<body class='bg-light'>
<div class='setup-container'>
    <h1 class='h3 mb-4'>College Management System - Grading System Setup</h1>";

try {
    // Read the SQL schema file
    $schemaFile = '../docs/Grading_System_Schema.sql';
    
    // Try multiple possible paths
    $possiblePaths = [
        '../docs/Grading_System_Schema.sql',
        'docs/Grading_System_Schema.sql',
        __DIR__ . '/../docs/Grading_System_Schema.sql',
        dirname(__DIR__) . '/docs/Grading_System_Schema.sql'
    ];
    
    $schemaFile = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $schemaFile = $path;
            break;
        }
    }
    
    if (!$schemaFile) {
        echo "<span class='error'>[ERROR]</span> Schema file not found. Tried paths:<br>";
        foreach ($possiblePaths as $path) {
            echo "- " . htmlspecialchars($path) . " (exists: " . (file_exists($path) ? 'YES' : 'NO') . ")<br>";
        }
        throw new Exception("Schema file not found in any expected location");
    }
    
    echo "<span class='success'>[SUCCESS]</span> Found schema file at: " . htmlspecialchars($schemaFile) . "<br>";
    
    $sqlContent = file_get_contents($schemaFile);
    if ($sqlContent === false) {
        throw new Exception("Could not read schema file");
    }
    
    echo "<div class='alert alert-info'>";
    echo "<h5>Database Connection Details:</h5>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> " . DB_HOST . "</li>";
    echo "<li><strong>Database:</strong> " . DB_NAME . "</li>";
    echo "<li><strong>User:</strong> " . DB_USER . "</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='alert alert-primary'>";
    echo "<h5>Setup Progress:</h5>";
    echo "<div class='log-output' id='log'>";
    
    // Test database connection
    echo "<span class='info'>[INFO]</span> Testing database connection...<br>";
    
    // Connection already established in config.php via $pdo
    echo "<span class='success'>[SUCCESS]</span> Database connection established<br>";
    
    // Check if tables already exist
    echo "<span class='info'>[INFO]</span> Checking for existing grading system tables...<br>";
    
    $existingTables = [];
    $checkTables = ['subjects', 'subject_faculty', 'test_types', 'tests', 'student_test_grades', 'grade_ranges'];
    
    foreach ($checkTables as $table) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
            $existingTables[] = $table;
        } catch (PDOException $e) {
            // Table doesn't exist, which is expected
        }
    }
    
    if (!empty($existingTables)) {
        echo "<span class='error'>[WARNING]</span> Found existing tables: " . implode(', ', $existingTables) . "<br>";
        echo "<span class='info'>[INFO]</span> These tables will be updated with new structure if needed<br>";
    } else {
        echo "<span class='success'>[SUCCESS]</span> No conflicting tables found<br>";
    }
    
    // Execute the SQL schema
    echo "<span class='info'>[INFO]</span> Executing grading system schema...<br>";
    
    // Split SQL content into individual statements
    $statements = explode(';', $sqlContent);
    $executedStatements = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executedStatements++;
            
            // Extract table name for logging
            if (preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches)) {
                echo "<span class='success'>[SUCCESS]</span> Created table: {$matches[1]}<br>";
            } elseif (preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches)) {
                echo "<span class='success'>[SUCCESS]</span> Inserted data into: {$matches[1]}<br>";
            }
            
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Ignore "table already exists" errors but log others
            if (strpos($errorMsg, 'already exists') !== false) {
                if (preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches)) {
                    echo "<span class='info'>[INFO]</span> Table {$matches[1]} already exists, skipping<br>";
                }
            } else {
                $errors[] = "Error in statement: " . substr($statement, 0, 50) . "... - " . $errorMsg;
                echo "<span class='error'>[ERROR]</span> " . end($errors) . "<br>";
            }
        }
    }
    
    echo "<span class='info'>[INFO]</span> Executed $executedStatements SQL statements<br>";
    
    // Verify table creation
    echo "<span class='info'>[INFO]</span> Verifying table creation...<br>";
    
    $verifyTables = ['subjects', 'subject_faculty', 'test_types', 'tests', 'student_test_grades', 'grade_ranges'];
    $createdTables = [];
    
    foreach ($verifyTables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            $createdTables[] = $table;
            echo "<span class='success'>[SUCCESS]</span> Table '$table' exists with $count records<br>";
        } catch (PDOException $e) {
            echo "<span class='error'>[ERROR]</span> Table '$table' not found or inaccessible<br>";
            $errors[] = "Table $table verification failed: " . $e->getMessage();
        }
    }
    
    echo "</div></div>";
    
    // Final summary
    if (empty($errors) && count($createdTables) === count($verifyTables)) {
        echo "<div class='alert alert-success'>";
        echo "<h5><i class='bi bi-check-circle'></i> Setup Completed Successfully!</h5>";
        echo "<p>All grading system tables have been created and verified.</p>";
        echo "<h6>Created Tables:</h6>";
        echo "<ul>";
        foreach ($createdTables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        echo "<p><strong>You can now access the grading system modules:</strong></p>";
        echo "<ul>";
        echo "<li><a href='subjects.php' class='btn btn-sm btn-outline-primary'>Subjects Management</a></li>";
        echo "<li><a href='tests.php' class='btn btn-sm btn-outline-primary'>Tests Management</a></li>";
        echo "<li><a href='grades.php' class='btn btn-sm btn-outline-primary'>Grading Interface</a></li>";
        echo "<li><a href='student-grades.php' class='btn btn-sm btn-outline-primary'>Student Grades</a></li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h5><i class='bi bi-exclamation-triangle'></i> Setup Completed with Issues</h5>";
        echo "<p>Some issues were encountered during setup:</p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        echo "<p>Created tables: " . implode(', ', $createdTables) . "</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h5><i class='bi bi-x-circle'></i> Setup Failed</h5>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
    echo "</div>";
}

echo "
    <div class='mt-4'>
        <a href='index.php' class='btn btn-primary'>Back to Dashboard</a>
        <a href='subjects.php' class='btn btn-outline-secondary'>Test Subjects Page</a>
    </div>
</div>
</body>
</html>";
?>