<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);
$notifications = getNotifications($userRole, $user);

// Handle delete test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_test') {
    $test_id = $_POST['test_id'] ?? 0;
    
    // Delete test and related grades (cascade)
    $stmt = $pdo->prepare("DELETE FROM tests WHERE test_id = ?");
    $stmt->execute([$test_id]);
    $_SESSION['message'] = 'Test deleted successfully';
    redirect('/tests.php');
}

// Handle publish/unpublish test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_publish') {
    $test_id = $_POST['test_id'] ?? 0;
    $is_published = $_POST['is_published'] ?? 0;
    
    $stmt = $pdo->prepare("UPDATE tests SET is_published = ? WHERE test_id = ?");
    $stmt->execute([!$is_published, $test_id]);
    
    $status = !$is_published ? 'published' : 'unpublished';
    $_SESSION['message'] = "Test {$status} successfully";
    redirect('/tests.php');
}

// Handle add/edit test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_test') {
    $data = [
        'test_name' => $_POST['test_name'] ?? '',
        'subject_id' => $_POST['subject_id'] ?? null,
        'test_type' => $_POST['test_type'] ?? 'Quiz',
        'faculty_id' => $_POST['faculty_id'] ?? null,
        'test_date' => $_POST['test_date'] ?? null,
        'duration' => $_POST['duration'] ?? 60,
        'max_marks' => $_POST['max_marks'] ?? 100,
        'instructions' => $_POST['instructions'] ?? '',
    ];
    
    if (isset($_POST['test_id']) && $_POST['test_id']) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE tests SET 
                test_name = ?,
                subject_id = ?,
                faculty_id = ?,
                test_type = ?,
                test_date = ?,
                duration = ?,
                max_marks = ?,
                instructions = ?,
                status = ?
            WHERE test_id = ?
        ");
        $stmt->execute([
            $data['test_name'],
            $data['subject_id'],
            $data['faculty_id'],
            $data['test_type'],
            $data['test_date'],
            $data['duration'],
            $data['max_marks'],
            $data['instructions'],
            isset($_POST['is_published']) ? 'Published' : 'Draft',
            $_POST['test_id']
        ]);
        $_SESSION['message'] = 'Test updated successfully';
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO tests (test_name, subject_id, faculty_id, test_type, 
                             test_date, duration, max_marks, instructions, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['test_name'],
            $data['subject_id'],
            $data['faculty_id'],
            $data['test_type'],
            $data['test_date'],
            $data['duration'],
            $data['max_marks'],
            $data['instructions'],
            isset($_POST['is_published']) ? 'Published' : 'Draft'
        ]);
        $_SESSION['message'] = 'Test created successfully';
    }
    redirect('/tests.php');
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$subject_filter = $_GET['subject'] ?? '';
$batch_filter = $_GET['batch'] ?? '';
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$faculty_filter = $_GET['faculty'] ?? '';

// Tests query
$tests_query = "
    SELECT t.*, s.subject_code, s.subject_name, t.test_type as type_name, f.full_name as faculty_name, 
           s.semester, s.credits,
           COALESCE(grade_stats.students_graded, 0) as students_graded,
           COALESCE(grade_stats.total_students, 0) as total_students,
           COALESCE(grade_stats.avg_marks, 0) as avg_marks
    FROM tests t
    JOIN subjects s ON t.subject_id = s.subject_id
    JOIN faculty f ON t.faculty_id = f.faculty_id
    LEFT JOIN (
        SELECT stg.test_id, 
               COUNT(*) as students_graded,
               (SELECT COUNT(*) FROM students WHERE academic_status = 'Active') as total_students,
               AVG(stg.marks_obtained) as avg_marks
        FROM student_test_grades stg
        GROUP BY stg.test_id
    ) grade_stats ON t.test_id = grade_stats.test_id
    WHERE 1=1
";
$tests_params = []; // No batch filter needed

if ($search) {
    $tests_query .= " AND (t.test_name LIKE ? OR s.subject_name LIKE ? OR f.full_name LIKE ?)";
    $searchParam = "%$search%";
    $tests_params[] = $searchParam;
    $tests_params[] = $searchParam;
    $tests_params[] = $searchParam;
}

if ($subject_filter) {
    $tests_query .= " AND t.subject_id = ?";
    $tests_params[] = $subject_filter;
}

// Batch filter removed for simplified structure

if ($type_filter) {
    $tests_query .= " AND t.test_type = ?";
    $tests_params[] = $type_filter;
}

if ($status_filter === 'published') {
    $tests_query .= " AND t.is_published = 1";
} elseif ($status_filter === 'unpublished') {
    $tests_query .= " AND t.is_published = 0";
}

if ($faculty_filter) {
    $tests_query .= " AND t.faculty_id = ?";
    $tests_params[] = $faculty_filter;
}

$tests_query .= " ORDER BY t.test_date DESC, t.created_date DESC";

