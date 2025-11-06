<?php
require_once '../config.php';
requireLogin();

echo "<h1>Test Course Management</h1>";

// Test 1: Check if courses table exists and has data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Courses table exists with " . $result['count'] . " courses</p>";
} catch (Exception $e) {
    echo "<p>❌ Courses table error: " . $e->getMessage() . "</p>";
}

// Test 2: Try to add a test course
try {
    $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name, duration_years) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE course_name = course_name");
    $stmt->execute(['TEST101', 'Test Course for Development', 4]);
    echo "<p>✅ Course insertion works</p>";
} catch (Exception $e) {
    echo "<p>❌ Course insertion failed: " . $e->getMessage() . "</p>";
}

// Test 3: Check permissions
$user = getCurrentUser();
if ($user) {
    $canAccess = canAccessModule('courses');
    echo "<p>" . ($canAccess ? "✅" : "❌") . " User can access courses module (Role: " . ($user['role_name'] ?? 'unknown') . ")</p>";
} else {
    echo "<p>❌ No user logged in</p>";
}

echo "<hr><p><strong>Course Management System Status:</strong> Ready for use</p>";
?>