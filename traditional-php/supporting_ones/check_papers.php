<?php
require_once 'config.php';

echo "Checking faculty_papers table...\n\n";

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'faculty_papers'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        echo "✅ faculty_papers table exists!\n";

        // Check data count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM faculty_papers");
        $count = $stmt->fetch()['count'];
        echo "📊 Contains $count paper(s)\n";

        if ($count > 0) {
            // Show sample data
            $stmt = $pdo->query("SELECT p.title, f.full_name FROM faculty_papers p JOIN faculty f ON p.faculty_id = f.faculty_id LIMIT 3");
            $papers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "\n📄 Sample papers:\n";
            foreach ($papers as $paper) {
                echo "  - {$paper['title']} (by {$paper['full_name']})\n";
            }
        }

        echo "\n🎉 Setup successful! You can now access faculty_papers.php\n";
    } else {
        echo "❌ faculty_papers table does not exist!\n";
        echo "Please run: php setup_database.php\n";
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>