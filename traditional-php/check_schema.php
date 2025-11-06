<?php
require_once 'config.php';

echo "<h1>Check Announcements Table Schema</h1>";

try {
    global $pdo;

    // Check the enum values for target_audience
    $stmt = $pdo->query("SHOW COLUMNS FROM announcements WHERE Field = 'target_audience'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h2>target_audience Column Definition:</h2>";
    echo "<pre>" . print_r($column, true) . "</pre>";

    if ($column && strpos($column['Type'], 'enum') !== false) {
        echo "<h3>Enum Values:</h3>";
        // Extract enum values from the type definition
        preg_match("/enum\((.*)\)/", $column['Type'], $matches);
        if ($matches) {
            $enumValues = str_replace("'", "", $matches[1]);
            echo "<p>Allowed values: $enumValues</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>