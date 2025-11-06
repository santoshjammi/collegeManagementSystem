<?php
/**
 * Add missing columns to student_test_grades table
 */

require_once 'config.php';

try {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Add Missing Columns</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h3 mb-4'>🔧 Adding Missing Columns</h1>
    <div class='card'>
        <div class='card-body'>";

    // Check if is_absent column exists
    $result = $pdo->query("SHOW COLUMNS FROM student_test_grades LIKE 'is_absent'");
    $is_absent_exists = $result->rowCount() > 0;

    // Check if graded_by column exists
    $result = $pdo->query("SHOW COLUMNS FROM student_test_grades LIKE 'graded_by'");
    $graded_by_exists = $result->rowCount() > 0;

    $changes_made = false;

    if (!$is_absent_exists) {
        $pdo->exec("ALTER TABLE student_test_grades ADD COLUMN is_absent TINYINT(1) DEFAULT 0 AFTER status");
        echo "<div class='alert alert-success'>✅ Successfully added 'is_absent' column to student_test_grades table.</div>";
        $changes_made = true;
    } else {
        echo "<div class='alert alert-info'>ℹ️ The 'is_absent' column already exists.</div>";
    }

    if (!$graded_by_exists) {
        $pdo->exec("ALTER TABLE student_test_grades ADD COLUMN graded_by VARCHAR(10) AFTER is_absent");
        $pdo->exec("ALTER TABLE student_test_grades ADD INDEX idx_graded_by (graded_by)");
        echo "<div class='alert alert-success'>✅ Successfully added 'graded_by' column to student_test_grades table.</div>";
        $changes_made = true;
    } else {
        echo "<div class='alert alert-info'>ℹ️ The 'graded_by' column already exists.</div>";
    }

    if ($changes_made) {
        echo "<div class='alert alert-info'>The columns have been added with appropriate defaults.</div>";
    } else {
        echo "<div class='alert alert-success'>✅ All required columns already exist in the table.</div>";
    }

    echo "<div class='mt-3'>
        <a href='grades.php' class='btn btn-primary'>Go to Grades Page</a>
        <a href='index.php' class='btn btn-secondary'>Back to Home</a>
    </div>";

    echo "</div></div></div></body></html>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<div class='mt-3'>
        <a href='index.php' class='btn btn-secondary'>Back to Home</a>
    </div>";
}
?>