<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);
$notifications = getNotifications($userRole, $user);

// Handle delete subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_subject') {
    $subject_id = $_POST['subject_id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
    $stmt->execute([$subject_id]);
    $_SESSION['message'] = 'Subject deleted successfully';
    redirect('/subjects.php');
}

// Handle faculty assignment removal (set faculty_id to NULL)
if (isset($_GET['remove_faculty'])) {
    $subject_id = $_GET['remove_faculty'];
    $stmt = $pdo->prepare("UPDATE subjects SET faculty_id = NULL WHERE subject_id = ?");
    $stmt->execute([$subject_id]);
    $_SESSION['success'] = 'Faculty assignment removed successfully';
    header('Location: subjects.php?tab=assignments');
    exit;
}

// Handle add/edit subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_subject') {
    $data = [
        'subject_code' => $_POST['subject_code'] ?? '',
        'subject_name' => $_POST['subject_name'] ?? '',
        'course_id' => $_POST['course_id'] ?? null,
        'semester' => $_POST['semester'] ?? 1,
        'credits' => $_POST['credits'] ?? 3,
        'description' => $_POST['description'] ?? '',
                'subject_type' => $_POST['subject_type'] ?? 'Theory',
        'status' => isset($_POST['is_active']) ? 'Active' : 'Inactive',
    ];
    
    if (isset($_POST['subject_id']) && $_POST['subject_id']) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE subjects SET 
                subject_code = ?,
                subject_name = ?,
                course_id = ?,
                semester = ?,
                credits = ?,
                description = ?,
                status = ?
            WHERE subject_id = ?
        ");
        $stmt->execute([
            $data['subject_code'],
            $data['subject_name'],
            $data['course_id'],
            $data['semester'],
            $data['credits'],
            $data['description'],
            $data['status'],
            $_POST['subject_id']
        ]);
        $_SESSION['message'] = 'Subject updated successfully';
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO subjects (subject_code, subject_name, course_id, semester, credits, description, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['subject_code'],
            $data['subject_name'],
            $data['course_id'],
            $data['semester'],
            $data['credits'],
            $data['description'],
            $data['status']
        ]);
        $_SESSION['message'] = 'Subject added successfully';
    }
    redirect('/subjects.php');
}

