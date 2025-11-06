<?php
require_once 'config.php';

try {
    // Test the main papers query
    $query = "
        SELECT
            fp.paper_id,
            fp.title,
            fp.authors,
            fp.journal_name,
            fp.publication_date,
            fp.doi,
            fp.abstract,
            fp.keywords,
            fp.paper_url,
            fp.citation_count,
            fp.publication_type,
            fp.status,
            fp.created_at,
            f.full_name as author_name,
            f.department,
            'faculty' as author_type,
            f.faculty_id as author_id
        FROM faculty_papers fp
        LEFT JOIN faculty f ON fp.faculty_id = f.faculty_id
        WHERE 1=1

        UNION ALL

        SELECT
            sp.paper_id,
            sp.title,
            sp.authors,
            sp.journal_name,
            sp.publication_date,
            sp.doi,
            sp.abstract,
            sp.keywords,
            sp.paper_url,
            sp.citation_count,
            sp.publication_type,
            sp.status,
            sp.created_at,
            s.full_name as author_name,
            CONCAT('Student - ', c.course_name) as department,
            'student' as author_type,
            s.student_pk_id as author_id
        FROM student_papers sp
        LEFT JOIN students s ON sp.student_id = s.student_pk_id
        LEFT JOIN courses c ON s.course_id = c.course_id
        WHERE 1=1
        ORDER BY COALESCE(publication_date, '9999-12-31') DESC, created_at DESC
        LIMIT 5
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $papers = $stmt->fetchAll();

    echo "Query executed successfully!\n";
    echo "Found " . count($papers) . " papers\n";

    if (count($papers) > 0) {
        echo "\nFirst paper:\n";
        echo "- Title: " . $papers[0]['title'] . "\n";
        echo "- Author: " . $papers[0]['author_name'] . "\n";
        echo "- Type: " . $papers[0]['author_type'] . "\n";
        echo "- Publication Date: " . ($papers[0]['publication_date'] ?? 'Not set') . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>