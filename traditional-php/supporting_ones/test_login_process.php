<?php
require_once 'config.php';

// Start session for testing
session_start();

// Test login process
echo "Testing login process...\n";

$email = 'admin@college.edu';
$password = 'admin123';

echo "Attempting login with: $email / $password\n";

$stmt = $pdo->prepare("
    SELECT u.*, r.role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.role_id
    WHERE u.username = ? AND u.is_active = 1
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    echo "User found: {$user['username']} (Role: {$user['role_name']})\n";

    if (password_verify($password, $user['password_hash'])) {
        echo "Password verified successfully\n";

        // Set session like login does
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role_name'];

        echo "Session set. user_id: {$_SESSION['user_id']}\n";

        // Test getCurrentUser
        $currentUser = getCurrentUser();
        if ($currentUser && is_array($currentUser)) {
            echo "getCurrentUser() successful: {$currentUser['username']}\n";
            echo "Login process should work!\n";
        } else {
            echo "ERROR: getCurrentUser() failed: " . var_export($currentUser, true) . "\n";
        }
    } else {
        echo "ERROR: Password verification failed\n";
    }
} else {
    echo "ERROR: User not found\n";
}
?>