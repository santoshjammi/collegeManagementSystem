<?php
require_once 'config.php';

try {
    global $pdo;

    // Check if announcements table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'announcements'");
    $tableExists = $stmt->fetch();

    if (!$tableExists) {
        echo "Announcements table does not exist. Creating it...\n";

        // Create announcements table
        $pdo->exec("
            CREATE TABLE announcements (
                announcement_id INT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(200) NOT NULL,
                content TEXT NOT NULL,
                priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                target_audience ENUM('All', 'Student', 'Faculty', 'Staff', 'Admin') DEFAULT 'All',
                expires_at DATETIME NULL,
                created_by INT NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_active_expires (is_active, expires_at),
                INDEX idx_target_audience (target_audience),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB
        ");

        echo "✓ Announcements table created successfully\n";
    } else {
        echo "✓ Announcements table already exists\n";
    }

    // Check if notifications table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $tableExists = $stmt->fetch();

    if (!$tableExists) {
        echo "Notifications table does not exist. Creating it...\n";

        // Create notifications table
        $pdo->exec("
            CREATE TABLE notifications (
                notification_id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
                url VARCHAR(500) NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_user_read (user_id, is_read),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB
        ");

        echo "✓ Notifications table created successfully\n";
    } else {
        echo "✓ Notifications table already exists\n";
    }

    // Test creating a sample announcement
    echo "\nTesting announcement creation...\n";

    // Get a faculty user for testing
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'Faculty') LIMIT 1");
    $stmt->execute();
    $facultyUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($facultyUser) {
        $testStmt = $pdo->prepare("
            INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");

        $testStmt->execute([
            'Test Announcement',
            'This is a test announcement to verify the system is working.',
            'General',
            'All',
            'Medium',
            $facultyUser['user_id']
        ]);

        echo "✓ Test announcement created successfully\n";
        echo "✓ Announcement creation is working!\n";
    } else {
        echo "⚠ No faculty users found for testing\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>