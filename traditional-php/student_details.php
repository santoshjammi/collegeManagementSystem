<?php
require_once 'config.php';
requireLogin();

// Check if user can access student details
if (!canRead('students')) {
    $_SESSION['error'] = 'You do not have permission to view student details.';
    redirect('/dashboard.php');
}

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);

// Get student ID from URL
$student_id = $_GET['id'] ?? 0;
if (!$student_id) {
    $_SESSION['error'] = 'Student ID is required.';
    redirect('/students.php');
}

// Get student details
$stmt = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code, b.batch_name, b.batch_year,
           u.username, u.is_active as user_active
    FROM students s
    LEFT JOIN courses c ON s.course_id = c.course_id
    LEFT JOIN batches b ON s.batch_id = b.batch_id
    LEFT JOIN users u ON s.user_id = u.user_id
    WHERE s.student_pk_id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $_SESSION['error'] = 'Student not found.';
    redirect('/students.php');
}

// Get notifications
$notifications = getNotifications($userRole, $user);

// Page title
$pageTitle = 'Student Details - ' . escape($student['full_name']);
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
        .profile-picture {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #e9ecef;
        }
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
                <li class="breadcrumb-item"><a href="students.php">Students</a></li>
                <li class="breadcrumb-item active"><?php echo escape($student['full_name']); ?></li>
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

        <!-- Student Profile Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <img src="<?php echo getProfilePictureUrl($student['profile_picture'] ?? null, $student['gender'] ?? 'Other'); ?>"
                             alt="Profile Picture" class="profile-picture mb-3">
                        <h5><?php echo escape($student['full_name']); ?></h5>
                        <p class="text-muted mb-0">Student ID: <?php echo escape($student['student_id']); ?></p>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">Academic Information</h6>
                                <p><strong>Course:</strong>
                                    <?php if ($student['course_name']): ?>
                                        <?php echo escape($student['course_code'] . ' - ' . $student['course_name']); ?>
                                    <?php else: ?>
                                        Not assigned
                                    <?php endif; ?>
                                </p>
                                <p><strong>Batch:</strong> <?php echo escape($student['batch_name'] ?? 'Not assigned'); ?></p>
                                <p><strong>Status:</strong>
                                    <span class="badge bg-<?php
                                        echo $student['academic_status'] === 'Active' ? 'success' :
                                            ($student['academic_status'] === 'Graduated' ? 'primary' : 'warning');
                                    ?>">
                                        <?php echo escape($student['academic_status']); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary">Contact Information</h6>
                                <p><strong>Email:</strong> <?php echo escape($student['email'] ?? 'Not provided'); ?></p>
                                <p><strong>Phone:</strong> <?php echo escape($student['contact_number'] ?? 'Not provided'); ?></p>
                                <p><strong>Username:</strong> <?php echo escape($student['username'] ?? 'Not set'); ?></p>
                                <p><strong>Account Status:</strong>
                                    <span class="badge bg-<?php echo ($student['user_active'] ?? 0) ? 'success' : 'secondary'; ?>">
                                        <?php echo ($student['user_active'] ?? 0) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Details Cards -->
        <div class="row">
            <div class="col-md-6">
                <div class="card detail-card h-100">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Personal Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Full Name:</strong> <?php echo escape($student['full_name']); ?></p>
                        <p><strong>Gender:</strong> <?php echo escape($student['gender'] ?? 'Not specified'); ?></p>
                        <p><strong>Student ID:</strong> <?php echo escape($student['student_id']); ?></p>
                        <p><strong>Created:</strong> <?php echo date('M d, Y', strtotime($student['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card detail-card h-100">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-mortarboard me-2"></i>Academic Progress</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Current Status:</strong>
                            <span class="badge bg-<?php
                                echo $student['academic_status'] === 'Active' ? 'success' :
                                    ($student['academic_status'] === 'Graduated' ? 'primary' : 'warning');
                            ?>">
                                <?php echo escape($student['academic_status']); ?>
                            </span>
                        </p>
                        <p><strong>Course:</strong> <?php echo escape($student['course_name'] ?? 'Not assigned'); ?></p>
                        <p><strong>Batch:</strong> <?php echo escape($student['batch_name'] ?? 'Not assigned'); ?></p>
                        <p><strong>Batch Year:</strong> <?php echo escape($student['batch_year'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-4">
            <a href="students.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Students
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>