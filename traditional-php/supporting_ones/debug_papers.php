<?php
// Debug script to check and create faculty_papers table
require_once 'config.php';

echo "Checking database connection...\n";

try {
    // Check what tables exist
    echo "Existing tables:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "- $table\n";
    }

    // Check if faculty_papers exists
    $facultyPapersExists = in_array('faculty_papers', $tables);

    if (!$facultyPapersExists) {
        echo "\nCreating faculty_papers table...\n";

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

        // Add some sample data
        echo "Adding sample papers...\n";

        // Get faculty members
        $stmt = $pdo->query("SELECT faculty_id, full_name FROM faculty WHERE is_active = 1 LIMIT 2");
        $faculty = $stmt->fetchAll();

        if (!empty($faculty)) {
            $samplePapers = [
                [
                    'faculty_id' => $faculty[0]['faculty_id'],
                    'title' => 'Machine Learning in Education: A Comprehensive Survey',
                    'authors' => $faculty[0]['full_name'] . ', John Smith, Mary Johnson',
                    'journal_name' => 'IEEE Transactions on Learning Technologies',
                    'publication_date' => '2024-03-15',
                    'doi' => '10.1109/TLT.2024.1234567',
                    'abstract' => 'This paper provides a comprehensive survey of machine learning applications in educational settings, covering various techniques and their effectiveness.',
                    'keywords' => 'machine learning, education, survey, technology',
                    'paper_url' => 'https://ieeexplore.ieee.org/document/1234567',
                    'citation_count' => 25,
                    'publication_type' => 'Journal Article',
                    'status' => 'Published'
                ],
                [
                    'faculty_id' => count($faculty) > 1 ? $faculty[1]['faculty_id'] : $faculty[0]['faculty_id'],
                    'title' => 'Cybersecurity Education in Higher Education',
                    'authors' => (count($faculty) > 1 ? $faculty[1]['full_name'] : $faculty[0]['full_name']) . ', David Chen',
                    'journal_name' => 'Journal of Cybersecurity Education',
                    'publication_date' => '2024-01-20',
                    'doi' => '10.1002/csec.1234',
                    'abstract' => 'An analysis of cybersecurity education programs in universities and recommendations for curriculum development.',
                    'keywords' => 'cybersecurity, education, curriculum',
                    'paper_url' => 'https://onlinelibrary.wiley.com/doi/10.1002/csec.1234',
                    'citation_count' => 18,
                    'publication_type' => 'Journal Article',
                    'status' => 'Published'
                ]
            ];

            foreach ($samplePapers as $paper) {
                $stmt = $pdo->prepare("
                    INSERT INTO faculty_papers
                    (faculty_id, title, authors, journal_name, publication_date, doi, abstract, keywords, paper_url, citation_count, publication_type, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $paper['faculty_id'], $paper['title'], $paper['authors'], $paper['journal_name'],
                    $paper['publication_date'], $paper['doi'], $paper['abstract'], $paper['keywords'],
                    $paper['paper_url'], $paper['citation_count'], $paper['publication_type'], $paper['status']
                ]);
            }

            echo "Sample papers added!\n";
        }
    } else {
        echo "\nfaculty_papers table already exists.\n";

        // Check if it has data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM faculty_papers");
        $result = $stmt->fetch();
        echo "Current paper count: " . $result['count'] . "\n";
    }

    // Test the query used in faculty_papers.php
    echo "\nTesting the query from faculty_papers.php...\n";
    $stmt = $pdo->prepare("
        SELECT fp.*, f.full_name as faculty_name, f.department
        FROM faculty_papers fp
        LEFT JOIN faculty f ON fp.faculty_id = f.faculty_id
        WHERE 1=1
        ORDER BY fp.publication_date DESC, fp.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $papers = $stmt->fetchAll();

    echo "Query successful! Found " . count($papers) . " papers.\n";

    if (!empty($papers)) {
        echo "\nSample paper:\n";
        echo "- Title: " . $papers[0]['title'] . "\n";
        echo "- Authors: " . $papers[0]['authors'] . "\n";
        echo "- Journal: " . $papers[0]['journal_name'] . "\n";
        echo "- URL: " . $papers[0]['paper_url'] . "\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>