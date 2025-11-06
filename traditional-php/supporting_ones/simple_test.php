<?php
echo "PHP is working!\n";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=college_management;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Database connection successful!\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users table has {$result['count']} records\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>