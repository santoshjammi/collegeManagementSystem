<?php
require_once 'config.php';

echo "Verifying database setup...\n\n";

try {
    // Check if faculty_papers table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'faculty_papers'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        echo "✅ faculty_papers table exists!\n";

        // Check data count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM faculty_papers");
        $count = $stmt->fetch()['count'];
        echo "📊 Contains $count paper(s)\n";

        // Check faculty count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM faculty");
        $facultyCount = $stmt->fetch()['count'];
        echo "👨‍🏫 Faculty members: $facultyCount\n";

        if ($count > 0) {
            // Show sample data
            $stmt = $pdo->query("SELECT p.title, f.full_name FROM faculty_papers p JOIN faculty f ON p.faculty_id = f.faculty_id LIMIT 2");
            $papers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "\n📄 Sample papers:\n";
            foreach ($papers as $paper) {
                echo "  - {$paper['title']} (by {$paper['full_name']})\n";
            }
        }

        echo "\n🎉 Setup successful! Visit http://localhost:8080/faculty_papers.php\n";
    } else {
        echo "❌ faculty_papers table does not exist!\n";
        echo "Please run: php setup_database.php\n";
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo ($exists ? "✅" : "❌") . " $table table " . ($exists ? "exists" : "missing") . "\n";

        if ($exists && $table === 'faculty_papers') {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM faculty_papers");
            $count = $stmt->fetch()['count'];
            echo "   📊 Contains $count paper(s)\n";
        }
    } catch (Exception $e) {
        echo "❌ Error checking $table: " . $e->getMessage() . "\n";
    }
}

echo "\nSetup verification complete!\n";
?>