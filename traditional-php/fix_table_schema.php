<?php
require_once 'config.php';

echo "<h1>Fix Announcements Table Schema</h1>";

try {
    global $pdo;

    // Check current table structure
    echo "<h2>Current Table Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE announcements");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check if we need to rename created_by to posted_by
    $hasCreatedBy = false;
    $hasPostedBy = false;

    foreach ($columns as $column) {
        if ($column['Field'] === 'created_by') $hasCreatedBy = true;
        if ($column['Field'] === 'posted_by') $hasPostedBy = true;
    }

    if ($hasCreatedBy && !$hasPostedBy) {
        echo "<h3>🔧 Renaming created_by to posted_by...</h3>";
        // First drop any foreign key constraints on created_by
        try {
            $pdo->exec("ALTER TABLE announcements DROP FOREIGN KEY fk_announcements_created_by");
            echo "<p>Dropped foreign key constraint on created_by</p>";
        } catch (Exception $e) {
            // Foreign key might not exist, continue
        }
        $pdo->exec("ALTER TABLE announcements CHANGE created_by posted_by INT NOT NULL");
        echo "<p style='color:green'>✅ Successfully renamed created_by to posted_by</p>";
    } else if ($hasPostedBy && $hasCreatedBy) {
        echo "<h3>🔧 Both created_by and posted_by exist - removing created_by...</h3>";
        // First drop any foreign key constraints on created_by
        try {
            $pdo->exec("ALTER TABLE announcements DROP FOREIGN KEY fk_announcements_created_by");
            echo "<p>Dropped foreign key constraint on created_by</p>";
        } catch (Exception $e) {
            // Foreign key might not exist, continue
        }
        $pdo->exec("ALTER TABLE announcements DROP COLUMN created_by");
        echo "<p style='color:green'>✅ Successfully removed duplicate created_by column</p>";
    } else if ($hasPostedBy) {
        echo "<p style='color:green'>✅ Table already has posted_by column</p>";
    } else {
        echo "<p style='color:red'>❌ ERROR: Table has neither created_by nor posted_by column!</p>";
    }

    // Check target_audience enum values
    $targetAudienceColumn = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'target_audience') {
            $targetAudienceColumn = $column;
            break;
        }
    }

    // Check if announcement_type column exists
    $hasAnnouncementType = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'announcement_type') {
            $hasAnnouncementType = true;
            break;
        }
    }

    if (!$hasAnnouncementType) {
        echo "<p style='color:orange'>⚠️  Missing announcement_type column</p>";
        echo "<h3>🔧 Adding announcement_type column...</h3>";
        try {
            $pdo->exec("ALTER TABLE announcements ADD COLUMN announcement_type ENUM('General', 'Academic', 'Event', 'Important', 'Emergency') DEFAULT 'General' AFTER content");
            echo "<p style='color:green'>✅ Successfully added announcement_type column</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Failed to add announcement_type column: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:green'>✅ announcement_type column already exists</p>";
    }

    echo "<h2>✅ Table Schema Check Complete</h2>";

    // Check and drop all foreign key constraints if requested
    echo "<h3>Foreign Key Constraints:</h3>";
    try {
        $fkStmt = $pdo->query("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'announcements' AND TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreignKeys = $fkStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($foreignKeys)) {
            echo "<p>Found " . count($foreignKeys) . " foreign key constraint(s):</p>";
            echo "<ul>";
            foreach ($foreignKeys as $fk) {
                echo "<li>{$fk['CONSTRAINT_NAME']} ({$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']})</li>";
            }
            echo "</ul>";

            echo "<h3>🔧 Dropping all foreign key constraints...</h3>";
            foreach ($foreignKeys as $fk) {
                try {
                    $pdo->exec("ALTER TABLE announcements DROP FOREIGN KEY {$fk['CONSTRAINT_NAME']}");
                    echo "<p style='color:green'>✅ Dropped constraint: {$fk['CONSTRAINT_NAME']}</p>";
                } catch (Exception $e) {
                    echo "<p style='color:orange'>⚠️  Could not drop {$fk['CONSTRAINT_NAME']}: " . $e->getMessage() . "</p>";
                }
            }
        } else {
            echo "<p>No foreign key constraints found.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:orange'>⚠️  Could not check foreign keys: " . $e->getMessage() . "</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>