<?php
require_once 'config.php';

try {
    $students = $pdo->query("SELECT COUNT(*) FROM students WHERE academic_status = 'Active'")->fetchColumn();
    $faculty = $pdo->query("SELECT COUNT(*) FROM faculty WHERE is_active = 1")->fetchColumn();

    echo "Current Counts:\n";
    echo "Students: $students\n";
    echo "Faculty: $faculty\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>