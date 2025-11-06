<?php
require_once 'config.php';
requireLogin();

// Initialize database connection
global $pdo;

$user = getCurrentUser();
// Temporarily disable user validation to test if login works
if (false && (!$user || !is_array($user))) {
    // Redirect to login if user not found
    header('Location: login.php');
    exit;
}

// If user validation fails, show error but don't redirect
if (!$user || !is_array($user)) {
    $user = [
        'user_id' => $_SESSION['user_id'] ?? 1,
        'username' => $_SESSION['username'] ?? 'unknown',
        'full_name' => 'Unknown User',
        'role_name' => $_SESSION['role'] ?? 'Student',
        'faculty_id' => null,
        'student_id' => null
    ];
    echo "<div class='alert alert-warning'>Warning: User data not found in database. Using session data.</div>";
}

$userRole = $user['role_name'] ?? 'Student';

// Get dashboard data based on role
$dashboardData = getDashboardData($userRole, $user);

// Get notifications
$notifications = getRoleNotifications($userRole, $user);

// Get pending actions
$pendingActions = getPendingActions($userRole, $user);

// Get recent announcements for dashboard
$dashboardAnnouncements = getDashboardAnnouncements($user);

function getDashboardData($role, $user) {
    global $pdo;

    $data = [
        'stats' => [],
        'recent_activity' => [],
        'upcoming_events' => [],
        'quick_actions' => []
    ];

    try {
        switch ($role) {
            case 'Administrative Staff':
            case 'Admin':
                // Admin/Administrative Staff stats
                $data['stats'] = [
                    'total_students' => $pdo->query("SELECT COUNT(*) FROM students WHERE academic_status = 'Active'")->fetchColumn(),
                    'total_faculty' => $pdo->query("SELECT COUNT(*) FROM faculty WHERE is_active = 1")->fetchColumn(),
                    'total_subjects' => $pdo->query("SELECT COUNT(*) FROM subjects WHERE status = 'Active'")->fetchColumn(),
                    'total_tests' => $pdo->query("SELECT COUNT(*) FROM tests WHERE status = 'Published'")->fetchColumn(),
                    'pending_admissions' => $pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'Pending'")->fetchColumn(),
                    'pending_fees' => $pdo->query("SELECT COUNT(*) FROM fee_payments WHERE payment_status = 'Pending'")->fetchColumn(),
                    'library_books_issued' => $pdo->query("SELECT COUNT(*) FROM library_transactions WHERE status = 'Issued'")->fetchColumn(),
                    'placement_offers' => $pdo->query("SELECT COUNT(*) FROM placement_offers WHERE status = 'Offered'")->fetchColumn()
                ];

                $data['quick_actions'] = [
                    ['title' => 'New Admission', 'icon' => 'person-plus', 'url' => 'admissions.php?action=new', 'color' => 'primary'],
                    ['title' => 'Add Student', 'icon' => 'person-add', 'url' => 'students.php?action=new', 'color' => 'success'],
                    ['title' => 'Schedule Test', 'icon' => 'clipboard-check', 'url' => 'tests.php?action=new', 'color' => 'info'],
                    ['title' => 'Fee Collection', 'icon' => 'currency-dollar', 'url' => 'fees.php', 'color' => 'warning'],
                    ['title' => 'Library Report', 'icon' => 'book', 'url' => 'library.php?report=1', 'color' => 'secondary'],
                    ['title' => 'Placement Drive', 'icon' => 'briefcase', 'url' => 'placements.php?action=new', 'color' => 'dark']
                ];
                break;

            case 'Faculty':
                // Faculty stats
                $facultyId = $user['faculty_id'] ?? null;

                // Prepare and execute queries properly
                $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE faculty_id = ? AND status = 'Active'");
                $stmt1->execute([$facultyId]);
                $mySubjects = $stmt1->fetchColumn();

                $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM tests WHERE faculty_id = ? AND status = 'Published'");
                $stmt2->execute([$facultyId]);
                $myTests = $stmt2->fetchColumn();

                $stmt3 = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM student_test_grades WHERE faculty_id = ?");
                $stmt3->execute([$facultyId]);
                $studentsGraded = $stmt3->fetchColumn();

                $stmt4 = $pdo->prepare("SELECT COUNT(*) FROM student_test_grades WHERE faculty_id = ? AND status = 'Pending'");
                $stmt4->execute([$facultyId]);
                $pendingGrades = $stmt4->fetchColumn();

                $data['stats'] = [
                    'my_subjects' => $mySubjects,
                    'my_tests' => $myTests,
                    'students_graded' => $studentsGraded,
                    'pending_grades' => $pendingGrades
                ];

                $data['quick_actions'] = [
                    ['title' => 'Create Test', 'icon' => 'clipboard-plus', 'url' => 'tests.php?action=new', 'color' => 'primary'],
                    ['title' => 'Grade Students', 'icon' => 'trophy', 'url' => 'grades.php', 'color' => 'success'],
                    ['title' => 'My Subjects', 'icon' => 'journal-text', 'url' => 'subjects.php?faculty=' . $facultyId, 'color' => 'info'],
                    ['title' => 'Student List', 'icon' => 'people', 'url' => 'students.php?faculty=' . $facultyId, 'color' => 'warning']
                ];
                break;

            case 'Student':
                // Student stats
                $studentId = $user['student_id'] ?? null;

                // Prepare and execute queries properly
                $stmt1 = $pdo->prepare("SELECT COUNT(DISTINCT subject_id) FROM student_test_grades WHERE student_id = ?");
                $stmt1->execute([$studentId]);
                $enrolledSubjects = $stmt1->fetchColumn();

                $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM student_test_grades WHERE student_id = ? AND status = 'Completed'");
                $stmt2->execute([$studentId]);
                $completedTests = $stmt2->fetchColumn();

                $stmt3 = $pdo->prepare("SELECT AVG(percentage) FROM student_test_grades WHERE student_id = ? AND percentage IS NOT NULL");
                $stmt3->execute([$studentId]);
                $averageGrade = $stmt3->fetchColumn();

                $stmt4 = $pdo->prepare("SELECT COUNT(*) FROM fee_payments WHERE student_id = ? AND payment_status = 'Pending'");
                $stmt4->execute([$studentId]);
                $pendingFees = $stmt4->fetchColumn();

                $stmt5 = $pdo->prepare("SELECT COUNT(*) FROM library_transactions WHERE student_id = ? AND status = 'Issued'");
                $stmt5->execute([$studentId]);
                $booksIssued = $stmt5->fetchColumn();

                $data['stats'] = [
                    'enrolled_subjects' => $enrolledSubjects,
                    'completed_tests' => $completedTests,
                    'average_grade' => $averageGrade,
                    'pending_fees' => $pendingFees,
                    'books_issued' => $booksIssued
                ];

                $data['quick_actions'] = [
                    ['title' => 'View Grades', 'icon' => 'trophy', 'url' => 'student-grades.php', 'color' => 'primary'],
                    ['title' => 'My Subjects', 'icon' => 'journal-text', 'url' => 'subjects.php?student=' . $studentId, 'color' => 'success'],
                    ['title' => 'Upcoming Tests', 'icon' => 'clipboard-check', 'url' => 'tests.php?student=' . $studentId, 'color' => 'info'],
                    ['title' => 'Fee Status', 'icon' => 'currency-dollar', 'url' => 'fees.php?student=' . $studentId, 'color' => 'warning'],
                    ['title' => 'Library Books', 'icon' => 'book', 'url' => 'library.php?student=' . $studentId, 'color' => 'secondary']
                ];
                break;
        }

        // Common data for all roles
        $data['recent_activity'] = getRecentActivity($role, $user);
        $data['upcoming_events'] = getUpcomingEvents($role, $user);

    } catch (Exception $e) {
        // Set defaults if database issues
        $data['stats'] = array_fill_keys(array_keys($data['stats'] ?? []), 0);
    }

    return $data;
}

