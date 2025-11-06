<?php
require_once 'config.php';

// Test database connection
global $pdo;

try {
    echo "<h2>Database Connection Test</h2>";
    
    if ($pdo) {
        echo "<p>✅ PDO object exists</p>";
        
        // Test simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        if ($result && $result['test'] == 1) {
            echo "<p>✅ Database connection working</p>";
        } else {
            echo "<p>❌ Database query failed</p>";
        }
        
        // Test if announcements table exists
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM announcements");
            $count = $stmt->fetchColumn();
            echo "<p>✅ Announcements table exists with $count records</p>";
        } catch (Exception $e) {
            echo "<p>❌ Announcements table issue: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p>❌ PDO object not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='dashboard.php'>Test Dashboard</a></p>";
?>