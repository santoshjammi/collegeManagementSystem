<?php
// Fix script to ensure proper role-based access control setup
require_once 'config.php';

echo "🔧 Fixing role-based access control setup...\n\n";

try {
    // Step 1: Ensure roles exist with correct IDs
    echo "1. Setting up roles...\n";
    $roles = [
        1 => 'Admin',
        2 => 'Administrative Staff',
        3 => 'Faculty',
        4 => 'Student'
    ];

    foreach ($roles as $id => $name) {
        $stmt = $pdo->prepare("INSERT INTO roles (role_id, role_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE role_name = VALUES(role_name)");
        $stmt->execute([$id, $name]);
        echo "   ✓ Role $id: $name\n";
    }

    // Step 2: Create test users with correct passwords
    echo "\n2. Creating test users...\n";
    $users = [
        ['username' => 'admin', 'password' => 'admin123', 'role_id' => 1, 'role_name' => 'Admin'],
        ['username' => 'staff', 'password' => 'staff123', 'role_id' => 2, 'role_name' => 'Administrative Staff'],
        ['username' => 'faculty1', 'password' => 'faculty123', 'role_id' => 3, 'role_name' => 'Faculty'],
        ['username' => 'student1', 'password' => 'student123', 'role_id' => 4, 'role_name' => 'Student']
    ];

    foreach ($users as $user) {
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, role_id, is_active)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
            password_hash = VALUES(password_hash),
            role_id = VALUES(role_id),
            is_active = VALUES(is_active)
        ");
        $stmt->execute([$user['username'], $hashedPassword, $user['role_id']]);
        echo "   ✓ User: {$user['username']} ({$user['role_name']}) - Password: {$user['password']}\n";
    }

    // Step 3: Verify setup
    echo "\n3. Verifying setup...\n";

    // Check roles
    $stmt = $pdo->query("SELECT role_id, role_name FROM roles ORDER BY role_id");
    $dbRoles = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "   Roles in database:\n";
    foreach ($dbRoles as $id => $name) {
        echo "     $id: $name\n";
    }

    // Check users
    $stmt = $pdo->query("
        SELECT u.username, r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        ORDER BY u.username
    ");
    $dbUsers = $stmt->fetchAll();
    echo "   Users in database:\n";
    foreach ($dbUsers as $user) {
        echo "     {$user['username']}: {$user['role_name']}\n";
    }

    // Test staff login
    echo "\n4. Testing staff login...\n";
    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE u.username = ? AND u.is_active = 1
    ");
    $stmt->execute(['staff']);
    $user = $stmt->fetch();

    if ($user) {
        $passwordValid = password_verify('staff123', $user['password_hash']);
        echo "   ✓ Staff user found\n";
        echo "   ✓ Password verification: " . ($passwordValid ? 'SUCCESS' : 'FAILED') . "\n";
        echo "   ✓ Role: {$user['role_name']}\n";
    } else {
        echo "   ❌ Staff user not found\n";
    }

    echo "\n🎉 Setup complete! You can now login with:\n";
    echo "- admin/admin123 (Admin)\n";
    echo "- staff/staff123 (Administrative Staff)\n";
    echo "- faculty1/faculty123 (Faculty)\n";
    echo "- student1/student123 (Student)\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please ensure your database is set up correctly and the tables exist.\n";
}
?>