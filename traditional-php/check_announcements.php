<?php
require_once 'config.php';

echo "<h1>Check Existing Announcements</h1>";

try {
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 10");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($announcements)) {
        echo "<p>No announcements found in database.</p>";
    } else {
        echo "<p>Found " . count($announcements) . " announcements:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Target Audience</th><th>Priority</th><th>Active</th><th>Posted By</th><th>Created</th></tr>";

        foreach ($announcements as $ann) {
            echo "<tr>";
            echo "<td>{$ann['announcement_id']}</td>";
            echo "<td>{$ann['title']}</td>";
            echo "<td>{$ann['target_audience']}</td>";
            echo "<td>{$ann['priority']}</td>";
            echo "<td>{$ann['is_active']}</td>";
            echo "<td>{$ann['posted_by']}</td>";
            echo "<td>{$ann['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>