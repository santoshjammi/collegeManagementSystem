<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $user = getCurrentUser();
    if (!$user || !hasRole(['Admin', 'Administrative Staff', 'Faculty'])) {
        throw new Exception('Unauthorized access');
    }

    // Validate input
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $target_audience = $_POST['target_audience'] ?? 'All';
    $expires_at = $_POST['expires_at'] ?? null;

    if (empty($title)) {
        throw new Exception('Title is required');
    }

    if (empty($content)) {
        throw new Exception('Content is required');
    }

    // For rich text content, check plain text length (strip HTML tags)
    $plainTextContent = strip_tags($content);
    if (strlen($plainTextContent) > 1000) {
        throw new Exception('Content must be 1000 characters or less (excluding formatting)');
    }

    // Store the rich text content as-is (with HTML)
    $content = $content;

    if (strlen($content) > 1000) {
        throw new Exception('Content must be 1000 characters or less');
    }

    if (!in_array($priority, ['Low', 'Medium', 'High', 'Critical'])) {
        throw new Exception('Invalid priority level');
    }

    $validAudiences = ['All', 'Students', 'Faculty'];
    if (hasRole(['Admin', 'Administrative Staff'])) {
        $validAudiences[] = 'Admin';
    }

    if (!in_array($target_audience, $validAudiences)) {
        throw new Exception('Invalid target audience');
    }

    // Validate expiry date if provided
    if (!empty($expires_at)) {
        $expiryTime = DateTime::createFromFormat('Y-m-d\TH:i', $expires_at);
        if (!$expiryTime || $expiryTime <= new DateTime()) {
            throw new Exception('Expiry date must be in the future');
        }
        $expires_at = $expiryTime->format('Y-m-d H:i:s');
    } else {
        $expires_at = null;
    }

    global $pdo;

    // Insert announcement
    $stmt = $pdo->prepare("
        INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by, expires_at, is_active, created_at)
        VALUES (?, ?, 'General', ?, ?, ?, ?, 1, NOW())
    ");

    $stmt->execute([
        $title,
        $content,
        $target_audience,
        $priority,
        $user['user_id'],
        $expires_at
    ]);

    $announcementId = $pdo->lastInsertId();

    // Create notifications for the announcement based on target audience
    $notificationTitle = "New Announcement: " . $title;
    $notificationMessage = "A new " . $priority . " priority announcement has been posted.";
    
    // Get users based on target audience
    if ($target_audience === 'All') {
        $userQuery = "SELECT u.user_id FROM users u WHERE u.is_active = 1";
        $userParams = [];
    } else {
        // Map target_audience enum values to role_name values
        $roleMapping = [
            'Students' => 'Student',
            'Faculty' => 'Faculty',
            'Admin' => 'Admin'
        ];
        $roleName = $roleMapping[$target_audience] ?? $target_audience;
        $userQuery = "SELECT u.user_id FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = ? AND u.is_active = 1";
        $userParams = [$roleName];
    }

    $userStmt = $pdo->prepare($userQuery);
    $userStmt->execute($userParams);
    $targetUsers = $userStmt->fetchAll(PDO::FETCH_COLUMN);

    // Insert notifications for target users
    if (!empty($targetUsers)) {
        $notificationStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, notification_type, related_id, related_type, created_at) 
            VALUES (?, ?, ?, 'Info', ?, 'announcement', NOW())
        ");

        foreach ($targetUsers as $userId) {
            $notificationStmt->execute([
                $userId,
                $notificationTitle,
                $notificationMessage,
                $announcementId
            ]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Announcement created successfully',
        'announcement_id' => $announcementId
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>