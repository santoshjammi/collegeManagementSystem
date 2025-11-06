<?php
// Test database connection and table existence
require_once 'config.php';

echo "Database Connection Test\n";
echo "========================\n\n";

try {
    // Test connection
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection successful\n";

    // Check if faculty_papers table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'faculty_papers'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        echo "✅ faculty_papers table exists!\n";

        // Check data count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM faculty_papers");
        $count = $stmt->fetch()['count'];
        echo "📊 Table contains $count paper(s)\n";

        // Show sample data
        if ($count > 0) {
            $stmt = $pdo->query("SELECT paper_id, title, authors FROM faculty_papers LIMIT 3");
            $papers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "\n📄 Sample papers:\n";
            foreach ($papers as $paper) {
                echo "  ID {$paper['paper_id']}: {$paper['title']} (by {$paper['authors']})\n";
            }
        }
    } else {
        echo "❌ faculty_papers table does not exist!\n";
        echo "Please run setup_database.php to create the table.\n";
    }

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>

    // Check if roles table exists
    $result = $pdo->query("SHOW TABLES LIKE 'roles'");
    if ($result->rowCount() > 0) {
        echo "✓ Roles table exists\n";

        // Check roles
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM roles");
        $count = $stmt->fetch()['count'];
        echo "✓ Roles table has $count records\n";

        // List roles
        $stmt = $pdo->query("SELECT * FROM roles");
        $roles = $stmt->fetchAll();
        echo "Roles found:\n";
        foreach ($roles as $role) {
            echo "  - {$role['role_name']}\n";
        }
    } else {
        echo "❌ Roles table does not exist\n";
    }

    // Check if users table exists
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() > 0) {
        echo "✓ Users table exists\n";

        // Check users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "✓ Users table has $count records\n";
    } else {
        echo "❌ Users table does not exist\n";
    }

} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>