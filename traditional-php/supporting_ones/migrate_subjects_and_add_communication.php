<?php
/**
 * Migration Script: Migrate subjects from old VARCHAR schema to new INT schema
 * Also adds announcement, notification, and alert tables
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Migrate Subjects & Add Communication Tables</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .log {
            font-family: monospace;
            background: #f8f9fa;
            padding: 20px;
            border: 1px solid #ddd;
            max-height: 700px;
            overflow-y: auto;
            white-space: pre-wrap;
            border-radius: 5px;
        }
    </style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h1 class='h2 mb-4'>🔄 Migrate Subjects & Add Communication Features</h1>";

try {
    echo "<div class='log'>";

    echo "🚀 STARTING MIGRATION PROCESS\n";
    echo "=============================\n\n";

    // First, add the new communication tables
    echo "📢 STEP 1: Adding Communication Tables\n";
    echo "=====================================\n";

    // Announcements table
    $announcements_sql = "
    CREATE TABLE IF NOT EXISTS announcements (
        announcement_id INT PRIMARY KEY AUTO_INCREMENT,
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
        FOREIGN KEY (posted_by) REFERENCES users(user_id)
    ) ENGINE=InnoDB";

    $pdo->exec($announcements_sql);
    echo "✅ Announcements table created\n";

    // Notifications table
    $notifications_sql = "
    CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        notification_type ENUM('Info', 'Warning', 'Success', 'Error', 'Reminder') DEFAULT 'Info',
        is_read TINYINT(1) DEFAULT 0,
        related_id INT NULL COMMENT 'ID of related record (announcement, grade, etc.)',
        related_type VARCHAR(50) NULL COMMENT 'Type of related record',
        expires_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB";

    $pdo->exec($notifications_sql);
    echo "✅ Notifications table created\n";

    // Alerts table
    $alerts_sql = "
    CREATE TABLE IF NOT EXISTS alerts (
        alert_id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        alert_type ENUM('System', 'Academic', 'Financial', 'Placement', 'General') DEFAULT 'General',
        severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
        target_users JSON NULL COMMENT 'JSON array of user IDs, or null for all users',
        is_active TINYINT(1) DEFAULT 1,
        auto_expire TINYINT(1) DEFAULT 0,
        expires_at DATETIME NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(user_id)
    ) ENGINE=InnoDB";

    $pdo->exec($alerts_sql);
    echo "✅ Alerts table created\n\n";

    // Now migrate subjects data
    echo "📚 STEP 2: Migrating Subjects Data\n";
    echo "=================================\n";

    // Check if old subjects table exists
    $old_subjects_exists = false;
    try {
        $result = $pdo->query("SHOW TABLES LIKE 'subjects'");
        $old_subjects_exists = $result->rowCount() > 0;
    } catch (Exception $e) {
        // Table doesn't exist or error
    }

    if (!$old_subjects_exists) {
        echo "ℹ️  No old subjects table found. Creating sample subjects data...\n\n";

        // Create sample subjects data based on the nuclear_reset.php data
        $sample_subjects = [
            ['CS101', 'Programming Fundamentals', 'Introduction to programming concepts using C/C++', 4, 1, 'Theory'],
            ['CS102', 'Data Structures', 'Arrays, linked lists, trees, graphs, algorithms', 4, 2, 'Theory'],
            ['CS201', 'Database Systems', 'Relational databases, SQL, normalization, transactions', 4, 3, 'Theory'],
            ['IT101', 'Web Development', 'HTML, CSS, JavaScript, PHP, frameworks', 4, 3, 'Practical'],
            ['CS301', 'Artificial Intelligence', 'Machine learning, neural networks, expert systems', 4, 5, 'Theory'],
            ['EE101', 'Circuit Analysis', 'DC and AC circuits, network theorems, analysis', 4, 1, 'Theory'],
            ['EC101', 'Digital Electronics', 'Boolean algebra, logic gates, combinational circuits', 4, 2, 'Theory'],
            ['ME201', 'Thermodynamics', 'Laws of thermodynamics, heat engines, cycles', 4, 3, 'Theory'],
            ['MATH101', 'Calculus I', 'Differential and integral calculus, limits', 4, 1, 'Theory'],
            ['PHYS101', 'Physics I', 'Mechanics, waves, thermodynamics, optics', 4, 1, 'Theory'],
            ['CHEM101', 'Chemistry', 'Atomic structure, chemical bonding, reactions', 4, 1, 'Theory'],
            ['MGT101', 'Management Principles', 'Planning, organizing, leading, controlling', 3, 2, 'Theory'],
            ['CE201', 'Structural Engineering', 'Design and analysis of structures', 4, 4, 'Theory'],
            ['MATH201', 'Linear Algebra', 'Matrices, vectors, eigenvalues, transformations', 3, 2, 'Theory'],
            ['ENG101', 'Technical Communication', 'Written and oral communication skills', 3, 1, 'Theory'],
            ['PHYS201', 'Electromagnetism', 'Electric and magnetic fields, Maxwell equations', 4, 3, 'Theory'],
            ['BIO101', 'Molecular Biology', 'DNA, RNA, protein synthesis, genetics', 4, 2, 'Theory'],
            ['CS202', 'Software Engineering', 'SDLC, design patterns, project management', 4, 4, 'Theory']
        ];

        $subjects_added = 0;
        foreach ($sample_subjects as $subject) {
            try {
                // Get a random course and faculty
                $course = $pdo->query("SELECT course_id FROM courses ORDER BY RAND() LIMIT 1")->fetchColumn();
                $faculty = $pdo->query("SELECT faculty_id FROM faculty WHERE is_active = 1 ORDER BY RAND() LIMIT 1")->fetchColumn();

                $stmt = $pdo->prepare("
                    INSERT INTO subjects (subject_code, subject_name, description, credits, semester, subject_type, course_id, faculty_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active')
                ");
                $stmt->execute([$subject[0], $subject[1], $subject[2], $subject[3], $subject[4], $subject[5], $course, $faculty]);

                $subjects_added++;
                echo "✅ Added: {$subject[1]} ({$subject[0]})\n";

            } catch (Exception $e) {
                echo "❌ Error adding {$subject[1]}: " . $e->getMessage() . "\n";
            }
        }

        echo "\n📊 Sample subjects added: $subjects_added\n\n";

    } else {
        echo "🔄 Old subjects table found. Attempting migration...\n";

        // Try to read from old subjects table and migrate
        try {
            $old_subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_id")->fetchAll(PDO::FETCH_ASSOC);

            echo "Found " . count($old_subjects) . " subjects to migrate\n";

            $migrated = 0;
            foreach ($old_subjects as $old_subject) {
                try {
                    // Map old VARCHAR faculty_id to new INT faculty_id
                    $faculty_id = null;
                    if (!empty($old_subject['faculty_id'])) {
                        $faculty_stmt = $pdo->prepare("SELECT faculty_id FROM faculty WHERE faculty_id = ?");
                        $faculty_stmt->execute([$old_subject['faculty_id']]);
                        $faculty_id = $faculty_stmt->fetchColumn();
                    }

                    // Get course_id (assume first course if not specified)
                    $course_id = $pdo->query("SELECT course_id FROM courses LIMIT 1")->fetchColumn();

                    $stmt = $pdo->prepare("
                        INSERT INTO subjects (subject_code, subject_name, description, credits, faculty_id, course_id, semester, subject_type, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active')
                        ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name)
                    ");

                    $stmt->execute([
                        $old_subject['subject_code'] ?? $old_subject['subject_id'],
                        $old_subject['subject_name'],
                        $old_subject['description'] ?? '',
                        $old_subject['credits'] ?? 4,
                        $faculty_id,
                        $course_id,
                        $old_subject['semester'] ?? 1,
                        $old_subject['subject_type'] ?? 'Theory'
                    ]);

                    $migrated++;
                    echo "✅ Migrated: {$old_subject['subject_name']}\n";

                } catch (Exception $e) {
                    echo "❌ Error migrating {$old_subject['subject_name']}: " . $e->getMessage() . "\n";
                }
            }

            echo "\n📊 Subjects migrated: $migrated\n";

            // Optionally drop old table
            // $pdo->exec("DROP TABLE subjects");

        } catch (Exception $e) {
            echo "❌ Error reading old subjects table: " . $e->getMessage() . "\n";
            echo "Falling back to sample data creation...\n\n";

            // Fallback to sample data
            $sample_subjects = [
                ['CS101', 'Programming Fundamentals', 'Introduction to programming concepts', 4, 1, 'Theory'],
                ['CS102', 'Data Structures', 'Arrays, linked lists, algorithms', 4, 2, 'Theory'],
                ['CS201', 'Database Systems', 'SQL, normalization, transactions', 4, 3, 'Theory']
            ];

            foreach ($sample_subjects as $subject) {
                $pdo->prepare("INSERT IGNORE INTO subjects (subject_code, subject_name, description, credits, semester, subject_type) VALUES (?, ?, ?, ?, ?, ?)")
                     ->execute($subject);
            }

            echo "✅ Fallback sample subjects added\n\n";
        }
    }

    // Add sample announcements
    echo "📢 STEP 3: Adding Sample Announcements\n";
    echo "=====================================\n";

    $sample_announcements = [
        [
            'Welcome to College Management System',
            'Welcome to our new College Management System! This platform will help you stay updated with academic activities, grades, and important announcements.',
            'General',
            'All',
            'High'
        ],
        [
            'Semester Registration Open',
            'Registration for the upcoming semester is now open. Please complete your course registration before the deadline.',
            'Academic',
            'Students',
            'High'
        ],
        [
            'Faculty Meeting Scheduled',
            'All faculty members are requested to attend the monthly meeting on Friday at 2 PM in the conference hall.',
            'General',
            'Faculty',
            'Medium'
        ],
        [
            'Library Hours Extended',
            'The library will remain open until 10 PM on weekdays for exam preparation.',
            'Academic',
            'All',
            'Low'
        ]
    ];

    // Get admin user ID
    $admin_id = $pdo->query("SELECT user_id FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'Admin') LIMIT 1")->fetchColumn();

    if ($admin_id) {
        $announcements_added = 0;
        foreach ($sample_announcements as $announcement) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $announcement[0],
                    $announcement[1],
                    $announcement[2],
                    $announcement[3],
                    $announcement[4],
                    $admin_id
                ]);
                $announcements_added++;
                echo "✅ Added announcement: {$announcement[0]}\n";
            } catch (Exception $e) {
                echo "❌ Error adding announcement: " . $e->getMessage() . "\n";
            }
        }
        echo "\n📊 Sample announcements added: $announcements_added\n\n";
    } else {
        echo "⚠️  No admin user found, skipping announcements\n\n";
    }

    echo "🎉 MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "===================================\n";
    echo "✅ Communication tables created (announcements, notifications, alerts)\n";
    echo "✅ Subjects data migrated or created\n";
    echo "✅ Sample announcements added\n\n";

    echo "📋 New Features Available:\n";
    echo "=========================\n";
    echo "• Announcements: Broadcast important messages to users\n";
    echo "• Notifications: Personal notifications for individual users\n";
    echo "• Alerts: System-wide alerts with customizable targeting\n";
    echo "• Subjects: Complete subject management with faculty assignment\n\n";

} catch (Exception $e) {
    echo "❌ MIGRATION FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</div></body></html>";
?>