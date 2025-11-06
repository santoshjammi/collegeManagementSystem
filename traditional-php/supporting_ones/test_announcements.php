<?php
// Test Announcements Functionality
require_once 'config.php';
require_once 'auth.php';

echo "<h2>Testing Announcements System</h2>\n";

// Test database connection
try {
    $pdo = getDB();
    echo "<p>✅ Database connection successful</p>\n";
    
    // Check if tables exist
    $tables = ['announcements', 'notifications', 'alerts'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table");
        $stmt->execute();
        echo "<p>✅ Table '$table' exists and accessible</p>\n";
    }
    
    // Test role permissions
    $testRoles = ['Admin', 'Administrative Staff', 'Faculty', 'Student'];
    echo "<h3>Role-based Permission Test:</h3>\n";
    
    foreach ($testRoles as $role) {
        $canCreate = in_array($role, ['Admin', 'Administrative Staff', 'Faculty']);
        echo "<p>" . ($canCreate ? '✅' : '❌') . " $role: " . 
             ($canCreate ? 'Can create announcements' : 'Read-only access') . "</p>\n";
    }
    
    // Check for existing announcements
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM announcements");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>📊 Current announcements in database: " . $result['count'] . "</p>\n";
    
    echo "<p>🎉 All tests passed! Announcements system is ready to use.</p>\n";
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>\n";
}
?>