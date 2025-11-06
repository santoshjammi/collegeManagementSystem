<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);

echo "<h1>Debug: Navigation Access Check</h1>";
echo "<h2>User Information:</h2>";
echo "<pre>";
echo "User ID: " . $user['user_id'] . "\n";
echo "Username: " . $user['username'] . "\n";
echo "Role: " . $userRole . "\n";
echo "</pre>";

echo "<h2>Permissions Check:</h2>";
$permissions = getPermissions();
echo "<pre>";
print_r($permissions[$userRole]);
echo "</pre>";

echo "<h2>Module Access Tests:</h2>";
$modules = ['courses', 'subjects', 'tests', 'grades', 'students', 'faculty'];
foreach ($modules as $module) {
    $access = canAccessModule($module);
    echo "<p><strong>$module:</strong> " . ($access ? '<span style="color: green;">ACCESSIBLE</span>' : '<span style="color: red;">NOT ACCESSIBLE</span>') . "</p>";
}

echo "<h2>Academic Dropdown Condition:</h2>";
$showAcademic = canAccessModule('courses') || canAccessModule('subjects') || canAccessModule('tests') || canAccessModule('grades') || canAccessModule('students') || canAccessModule('faculty');
echo "<p><strong>Show Academic Dropdown:</strong> " . ($showAcademic ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>') . "</p>";

echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>