<?php
// Simple debug - check if we can connect and see users
echo "Starting debug...\n";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=college_management;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Database connected successfully\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users table has {$result['count']} records\n";

    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT username FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Sample users:\n";
        foreach ($users as $user) {
            echo "- {$user['username']}\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>