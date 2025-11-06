<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);
$notifications = getNotifications($userRole, $user);

// Get current statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_students FROM students");
    $stmt->execute();
    $total_students = $stmt->fetch()['total_students'];
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN academic_status = 'Active' THEN 1 END) as active_students,
            COUNT(CASE WHEN academic_status = 'Inactive' THEN 1 END) as inactive_students
        FROM students
    ");
    $stmt->execute();
    $student_status = $stmt->fetch();
    
    // Get recent student enrollments (last 30 days)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as recent_enrollments 
        FROM students 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $recent_enrollments = $stmt->fetch()['recent_enrollments'];
    
} catch (Exception $e) {
    $total_students = 0;
    $student_status = ['active_students' => 0, 'inactive_students' => 0];
    $recent_enrollments = 0;
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admissions Information - College Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .info-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: all 0.15s ease-in-out;
        }
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline:before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #007bff;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding-left: 30px;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -5px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #007bff;
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #007bff;
        }
    </style>
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
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-3">
                    <i class="bi bi-file-earmark-text me-2"></i>Admissions Information
                </h1>
                <p class="text-muted">
                    Comprehensive information about our college admission process and current enrollment statistics.
                </p>
            </div>
        </div>

        <!-- Current Statistics -->
        <div class="row mb-5">
            <div class="col-md-3 mb-3">
                <div class="card info-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Students</h6>
                                <h3><?= number_format($total_students) ?></h3>
                            </div>
                            <i class="bi bi-people" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card info-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Active Students</h6>
                                <h3><?= number_format($student_status['active_students']) ?></h3>
                            </div>
                            <i class="bi bi-person-check" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card info-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Recent Enrollments</h6>
                                <h3><?= number_format($recent_enrollments) ?></h3>
                                <small>Last 30 days</small>
                            </div>
                            <i class="bi bi-person-plus" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card info-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Inactive Students</h6>
                                <h3><?= number_format($student_status['inactive_students']) ?></h3>
                            </div>
                            <i class="bi bi-person-x" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admission Information -->
        <div class="row mb-5">
            <div class="col-lg-8">
                <div class="card info-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i>Admission Process Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <h6><strong>Application Submission</strong></h6>
                                <p class="text-muted mb-2">Submit your completed application form along with required documents including academic transcripts, identification, and application fee.</p>
                            </div>
                            <div class="timeline-item">
                                <h6><strong>Document Verification</strong></h6>
                                <p class="text-muted mb-2">Our admissions team will verify all submitted documents for authenticity and completeness.</p>
                            </div>
                            <div class="timeline-item">
                                <h6><strong>Entrance Examination</strong></h6>
                                <p class="text-muted mb-2">Candidates must appear for the entrance examination based on their chosen course of study.</p>
                            </div>
                            <div class="timeline-item">
                                <h6><strong>Merit List Publication</strong></h6>
                                <p class="text-muted mb-2">Merit lists are published based on entrance exam scores and academic performance.</p>
                            </div>
                            <div class="timeline-item">
                                <h6><strong>Counseling & Seat Allotment</strong></h6>
                                <p class="text-muted mb-2">Selected candidates participate in counseling sessions for course and seat allocation.</p>
                            </div>
                            <div class="timeline-item">
                                <h6><strong>Fee Payment & Admission Confirmation</strong></h6>
                                <p class="text-muted mb-2">Complete fee payment and document submission to confirm your admission.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card info-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-file-earmark-check me-2"></i>Required Documents
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        Completed Application Form
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        High School/12th Grade Transcripts
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        Birth Certificate
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        Government ID Proof
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        Passport Size Photographs (6 copies)
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        Category Certificate (if applicable)
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        Medical Certificate
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        Migration Certificate (for transfers)
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card info-card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-event me-2"></i>Important Dates 2024
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Application Start:</strong><br>
                            <span class="text-muted">March 1, 2024</span>
                        </div>
                        <div class="mb-3">
                            <strong>Application Deadline:</strong><br>
                            <span class="text-muted">May 15, 2024</span>
                        </div>
                        <div class="mb-3">
                            <strong>Entrance Exam:</strong><br>
                            <span class="text-muted">June 1-15, 2024</span>
                        </div>
                        <div class="mb-3">
                            <strong>Results Declaration:</strong><br>
                            <span class="text-muted">June 25, 2024</span>
                        </div>
                        <div class="mb-3">
                            <strong>Counseling Period:</strong><br>
                            <span class="text-muted">July 1-15, 2024</span>
                        </div>
                        <div class="mb-0">
                            <strong>Classes Begin:</strong><br>
                            <span class="text-muted">August 1, 2024</span>
                        </div>
                    </div>
                </div>

                <div class="card info-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-currency-dollar me-2"></i>Fee Structure
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Application Fee:</strong><br>
                            <span class="text-muted">₹500 (General)</span><br>
                            <span class="text-muted">₹250 (SC/ST)</span>
                        </div>
                        <div class="mb-3">
                            <strong>Annual Tuition:</strong><br>
                            <span class="text-muted">₹25,000 - ₹75,000</span><br>
                            <small class="text-muted">Varies by course</small>
                        </div>
                        <div class="mb-3">
                            <strong>Hostel Fee:</strong><br>
                            <span class="text-muted">₹15,000 per semester</span>
                        </div>
                        <div class="mb-0">
                            <strong>Other Charges:</strong><br>
                            <span class="text-muted">₹5,000 - ₹10,000</span><br>
                            <small class="text-muted">Library, Lab, Sports</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features & Benefits -->
        <div class="row mb-5">
            <div class="col-12 mb-4">
                <h3 class="text-center">
                    <i class="bi bi-star me-2"></i>Why Choose Our College?
                </h3>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card info-card text-center h-100">
                    <div class="card-body">
                        <i class="bi bi-award feature-icon mb-3"></i>
                        <h5>Excellence in Education</h5>
                        <p class="text-muted">Top-notch faculty and modern curriculum designed for industry needs.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card info-card text-center h-100">
                    <div class="card-body">
                        <i class="bi bi-building feature-icon mb-3"></i>
                        <h5>Modern Infrastructure</h5>
                        <p class="text-muted">State-of-the-art facilities including smart classrooms and labs.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card info-card text-center h-100">
                    <div class="card-body">
                        <i class="bi bi-briefcase feature-icon mb-3"></i>
                        <h5>100% Placement Support</h5>
                        <p class="text-muted">Dedicated placement cell with tie-ups with leading companies.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card info-card text-center h-100">
                    <div class="card-body">
                        <i class="bi bi-globe feature-icon mb-3"></i>
                        <h5>Global Opportunities</h5>
                        <p class="text-muted">International exchange programs and global certification courses.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card info-card">
                    <div class="card-header bg-dark text-white text-center">
                        <h5 class="mb-0">
                            <i class="bi bi-telephone me-2"></i>Admissions Contact Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <i class="bi bi-envelope feature-icon mb-2 d-block"></i>
                                <h6>Email</h6>
                                <p class="text-muted mb-0">admissions@college.edu</p>
                                <p class="text-muted">info@college.edu</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="bi bi-telephone feature-icon mb-2 d-block"></i>
                                <h6>Phone</h6>
                                <p class="text-muted mb-0">+91-XXX-XXX-XXXX</p>
                                <p class="text-muted">Toll Free: 1800-XXX-XXXX</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="bi bi-clock feature-icon mb-2 d-block"></i>
                                <h6>Office Hours</h6>
                                <p class="text-muted mb-0">Mon-Fri: 9:00 AM - 5:00 PM</p>
                                <p class="text-muted">Sat: 9:00 AM - 2:00 PM</p>
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <a href="students.php" class="btn btn-primary me-3">
                                <i class="bi bi-people me-2"></i>View Current Students
                            </a>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="bi bi-house-door me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Spacing -->
    <div style="height: 50px;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>