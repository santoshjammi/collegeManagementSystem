<?php
// Setup script to ensure roles exist and create test users
require_once 'config.php';

echo "Setting up roles and test users...\n\n";

// Ensure roles exist
$roles = [
    'Admin',
    'Administrative Staff',
    'Faculty',
    'Student'
];

echo "Creating roles...\n";
foreach ($roles as $roleName) {
    $stmt = $pdo->prepare("INSERT INTO roles (role_name) VALUES (?) ON DUPLICATE KEY UPDATE role_name = role_name");
    $stmt->execute([$roleName]);
    echo "✓ Role: $roleName\n";
}

echo "\nCreating test users...\n";

// Sample users for testing
$testUsers = [
    [
        'username' => 'admin',
        'password' => 'admin123',
        'role' => 'Admin',
        'full_name' => 'System Administrator'
    ],
    [
        'username' => 'staff',
        'password' => 'staff123',
        'role' => 'Administrative Staff',
        'full_name' => 'Administrative Staff'
    ],
    [
        'username' => 'faculty1',
        'password' => 'faculty123',
        'role' => 'Faculty',
        'full_name' => 'Dr. John Smith'
    ],
    [
        'username' => 'student1',
        'password' => 'student123',
        'role' => 'Student',
        'full_name' => 'Alice Johnson'
    ]
];

foreach ($testUsers as $userData) {
    try {
        // Get role ID
        $stmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = ?");
        $stmt->execute([$userData['role']]);
        $role = $stmt->fetch();

        if (!$role) {
            echo "❌ Role '{$userData['role']}' not found. Skipping user '{$userData['username']}'.\n";
            continue;
        }

        // Create user
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, role_id, is_active)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
            password_hash = VALUES(password_hash),
            role_id = VALUES(role_id)
        ");
        $stmt->execute([$userData['username'], $hashedPassword, $role['role_id']]);

        echo "✓ Created user: {$userData['username']} ({$userData['role']})\n";

    } catch (Exception $e) {
        echo "❌ Error creating user '{$userData['username']}': " . $e->getMessage() . "\n";
    }
}

echo "\n🎉 Setup complete!\n";
echo "You can now login with:\n";
echo "- admin/admin123 (Full access)\n";
echo "- staff/staff123 (Administrative staff access)\n";
echo "- faculty1/faculty123 (Faculty access)\n";
echo "- student1/student123 (Student access)\n";
?>