function getRecentActivity($role, $user) {
    global $pdo;

    $activities = [];

    try {
        switch ($role) {
            case 'Management':
            case 'Admin':
                // Recent admissions, fee payments, test results
                $stmt = $pdo->query("
                    SELECT 'New Admission' as type, CONCAT(s.full_name, ' admitted') as description,
                           a.created_at as date
                    FROM admissions a
                    JOIN students s ON a.student_id = s.student_id
                    ORDER BY a.created_at DESC LIMIT 3
                ");
                $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'Faculty':
                // Recent grades entered, tests created
                $facultyId = $user['faculty_id'] ?? null;
                $stmt = $pdo->prepare("
                    SELECT 'Grade Entered' as type,
                           CONCAT('Graded ', COUNT(*), ' students for test') as description,
                           MAX(graded_at) as date
                    FROM student_test_grades
                    WHERE faculty_id = ? AND graded_at IS NOT NULL
                    GROUP BY test_id
                    ORDER BY graded_at DESC LIMIT 3
                ");
                $stmt->execute([$facultyId]);
                $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'Student':
                // Recent grades received, fees paid
                $studentId = $user['student_id'] ?? null;
                if ($studentId) {
                    $stmt = $pdo->prepare("
                        SELECT 'Grade Received' as type,
                               CONCAT('Received grade for ', t.test_name) as description,
                               stg.graded_at as date
                        FROM student_test_grades stg
                        JOIN tests t ON stg.test_id = t.test_id
                        WHERE stg.student_id = ? AND stg.graded_at IS NOT NULL
                        ORDER BY stg.graded_at DESC LIMIT 3
                    ");
                    $stmt->execute([$studentId]);
                    $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
                }
                break;
        }
    } catch (Exception $e) {
        // Return empty array if error
    }

    return array_slice($activities, 0, 5);
}

function getUpcomingEvents($role, $user) {
    global $pdo;

    $events = [];

    try {
        switch ($role) {
            case 'Management':
            case 'Admin':
            case 'Faculty':
                // Upcoming tests
                $stmt = $pdo->query("
                    SELECT CONCAT('Test: ', test_name) as title,
                           test_date as date,
                           CONCAT('Subject: ', s.subject_name) as description
                    FROM tests t
                    JOIN subjects s ON t.subject_id = s.subject_id
                    WHERE t.test_date >= CURDATE() AND t.status = 'Published'
                    ORDER BY t.test_date ASC LIMIT 3
                ");
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'Student':
                // Student's upcoming tests
                $studentId = $user['student_id'] ?? null;
                if ($studentId) {
                    $stmt = $pdo->prepare("
                        SELECT CONCAT('Test: ', t.test_name) as title,
                               t.test_date as date,
                               CONCAT('Subject: ', s.subject_name) as description
                        FROM tests t
                        JOIN subjects s ON t.subject_id = s.subject_id
                        JOIN student_test_grades stg ON t.test_id = stg.test_id
                        WHERE stg.student_id = ? AND t.test_date >= CURDATE() AND t.status = 'Published'
                        ORDER BY t.test_date ASC LIMIT 3
                    ");
                    $stmt->execute([$studentId]);
                    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $events = [];
                }
                break;
        }
    } catch (Exception $e) {
        // Return empty array if error
    }

    return $events;
}

function getRoleNotifications($role, $user) {
    global $pdo;

    $notifications = [];

    try {
        switch ($role) {
            case 'Management':
            case 'Admin':
                // System notifications
                $pendingAdmissions = $pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'Pending'")->fetchColumn();
                if ($pendingAdmissions > 0) {
                    $notifications[] = [
                        'type' => 'warning',
                        'message' => "$pendingAdmissions admission(s) pending approval",
                        'url' => 'admissions.php?status=pending'
                    ];
                }
                break;

            case 'Faculty':
                // Faculty notifications
                $facultyId = $user['faculty_id'] ?? null;
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_test_grades WHERE faculty_id = ? AND status = 'Pending'");
                $stmt->execute([$facultyId]);
                $pendingGrades = $stmt->fetchColumn();
                if ($pendingGrades > 0) {
                    $notifications[] = [
                        'type' => 'info',
                        'message' => "$pendingGrades student grade(s) pending",
                        'url' => 'grades.php'
                    ];
                }
                break;

            case 'Student':
                // Student notifications
                $studentId = $user['student_id'] ?? null;
                if ($studentId) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_payments WHERE student_id = ? AND payment_status = 'Pending'");
                    $stmt->execute([$studentId]);
                    $pendingFees = $stmt->fetchColumn();
                    if ($pendingFees > 0) {
                        $notifications[] = [
                            'type' => 'danger',
                            'message' => "You have $pendingFees pending fee payment(s)",
                            'url' => 'fees.php'
                        ];
                    }
                }

                // Upcoming tests
                $stmt2 = $pdo->prepare("
                    SELECT COUNT(*) FROM tests t
                    JOIN student_test_grades stg ON t.test_id = stg.test_id
                    WHERE stg.student_id = ? AND t.test_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ");
                $stmt2->execute([$studentId]);
                $upcomingTests = $stmt2->fetchColumn();
                if ($upcomingTests > 0) {
                    $notifications[] = [
                        'type' => 'info',
                        'message' => "$upcomingTests test(s) scheduled this week",
                        'url' => 'tests.php'
                    ];
                }
                break;
        }
    } catch (Exception $e) {
        // Return empty array if error
    }

    return $notifications;
}

function getDashboardAnnouncements($user) {
    global $pdo;

    try {
        $userRole = $user['role_name'] ?? 'Student';
        
        // Map role_name to target_audience enum values
        $audienceMapping = [
            'Student' => 'Students',
            'Faculty' => 'Faculty', 
            'Admin' => 'Admin',
            'Administrative Staff' => 'Admin'  // Staff can see Admin announcements
        ];
        $targetAudience = $audienceMapping[$userRole] ?? $userRole;
        
        $currentTime = date('Y-m-d H:i:s');
        
        // Get announcements based on user role
        $stmt = $pdo->prepare("
            SELECT 
                a.announcement_id,
                a.title,
                a.content,
                a.priority,
                a.target_audience,
                a.created_at,
                COALESCE(s.full_name, f.full_name, u.username) as created_by_name
            FROM announcements a
            LEFT JOIN users u ON a.posted_by = u.user_id
            LEFT JOIN students s ON u.user_id = s.user_id
            LEFT JOIN faculty f ON u.user_id = f.user_id
            WHERE a.is_active = 1 
                AND (a.expires_at IS NULL OR a.expires_at > ?)
                AND (a.target_audience = 'All' 
                     OR a.target_audience = ?)
            ORDER BY 
                CASE a.priority 
                    WHEN 'Critical' THEN 1 
                    WHEN 'High' THEN 2 
                    WHEN 'Medium' THEN 3 
                    ELSE 4 
                END,
                a.created_at DESC
            LIMIT 10
        ");
        
        $stmt->execute([$currentTime, $targetAudience]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        return [];
    }
}

function getPendingActions($role, $user) {
    global $pdo;

    $actions = [];

    try {
        switch ($role) {
            case 'Management':
            case 'Admin':
                $stmt1 = $pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'Pending'");
                $pendingAdmissions = $stmt1->fetchColumn();

                $stmt2 = $pdo->query("SELECT COUNT(*) FROM fee_payments WHERE payment_status = 'Pending'");
                $pendingFees = $stmt2->fetchColumn();

                $stmt3 = $pdo->query("SELECT COUNT(*) FROM library_transactions WHERE status = 'Issued' AND due_date < CURDATE()");
                $overdueBooks = $stmt3->fetchColumn();

                $actions = [
                    ['title' => 'Review Admissions', 'count' => $pendingAdmissions, 'url' => 'admissions.php?status=pending', 'icon' => 'file-earmark-text'],
                    ['title' => 'Fee Collections', 'count' => $pendingFees, 'url' => 'fees.php?status=pending', 'icon' => 'currency-dollar'],
                    ['title' => 'Library Returns', 'count' => $overdueBooks, 'url' => 'library.php?overdue=1', 'icon' => 'book']
                ];
                break;

            case 'Faculty':
                $facultyId = $user['faculty_id'] ?? null;
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_test_grades WHERE faculty_id = ? AND status = 'Pending'");
                $stmt->execute([$facultyId]);
                $pendingGrades = $stmt->fetchColumn();

                $actions = [
                    ['title' => 'Grade Students', 'count' => $pendingGrades, 'url' => 'grades.php', 'icon' => 'trophy'],
                    ['title' => 'Create Tests', 'count' => 0, 'url' => 'tests.php?action=new', 'icon' => 'clipboard-plus']
                ];
                break;

            case 'Student':
                $studentId = $user['student_id'] ?? null;
                $pendingFees = 0;
                $overdueBooks = 0;

                if ($studentId) {
                    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM fee_payments WHERE student_id = ? AND payment_status = 'Pending'");
                    $stmt1->execute([$studentId]);
                    $pendingFees = $stmt1->fetchColumn();

                    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM library_transactions WHERE student_id = ? AND status = 'Issued' AND due_date < CURDATE()");
                    $stmt2->execute([$studentId]);
                    $overdueBooks = $stmt2->fetchColumn();
                }

                $actions = [
                    ['title' => 'Pay Fees', 'count' => $pendingFees, 'url' => 'fees.php', 'icon' => 'currency-dollar'],
                    ['title' => 'Return Books', 'count' => $overdueBooks, 'url' => 'library.php', 'icon' => 'book']
                ];
                break;
        }
    } catch (Exception $e) {
        // Return empty array if error
    }

    return array_filter($actions, function($action) {
        return $action['count'] > 0 || $action['count'] === 0; // Include all actions, even with 0 count for some
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #43e97b;
            --warning-color: #fa709a;
            --info-color: #4facfe;
            --danger-color: #f5576c;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
            position: relative;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .stat-card .card-body {
            padding: 1.5rem;
        }

        .stat-card .stat-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 2rem;
            opacity: 0.3;
        }

        .stat-card.students { border-left: 4px solid var(--primary-color); }
        .stat-card.faculty { border-left: 4px solid var(--success-color); }
        .stat-card.subjects { border-left: 4px solid var(--info-color); }
        .stat-card.tests { border-left: 4px solid var(--warning-color); }
        .stat-card.admissions { border-left: 4px solid var(--danger-color); }
        .stat-card.fees { border-left: 4px solid #6f42c1; }
        .stat-card.library { border-left: 4px solid #20c997; }
        .stat-card.placements { border-left: 4px solid #fd7e14; }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .activity-card, .events-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            height: 100%;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quick-action-btn {
            background: white;
            border: none;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: 100%;
        }

        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .search-container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .search-input {
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
        }

        .search-input:focus {
            box-shadow: none;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .action-center-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .pending-action {
            padding: 1rem;
            border-bottom: 1px solid #f8f9fa;
            transition: background-color 0.3s ease;
        }

        .pending-action:hover {
            background-color: #f8f9fa;
        }

        .pending-action:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .stat-card .stat-icon {
                font-size: 1.5rem;
                top: 15px;
                right: 15px;
            }

            .quick-action-btn {
                margin-bottom: 1rem;
            }
        }

        .role-badge {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .announcements-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: none;
            overflow: hidden;
        }

        .announcement-item {
            transition: background-color 0.3s ease;
        }

        .announcement-item:hover {
            background-color: #f8f9fa;
        }

        .announcement-item:last-child {
            border-bottom: none !important;
        }

        .badge-sm {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        .announcements-card .card-header {
            background: linear-gradient(135deg, var(--info-color), #00d2ff);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
        }

        .announcements-card .card-header .btn-outline-info {
            color: white;
            border-color: rgba(255,255,255,0.5);
        }

        .announcements-card .card-header .btn-outline-info:hover {
            background-color: rgba(255,255,255,0.2);
            border-color: white;
        }

        .announcement-content {
            color: #6c757d;
            line-height: 1.4;
        }
        .announcement-content p {
            margin-bottom: 0.5rem;
        }
        .announcement-content p:last-child {
            margin-bottom: 0;
        }
        .announcement-content ul, .announcement-content ol {
            padding-left: 1.2rem;
            margin-bottom: 0.5rem;
        }
        .announcement-content li {
            margin-bottom: 0.2rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <i class="bi bi-mortarboard-fill me-2"></i>
                College Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>

                    <?php if (hasRole(['Administrative Staff', 'Admin'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Administration
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="admissions.php"><i class="bi bi-file-earmark-text"></i> Admissions</a></li>
                            <li><a class="dropdown-item" href="fees.php"><i class="bi bi-currency-dollar"></i> Fees</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if (canAccessModule('courses') || canAccessModule('subjects') || canAccessModule('tests') || canAccessModule('grades') || canAccessModule('students') || canAccessModule('faculty')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="academicDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-mortarboard"></i> Academic
                        </a>
                        <ul class="dropdown-menu">
                            <?php if (canAccessModule('students')): ?>
                            <li><a class="dropdown-item" href="students.php"><i class="bi bi-people"></i> Students</a></li>
                            <?php endif; ?>
                            <?php if (canAccessModule('faculty')): ?>
                            <li><a class="dropdown-item" href="faculty.php"><i class="bi bi-person-badge"></i> Faculty</a></li>
                            <?php endif; ?>
                            <?php if (canAccessModule('courses')): ?>
                            <li><a class="dropdown-item" href="courses.php"><i class="bi bi-mortarboard-fill"></i> Courses</a></li>
                            <?php endif; ?>
                            <?php if (canAccessModule('subjects')): ?>
                            <li><a class="dropdown-item" href="subjects.php"><i class="bi bi-journal-text"></i> Subjects</a></li>
                            <?php endif; ?>
                            <?php if (canAccessModule('tests')): ?>
                            <li><a class="dropdown-item" href="tests.php"><i class="bi bi-clipboard-check"></i> Tests</a></li>
                            <?php endif; ?>
                            <?php if (canAccessModule('grades')): ?>
                            <li><a class="dropdown-item" href="grades.php"><i class="bi bi-graph-up"></i> Grades</a></li>
                            <?php endif; ?>
                            <?php if (canAccessModule('papers')): ?>
                            <li><a class="dropdown-item" href="faculty_papers.php"><i class="bi bi-file-earmark-text"></i> Papers & Publications</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if (canAccessModule('library')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="library.php">
                            <i class="bi bi-book"></i> Library
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (canAccessModule('placements')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="placements.php">
                            <i class="bi bi-briefcase"></i> Placements
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (hasRole(['Student'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="student-grades.php">
                            <i class="bi bi-award"></i> My Grades
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="fees.php">
                            <i class="bi bi-currency-dollar"></i> Fees
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="library.php">
                            <i class="bi bi-book"></i> Library
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <!-- Notifications -->
                    <?php if (!empty($notifications)): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <span class="notification-badge"><?php echo count($notifications); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php foreach ($notifications as $notification): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-start" href="<?php echo $notification['url']; ?>">
                                    <div class="me-2 mt-1">
                                        <i class="bi bi-<?php
                                            echo $notification['type'] === 'danger' ? 'exclamation-triangle' :
                                                 ($notification['type'] === 'warning' ? 'exclamation-circle' :
                                                 ($notification['type'] === 'info' ? 'info-circle' : 'check-circle'));
                                        ?> text-<?php echo $notification['type']; ?>"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted"><?php echo $notification['message']; ?></small>
                                    </div>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo escape($user['full_name'] ?? $user['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header"><?php echo escape($userRole); ?></h6></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 px-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-card p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">Welcome back, <?php echo escape($user['full_name'] ?? $user['username']); ?>!</h2>
                            <p class="mb-0 opacity-75">Here's what's happening in your college management system today.</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="role-badge"><?php echo $userRole; ?> Dashboard</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Search -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="search-container">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="search-input form-control" placeholder="Search students, faculty, subjects, books, companies..." id="globalSearch">
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <?php foreach ($dashboardData['stats'] as $key => $value): ?>
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="stat-card <?php echo $key; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1"><?php echo number_format($value ?? 0); ?></h3>
                                <p class="text-muted mb-0"><?php echo ucwords(str_replace('_', ' ', $key)); ?></p>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-<?php
                                    echo strpos($key, 'student') !== false ? 'people' :
                                         (strpos($key, 'faculty') !== false ? 'person-badge' :
                                         (strpos($key, 'subject') !== false ? 'journal-text' :
                                         (strpos($key, 'test') !== false ? 'clipboard-check' :
                                         (strpos($key, 'admission') !== false ? 'file-earmark-text' :
                                         (strpos($key, 'fee') !== false ? 'currency-dollar' :
                                         (strpos($key, 'library') !== false ? 'book' :
                                         (strpos($key, 'placement') !== false ? 'briefcase' : 'graph-up')))))));
                                ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Quick Actions -->
        <?php if (!empty($dashboardData['quick_actions'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3">Quick Actions</h5>
                <div class="row">
                    <?php foreach ($dashboardData['quick_actions'] as $action): ?>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                        <a href="<?php echo $action['url']; ?>" class="text-decoration-none">
                            <div class="quick-action-btn">
                                <i class="bi bi-<?php echo $action['icon']; ?> text-<?php echo $action['color']; ?> mb-2" style="font-size: 2rem;"></i>
                                <h6 class="mb-0"><?php echo $action['title']; ?></h6>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Announcements Section -->
        <?php 
        // Debug: Show announcement count for troubleshooting
        if (isset($_GET['debug'])) {
            echo "<div class='alert alert-info'>Debug: Found " . count($dashboardAnnouncements) . " announcements for user role: " . ($user['role_name'] ?? 'Unknown') . "</div>";
        }
        ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="announcements-card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-megaphone me-2 text-info"></i>
                            <h5 class="mb-0">Announcements</h5>
                        </div>
                        <?php if (hasRole(['Admin', 'Administrative Staff', 'Faculty'])): ?>
                        <button class="btn btn-sm btn-outline-light" onclick="showCreateAnnouncementModal()">
                            <i class="bi bi-plus-circle"></i> Create
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($dashboardAnnouncements)): ?>
                            <?php foreach ($dashboardAnnouncements as $announcement): ?>
                        <div class="announcement-item border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="mb-0 me-2"><?php echo escape($announcement['title']); ?></h6>
                                        <span class="badge bg-<?php 
                                            echo $announcement['priority'] === 'Critical' ? 'danger' : 
                                                ($announcement['priority'] === 'High' ? 'warning' : 
                                                ($announcement['priority'] === 'Medium' ? 'primary' : 'secondary')); 
                                        ?> badge-sm">
                                            <?php echo ucfirst($announcement['priority']); ?>
                                        </span>
                                        <span class="badge bg-light text-dark badge-sm ms-1">
                                            <?php echo $announcement['target_audience']; ?>
                                        </span>
                                    </div>
                                    <div class="announcement-content mb-2 small">
                                        <?php 
                                        // Allow basic formatting tags but sanitize for security
                                        $allowed_tags = '<p><br><strong><b><em><i><u><ul><ol><li><a>';
                                        $content = strip_tags($announcement['content'], $allowed_tags);
                                        
                                        // Truncate if too long
                                        if (strlen(strip_tags($content)) > 200) {
                                            // Find a good break point
                                            $plain_text = strip_tags($content);
                                            $truncated = substr($plain_text, 0, 200);
                                            $last_space = strrpos($truncated, ' ');
                                            if ($last_space !== false) {
                                                $truncated = substr($truncated, 0, $last_space);
                                            }
                                            echo $truncated . '...';
                                        } else {
                                            echo $content;
                                        }
                                        ?>
                                    </div>
                                    <div class="d-flex align-items-center text-muted small">
                                        <i class="bi bi-person me-1"></i>
                                        <span class="me-3"><?php echo escape($announcement['created_by_name'] ?? 'System'); ?></span>
                                        <i class="bi bi-clock me-1"></i>
                                        <span><?php echo formatDateTime($announcement['created_at']); ?></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <?php if ($announcement['priority'] === 'Critical'): ?>
                                    <i class="bi bi-exclamation-triangle-fill text-danger me-2" style="font-size: 1.2rem;"></i>
                                    <?php endif; ?>
                                    <?php if (hasRole(['Admin', 'Administrative Staff', 'Faculty'])): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <button class="dropdown-item" onclick="toggleAnnouncementStatus(<?php echo $announcement['announcement_id']; ?>, false)">
                                                    <i class="bi bi-eye-slash"></i> Hide
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger" onclick="deleteAnnouncement(<?php echo $announcement['announcement_id']; ?>)">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-megaphone text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 text-muted">No Announcements Yet</h5>
                                <p class="text-muted mb-3">Stay tuned for important updates and news.</p>
                                <?php if (hasRole(['Admin', 'Administrative Staff', 'Faculty'])): ?>
                                <button class="btn btn-primary" onclick="showCreateAnnouncementModal()">
                                    <i class="bi bi-plus-circle"></i> Create First Announcement
                                </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="row">
            <!-- Action Center -->
            <?php if (!empty($pendingActions)): ?>
            <div class="col-xl-4 col-lg-5 mb-4">
                <div class="action-center-card">
                    <div class="card-header bg-primary text-white d-flex align-items-center">
                        <i class="bi bi-check-circle me-2"></i>
                        <h5 class="mb-0">Action Center</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($pendingActions as $action): ?>
                        <a href="<?php echo $action['url']; ?>" class="pending-action text-decoration-none d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-<?php echo $action['icon']; ?> text-primary" style="font-size: 1.5rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?php echo $action['title']; ?></div>
                                <small class="text-muted">
                                    <?php if ($action['count'] > 0): ?>
                                        <?php echo $action['count']; ?> pending
                                    <?php else: ?>
                                        Take action
                                    <?php endif; ?>
                                </small>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Activity & Upcoming Events -->
            <div class="col-xl-<?php echo !empty($pendingActions) ? '8' : '12'; ?> col-lg-<?php echo !empty($pendingActions) ? '7' : '12'; ?>">
                <div class="row">
                    <!-- Recent Activity -->
                    <div class="col-lg-6 mb-4">
                        <div class="activity-card">
                            <div class="card-header d-flex align-items-center">
                                <i class="bi bi-activity me-2 text-primary"></i>
                                <h5 class="mb-0">Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($dashboardData['recent_activity'])): ?>
                                    <?php foreach ($dashboardData['recent_activity'] as $activity): ?>
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-<?php
                                                    echo $activity['type'] === 'New Admission' ? 'person-plus' :
                                                         ($activity['type'] === 'Grade Entered' ? 'trophy' :
                                                         ($activity['type'] === 'Grade Received' ? 'award' :
                                                         ($activity['type'] === 'Fee Paid' ? 'currency-dollar' : 'circle')));
                                                ?> text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold small"><?php echo $activity['type']; ?></div>
                                            <div class="text-muted small"><?php echo $activity['description']; ?></div>
                                            <div class="text-muted small"><?php echo $activity['date'] ? formatDate($activity['date']) : 'Recent'; ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-activity" style="font-size: 3rem;"></i>
                                        <p class="mt-2">No recent activity</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Events -->
                    <div class="col-lg-6 mb-4">
                        <div class="events-card">
                            <div class="card-header d-flex align-items-center">
                                <i class="bi bi-calendar-event me-2 text-success"></i>
                                <h5 class="mb-0">Upcoming Events</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($dashboardData['upcoming_events'])): ?>
                                    <?php foreach ($dashboardData['upcoming_events'] as $event): ?>
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-calendar-event text-success"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold small"><?php echo $event['title']; ?></div>
                                            <div class="text-muted small"><?php echo $event['description']; ?></div>
                                            <div class="text-muted small"><?php echo formatDate($event['date']); ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-calendar-event" style="font-size: 3rem;"></i>
                                        <p class="mt-2">No upcoming events</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Charts (for Admin/Management) -->
        <?php if (hasRole(['Management', 'Admin'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="chart-container">
                    <h5 class="mb-4">Analytics Overview</h5>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <canvas id="enrollmentChart" width="400" height="200"></canvas>
                        </div>
                        <div class="col-md-6 mb-4">
                            <canvas id="performanceChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>

    <script>
        // Global Search Functionality with Real-time Search
        let searchTimeout;

        // Real-time search as user types (debounced)
        document.getElementById('globalSearch').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();

            // Only search if query is at least 2 characters
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300); // 300ms delay to avoid too many requests
            }
        });

        // Search on Enter key (immediate)
        document.getElementById('globalSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout); // Cancel any pending debounced search
                const query = e.target.value.trim();
                if (query) {
                    performSearch(query);
                }
            }
        });

        function performSearch(query) {
            if (query && query.length >= 2) {
                // Redirect to search results page
                window.location.href = `search.php?q=${encodeURIComponent(query)}`;
            }
        }

        // Analytics Charts (for Admin/Management)
        <?php if (hasRole(['Management', 'Admin'])): ?>
        // Enrollment Chart
        const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
        new Chart(enrollmentCtx, {
            type: 'doughnut',
            data: {
                labels: ['Students', 'Faculty', 'Staff'],
                datasets: [{
                    data: [
                        <?php echo $dashboardData['stats']['total_students'] ?? 0; ?>,
                        <?php echo $dashboardData['stats']['total_faculty'] ?? 0; ?>,
                        10 // Placeholder for staff
                    ],
                    backgroundColor: [
                        '#667eea',
                        '#43e97b',
                        '#fa709a'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'College Population'
                    }
                }
            }
        });

        // Performance Chart
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        new Chart(performanceCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Admissions',
                    data: [12, 19, 15, 25, 22, 30],
                    backgroundColor: '#4facfe'
                }, {
                    label: 'Placements',
                    data: [8, 12, 10, 18, 15, 22],
                    backgroundColor: '#43e97b'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'Monthly Performance'
                    }
                }
            }
        });
        <?php endif; ?>

        // Auto-refresh notifications every 5 minutes
        setInterval(function() {
            // You can implement AJAX call here to refresh notifications
        }, 300000);

        // Announcement creation modal functions
        function showCreateAnnouncementModal() {
            $('#createAnnouncementModal').modal('show');
        }

        // Initialize modal when shown
        $('#createAnnouncementModal').on('shown.bs.modal', function () {
            // Focus on title field
            setTimeout(function() {
                document.getElementById('announcementTitle').focus();
                updateCharCount();
            }, 300);
        });

        // Clean up when modal is hidden
        $('#createAnnouncementModal').on('hidden.bs.modal', function () {
            // Clear any error messages
            document.getElementById('announcementError').innerHTML = '';
            // Reset form
            document.getElementById('createAnnouncementForm').reset();
            updateCharCount();
        });

        // Submit announcement via AJAX
        function submitAnnouncement() {
            const form = document.getElementById('createAnnouncementForm');
            const formData = new FormData(form);

            // Get content from textarea
            const content = formData.get('content');

            // Validate required fields
            const title = formData.get('title');

            if (!title || title.trim() === '') {
                document.getElementById('announcementError').innerHTML =
                    '<div class="alert alert-danger">Title is required</div>';
                return;
            }

            if (!content || content.trim() === '') {
                document.getElementById('announcementError').innerHTML =
                    '<div class="alert alert-danger">Content is required</div>';
                return;
            }

            // Show loading state
            const submitBtn = document.querySelector('#createAnnouncementModal .btn-primary');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Creating...';
            submitBtn.disabled = true;

            fetch('ajax/create_announcement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#createAnnouncementModal').modal('hide');
                    // Reset form
                    form.reset();
                    updateCharCount();
                    location.reload(); // Refresh to show new announcement
                } else {
                    document.getElementById('announcementError').innerHTML =
                        '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('announcementError').innerHTML =
                    '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            })
            .finally(() => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }        // Character counter for announcement content
        function updateCharCount() {
            const textarea = document.getElementById('announcementContent');
            const counter = document.getElementById('charCount');
            const remaining = 1000 - textarea.value.length;
            counter.textContent = remaining;
            counter.className = remaining < 100 ? 'text-danger' : (remaining < 200 ? 'text-warning' : 'text-muted');
        }

        // Toggle announcement active status
        function toggleAnnouncementStatus(id, status) {
            if (confirm(status ? 'Show this announcement?' : 'Hide this announcement?')) {
                fetch('ajax/toggle_announcement.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'announcement_id=' + id + '&status=' + (status ? 1 : 0)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        }

        // Delete announcement
        function deleteAnnouncement(id) {
            if (confirm('Are you sure you want to delete this announcement? This action cannot be undone.')) {
                fetch('ajax/delete_announcement.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'announcement_id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        }
    </script>

    <!-- Create Announcement Modal -->
    <?php if (hasRole(['Admin', 'Administrative Staff', 'Faculty'])): ?>
    <div class="modal fade" id="createAnnouncementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-megaphone me-2"></i>Create Announcement
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="announcementError"></div>
                    <form id="createAnnouncementForm">
                        <div class="mb-3">
                            <label for="announcementTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="announcementTitle" name="title" required maxlength="200">
                        </div>
                        <div class="mb-3">
                            <label for="announcementContent" class="form-label">Content</label>
                            <textarea class="form-control" id="announcementContent" name="content" rows="4" required oninput="updateCharCount()"></textarea>
                            <small class="text-muted">
                                <span id="charCount">1000</span> characters remaining
                            </small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="announcementPriority" class="form-label">Priority</label>
                                <select class="form-select" id="announcementPriority" name="priority" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="announcementAudience" class="form-label">Target Audience</label>
                                <select class="form-select" id="announcementAudience" name="target_audience" required>
                                    <option value="All" selected>All Users</option>
                                    <option value="Students">Students Only</option>
                                    <option value="Faculty">Faculty Only</option>
                                    <?php if (hasRole(['Admin', 'Administrative Staff'])): ?>
                                    <option value="Admin">Administrators Only</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label for="announcementExpiry" class="form-label">Expiry Date (Optional)</label>
                            <input type="datetime-local" class="form-control" id="announcementExpiry" name="expires_at">
                            <small class="text-muted">Leave empty for no expiration</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitAnnouncement()">
                        <i class="bi bi-check-circle"></i> Create Announcement
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>