<?php
require_once '../config.php';
requireLogin();

echo "<h1>Test Notification Query Fix</h1>";

// Test the role-based user selection queries
$testCases = [
    'All' => 'All users',
    'Students' => 'Student role',
    'Faculty' => 'Faculty role',
    'Admin' => 'Admin role'
];

global $pdo;

foreach ($testCases as $audience => $description) {
    echo "<h3>Testing: $description ($audience)</h3>";

    try {
        if ($audience === 'All') {
            $userQuery = "SELECT u.user_id FROM users u WHERE u.is_active = 1";
            $userParams = [];
        } else {
            // Map target_audience enum values to role_name values
            $roleMapping = [
                'Students' => 'Student',
                'Faculty' => 'Faculty',
                'Admin' => 'Admin'
            ];
            $roleName = $roleMapping[$audience] ?? $audience;
            $userQuery = "SELECT u.user_id FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = ? AND u.is_active = 1";
            $userParams = [$roleName];
        }

        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute($userParams);
        $targetUsers = $userStmt->fetchAll(PDO::FETCH_COLUMN);

        echo "<p>✅ Query successful: Found " . count($targetUsers) . " users</p>";
        if (!empty($targetUsers)) {
            echo "<p>User IDs: " . implode(', ', array_slice($targetUsers, 0, 5)) . (count($targetUsers) > 5 ? '...' : '') . "</p>";
        }

    } catch (Exception $e) {
        echo "<p>❌ Query failed: " . $e->getMessage() . "</p>";
    }
}

echo "<hr><p><strong>Fix Applied:</strong> Changed user query to properly join users table with roles table using role_id and role_name columns.</p>";
?>