<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

// Simulate the exact same logic as dashboard.php
if (!$user || !is_array($user)) {
    $user = [
        'user_id' => $_SESSION['user_id'] ?? 1,
        'username' => $_SESSION['username'] ?? 'unknown',
        'full_name' => 'Unknown User',
        'role_name' => $_SESSION['role'] ?? 'Student',
        'faculty_id' => null,
        'student_id' => null
    ];
    echo "<div class='alert alert-warning'>⚠️ Using session fallback data (same as dashboard)</div>";
}

$userRole = $user['role_name'] ?? 'Student';

echo "<h1>Dashboard Logic Debug</h1>";
echo "<h2>User Object (after fallback):</h2>";
echo "<pre>" . print_r($user, true) . "</pre>";
echo "<h2>User Role: $userRole</h2>";

// Map role_name to target_audience enum values (exact same as dashboard)
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

    $currentTime = date('Y-m-d H:i:s');
    echo "<h2>Current Time: $currentTime</h2>";

    // Exact same query as getDashboardAnnouncements
    $stmt = $pdo->prepare("
        SELECT
            a.announcement_id,
            a.title,
            a.content,
            a.priority,
            a.target_audience,
            a.created_at,
            COALESCE(s.full_name, f.full_name, u.username) as created_by_name
        FROM announcements a
        LEFT JOIN users u ON a.posted_by = u.user_id
        LEFT JOIN students s ON u.user_id = s.user_id
        LEFT JOIN faculty f ON u.user_id = f.user_id
        WHERE a.is_active = 1
            AND (a.expires_at IS NULL OR a.expires_at > ?)
            AND (a.target_audience = 'All'
                 OR a.target_audience = ?)
        ORDER BY
            CASE a.priority
                WHEN 'Critical' THEN 1
                WHEN 'High' THEN 2
                WHEN 'Medium' THEN 3
                ELSE 4
            END,
            a.created_at DESC
        LIMIT 10
    ");

    $stmt->execute([$currentTime, $targetAudience]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Dashboard Query Results: " . count($announcements) . " announcements</h2>";

    if (!empty($announcements)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Target</th><th>Priority</th><th>Created</th></tr>";
        foreach ($announcements as $ann) {
            echo "<tr>";
            echo "<td>{$ann['announcement_id']}</td>";
            echo "<td>{$ann['title']}</td>";
            echo "<td>{$ann['target_audience']}</td>";
            echo "<td>{$ann['priority']}</td>";
            echo "<td>{$ann['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No announcements found with current query.</p>";

        // Debug: Check what announcements exist
        echo "<h3>All Active Announcements:</h3>";
        $stmt = $pdo->query("SELECT announcement_id, title, target_audience, priority, is_active FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5");
        $allAnnouncements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($allAnnouncements)) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Title</th><th>Target</th><th>Priority</th><th>Active</th></tr>";
            foreach ($allAnnouncements as $ann) {
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
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>