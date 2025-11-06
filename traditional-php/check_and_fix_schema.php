<?php
require_once 'config.php';

echo "<h1>Current Database Table Structure Check</h1>";

try {
    global $pdo;

    // Check announcements table structure
    echo "<h2>Announcements Table:</h2>";
    $stmt = $pdo->query("DESCRIBE announcements");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

    $hasPostedBy = false;
    $hasCreatedBy = false;
    $hasAnnouncementType = false;

    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";

        if ($column['Field'] === 'posted_by') $hasPostedBy = true;
        if ($column['Field'] === 'created_by') $hasCreatedBy = true;
        if ($column['Field'] === 'announcement_type') $hasAnnouncementType = true;
    }
    echo "</table>";

    echo "<h3>Analysis:</h3>";
    echo "<p>Has posted_by: " . ($hasPostedBy ? "✅ YES" : "❌ NO") . "</p>";
    echo "<p>Has created_by: " . ($hasCreatedBy ? "✅ YES" : "❌ NO") . "</p>";
    echo "<p>Has announcement_type: " . ($hasAnnouncementType ? "✅ YES" : "❌ NO") . "</p>";

    if ($hasCreatedBy && !$hasPostedBy) {
        echo "<h3>🔧 Fixing: Renaming created_by to posted_by</h3>";
        try {
            $pdo->exec("ALTER TABLE announcements CHANGE created_by posted_by INT NOT NULL");
            echo "<p style='color:green'>✅ Successfully renamed created_by to posted_by</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Failed to rename column: " . $e->getMessage() . "</p>";
        }
    }

    if (!$hasAnnouncementType) {
        echo "<h3>🔧 Adding announcement_type column</h3>";
        try {
            $pdo->exec("ALTER TABLE announcements ADD COLUMN announcement_type ENUM('General', 'Academic', 'Event', 'Important', 'Emergency') DEFAULT 'General' AFTER content");
            echo "<p style='color:green'>✅ Successfully added announcement_type column</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Failed to add column: " . $e->getMessage() . "</p>";
        }
    }

    // Test INSERT with correct fields
    echo "<h3>🧪 Testing INSERT with correct fields</h3>";
    try {
        $stmt = $pdo->prepare("
            INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by, is_active)
            VALUES (?, ?, 'General', ?, ?, ?, 1)
        ");

        $stmt->execute([
            'Schema Fix Test - ' . date('H:i:s'),
            '<p>This is a test announcement to verify the schema fix.</p>',
            'All',
            'Medium',
            1 // Assuming user_id 1 exists
        ]);

        echo "<p style='color:green'>✅ Test INSERT successful! ID: " . $pdo->lastInsertId() . "</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Test INSERT failed: " . $e->getMessage() . "</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>