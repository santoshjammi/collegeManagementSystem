<?php
// Test login functionality
require_once 'config.php';

echo "Testing login functionality...\n\n";

$testCredentials = [
    ['email' => 'alice.johnson@college.edu', 'password' => 'alice.johnson123'],
    ['email' => 'staff@college.edu', 'password' => 'staff123'],
    ['email' => 'admin@college.edu', 'password' => 'admin123']
];

foreach ($testCredentials as $cred) {
    echo "Testing login for: {$cred['email']}\n";

    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE u.username = ? AND u.is_active = 1
    ");
    $stmt->execute([$cred['email']]);
    $user = $stmt->fetch();

    if ($user) {
        echo "✓ User found in database\n";
        $passwordValid = password_verify($cred['password'], $user['password_hash']);
        echo "✓ Password verification: " . ($passwordValid ? 'SUCCESS' : 'FAILED') . "\n";
        echo "✓ Role: {$user['role_name']}\n";
    } else {
        echo "✗ User not found in database\n";
    }
    echo "\n";
}
?>