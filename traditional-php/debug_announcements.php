<?php
// Debug script to test announcement creation
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
echo "<h1>Announcement Creation Debug</h1>";
echo "<p>Current User: " . ($user ? $user['full_name'] . ' (' . $user['role_name'] . ')' : 'Not logged in') . "</p>";

// Check if user has permission
if (!$user || !hasRole(['Admin', 'Administrative Staff', 'Faculty'])) {
    echo "<p style='color: red;'>❌ User does not have permission to create announcements</p>";
    exit;
}

echo "<p style='color: green;'>✓ User has permission to create announcements</p>";

// Check database connection
global $pdo;
echo "<p>✓ Database connection established</p>";

// Check if announcements table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'announcements'");
    $tableExists = $stmt->fetch();
    if ($tableExists) {
        echo "<p style='color: green;'>✓ Announcements table exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Announcements table does not exist</p>";
        echo "<p>Please run the SQL script: create_announcements_tables.sql</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking announcements table: " . $e->getMessage() . "</p>";
}

// Check if notifications table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $tableExists = $stmt->fetch();
    if ($tableExists) {
        echo "<p style='color: green;'>✓ Notifications table exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Notifications table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking notifications table: " . $e->getMessage() . "</p>";
}

// Test announcement creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Testing Announcement Creation</h2>";

    $title = $_POST['title'] ?? 'Test Announcement';
    $content = $_POST['content'] ?? 'This is a test announcement.';
    $priority = $_POST['priority'] ?? 'medium';
    $target_audience = $_POST['target_audience'] ?? 'All';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");

        $stmt->execute([$title, $content, 'General', $target_audience, $priority, $user['user_id']]);
        $announcementId = $pdo->lastInsertId();

        echo "<p style='color: green;'>✓ Announcement created successfully! ID: $announcementId</p>";

        // Test notification creation
        $notificationTitle = "New Announcement: " . $title;
        $notificationMessage = "A new $priority priority announcement has been posted.";

        $userQuery = "SELECT user_id FROM users WHERE is_active = 1";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute();
        $targetUsers = $userStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($targetUsers)) {
            $notificationStmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, notification_type, related_id, related_type, created_at)
                VALUES (?, ?, ?, 'Info', ?, 'announcement', NOW())
            ");

            $notificationCount = 0;
            foreach ($targetUsers as $userId) {
                $notificationStmt->execute([$userId, $notificationTitle, $notificationMessage, $announcementId]);
                $notificationCount++;
            }

            echo "<p style='color: green;'>✓ Created $notificationCount notifications</p>";
        }

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error creating announcement: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<h2>Test Announcement Creation</h2>";
    echo "<form method='POST'>";
    echo "<div class='mb-3'>";
    echo "<label>Title:</label>";
    echo "<input type='text' name='title' value='Test Announcement' class='form-control' required>";
    echo "</div>";
    echo "<div class='mb-3'>";
    echo "<label>Content:</label>";
    echo "<textarea name='content' class='form-control' rows='3' required>This is a test announcement.</textarea>";
    echo "</div>";
    echo "<div class='mb-3'>";
    echo "<label>Priority:</label>";
    echo "<select name='priority' class='form-select'>";
    echo "<option value='low'>Low</option>";
    echo "<option value='medium' selected>Medium</option>";
    echo "<option value='high'>High</option>";
    echo "<option value='urgent'>Urgent</option>";
    echo "</select>";
    echo "</div>";
    echo "<div class='mb-3'>";
    echo "<label>Target Audience:</label>";
    echo "<select name='target_audience' class='form-select'>";
    echo "<option value='All' selected>All Users</option>";
    echo "<option value='Student'>Students Only</option>";
    echo "<option value='Faculty'>Faculty Only</option>";
    echo "</select>";
    echo "</div>";
    echo "<button type='submit' class='btn btn-primary'>Create Test Announcement</button>";
    echo "</form>";
}
?>