<?php
require_once 'config.php';
require_once 'auth.php';

echo "<h2>Announcements Debug Information</h2>";

try {
    global $pdo;
    echo "<p>✅ Database connection successful</p>";
    
    // Check if announcements table exists
    $stmt = $pdo->query("DESCRIBE announcements");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>✅ Announcements table exists with columns: " . implode(', ', $columns) . "</p>";
    
    // Check total announcements count
    $stmt = $pdo->query("SELECT COUNT(*) FROM announcements");
    $total = $stmt->fetchColumn();
    echo "<p>📊 Total announcements in database: $total</p>";
    
    // Check active announcements
    $stmt = $pdo->query("SELECT COUNT(*) FROM announcements WHERE is_active = 1");
    $active = $stmt->fetchColumn();
    echo "<p>📊 Active announcements: $active</p>";
    
    // Test the dashboard function
    $testUser = [
        'user_id' => 1,
        'role_name' => 'Admin',
        'username' => 'admin'
    ];
    
    // Copy the function logic to test
    $userRole = $testUser['role_name'];
    $currentTime = date('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare("
        SELECT 
            a.announcement_id,
            a.title,
            a.content,
            a.priority,
            a.target_audience,
            a.created_at,
            u.full_name as created_by_name
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.is_active = 1 
            AND (a.expires_at IS NULL OR a.expires_at > ?)
            AND (a.target_audience = 'All' 
                 OR a.target_audience = ?
                 OR (a.target_audience = 'Staff' AND ? IN ('Admin', 'Administrative Staff')))
        ORDER BY 
            CASE a.priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                ELSE 4 
            END,
            a.created_at DESC
        LIMIT 10
    ");
    
    $stmt->execute([$currentTime, $userRole, $userRole]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>🔍 Dashboard announcements query returned " . count($announcements) . " results for Admin user</p>";
    
    if (!empty($announcements)) {
        echo "<h3>Sample Announcements:</h3>";
        foreach ($announcements as $announcement) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px;'>";
            echo "<strong>" . htmlspecialchars($announcement['title']) . "</strong><br>";
            echo "Priority: " . $announcement['priority'] . "<br>";
            echo "Audience: " . $announcement['target_audience'] . "<br>";
            echo "Content: " . substr(htmlspecialchars($announcement['content']), 0, 100) . "...<br>";
            echo "</div>";
        }
    }
    
    // List all announcements for debugging
    echo "<h3>All Announcements in Database:</h3>";
    $stmt = $pdo->query("SELECT announcement_id, title, priority, target_audience, is_active, created_at FROM announcements ORDER BY created_at DESC");
    $allAnnouncements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allAnnouncements)) {
        echo "<p>❌ No announcements found. <a href='create_sample_announcements.php'>Create sample announcements</a></p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Priority</th><th>Audience</th><th>Active</th><th>Created</th></tr>";
        foreach ($allAnnouncements as $ann) {
            echo "<tr>";
            echo "<td>" . $ann['announcement_id'] . "</td>";
            echo "<td>" . htmlspecialchars($ann['title']) . "</td>";
            echo "<td>" . $ann['priority'] . "</td>";
            echo "<td>" . $ann['target_audience'] . "</td>";
            echo "<td>" . ($ann['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $ann['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
?>