<?php
// Script to add sample papers for demonstration
require_once 'config.php';

try {
    // Check if table exists and create if needed
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
    }

    // Get some faculty members to assign papers to
    $stmt = $pdo->query("SELECT faculty_id, full_name FROM faculty WHERE is_active = 1 LIMIT 3");
    $faculty = $stmt->fetchAll();

    if (empty($faculty)) {
        echo "No active faculty members found. Please add some faculty first.\n";
        exit;
    }

    // Sample papers data
    $samplePapers = [
        [
            'faculty_id' => $faculty[0]['faculty_id'],
            'title' => 'Machine Learning Approaches for Educational Data Mining',
            'authors' => $faculty[0]['full_name'] . ', Sarah Johnson, Michael Chen',
            'journal_name' => 'IEEE Transactions on Learning Technologies',
            'publication_date' => '2024-03-15',
            'doi' => '10.1109/TLT.2024.1234567',
            'abstract' => 'This paper explores various machine learning techniques applied to educational data mining, focusing on student performance prediction and personalized learning recommendations.',
            'keywords' => 'machine learning, educational data mining, student performance, personalized learning',
            'paper_url' => 'https://ieeexplore.ieee.org/document/1234567',
            'citation_count' => 23,
            'publication_type' => 'Journal Article',
            'status' => 'Published'
        ],
        [
            'faculty_id' => $faculty[0]['faculty_id'],
            'title' => 'Adaptive Learning Systems: A Survey of Current Trends',
            'authors' => $faculty[0]['full_name'] . ', Robert Davis',
            'journal_name' => 'International Journal of Artificial Intelligence in Education',
            'publication_date' => '2023-11-08',
            'doi' => '10.1007/s40593-023-00345-6',
            'abstract' => 'A comprehensive survey of adaptive learning systems, examining their implementation, effectiveness, and future directions in educational technology.',
            'keywords' => 'adaptive learning, educational technology, survey, artificial intelligence',
            'paper_url' => 'https://link.springer.com/article/10.1007/s40593-023-00345-6',
            'citation_count' => 45,
            'publication_type' => 'Journal Article',
            'status' => 'Published'
        ],
        [
            'faculty_id' => count($faculty) > 1 ? $faculty[1]['faculty_id'] : $faculty[0]['faculty_id'],
            'title' => 'Cybersecurity Education in Higher Learning Institutions',
            'authors' => (count($faculty) > 1 ? $faculty[1]['full_name'] : $faculty[0]['full_name']) . ', Lisa Wang, David Kumar',
            'journal_name' => 'Journal of Cybersecurity Education, Research and Practice',
            'publication_date' => '2024-01-20',
            'doi' => '10.1002/csec.1234',
            'abstract' => 'This study examines the current state of cybersecurity education in universities and proposes a comprehensive curriculum framework.',
            'keywords' => 'cybersecurity, education, curriculum, higher education',
            'paper_url' => 'https://onlinelibrary.wiley.com/doi/10.1002/csec.1234',
            'citation_count' => 18,
            'publication_type' => 'Journal Article',
            'status' => 'Published'
        ],
        [
            'faculty_id' => count($faculty) > 2 ? $faculty[2]['faculty_id'] : $faculty[0]['faculty_id'],
            'title' => 'Big Data Analytics in Healthcare: Challenges and Opportunities',
            'authors' => (count($faculty) > 2 ? $faculty[2]['full_name'] : $faculty[0]['full_name']) . ', Maria Rodriguez, James Wilson',
            'journal_name' => 'Journal of Biomedical Informatics',
            'publication_date' => '2023-09-12',
            'doi' => '10.1016/j.jbi.2023.104567',
            'abstract' => 'An analysis of big data applications in healthcare, discussing technical challenges, privacy concerns, and potential benefits for patient care.',
            'keywords' => 'big data, healthcare, analytics, privacy, patient care',
            'paper_url' => 'https://www.sciencedirect.com/science/article/pii/S1532046423004567',
            'citation_count' => 67,
            'publication_type' => 'Journal Article',
            'status' => 'Published'
        ],
        [
            'faculty_id' => $faculty[0]['faculty_id'],
            'title' => 'Deep Learning for Automated Code Review',
            'authors' => $faculty[0]['full_name'] . ', Alex Thompson',
            'journal_name' => 'ACM Transactions on Software Engineering and Methodology',
            'publication_date' => '2024-06-01',
            'doi' => '10.1145/1234567.1234568',
            'abstract' => 'This paper presents a novel deep learning approach for automated code review, demonstrating significant improvements in bug detection accuracy.',
            'keywords' => 'deep learning, code review, software engineering, bug detection',
            'paper_url' => 'https://dl.acm.org/doi/10.1145/1234567.1234568',
            'citation_count' => 12,
            'publication_type' => 'Journal Article',
            'status' => 'Published'
        ]
    ];

    // Insert sample papers
    $inserted = 0;
    foreach ($samplePapers as $paper) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO faculty_papers
                (faculty_id, title, authors, journal_name, publication_date, doi, abstract, keywords, paper_url, citation_count, publication_type, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $paper['faculty_id'],
                $paper['title'],
                $paper['authors'],
                $paper['journal_name'],
                $paper['publication_date'],
                $paper['doi'],
                $paper['abstract'],
                $paper['keywords'],
                $paper['paper_url'],
                $paper['citation_count'],
                $paper['publication_type'],
                $paper['status']
            ]);
            $inserted++;
        } catch (PDOException $e) {
            echo "Error inserting paper '{$paper['title']}': " . $e->getMessage() . "\n";
        }
    }

    echo "Successfully inserted $inserted sample papers!\n";
    echo "You can now view them at faculty_papers.php\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>