<?php
// Quick test to verify faculty_papers table exists
require_once 'config.php';

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'faculty_papers'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        echo "✅ faculty_papers table exists!\n";

        // Check if it has data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM faculty_papers");
        $count = $stmt->fetch()['count'];
        echo "📊 Table contains $count paper(s)\n";

        // Show a sample paper if any exist
        if ($count > 0) {
            $stmt = $pdo->query("SELECT title, authors, journal_name FROM faculty_papers LIMIT 1");
            $paper = $stmt->fetch();
            echo "📄 Sample paper: " . $paper['title'] . "\n";
        }
    } else {
        echo "❌ faculty_papers table does not exist!\n";
        echo "Please run setup_database.php to create the table.\n";
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>