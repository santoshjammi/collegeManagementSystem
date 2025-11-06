<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
echo "<h1>Debug User and Announcements</h1>";

echo "<h2>User Information:</h2>";
echo "<pre>" . print_r($user, true) . "</pre>";

echo "<h2>Session Data:</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

$userRole = $user['role_name'] ?? 'Student';
echo "<h2>User Role: $userRole</h2>";

// Map role_name to target_audience enum values
$audienceMapping = [
    'Student' => 'Students',
    'Faculty' => 'Faculty',
    'Admin' => 'Admin',
    'Administrative Staff' => 'Admin'
];
$targetAudience = $audienceMapping[$userRole] ?? $userRole;
echo "<h2>Mapped Target Audience: $targetAudience</h2>";

try {
    global $pdo;

    // Check total announcements
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM announcements");
    $total = $stmt->fetch()['total'];
    echo "<h2>Total Announcements in DB: $total</h2>";

    // Check active announcements
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM announcements WHERE is_active = 1");
    $active = $stmt->fetch()['active'];
    echo "<h2>Active Announcements: $active</h2>";

    // Check announcements for this user's audience
    $currentTime = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as visible FROM announcements
        WHERE is_active = 1
        AND (expires_at IS NULL OR expires_at > ?)
        AND (target_audience = 'All' OR target_audience = ?)
    ");
    $stmt->execute([$currentTime, $targetAudience]);
    $visible = $stmt->fetch()['visible'];
    echo "<h2>Announcements Visible to User: $visible</h2>";

    // Show sample announcements
    $stmt = $pdo->prepare("
        SELECT announcement_id, title, target_audience, priority, is_active
        FROM announcements
        WHERE is_active = 1
        AND (expires_at IS NULL OR expires_at > ?)
        AND (target_audience = 'All' OR target_audience = ?)
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$currentTime, $targetAudience]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Sample Visible Announcements:</h2>";
    if (empty($announcements)) {
        echo "<p>No announcements visible to this user.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Target</th><th>Priority</th><th>Active</th></tr>";
        foreach ($announcements as $ann) {
            echo "<tr>";
            echo "<td>{$ann['announcement_id']}</td>";
            echo "<td>{$ann['title']}</td>";
            echo "<td>{$ann['target_audience']}</td>";
            echo "<td>{$ann['priority']}</td>";
            echo "<td>{$ann['is_active']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>