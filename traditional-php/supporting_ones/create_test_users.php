<?php
// Test script to create sample users with different roles
// Login ID is email, default password is emailID123 (user can change in profile)

require_once 'config.php';

// Sample users for testing with email-based credentials
$testUsers = [
    [
        'email' => 'admin@college.edu',
        'username' => 'admin',  // Also create username version for compatibility
        'role' => 'Admin',
        'full_name' => 'System Administrator'
    ],
    [
        'email' => 'staff@college.edu',
        'username' => 'staff',
        'role' => 'Administrative Staff',
        'full_name' => 'Administrative Staff'
    ],
    [
        'email' => 'john.smith@college.edu',
        'username' => 'faculty1',
        'role' => 'Faculty',
        'full_name' => 'Dr. John Smith'
    ],
    [
        'email' => 'alice.johnson@college.edu',
        'username' => 'student1',
        'alt_username' => 'alice.johnson',  // Additional username for convenience
        'role' => 'Student',
        'full_name' => 'Alice Johnson'
    ]
];

echo "Creating test users with email-based login...\n";
echo "Default password format: emailID123 (e.g., john.smith@college.edu123)\n\n";

// First check if roles exist
echo "Checking roles...\n";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM roles");
$roleCount = $stmt->fetch()['count'];
echo "Found $roleCount roles in database\n\n";

foreach ($testUsers as $userData) {
    try {
        // Get role ID
        $stmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = ?");
        $stmt->execute([$userData['role']]);
        $role = $stmt->fetch();

        if (!$role) {
            echo "Role '{$userData['role']}' not found. Skipping user '{$userData['email']}'.\n";
            continue;
        }

        // Generate password: emailID123 (remove @domain part and add 123)
        $emailPrefix = explode('@', $userData['email'])[0];
        $defaultPassword = $emailPrefix . '123';

        // Create user with email as username
        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, role_id, is_active)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
            password_hash = VALUES(password_hash),
            role_id = VALUES(role_id)
        ");
        $stmt->execute([$userData['email'], $hashedPassword, $role['role_id']]);

        echo "✓ Created user: {$userData['email']} ({$userData['role']})\n";
        echo "  Password: {$defaultPassword}\n";

        // Also create username version for backward compatibility
        if (isset($userData['username'])) {
            $stmt->execute([$userData['username'], $hashedPassword, $role['role_id']]);
            echo "✓ Also created: {$userData['username']} (same password)\n";
        }

        // Also create alt_username if specified
        if (isset($userData['alt_username'])) {
            $stmt->execute([$userData['alt_username'], $hashedPassword, $role['role_id']]);
            echo "✓ Also created: {$userData['alt_username']} (same password)\n";
        }

        echo "  Full Name: {$userData['full_name']}\n\n";

    } catch (Exception $e) {
        echo "❌ Error creating user '{$userData['email']}': " . $e->getMessage() . "\n";
    }
}

echo "🎉 Test users created successfully!\n\n";
echo "Login with your email address OR username:\n";
echo "• admin@college.edu OR admin / admin123 (Full system access)\n";
echo "• staff@college.edu OR staff / staff123 (Administrative staff access)\n";
echo "• john.smith@college.edu OR faculty1 / john.smith123 (Faculty access)\n";
echo "• alice.johnson@college.edu OR student1 OR alice.johnson / alice.johnson123 (Student access)\n\n";
echo "Users can change their password in their profile page.\n";
?>