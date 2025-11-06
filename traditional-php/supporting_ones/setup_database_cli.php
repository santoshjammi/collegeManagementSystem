<?php
/**
 * CLI Database Setup Script for Grading System
 * Run this from command line: php setup_database_cli.php
 */

// Include the existing configuration
require_once 'config.php';

echo "=== College Management System - Grading System Setup ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Database: " . DB_NAME . " @ " . DB_HOST . "\n";
echo "========================================================\n\n";

try {
    // Read the SQL schema file
    $schemaFile = '../docs/Grading_System_Schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found at: $schemaFile");
    }
    
    echo "📁 Reading schema file...\n";
    $sqlContent = file_get_contents($schemaFile);
    if ($sqlContent === false) {
        throw new Exception("Could not read schema file");
    }
    
    echo "✅ Schema file loaded successfully\n\n";
    
    // Test database connection
    echo "🔌 Testing database connection...\n";
    
    // Connection already established in config.php via $pdo
    echo "✅ Database connection established\n\n";
    
    // Check if tables already exist
    echo "🔍 Checking for existing grading system tables...\n";
    
    $existingTables = [];
    $checkTables = ['subjects', 'subject_faculty', 'test_types', 'tests', 'student_test_grades', 'grade_ranges'];
    
    foreach ($checkTables as $table) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
            $existingTables[] = $table;
            echo "⚠️  Table '$table' already exists\n";
        } catch (PDOException $e) {
            echo "ℹ️  Table '$table' not found (will be created)\n";
        }
    }
    
    if (!empty($existingTables)) {
        echo "\n⚠️  WARNING: Some tables already exist and will be updated if needed\n";
    }
    
    echo "\n";
    
    // Execute the SQL schema
    echo "🚀 Executing grading system schema...\n";
    
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
                echo "✅ Created table: {$matches[1]}\n";
            } elseif (preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches)) {
                echo "✅ Inserted data into: {$matches[1]}\n";
            }
            
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Ignore "table already exists" errors but log others
            if (strpos($errorMsg, 'already exists') !== false) {
                if (preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches)) {
                    echo "ℹ️  Table {$matches[1]} already exists, skipping\n";
                }
            } else {
                $errors[] = "Error in statement: " . substr($statement, 0, 50) . "... - " . $errorMsg;
                echo "❌ ERROR: " . end($errors) . "\n";
            }
        }
    }
    
    echo "\n📊 Executed $executedStatements SQL statements\n\n";
    
    // Verify table creation
    echo "🔍 Verifying table creation...\n";
    
    $verifyTables = ['subjects', 'subject_faculty', 'test_types', 'tests', 'student_test_grades', 'grade_ranges'];
    $createdTables = [];
    
    foreach ($verifyTables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            $createdTables[] = $table;
            echo "✅ Table '$table' verified ($count records)\n";
        } catch (PDOException $e) {
            echo "❌ Table '$table' not found or inaccessible\n";
            $errors[] = "Table $table verification failed: " . $e->getMessage();
        }
    }
    
    echo "\n";
    
    // Final summary
    if (empty($errors) && count($createdTables) === count($verifyTables)) {
        echo "🎉 SETUP COMPLETED SUCCESSFULLY!\n";
        echo "========================================\n";
        echo "All grading system tables have been created and verified.\n\n";
        echo "📋 Created Tables:\n";
        foreach ($createdTables as $table) {
            echo "   - $table\n";
        }
        echo "\n🚀 You can now access the grading system modules:\n";
        echo "   - Subjects Management: subjects.php\n";
        echo "   - Tests Management: tests.php\n";
        echo "   - Grading Interface: grades.php\n";
        echo "   - Student Grades: student-grades.php\n";
        echo "\n";
    } else {
        echo "⚠️  SETUP COMPLETED WITH ISSUES\n";
        echo "===================================\n";
        if (!empty($errors)) {
            echo "Errors encountered:\n";
            foreach ($errors as $error) {
                echo "   - $error\n";
            }
        }
        echo "\nCreated tables: " . implode(', ', $createdTables) . "\n";
        echo "Some functionality may not work correctly until all issues are resolved.\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ SETUP FAILED\n";
    echo "================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please check your database connection and file paths.\n\n";
    exit(1);
}

echo "Setup completed at: " . date('Y-m-d H:i:s') . "\n";
?>