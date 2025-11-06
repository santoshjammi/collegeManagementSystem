<?php
// Test script to create faculty_papers table if it doesn't exist
require_once 'config.php';

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'faculty_papers'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        echo "Creating faculty_papers table...\n";

        $sql = "
        CREATE TABLE faculty_papers (
            paper_id INT PRIMARY KEY AUTO_INCREMENT,
            faculty_id INT NOT NULL,
            title VARCHAR(500) NOT NULL,
            authors TEXT NOT NULL COMMENT 'Comma-separated list of authors',
            journal_name VARCHAR(300) NULL COMMENT 'Journal, conference, or publication venue',
            publication_date DATE NULL,
            doi VARCHAR(100) NULL COMMENT 'Digital Object Identifier',
            abstract TEXT NULL,
            keywords TEXT NULL COMMENT 'Comma-separated keywords',
            paper_url VARCHAR(500) NULL COMMENT 'Link to the paper if available online',
            citation_count INT DEFAULT 0 COMMENT 'Number of citations',
            publication_type ENUM('Journal Article', 'Conference Paper', 'Book Chapter', 'Book', 'Thesis', 'Working Paper', 'Other') DEFAULT 'Journal Article',
            status ENUM('Published', 'Accepted', 'Submitted', 'Draft') DEFAULT 'Published',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
        ";

        $pdo->exec($sql);
        echo "Table created successfully!\n";
    } else {
        echo "Table already exists.\n";
    }

    // Test if we can query the table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM faculty_papers");
    $result = $stmt->fetch();
    echo "Table is accessible. Current paper count: " . $result['count'] . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>