try {
    // Execute tests query
    $stmt = $pdo->prepare($tests_query);
    $stmt->execute($tests_params);
    $tests = $stmt->fetchAll();
    
    // Get subjects for dropdowns
    $stmt = $pdo->prepare("
        SELECT s.*, c.course_name 
        FROM subjects s 
        LEFT JOIN courses c ON s.course_id = c.course_id 
        WHERE s.status = 'Active' 
        ORDER BY c.course_name, s.subject_name
    ");
    $stmt->execute();
    $subjects = $stmt->fetchAll();
    
    // Define test types array (no separate table needed)
    $test_types = [
        ['type_id' => 'Quiz', 'type_name' => 'Quiz'],
        ['type_id' => 'Midterm', 'type_name' => 'Midterm Exam'],
        ['type_id' => 'Final', 'type_name' => 'Final Exam'],
        ['type_id' => 'Assignment', 'type_name' => 'Assignment'],
        ['type_id' => 'Practical', 'type_name' => 'Practical/Lab'],
        ['type_id' => 'Test', 'type_name' => 'Test'],
        ['type_id' => 'Assessment', 'type_name' => 'Assessment']
    ];
    
    // Get faculty
    $stmt = $pdo->prepare("SELECT * FROM faculty ORDER BY full_name");
    $stmt->execute();
    $faculty_list = $stmt->fetchAll();
    
    // Batches not needed in simplified structure
    
    // Calculate summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_tests,
            COUNT(CASE WHEN is_published = 1 THEN 1 END) as published_tests,
            COUNT(CASE WHEN test_date >= CURDATE() THEN 1 END) as upcoming_tests,
            COUNT(CASE WHEN test_date < CURDATE() THEN 1 END) as completed_tests
        FROM tests
    ");
    $stmt->execute();
    $summary = $stmt->fetch();
    
} catch (Exception $e) {
    $tests = [];
    $subjects = [];
    $test_types = [
        ['type_id' => 'Quiz', 'type_name' => 'Quiz'],
        ['type_id' => 'Midterm', 'type_name' => 'Midterm Exam'],
        ['type_id' => 'Final', 'type_name' => 'Final Exam'],
        ['type_id' => 'Assignment', 'type_name' => 'Assignment'],
        ['type_id' => 'Practical', 'type_name' => 'Practical/Lab']
    ];
    $faculty_list = [];
    // Batches not needed
    $summary = ['total_tests' => 0, 'published_tests' => 0, 'upcoming_tests' => 0, 'completed_tests' => 0];
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tests Management - College Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body class="bg-light">
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
                        <a class="nav-link" href="dashboard.php">
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

                    <?php if (canAccessModule('subjects') || canAccessModule('tests') || canAccessModule('grades') || canAccessModule('students') || canAccessModule('faculty')): ?>
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

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-3">
                    <i class="bi bi-clipboard-check me-2"></i>Tests Management
                </h1>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal">
                    <i class="bi bi-plus"></i> Create Test
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Tests</h6>
                                <h4><?= number_format($summary['total_tests'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-clipboard-check" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Published</h6>
                                <h4><?= number_format($summary['published_tests'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-eye" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Upcoming</h6>
                                <h4><?= number_format($summary['upcoming_tests'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-clock" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Completed</h6>
                                <h4><?= number_format($summary['completed_tests'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-check-circle" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="searchForm">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" id="searchInput"
                               placeholder="Search tests, subjects, faculty..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Subject</label>
                        <select name="subject" class="form-select" id="subjectSelect">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['subject_id'] ?>" <?= $subject_filter == $subject['subject_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['subject_code']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" id="typeSelect">
                            <option value="">All Types</option>
                            <?php foreach ($test_types as $type): ?>
                                <option value="<?= $type['type_id'] ?>" <?= $type_filter == $type['type_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['type_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="unpublished" <?= $status_filter === 'unpublished' ? 'selected' : '' ?>>Unpublished</option>
                        </select>
                    </div>
                    <!-- Batch filter removed for simplified structure -->
                    <div class="col-md-1 d-flex align-items-end">
                        <a href="tests.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tests Table -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($tests)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard-check text-muted" style="font-size: 4rem;"></i>
                        <h5 class="mt-3 text-muted">No Tests Found</h5>
                        <p class="text-muted">Start by creating tests for your subjects.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal">
                            <i class="bi bi-plus"></i> Create Test
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Test Details</th>
                                    <th>Subject Details</th>
                                    <th>Date & Duration</th>
                                    <th>Marks</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tests as $test): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($test['test_name']) ?></strong><br>
                                                <small class="text-muted">
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($test['type_name']) ?></span>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <?= htmlspecialchars($test['subject_code']) ?><br>
                                                <small class="text-muted">Semester <?= $test['semester'] ?> • <?= $test['credits'] ?> credits</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <?= date('M j, Y', strtotime($test['test_date'])) ?><br>
                                                <small class="text-muted"><?= $test['duration'] ?> mins</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= $test['max_marks'] ?> marks</strong><br>
                                                <small class="text-muted"><?= $test['duration'] ?> minutes</small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($test['total_students'] > 0): ?>
                                                <div class="progress mb-1" style="height: 10px;">
                                                    <?php 
                                                    $progress = ($test['students_graded'] / $test['total_students']) * 100;
                                                    ?>
                                                    <div class="progress-bar" style="width: <?= $progress ?>%"></div>
                                                </div>
                                                <small class="text-muted">
                                                    <?= $test['students_graded'] ?>/<?= $test['total_students'] ?> graded
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">No students</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $test['is_published'] ? 'bg-success' : 'bg-warning' ?>">
                                                <?= $test['is_published'] ? 'Published' : 'Draft' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="editTest(<?= htmlspecialchars(json_encode($test)) ?>)"
                                                        title="Edit Test">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="grades.php?test_id=<?= $test['test_id'] ?>" 
                                                   class="btn btn-outline-success"
                                                   title="Manage Grades">
                                                    <i class="bi bi-trophy"></i>
                                                </a>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_publish">
                                                    <input type="hidden" name="test_id" value="<?= $test['test_id'] ?>">
                                                    <input type="hidden" name="is_published" value="<?= $test['is_published'] ?>">
                                                    <button type="submit" class="btn btn-outline-info"
                                                            title="<?= $test['is_published'] ? 'Unpublish' : 'Publish' ?>">
                                                        <i class="bi bi-<?= $test['is_published'] ? 'eye-slash' : 'eye' ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_test">
                                                    <input type="hidden" name="test_id" value="<?= $test['test_id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to delete this test? This will also delete all grades.')"
                                                            title="Delete Test">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Test Modal -->
    <div class="modal fade" id="testModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testModalTitle">Create Test</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="testForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_test">
                        <input type="hidden" name="test_id" id="testId">
                        
                        <div class="mb-3">
                            <label class="form-label">Test Title *</label>
                            <input type="text" name="test_name" id="testTitle" class="form-control" 
                                   placeholder="e.g., Mid-term Examination - Computer Science" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Subject *</label>
                                    <select name="subject_id" id="subjectId" class="form-select" required>
                                        <option value="">Select Subject</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?= $subject['subject_id'] ?>">
                                                <?= htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Test Type *</label>
                                    <select name="test_type" id="testTypeId" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <?php foreach ($test_types as $type): ?>
                                            <option value="<?= $type['type_id'] ?>">
                                                <?= htmlspecialchars($type['type_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Batch field removed for simplified structure -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Faculty *</label>
                                    <select name="faculty_id" id="facultyId" class="form-select" required>
                                        <option value="">Select Faculty</option>
                                        <?php foreach ($faculty_list as $faculty): ?>
                                            <option value="<?= $faculty['faculty_id'] ?>">
                                                <?= htmlspecialchars($faculty['full_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Test Date *</label>
                                    <input type="date" name="test_date" id="testDate" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Duration (minutes)</label>
                                    <input type="number" name="duration" id="durationMinutes" 
                                           class="form-control" min="15" max="480" value="60">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Total Marks</label>
                                    <input type="number" name="max_marks" id="totalMarks" 
                                           class="form-control" min="1" max="1000" value="100" step="0.01">
                                </div>
                            </div>
                        </div>

                        <!-- Passing marks field removed for simplified structure -->

                        <div class="mb-3">
                            <label class="form-label">Instructions</label>
                            <textarea name="instructions" id="instructions" class="form-control" rows="3"
                                      placeholder="Special instructions for students..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Test</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editTest(test) {
            document.getElementById('testModalTitle').textContent = 'Edit Test';
            document.getElementById('testId').value = test.test_id;
            document.getElementById('testTitle').value = test.test_name;
            document.getElementById('subjectId').value = test.subject_id;
            document.getElementById('testTypeId').value = test.test_type;
            // Batch field removed for simplified structure
            document.getElementById('facultyId').value = test.faculty_id;
            document.getElementById('testDate').value = test.test_date;
            document.getElementById('durationMinutes').value = test.duration;
            document.getElementById('totalMarks').value = test.max_marks;
            // Passing marks field removed
            document.getElementById('instructions').value = test.instructions || '';
            
            new bootstrap.Modal(document.getElementById('testModal')).show();
        }

        // Reset form when modal is closed
        document.getElementById('testModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('testModalTitle').textContent = 'Create Test';
            document.getElementById('testForm').reset();
            document.getElementById('testId').value = '';
            document.getElementById('durationMinutes').value = '60';
            document.getElementById('totalMarks').value = '100';
            document.getElementById('passingMarks').value = '40';
        });

        // Auto-calculate passing marks as 40% of total marks
        document.getElementById('totalMarks').addEventListener('input', function() {
            const totalMarks = parseFloat(this.value) || 0;
            const passingMarks = Math.round(totalMarks * 0.4 * 100) / 100; // 40% rounded to 2 decimal places
            document.getElementById('passingMarks').value = passingMarks;
        });

        // Real-time search functionality
        let searchTimeout;
        function submitSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('searchForm').submit();
            }, 500);
        }

        // Add event listeners for real-time search
        document.getElementById('searchInput').addEventListener('input', submitSearch);
        document.getElementById('subjectSelect').addEventListener('change', submitSearch);
        document.getElementById('typeSelect').addEventListener('change', submitSearch);
    </script>
</body>
</html>