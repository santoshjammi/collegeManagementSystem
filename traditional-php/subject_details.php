<?php
require_once 'config.php';
requireLogin();

// Check if user can access subjects
if (!canRead('subjects')) {
    $_SESSION['error'] = 'You do not have permission to view subject details.';
    redirect('/dashboard.php');
}

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);

// Get subject ID from URL
$subject_id = $_GET['id'] ?? 0;
if (!$subject_id) {
    $_SESSION['error'] = 'Subject ID is required.';
    redirect('/subjects.php');
}

// Get subject details
$stmt = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code,
           f.full_name as faculty_name, f.email as faculty_email
    FROM subjects s
    LEFT JOIN courses c ON s.course_id = c.course_id
    LEFT JOIN faculty f ON s.faculty_id = f.faculty_id
    WHERE s.subject_id = ?
");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subject) {
    $_SESSION['error'] = 'Subject not found.';
    redirect('/subjects.php');
}

// Get notifications
$notifications = getNotifications($userRole, $user);

// Page title
$pageTitle = 'Subject Details - ' . escape($subject['subject_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - College Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .detail-card {
            transition: transform 0.2s;
        }
        .detail-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building"></i> College Management System
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
                        <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <span class="badge bg-danger"><?php echo count($notifications); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($notifications as $notification): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo $notification['url'] ?? '#'; ?>">
                                    <i class="bi bi-<?php echo $notification['type'] === 'warning' ? 'exclamation-triangle' : 'info-circle'; ?>"></i>
                                    <?php echo escape($notification['message']); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo escape($user['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
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

    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="subjects.php">Subjects</a></li>
                <li class="breadcrumb-item active"><?php echo escape($subject['subject_name']); ?></li>
            </ol>
        </nav>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo escape($_SESSION['message']); unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo escape($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Subject Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h2 class="mb-2"><?php echo escape($subject['subject_name']); ?></h2>
                        <h5 class="text-muted mb-3"><?php echo escape($subject['subject_code']); ?></h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Course:</strong>
                                    <?php if ($subject['course_name']): ?>
                                        <span class="badge bg-info"><?php echo escape($subject['course_code'] . ' - ' . $subject['course_name']); ?></span>
                                    <?php else: ?>
                                        Not assigned
                                    <?php endif; ?>
                                </p>
                                <p><strong>Semester:</strong> <?php echo escape($subject['semester']); ?></p>
                                <p><strong>Credits:</strong> <?php echo escape($subject['credits']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Status:</strong>
                                    <span class="badge bg-<?php echo $subject['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                        <?php echo escape($subject['status']); ?>
                                    </span>
                                </p>
                                <p><strong>Faculty:</strong> <?php echo escape($subject['faculty_name'] ?? 'Not assigned'); ?></p>
                                <?php if ($subject['faculty_email']): ?>
                                <p><strong>Faculty Email:</strong> <?php echo escape($subject['faculty_email']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="bg-light rounded p-4">
                            <i class="bi bi-journal-text text-primary" style="font-size: 4rem;"></i>
                            <h4 class="mt-3">Subject Details</h4>
                            <p class="text-muted">Academic Information</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Information Cards -->
        <div class="row">
            <div class="col-md-6">
                <div class="card detail-card h-100">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Subject Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Subject Code:</strong> <?php echo escape($subject['subject_code']); ?></p>
                        <p><strong>Subject Name:</strong> <?php echo escape($subject['subject_name']); ?></p>
                        <p><strong>Description:</strong> <?php echo escape($subject['description'] ?? 'No description available'); ?></p>
                        <p><strong>Subject Type:</strong> <?php echo escape($subject['subject_type'] ?? 'Not specified'); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card detail-card h-100">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Faculty Assignment</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($subject['faculty_name']): ?>
                        <p><strong>Assigned Faculty:</strong> <?php echo escape($subject['faculty_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo escape($subject['faculty_email']); ?></p>
                        <p><strong>Role:</strong> <span class="badge bg-primary">Primary Instructor</span></p>
                        <?php else: ?>
                        <p class="text-muted">No faculty assigned to this subject yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-4">
            <a href="subjects.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Subjects
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>