<?php
// Create Sample Announcements for Testing Dashboard
require_once 'config.php';
require_once 'auth.php';

try {
    $pdo = getDB();
    
    // Insert a few sample announcements
    $sampleAnnouncements = [
                [
            'title' => 'Welcome New Students!',
            'content' => 'We warmly welcome all new students joining us this semester. Please report to the main office for orientation details and class schedules.',
            'priority' => 'high',
            'target_audience' => 'Students',
            'posted_by' => 1 // Admin user
        ],
        [
            'title' => 'Library Hours Extended',
            'content' => 'Starting next week, library hours will be extended until 10 PM on weekdays to accommodate student study needs during exam season.',
            'priority' => 'medium',
            'target_audience' => 'All',
            'posted_by' => 1
        ],
        [
            'title' => 'Faculty Meeting Notice',
            'content' => 'All faculty members are requested to attend the monthly meeting on Friday at 2 PM in the conference hall. Agenda includes curriculum updates and student performance reviews.',
            'priority' => 'medium',
            'target_audience' => 'Faculty',
            'posted_by' => 1
        ],
        [
            'title' => 'System Maintenance',
            'content' => 'The college management system will undergo scheduled maintenance this Saturday from 10 PM to 2 AM. Some services may be temporarily unavailable.',
            'priority' => 'low',
            'target_audience' => 'All',
            'posted_by' => 1
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by, is_active, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $created = 0;
    foreach ($sampleAnnouncements as $announcement) {
        // Check if announcement already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM announcements WHERE title = ?");
        $checkStmt->execute([$announcement['title']]);
        
        if ($checkStmt->fetchColumn() == 0) {
            $stmt->execute([
                $announcement['title'],
                $announcement['content'],
                'General',
                $announcement['target_audience'],
                $announcement['priority'],
                $announcement['posted_by']
            ]);
            $created++;
        }
    }
    
    echo "<h2>✅ Sample Announcements Created</h2>";
    echo "<p>Created $created new sample announcements for testing the dashboard.</p>";
    echo "<p><a href='dashboard.php' class='btn btn-primary'>View Dashboard</a></p>";
    echo "<p><a href='announcements.php' class='btn btn-secondary'>Manage Announcements</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Failed to create sample announcements: " . $e->getMessage() . "</p>";
}
?>