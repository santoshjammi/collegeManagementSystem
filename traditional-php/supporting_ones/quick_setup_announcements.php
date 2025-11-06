<?php
require_once 'config.php';

try {
    global $pdo;
    
    // First, ensure the announcements table exists
    $stmt = $pdo->prepare("
        CREATE TABLE IF NOT EXISTS announcements (
            announcement_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            announcement_type ENUM('General', 'Academic', 'Event', 'Important', 'Emergency') DEFAULT 'General',
            target_audience ENUM('All', 'Students', 'Faculty', 'Admin') DEFAULT 'All',
            priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
            posted_by INT NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            expires_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (posted_by) REFERENCES users(user_id),
            INDEX idx_active_expires (is_active, expires_at),
            INDEX idx_audience (target_audience),
            INDEX idx_priority (priority)
        ) ENGINE=InnoDB
    ");
    $stmt->execute();
    
    // Clear any existing sample announcements
    $pdo->query("DELETE FROM announcements WHERE title LIKE 'Sample:%'");
    
    // Insert fresh sample announcements
    $announcements = [
        [
            'title' => 'Sample: Welcome to the New Academic Year!',
            'content' => 'We are excited to welcome all students, faculty, and staff to the new academic year. Please check your schedules and report to your respective departments.',
            'priority' => 'High',
            'target_audience' => 'All',
            'posted_by' => 1
        ],
        [
            'title' => 'Sample: Library Extended Hours',
            'content' => 'The library will now be open until 11 PM on weekdays and 9 PM on weekends to support student study needs.',
            'priority' => 'Medium',
            'target_audience' => 'All',
            'posted_by' => 1
        ],
        [
            'title' => 'Sample: Important Faculty Meeting',
            'content' => 'All faculty members are required to attend the departmental meeting scheduled for Friday at 3 PM in the main conference room.',
            'priority' => 'High',
            'target_audience' => 'Faculty',
            'posted_by' => 1
        ],
        [
            'title' => 'Sample: Student Registration Reminder',
            'content' => 'Reminder: Course registration for the spring semester closes this Friday. Please complete your registration to avoid late fees.',
            'priority' => 'High',
            'target_audience' => 'Students',
            'posted_by' => 1
        ],
        [
            'title' => 'Sample: System Maintenance Notice',
            'content' => 'The college management system will undergo maintenance this Sunday from 2 AM to 6 AM. Some services may be temporarily unavailable.',
            'priority' => 'Low',
            'target_audience' => 'All',
            'posted_by' => 1
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by, is_active, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $inserted = 0;
    foreach ($announcements as $ann) {
                $stmt->execute([
            $ann['title'],
            $ann['content'],
            'General',
            $ann['target_audience'],
            $ann['priority'],
            $ann['posted_by']
        ]);
        $inserted++;
    }
    
    echo "<h2>✅ Sample Announcements Created Successfully!</h2>";
    echo "<p>Inserted $inserted sample announcements into the database.</p>";
    
    // Verify the data
    $stmt = $pdo->query("SELECT COUNT(*) FROM announcements WHERE is_active = 1");
    $count = $stmt->fetchColumn();
    echo "<p>Total active announcements in database: $count</p>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li><a href='debug_announcements.php'>Debug Announcements System</a></li>";
    echo "<li><a href='dashboard.php'>View Dashboard</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error Creating Sample Announcements</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>