<?php
// Debug script to check database state
require_once 'config.php';

echo "Checking database state...\n\n";

// Check roles table
echo "Roles in database:\n";
$stmt = $pdo->query("SELECT * FROM roles");
$roles = $stmt->fetchAll();
foreach ($roles as $role) {
    echo "- ID: {$role['role_id']}, Name: {$role['role_name']}\n";
}

echo "\nUsers in database:\n";
$stmt = $pdo->query("SELECT u.user_id, u.username, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id");
$users = $stmt->fetchAll();
foreach ($users as $user) {
    echo "- ID: {$user['user_id']}, Username: {$user['username']}, Role: {$user['role_name']}\n";
}

echo "\nTesting password verification for 'staff@college.edu' user:\n";
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(['staff@college.edu']);
$user = $stmt->fetch();
if ($user) {
    echo "User found: {$user['username']}\n";
    echo "Password hash: {$user['password_hash']}\n";
    $testPassword = 'staff123';
    $verify = password_verify($testPassword, $user['password_hash']);
    echo "Password 'staff123' verification: " . ($verify ? 'SUCCESS' : 'FAILED') . "\n";
} else {
    echo "User 'staff@college.edu' not found in database\n";
}

echo "\nTesting password verification for 'alice.johnson@college.edu' user:\n";
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(['alice.johnson@college.edu']);
$user = $stmt->fetch();
if ($user) {
    echo "User found: {$user['username']}\n";
    echo "Password hash: {$user['password_hash']}\n";
    $testPassword = 'alice.johnson123';
    $verify = password_verify($testPassword, $user['password_hash']);
    echo "Password 'alice.johnson123' verification: " . ($verify ? 'SUCCESS' : 'FAILED') . "\n";
} else {
    echo "User 'alice.johnson@college.edu' not found in database\n";
}
?>