// Handle add faculty assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_assignment') {
    $subject_id = $_POST['subject_id'] ?? null;
    $faculty_id = $_POST['faculty_id'] ?? null;
    
    if ($subject_id && $faculty_id) {
        // Check if subject exists
        $stmt = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_id = ?");
        $stmt->execute([$subject_id]);
        
        if ($stmt->fetch()) {
            // Update the faculty assignment in subjects table
            $stmt = $pdo->prepare("UPDATE subjects SET faculty_id = ? WHERE subject_id = ?");
            $stmt->execute([$faculty_id, $subject_id]);
            $_SESSION['message'] = 'Faculty assigned to subject successfully';
        } else {
            $_SESSION['error'] = 'Subject not found';
        }
    } else {
        $_SESSION['error'] = 'Please select both subject and faculty';
    }
    redirect('/subjects.php');
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$course_filter = $_GET['course'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
$tab = $_GET['tab'] ?? 'subjects'; // subjects, assignments

// Subjects query
$subjects_query = "
    SELECT s.*, c.course_name, c.course_code
    FROM subjects s
    LEFT JOIN courses c ON s.course_id = c.course_id
    WHERE 1=1
";
$subjects_params = [];

if ($search && $tab === 'subjects') {
    $subjects_query .= " AND (s.subject_code LIKE ? OR s.subject_name LIKE ? OR c.course_name LIKE ?)";
    $searchParam = "%$search%";
    $subjects_params[] = $searchParam;
    $subjects_params[] = $searchParam;
    $subjects_params[] = $searchParam;
}

if ($course_filter) {
    $subjects_query .= " AND s.course_id = ?";
    $subjects_params[] = $course_filter;
}

if ($semester_filter) {
    $subjects_query .= " AND s.semester = ?";
    $subjects_params[] = $semester_filter;
}

$subjects_query .= " ORDER BY c.course_name, s.semester, s.subject_code";

// Faculty assignments query (using subjects table with faculty_id)
$assignments_query = "
    SELECT s.subject_id, s.subject_code, s.subject_name, s.semester, s.credits,
           f.faculty_id, f.full_name as faculty_name, f.department,
           c.course_name
    FROM subjects s
    LEFT JOIN faculty f ON s.faculty_id = f.faculty_id
    LEFT JOIN courses c ON s.course_id = c.course_id
    WHERE s.faculty_id IS NOT NULL
";
$assignments_params = [];

if ($search && $tab === 'assignments') {
    $assignments_query .= " AND (s.subject_code LIKE ? OR s.subject_name LIKE ? OR f.full_name LIKE ?)";
    $searchParam = "%$search%";
    $assignments_params[] = $searchParam;
    $assignments_params[] = $searchParam;
    $assignments_params[] = $searchParam;
}

$assignments_query .= " ORDER BY c.course_name, s.subject_code, f.full_name";

try {
    // Execute queries
    $stmt = $pdo->prepare($subjects_query);
    $stmt->execute($subjects_params);
    $subjects = $stmt->fetchAll();
    
    $stmt = $pdo->prepare($assignments_query);
    $stmt->execute($assignments_params);
    $assignments = $stmt->fetchAll();
    
    // Get courses for dropdowns
    $stmt = $pdo->prepare("SELECT * FROM courses ORDER BY course_name");
    $stmt->execute();
    $courses = $stmt->fetchAll();
    
    // Get faculty for dropdowns
    $stmt = $pdo->prepare("SELECT * FROM faculty WHERE is_active = 1 ORDER BY full_name");
    $stmt->execute();
    $faculty_list = $stmt->fetchAll();
    
    // Batches not needed for simplified faculty assignment
    
    // Calculate summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_subjects,
            COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_subjects,
            COUNT(DISTINCT course_id) as courses_with_subjects,
            AVG(credits) as avg_credits
        FROM subjects
    ");
    $stmt->execute();
    $summary = $stmt->fetch();
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_assignments,
            COUNT(DISTINCT faculty_id) as faculty_assigned,
            COUNT(*) as primary_assignments
        FROM subjects
        WHERE faculty_id IS NOT NULL
    ");
    $stmt->execute();
    $assignments_summary = $stmt->fetch();
    
} catch (Exception $e) {
    $subjects = [];
    $assignments = [];
    $courses = [];
    $faculty_list = [];
    // Batches not needed
    $summary = ['total_subjects' => 0, 'active_subjects' => 0, 'courses_with_subjects' => 0, 'avg_credits' => 0];
    $assignments_summary = ['total_assignments' => 0, 'faculty_assigned' => 0, 'primary_assignments' => 0];
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects Management - College Management System</title>
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
                    <i class="bi bi-journal-text me-2"></i>
                    <?php echo hasRole(['Faculty']) ? 'Subject Directory' : 'Subjects Management'; ?>
                </h1>
                <?php if (hasRole(['Faculty'])): ?>
                <p class="text-muted">View subject information and academic details</p>
                <?php endif; ?>
            </div>
            <div class="col-auto">
                <?php if ($tab === 'subjects'): ?>
                    <?php if (canWrite('subjects')): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#subjectModal">
                        <i class="bi bi-plus"></i> Add Subject
                    </button>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (canWrite('subjects')): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignmentModal">
                        <i class="bi bi-plus"></i> Assign Faculty
                    </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Subjects</h6>
                                <h4><?= number_format($summary['total_subjects'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-journal-text" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Active Subjects</h6>
                                <h4><?= number_format($summary['active_subjects'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-check-circle" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Faculty Assigned</h6>
                                <h4><?= number_format($assignments_summary['faculty_assigned'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-person-badge" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Assignments</h6>
                                <h4><?= number_format($assignments_summary['total_assignments'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-diagram-2" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="searchForm">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" id="searchInput"
                               placeholder="<?= $tab === 'subjects' ? 'Search subjects or courses' : 'Search assignments or faculty' ?>" 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <?php if ($tab === 'subjects'): ?>
                        <div class="col-md-3">
                            <label class="form-label">Course</label>
                            <select name="course" class="form-select" id="courseSelect">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['course_id'] ?>" <?= $course_filter == $course['course_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['course_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select">
                                <option value="">All Semesters</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>" <?= $semester_filter == $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="col-md-6"></div>
                    <?php endif; ?>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="subjects.php?tab=<?= htmlspecialchars($tab) ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'subjects' ? 'active' : '' ?>" 
                   href="?tab=subjects<?= $search ? '&search=' . urlencode($search) : '' ?>">
                    <i class="bi bi-journal-text"></i> Subjects (<?= count($subjects) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'assignments' ? 'active' : '' ?>" 
                   href="?tab=assignments<?= $search ? '&search=' . urlencode($search) : '' ?>">
                    <i class="bi bi-diagram-2"></i> Faculty Assignments (<?= count($assignments) ?>)
                </a>
            </li>
        </ul>

        <!-- Subjects Tab -->
        <?php if ($tab === 'subjects'): ?>
            <div class="card">
                <div class="card-body">
                    <?php if (empty($subjects)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-journal-text text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 text-muted">No Subjects Found</h5>
                            <p class="text-muted">Start by adding subjects for your courses.</p>
                            <?php if (canWrite('subjects')): ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#subjectModal">
                                <i class="bi bi-plus"></i> Add Subject
                            </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Course</th>
                                        <th>Semester</th>
                                        <th>Credits</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($subject['subject_code']) ?></strong></td>
                                            <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars($subject['course_name'] ?? 'N/A') ?>
                                                </span>
                                            </td>
                                            <td><?= $subject['semester'] ?></td>
                                            <td><?= $subject['credits'] ?></td>
                                            <td>
                                                <span class="badge <?= $subject['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= htmlspecialchars($subject['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (canWrite('subjects')): ?>
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editSubject(<?= htmlspecialchars(json_encode($subject)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_subject">
                                                    <input type="hidden" name="subject_id" value="<?= $subject['subject_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to delete this subject?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="viewSubject(<?= $subject['subject_id'] ?>)">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <!-- Faculty Assignments Tab -->
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <?php if (empty($assignments)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-diagram-2 text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 text-muted">No Faculty Assignments Found</h5>
                            <p class="text-muted">Assign faculty members to subjects they will teach.</p>
                            <?php if (canWrite('subjects')): ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignmentModal">
                                <i class="bi bi-plus"></i> Assign Faculty
                            </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject</th>
                                        <th>Faculty</th>
                                        <th>Course Details</th>
                                        <th>Academic Year</th>
                                        <th>Role</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $assignment): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($assignment['subject_code']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($assignment['subject_name']) ?></small>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($assignment['faculty_name']) ?></td>
                                            <td>
                                                <div>
                                                    <?= htmlspecialchars($assignment['course_name']) ?><br>
                                                    <small class="text-muted">Semester <?= $assignment['semester'] ?> • <?= $assignment['credits'] ?> credits</small>
                                                </div>
                                            </td>
                                            <td><?= date('Y') ?></td>
                                            <td>
                                                <span class="badge bg-primary">Primary</span>
                                            </td>
                                            <td>
                                                <?php if (canWrite('subjects')): ?>
                                                <a href="?remove_faculty=<?= $assignment['subject_id'] ?>&tab=assignments" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to remove this faculty assignment?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <?php else: ?>
                                                <span class="text-muted">Read-only</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Subject Modal -->
    <div class="modal fade" id="subjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subjectModalTitle">Add Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="subjectForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_subject">
                        <input type="hidden" name="subject_id" id="subjectId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Subject Code *</label>
                                    <input type="text" name="subject_code" id="subjectCode" class="form-control" 
                                           placeholder="e.g., CS101" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Credits</label>
                                    <input type="number" name="credits" id="credits" class="form-control" 
                                           min="1" max="10" value="3">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subject Name *</label>
                            <input type="text" name="subject_name" id="subjectName" class="form-control" 
                                   placeholder="e.g., Introduction to Computer Science" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Course *</label>
                                    <select name="course_id" id="courseId" class="form-select" required>
                                        <option value="">Select Course</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?= $course['course_id'] ?>">
                                                <?= htmlspecialchars($course['course_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Semester *</label>
                                    <select name="semester" id="semester" class="form-select" required>
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                            <option value="<?= $i ?>">Semester <?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3"
                                      placeholder="Brief description of the subject..."></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="isActive" class="form-check-input" checked>
                                <label class="form-check-label" for="isActive">
                                    Active Subject
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Faculty Assignment Modal -->
    <div class="modal fade" id="assignmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Faculty to Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="assignmentForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_assignment">
                        
                        <div class="mb-3">
                            <label class="form-label">Subject *</label>
                            <select name="subject_id" id="assignmentSubjectId" class="form-select" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['subject_id'] ?>">
                                        <?= htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Faculty *</label>
                            <select name="faculty_id" id="assignmentFacultyId" class="form-select" required>
                                <option value="">Select Faculty</option>
                                <?php foreach ($faculty_list as $faculty): ?>
                                    <option value="<?= $faculty['faculty_id'] ?>">
                                        <?= htmlspecialchars($faculty['full_name'] . ' (' . $faculty['department'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <small>This will assign the selected faculty as the primary instructor for this subject.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Faculty</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editSubject(subject) {
            document.getElementById('subjectModalTitle').textContent = 'Edit Subject';
            document.getElementById('subjectId').value = subject.subject_id;
            document.getElementById('subjectCode').value = subject.subject_code;
            document.getElementById('subjectName').value = subject.subject_name;
            document.getElementById('courseId').value = subject.course_id;
            document.getElementById('semester').value = subject.semester;
            document.getElementById('credits').value = subject.credits;
            document.getElementById('description').value = subject.description || '';
            document.getElementById('isActive').checked = subject.status === 'Active';
            
            new bootstrap.Modal(document.getElementById('subjectModal')).show();
        }

        // Reset subject form when modal is closed
        document.getElementById('subjectModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('subjectModalTitle').textContent = 'Add Subject';
            document.getElementById('subjectForm').reset();
            document.getElementById('subjectId').value = '';
            document.getElementById('isActive').checked = true;
        });

        // Reset assignment form when modal is closed
        document.getElementById('assignmentModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('assignmentForm').reset();
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
        const courseSelect = document.getElementById('courseSelect');
        if (courseSelect) {
            courseSelect.addEventListener('change', submitSearch);
        }

        // View subject details function for faculty
        function viewSubject(subjectId) {
            window.location.href = 'subject_details.php?id=' + subjectId;
        }
    </script>
</body>
</html>