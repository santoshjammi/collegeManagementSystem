<?php
require_once 'config.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

echo "<h1>Login Test Results</h1>";
echo "<p>Testing login for: <strong>$email</strong></p>";

if ($email && $password) {
    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE u.username = ? AND u.is_active = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "<p style='color: green;'>✓ User found in database</p>";
        echo "<p>Role: {$user['role_name']}</p>";

        $passwordValid = password_verify($password, $user['password_hash']);
        if ($passwordValid) {
            echo "<p style='color: green;'>✓ Password is correct!</p>";
            echo "<p>Login should work. Redirecting to dashboard...</p>";
            // Don't actually redirect for testing
        } else {
            echo "<p style='color: red;'>✗ Password is incorrect</p>";
            echo "<p>Expected password hash: " . substr($user['password_hash'], 0, 20) . "...</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ User not found in database</p>";

        // Show all users for debugging
        echo "<h3>All users in database:</h3>";
        $stmt = $pdo->query("SELECT u.username, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $u) {
            echo "<p>- {$u['username']} ({$u['role_name']})</p>";
        }
    }
} else {
    echo "<p style='color: red;'>Please enter both email and password</p>";
}

echo "<br><a href='login_test.html'>Back to test form</a>";
?>