<?php
/**
 * College Management System File Organization Script
 * Moves supporting files to supporting_ones directory
 */

echo "<h1>🗂️ College Management System File Organization</h1>";
echo "<hr>";

// Create supporting_ones directory if it doesn't exist
if (!is_dir('supporting_ones')) {
    mkdir('supporting_ones', 0755, true);
    echo "<p>📁 Created supporting_ones directory</p>";
}

// Define files to move
$filesToMove = [
    // Documentation files
    'ANNOUNCEMENTS_README.md',
    'DASHBOARD_ANNOUNCEMENTS.md',
    'DASHBOARD_ANNOUNCEMENTS_FINAL.md',
    'TROUBLESHOOTING_ANNOUNCEMENTS.md',
    'FILE_ORGANIZATION_ANALYSIS.md',
    
    // Setup and utility scripts
    'add_sample_papers.php',
    'add_subjects_data.php',
    'create_sample_announcements.php',
    'create_test_users.php',
    'fix_roles.php',
    'migrate_subjects_and_add_communication.php',
    'quick_setup_announcements.php',
    'setup_users.php',
    
    // Debug files
    'debug_announcements.php',
    'debug_db.php',
    'debug_login.php',
    'debug_papers.php',
    'debug_subjects.php',
    'simple_debug.php',
    'simple_test.php',
    
    // Test files
    'test_alice_login.php',
    'test_announcements.php',
    'test_db.php',
    'test_db_connection.php',
    'test_full_login_flow.php',
    'test_login.php',
    'test_login_process.php',
    'test_query.php',
    'test_table.php',
    
    // Check files
    'check_counts.php',
    'check_papers.php',
    'check_subjects.php',
    'check_table.php',
    'check_users.php',
    
    // Legacy files
    'announcements.php',
    'login_test.html',
    'login_test.php',
    'verify_setup.php',
    
    // This organization script itself
    'organize_files.php',
    'organize_files.sh'
];

$moved = 0;
$skipped = 0;
$errors = 0;

echo "<h2>📦 Moving Files to supporting_ones/</h2>";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 15px; border-radius: 5px;'>";

foreach ($filesToMove as $file) {
    if (file_exists($file)) {
        if (rename($file, "supporting_ones/$file")) {
            echo "✅ Moved: $file<br>";
            $moved++;
        } else {
            echo "❌ Error moving: $file<br>";
            $errors++;
        }
    } else {
        echo "⏭️ Not found: $file<br>";
        $skipped++;
    }
}

echo "</div>";

echo "<h2>📊 Summary</h2>";
echo "<ul>";
echo "<li><strong>✅ Files moved:</strong> $moved</li>";
echo "<li><strong>⏭️ Files not found:</strong> $skipped</li>";
echo "<li><strong>❌ Errors:</strong> $errors</li>";
echo "</ul>";

if ($errors == 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 File Organization Complete!</h3>";
    echo "<p>Your main directory now contains only essential operational files.</p>";
    echo "<p>All development, testing, and setup utilities are now in <code>supporting_ones/</code></p>";
    echo "</div>";
}

// Show current main directory files
echo "<h2>📁 Current Main Directory (Core Files Only)</h2>";
echo "<div style='font-family: monospace; background: #f8f9fa; padding: 15px; border-radius: 5px;'>";

$coreFiles = array_filter(scandir('.'), function($file) {
    return !is_dir($file) && $file !== '.' && $file !== '..' && 
           (pathinfo($file, PATHINFO_EXTENSION) === 'php' || 
            pathinfo($file, PATHINFO_EXTENSION) === 'html');
});

foreach ($coreFiles as $file) {
    echo "📄 $file<br>";
}

echo "</div>";

// Show what's in supporting_ones
echo "<h2>🗃️ Supporting Files Directory</h2>";
$supportingFiles = array_filter(scandir('supporting_ones'), function($file) {
    return $file !== '.' && $file !== '..';
});

echo "<p><strong>Total files in supporting_ones:</strong> " . count($supportingFiles) . "</p>";

echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h3>🎯 Clean Directory Structure Achieved!</h3>";
echo "<p><strong>Main Directory:</strong> Contains only essential operational files for daily use</p>";
echo "<p><strong>supporting_ones/:</strong> Contains all development tools, tests, and utilities</p>";
echo "<p><a href='dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Go to Dashboard</a></p>";
echo "</div>";
?>