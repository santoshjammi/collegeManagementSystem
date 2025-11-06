<?php
/**
 * Quick Subjects Check
 */

require_once 'config.php';

echo "<h2>Subjects in Database</h2>";

try {
    $count = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    echo "<p><strong>Total subjects:</strong> $count</p>";

    if ($count > 0) {
        $subjects = $pdo->query("SELECT subject_code, subject_name, semester, status FROM subjects ORDER BY subject_code LIMIT 10")->fetchAll();

        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Code</th><th>Name</th><th>Semester</th><th>Status</th></tr>";

        foreach ($subjects as $subject) {
            echo "<tr>";
            echo "<td>{$subject['subject_code']}</td>";
            echo "<td>{$subject['subject_name']}</td>";
            echo "<td>{$subject['semester']}</td>";
            echo "<td>{$subject['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No subjects found in database!</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>