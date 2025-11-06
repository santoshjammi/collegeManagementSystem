<?php
// Simulate the complete login and dashboard access flow
require_once 'config.php';

echo "=== LOGIN FLOW TEST ===\n\n";

// Step 1: Simulate login form submission
echo "1. Simulating login form submission...\n";
$_POST['email'] = 'admin@college.edu';
$_POST['password'] = 'admin123';
$_SERVER['REQUEST_METHOD'] = 'POST';

include 'login.php';

echo "\n2. Checking session after login...\n";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Session username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "Session role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";

if (!isset($_SESSION['user_id'])) {
    echo "ERROR: Session not set after login!\n";
    exit;
}

echo "\n3. Testing getCurrentUser()...\n";
$currentUser = getCurrentUser();
if ($currentUser && is_array($currentUser)) {
    echo "SUCCESS: getCurrentUser returned user: " . $currentUser['username'] . "\n";
} else {
    echo "ERROR: getCurrentUser failed: " . var_export($currentUser, true) . "\n";
}

echo "\n4. Simulating dashboard access...\n";
// Simulate accessing dashboard.php
$user = getCurrentUser();
if (!$user || !is_array($user)) {
    echo "DASHBOARD WOULD REDIRECT: User validation failed\n";
    echo "getCurrentUser returned: " . var_export($user, true) . "\n";
} else {
    echo "DASHBOARD WOULD LOAD: User validation passed\n";
    echo "User: " . $user['username'] . " (" . $user['role_name'] . ")\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>