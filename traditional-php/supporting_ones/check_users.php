<?php
require_once 'config.php';

echo "Database connection test:\n";
echo "Connected successfully!\n\n";

echo "Users in database:\n";
try {
    $stmt = $pdo->query("SELECT u.username, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        echo "- {$user['username']} ({$user['role_name']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTesting alice.johnson@college.edu login:\n";
try {
    $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE u.username = ? AND u.is_active = 1");
    $stmt->execute(['alice.johnson@college.edu']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "User found: {$user['username']}\n";
        $passwordValid = password_verify('alice.johnson123', $user['password_hash']);
        echo "Password valid: " . ($passwordValid ? 'YES' : 'NO') . "\n";
        echo "Role: {$user['role_name']}\n";
    } else {
        echo "User not found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>