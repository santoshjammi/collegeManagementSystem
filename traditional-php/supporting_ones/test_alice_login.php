<?php
require_once 'config.php';

echo "Testing login for alice.johnson...\n";

// Test both username and email versions
$testCredentials = [
    ['username' => 'alice.johnson@college.edu', 'password' => 'alice.johnson123'],
    ['username' => 'student1', 'password' => 'alice.johnson123']
];

foreach ($testCredentials as $cred) {
    echo "\nTesting: {$cred['username']} / {$cred['password']}\n";

    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE u.username = ? AND u.is_active = 1
    ");
    $stmt->execute([$cred['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "✓ User found: {$user['username']} (Role: {$user['role_name']})\n";
        $passwordValid = password_verify($cred['password'], $user['password_hash']);
        echo "✓ Password verification: " . ($passwordValid ? 'SUCCESS' : 'FAILED') . "\n";

        if ($passwordValid) {
            echo "✓ LOGIN SHOULD WORK!\n";
        }
    } else {
        echo "✗ User not found in database\n";
    }
}

echo "\nDone.\n";
?>