<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

echo "<h1>Quick Announcement Test</h1>";
echo "<p>User: " . ($user ? $user['full_name'] . ' (' . $user['role_name'] . ')' : 'Not logged in') . "</p>";

if (!$user || !hasRole(['Admin', 'Administrative Staff', 'Faculty'])) {
    echo "<p style='color:red'>❌ No permission to create announcements</p>";
    exit;
}

try {
    global $pdo;

    // Test insert
    $stmt = $pdo->prepare("
        INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by, is_active)
        VALUES (?, ?, 'General', ?, ?, ?, 1)
    ");

    $stmt->execute([
        'Test Announcement - ' . date('H:i:s'),
        '<p>This is a <strong>test</strong> announcement created at ' . date('Y-m-d H:i:s') . '</p>',
        'All',
        'Medium',
        $user['user_id']
    ]);

    $id = $pdo->lastInsertId();
    echo "<p style='color:green'>✅ Announcement created successfully! ID: $id</p>";

    // Test select
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE announcement_id = ?");
    $stmt->execute([$id]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h2>Created Announcement:</h2>";
    echo "<p><strong>Title:</strong> " . htmlspecialchars($announcement['title']) . "</p>";
    echo "<p><strong>Content:</strong> " . $announcement['content'] . "</p>";
    echo "<p><strong>Posted by:</strong> " . $announcement['posted_by'] . "</p>";
    echo "<p><strong>Priority:</strong> " . $announcement['priority'] . "</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>