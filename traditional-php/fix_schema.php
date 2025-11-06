<?php
require_once 'config.php';

echo "<h1>Database Schema Fix</h1>";

try {
    global $pdo;

    // Check current announcements table structure
    echo "<h2>Current Announcements Table Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE announcements");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";

    // Check if we need to alter the table
    $hasPostedBy = false;
    $hasAnnouncementType = false;
    $hasCreatedBy = false;

    foreach ($columns as $col) {
        if ($col['Field'] === 'posted_by') $hasPostedBy = true;
        if ($col['Field'] === 'announcement_type') $hasAnnouncementType = true;
        if ($col['Field'] === 'created_by') $hasCreatedBy = true;
    }

    echo "<h2>Schema Analysis:</h2>";
    echo "<p>Has posted_by: " . ($hasPostedBy ? "✅" : "❌") . "</p>";
    echo "<p>Has announcement_type: " . ($hasAnnouncementType ? "✅" : "❌") . "</p>";
    echo "<p>Has created_by: " . ($hasCreatedBy ? "✅" : "❌") . "</p>";

    if (!$hasAnnouncementType) {
        echo "<p>Adding announcement_type column...</p>";
        $pdo->exec("ALTER TABLE announcements ADD COLUMN announcement_type ENUM('General', 'Academic', 'Event', 'Important', 'Emergency') DEFAULT 'General' AFTER content");
        echo "<p style='color:green'>✓ announcement_type column added</p>";
    }

    if ($hasCreatedBy && !$hasPostedBy) {
        echo "<p>Renaming created_by to posted_by...</p>";
        $pdo->exec("ALTER TABLE announcements CHANGE created_by posted_by INT NOT NULL");
        echo "<p style='color:green'>✓ Column renamed from created_by to posted_by</p>";
    }

    // Update priority enum if needed
    echo "<p>Updating priority enum values...</p>";
    try {
        $pdo->exec("ALTER TABLE announcements MODIFY COLUMN priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium'");
        echo "<p style='color:green'>✓ Priority enum updated</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange'>⚠ Could not update priority enum: " . $e->getMessage() . "</p>";
    }

    echo "<h2>✅ Schema Fix Complete</h2>";
    echo "<p>The announcements table should now work with the PHP code.</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>