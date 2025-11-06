<?php
require_once 'config.php';

echo "<h1>Login Debug</h1>";

// Test database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>✓ Database connected. Users table has {$result['count']} records.</p>";
} catch (Exception $e) {
    echo "<p>✗ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Show all users
echo "<h2>All Users in Database:</h2>";
try {
    $stmt = $pdo->query("SELECT u.username, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<ul>";
    foreach ($users as $user) {
        echo "<li>{$user['username']} ({$user['role_name']})</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>✗ Error fetching users: " . $e->getMessage() . "</p>";
}

// Test specific user lookups
$testUsers = ['alice.johnson@college.edu', 'student1', 'admin@college.edu', 'admin'];

echo "<h2>Testing User Lookups:</h2>";
foreach ($testUsers as $username) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id
            WHERE u.username = ? AND u.is_active = 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo "<p>✓ Found user: {$username} (Role: {$user['role_name']})</p>";
            echo "<p>  Password hash: " . substr($user['password_hash'], 0, 20) . "...</p>";

            // Test password verification
            $testPasswords = ['alice.johnson123', 'admin123', 'student1123'];
            foreach ($testPasswords as $testPass) {
                $valid = password_verify($testPass, $user['password_hash']);
                echo "<p>  Password '$testPass': " . ($valid ? '✓ VALID' : '✗ INVALID') . "</p>";
            }
        } else {
            echo "<p>✗ User not found: {$username}</p>";
        }
    } catch (Exception $e) {
        echo "<p>✗ Error testing {$username}: " . $e->getMessage() . "</p>";
    }
    echo "<hr>";
}
?>