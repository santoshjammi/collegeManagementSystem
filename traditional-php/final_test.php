<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

echo "<h1>Final Announcement Creation Test</h1>";
echo "<p>User: " . ($user ? $user['full_name'] . ' (' . $user['role_name'] . ')' : 'Not logged in') . "</p>";

if (!$user || !hasRole(['Admin', 'Administrative Staff', 'Faculty'])) {
    echo "<p style='color:red'>❌ No permission to create announcements</p>";
    exit;
}

try {
    global $pdo;

    // Test the exact same INSERT as the AJAX handler
    $title = 'Final Test Announcement - ' . date('H:i:s');
    $content = '<p>This is a <strong>final test</strong> announcement with <em>rich formatting</em>.</p><ul><li>Point 1</li><li>Point 2</li></ul>';
    $target_audience = 'All';
    $priority = 'Medium';
    $expires_at = null;

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

    $id = $pdo->lastInsertId();
    echo "<p style='color:green'>✅ SUCCESS! Announcement created with ID: $id</p>";

    // Verify the data was inserted correctly
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE announcement_id = ?");
    $stmt->execute([$id]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h2>Inserted Data:</h2>";
    echo "<table border='1' cellpadding='5'>";
    foreach ($announcement as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    echo "</table>";

    echo "<h2>🎉 The issue is FIXED!</h2>";
    echo "<p>The announcement creation should now work properly in the dashboard.</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ ERROR: " . $e->getMessage() . "</p>";
    echo "<p>Please check the database schema and try again.</p>";
}
?>