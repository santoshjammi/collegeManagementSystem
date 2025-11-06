<?php
require_once 'config.php';

echo "<h1>Quick Table Check</h1>";

try {
    global $pdo;

    // Check announcements table
    $stmt = $pdo->query("SHOW TABLES LIKE 'announcements'");
    $announcementsExists = $stmt->fetch();

    // Check notifications table
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $notificationsExists = $stmt->fetch();

    echo "<p>Announcements table: " . ($announcementsExists ? "<span style='color:green'>EXISTS</span>" : "<span style='color:red'>MISSING</span>") . "</p>";
    echo "<p>Notifications table: " . ($notificationsExists ? "<span style='color:green'>EXISTS</span>" : "<span style='color:red'>MISSING</span>") . "</p>";

    if (!$announcementsExists || !$notificationsExists) {
        echo "<h2>Creating missing tables...</h2>";

        if (!$announcementsExists) {
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
            echo "<p style='color:green'>✓ Announcements table created</p>";
        }

        if (!$notificationsExists) {
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
            echo "<p style='color:green'>✓ Notifications table created</p>";
        }
    }

    echo "<h2>Testing announcement creation...</h2>";

        // Get a test user
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'Faculty') LIMIT 1");
    $stmt->execute();
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($testUser) {
        $stmt = $pdo->prepare("
            INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by, is_active)
            VALUES (?, ?, 'General', ?, ?, ?, 1)
        ");

        $stmt->execute([
            'Test Announcement',
            '<p>This is a <strong>test</strong> announcement with <em>rich text</em> formatting.</p><ul><li>Bullet point 1</li><li>Bullet point 2</li></ul>',
            'All',
            'Medium',
            $testUser['user_id']
        ]);

        echo "<p style='color:green'>✓ Test announcement created successfully!</p>";
    } else {
        echo "<p style='color:red'>❌ No faculty users found for testing</p>";
    }} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>