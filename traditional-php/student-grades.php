<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);
$notifications = getNotifications($userRole, $user);

// Get student ID - either from URL parameter or current logged-in user
$student_id = $_GET['student_id'] ?? null;
$viewing_own_grades = false;

// If user is a student, they can only view their own grades
if ($user['role_name'] === 'Student') {
    $student_id = $user['linked_entity_id'];
    $viewing_own_grades = true;
}

// If no student selected and user is not a student, show student selection
if (!$student_id) {
    $show_student_selection = true;
} else {
    $show_student_selection = false;
}

// Get filter parameters
$subject_filter = $_GET['subject'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
$academic_year_filter = $_GET['academic_year'] ?? date('Y');

try {
    // Get student details
    $student_info = null;
    if ($student_id) {
        $stmt = $pdo->prepare("
            SELECT s.*, c.course_name, c.course_code, b.batch_name, b.batch_year
            FROM students s
            JOIN courses c ON s.course_id = c.course_id
            JOIN batches b ON s.batch_id = b.batch_id
            WHERE s.student_pk_id = ?
        ");
        $stmt->execute([$student_id]);
        $student_info = $stmt->fetch();
        
        if (!$student_info) {
            $_SESSION['error'] = 'Student not found';
            redirect('/student-grades.php');
        }
    }
    
    // Get student's test grades with subject and test details
    $grades_data = [];
    if ($student_id) {
        $grades_query = "
            SELECT stg.*, t.test_name, t.test_date, t.max_marks, t.is_published,
                   s.subject_code, s.subject_name, s.semester, s.credits,
                   t.test_type,
                   f.full_name as faculty_name
            FROM student_test_grades stg
            JOIN tests t ON stg.test_id = t.test_id
            JOIN subjects s ON t.subject_id = s.subject_id
            JOIN faculty f ON t.faculty_id = f.faculty_id
            WHERE stg.student_id = ? AND t.is_published = 1
        ";
        $grades_params = [$student_id];
        
        if ($subject_filter) {
            $grades_query .= " AND s.subject_id = ?";
            $grades_params[] = $subject_filter;
        }
        
        if ($semester_filter) {
            $grades_query .= " AND s.semester = ?";
            $grades_params[] = $semester_filter;
        }
        
        // Filter by academic year based on test date
        $grades_query .= " AND YEAR(t.test_date) = ?";
        $grades_params[] = $academic_year_filter;
        
        $grades_query .= " ORDER BY s.semester, s.subject_code, t.test_date DESC";
        
        $stmt = $pdo->prepare($grades_query);
        $stmt->execute($grades_params);
        $grades_data = $stmt->fetchAll();
        
        // Calculate passing marks for each test (40% of max marks)
        foreach ($grades_data as &$grade) {
            $grade['passing_marks'] = $grade['max_marks'] * 0.4;
        }
    }
    
    // Group grades by subject
    $subjects_grades = [];
    $overall_stats = [
        'total_tests' => 0,
        'total_marks_obtained' => 0,
        'total_marks_possible' => 0,
        'subjects_count' => 0
    ];
    
    foreach ($grades_data as $grade) {
        $subject_key = $grade['subject_code'];
        
        if (!isset($subjects_grades[$subject_key])) {
            $subjects_grades[$subject_key] = [
                'subject_info' => [
                    'subject_code' => $grade['subject_code'],
                    'subject_name' => $grade['subject_name'],
                    'semester' => $grade['semester'],
                    'credits' => $grade['credits']
                ],
                'tests' => [],
                'stats' => [
                    'total_tests' => 0,
                    'total_marks_obtained' => 0,
                    'total_marks_possible' => 0,
                    'average_percentage' => 0
                ]
            ];
        }
        
        $subjects_grades[$subject_key]['tests'][] = $grade;
        $subjects_grades[$subject_key]['stats']['total_tests']++;
        
        if (!$grade['is_absent']) {
            $subjects_grades[$subject_key]['stats']['total_marks_obtained'] += $grade['marks_obtained'];
            $overall_stats['total_marks_obtained'] += $grade['marks_obtained'];
        }
        
        $subjects_grades[$subject_key]['stats']['total_marks_possible'] += $grade['max_marks'];
        $overall_stats['total_marks_possible'] += $grade['max_marks'];
        $overall_stats['total_tests']++;
    }
    
    // Calculate subject averages
    foreach ($subjects_grades as $subject_key => &$subject_data) {
        if ($subject_data['stats']['total_marks_possible'] > 0) {
            $subject_data['stats']['average_percentage'] = 
                ($subject_data['stats']['total_marks_obtained'] / $subject_data['stats']['total_marks_possible']) * 100;
        }
    }
    
    $overall_stats['subjects_count'] = count($subjects_grades);
    $overall_stats['overall_percentage'] = $overall_stats['total_marks_possible'] > 0 
        ? ($overall_stats['total_marks_obtained'] / $overall_stats['total_marks_possible']) * 100 
        : 0;
    
    // Get available students (for admin/faculty view)
    $students_list = [];
    if (!$viewing_own_grades) {
        $stmt = $pdo->prepare("
            SELECT s.student_pk_id, s.student_id, s.full_name, c.course_name, b.batch_name
            FROM students s
            JOIN courses c ON s.course_id = c.course_id
            JOIN batches b ON s.batch_id = b.batch_id
            WHERE s.academic_status = 'Active'
            ORDER BY s.full_name
        ");
        $stmt->execute();
        $students_list = $stmt->fetchAll();
    }
    
    // Get subjects for filter (only subjects that have tests for this student)
    $available_subjects = [];
    if ($student_id) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.subject_id, s.subject_code, s.subject_name, s.semester
            FROM subjects s
            JOIN tests t ON s.subject_id = t.subject_id
            JOIN student_test_grades stg ON t.test_id = stg.test_id
            WHERE stg.student_id = ? AND t.is_published = 1
            ORDER BY s.semester, s.subject_code
        ");
        $stmt->execute([$student_id]);
        $available_subjects = $stmt->fetchAll();
    }
    
    // Get grade scale for reference
    $grade_ranges = [
        ['grade_label' => 'A+', 'min_percentage' => 90, 'max_percentage' => 100, 'grade_points' => 4.0],
        ['grade_label' => 'A', 'min_percentage' => 85, 'max_percentage' => 89, 'grade_points' => 4.0],
        ['grade_label' => 'A-', 'min_percentage' => 80, 'max_percentage' => 84, 'grade_points' => 3.7],
        ['grade_label' => 'B+', 'min_percentage' => 75, 'max_percentage' => 79, 'grade_points' => 3.3],
        ['grade_label' => 'B', 'min_percentage' => 70, 'max_percentage' => 74, 'grade_points' => 3.0],
        ['grade_label' => 'B-', 'min_percentage' => 65, 'max_percentage' => 69, 'grade_points' => 2.7],
        ['grade_label' => 'C+', 'min_percentage' => 60, 'max_percentage' => 64, 'grade_points' => 2.3],
        ['grade_label' => 'C', 'min_percentage' => 55, 'max_percentage' => 59, 'grade_points' => 2.0],
        ['grade_label' => 'C-', 'min_percentage' => 50, 'max_percentage' => 54, 'grade_points' => 1.7],
        ['grade_label' => 'D+', 'min_percentage' => 45, 'max_percentage' => 49, 'grade_points' => 1.3],
        ['grade_label' => 'D', 'min_percentage' => 40, 'max_percentage' => 44, 'grade_points' => 1.0],
        ['grade_label' => 'F', 'min_percentage' => 0, 'max_percentage' => 39, 'grade_points' => 0.0]
    ];
    
} catch (Exception $e) {
    $student_info = null;
    $grades_data = [];
    $subjects_grades = [];
    $overall_stats = ['total_tests' => 0, 'total_marks_obtained' => 0, 'total_marks_possible' => 0, 'subjects_count' => 0, 'overall_percentage' => 0];
    $students_list = [];
    $available_subjects = [];
    $grade_ranges = [];
    $error_message = "Database error: " . $e->getMessage();
}

// Function to get grade color class
function getGradeColorClass($percentage) {
    if ($percentage >= 90) return 'text-success';
    if ($percentage >= 80) return 'text-primary';
    if ($percentage >= 70) return 'text-info';
    if ($percentage >= 60) return 'text-warning';
    return 'text-danger';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $viewing_own_grades ? 'My Grades' : 'Student Grades' ?> - College Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .grade-card { 
            transition: transform 0.2s;
            border-left: 4px solid #007bff;
        }
        .grade-card:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .subject-performance {
            background: linear-gradient(45deg, #f8f9fa, #ffffff);
        }
        .grade-excellent { border-left-color: #28a745; }
        .grade-good { border-left-color: #17a2b8; }
        .grade-average { border-left-color: #ffc107; }
        .grade-poor { border-left-color: #dc3545; }
        .performance-ring {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        .test-item {
            border-left: 3px solid #dee2e6;
            margin-bottom: 10px;
            padding: 10px;
            background: #f8f9fa;
        }
        .test-passed { border-left-color: #28a745; }
        .test-failed { border-left-color: #dc3545; }
        .test-absent { border-left-color: #6c757d; }
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

                    <?php if (canAccessModule('courses') || canAccessModule('subjects') || canAccessModule('tests') || canAccessModule('grades') || canAccessModule('students') || canAccessModule('faculty')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="academicDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-mortarboard"></i> Academic
                        </a>
                        <ul class="dropdown-menu">
                            <?php if (canAccessModule('students')): ?>
                            <li><a class="dropdown-item" href="students.php"><i class="bi bi-people"></i> Students</a></li>
                            <?php endif; ?>
                            <?php if (canAccessModule('courses')): ?>
                            <li><a class="dropdown-item" href="courses.php"><i class="bi bi-book"></i> Courses</a></li>
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
                    <i class="bi bi-award me-2"></i><?= $viewing_own_grades ? 'My Academic Grades' : 'Student Academic Performance' ?>
                    <?php if ($student_info): ?>
                        <small class="text-muted">- <?= htmlspecialchars($student_info['full_name']) ?></small>
                    <?php endif; ?>
                </h1>
            </div>
            <div class="col-auto">
                <?php if (!$viewing_own_grades && !$show_student_selection): ?>
                    <a href="student-grades.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Selection
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($show_student_selection): ?>
            <!-- Student Selection View -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Select Student to View Grades</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($students_list)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 text-muted">No Students Found</h5>
                            <p class="text-muted">No active students available to view grades.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($students_list as $student): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($student['full_name']) ?></h6>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    ID: <?= htmlspecialchars($student['student_id']) ?><br>
                                                    <?= htmlspecialchars($student['course_name']) ?><br>
                                                    <?= htmlspecialchars($student['batch_name']) ?>
                                                </small>
                                            </p>
                                            <a href="student-grades.php?student_id=<?= $student['student_pk_id'] ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="bi bi-award"></i> View Grades
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($student_info): ?>
            <!-- Student Information -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><?= htmlspecialchars($student_info['full_name']) ?></h5>
                                    <p class="mb-1"><strong>Student ID:</strong> <?= htmlspecialchars($student_info['student_id']) ?></p>
                                    <p class="mb-1"><strong>Course:</strong> <?= htmlspecialchars($student_info['course_name']) ?></p>
                                    <p class="mb-0"><strong>Batch:</strong> <?= htmlspecialchars($student_info['batch_name']) ?> (<?= $student_info['batch_year'] ?>)</p>
                                </div>
                                <div class="col-md-6">
                                    <?php 
                                    $performance_class = 'bg-secondary';
                                    $performance_text = 'No Data';
                                    if ($overall_stats['overall_percentage'] > 0) {
                                        if ($overall_stats['overall_percentage'] >= 90) {
                                            $performance_class = 'bg-success';
                                            $performance_text = 'Excellent';
                                        } elseif ($overall_stats['overall_percentage'] >= 80) {
                                            $performance_class = 'bg-primary';
                                            $performance_text = 'Good';
                                        } elseif ($overall_stats['overall_percentage'] >= 60) {
                                            $performance_class = 'bg-warning';
                                            $performance_text = 'Average';
                                        } else {
                                            $performance_class = 'bg-danger';
                                            $performance_text = 'Needs Improvement';
                                        }
                                    }
                                    ?>
                                    <div class="text-center">
                                        <div class="performance-ring <?= $performance_class ?> mx-auto mb-2">
                                            <?= number_format($overall_stats['overall_percentage'], 1) ?>%
                                        </div>
                                        <small class="text-muted"><?= $performance_text ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card subject-performance">
                        <div class="card-body">
                            <h6>Academic Summary</h6>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="h4 text-primary"><?= $overall_stats['subjects_count'] ?></div>
                                    <small class="text-muted">Subjects</small>
                                </div>
                                <div class="col-6">
                                    <div class="h4 text-success"><?= $overall_stats['total_tests'] ?></div>
                                    <small class="text-muted">Tests</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="student_id" value="<?= $student_id ?>">
                        <div class="col-md-4">
                            <label class="form-label">Subject</label>
                            <select name="subject" class="form-select">
                                <option value="">All Subjects</option>
                                <?php foreach ($available_subjects as $subject): ?>
                                    <option value="<?= $subject['subject_id'] ?>" <?= $subject_filter == $subject['subject_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']) ?>
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
                        <div class="col-md-3">
                            <label class="form-label">Academic Year</label>
                            <select name="academic_year" class="form-select">
                                <?php for ($year = date('Y') + 1; $year >= date('Y') - 3; $year--): ?>
                                    <option value="<?= $year ?>" <?= $academic_year_filter == $year ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="student-grades.php?student_id=<?= $student_id ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Grades by Subject -->
            <?php if (empty($subjects_grades)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-award text-muted" style="font-size: 4rem;"></i>
                        <h5 class="mt-3 text-muted">No Grades Found</h5>
                        <p class="text-muted">No published test results found for the selected filters.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-8">
                        <!-- Subject-wise Grades -->
                        <?php foreach ($subjects_grades as $subject_data): ?>
                            <?php 
                            $subject_info = $subject_data['subject_info'];
                            $subject_stats = $subject_data['stats'];
                            $tests = $subject_data['tests'];
                            
                            // Determine grade card class
                            $grade_class = 'grade-card';
                            if ($subject_stats['average_percentage'] >= 90) {
                                $grade_class .= ' grade-excellent';
                            } elseif ($subject_stats['average_percentage'] >= 80) {
                                $grade_class .= ' grade-good';
                            } elseif ($subject_stats['average_percentage'] >= 60) {
                                $grade_class .= ' grade-average';
                            } else {
                                $grade_class .= ' grade-poor';
                            }
                            ?>
                            <div class="card <?= $grade_class ?> mb-4">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h5 class="mb-0"><?= htmlspecialchars($subject_info['subject_code']) ?> - <?= htmlspecialchars($subject_info['subject_name']) ?></h5>
                                            <small class="text-muted">
                                                Semester <?= $subject_info['semester'] ?> • <?= $subject_info['credits'] ?> Credits
                                            </small>
                                        </div>
                                        <div class="col-auto">
                                            <div class="text-end">
                                                <div class="h4 mb-0 <?= getGradeColorClass($subject_stats['average_percentage']) ?>">
                                                    <?= number_format($subject_stats['average_percentage'], 1) ?>%
                                                </div>
                                                <small class="text-muted"><?= $subject_stats['total_tests'] ?> tests</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($tests as $test): ?>
                                        <?php 
                                        $test_class = 'test-item';
                                        if ($test['is_absent']) {
                                            $test_class .= ' test-absent';
                                        } elseif ($test['marks_obtained'] >= $test['passing_marks']) {
                                            $test_class .= ' test-passed';
                                        } else {
                                            $test_class .= ' test-failed';
                                        }
                                        ?>
                                        <div class="<?= $test_class ?>">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <div class="fw-bold"><?= htmlspecialchars($test['test_name']) ?></div>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($test['test_type']) ?> • 
                                                        <?= date('M j, Y', strtotime($test['test_date'])) ?> • 
                                                        <?= htmlspecialchars($test['faculty_name']) ?>
                                                    </small>
                                                </div>
                                                <div class="col-auto text-end">
                                                    <?php if ($test['is_absent']): ?>
                                                        <span class="badge bg-secondary">Absent</span>
                                                    <?php else: ?>
                                                        <div class="fw-bold <?= getGradeColorClass($test['percentage']) ?>">
                                                            <?= $test['marks_obtained'] ?>/<?= $test['max_marks'] ?>
                                                        </div>
                                                        <div class="small">
                                                            <?= number_format($test['percentage'], 1) ?>% 
                                                            <?php if ($test['letter_grade']): ?>
                                                                (<?= $test['letter_grade'] ?>)
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if ($test['remarks']): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-chat-text"></i> <?= htmlspecialchars($test['remarks']) ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="col-md-4">
                        <!-- Grade Scale Reference -->
                        <div class="card mb-3" style="position: sticky; top: 20px;">
                            <div class="card-header">
                                <h6 class="mb-0">Grade Scale</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tbody>
                                        <?php foreach ($grade_ranges as $range): ?>
                                            <tr>
                                                <td><strong><?= $range['grade_label'] ?></strong></td>
                                                <td><?= $range['min_percentage'] ?>-<?= $range['max_percentage'] ?>%</td>
                                                <td><?= $range['grade_points'] ?> pts</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Performance Insights -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Performance Insights</h6>
                            </div>
                            <div class="card-body">
                                <?php if ($overall_stats['total_tests'] > 0): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <small>Overall Performance:</small>
                                            <small class="<?= getGradeColorClass($overall_stats['overall_percentage']) ?>">
                                                <strong><?= number_format($overall_stats['overall_percentage'], 1) ?>%</strong>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <?php
                                    // Find best and worst performing subjects
                                    $best_subject = null;
                                    $worst_subject = null;
                                    $best_percentage = 0;
                                    $worst_percentage = 100;
                                    
                                    foreach ($subjects_grades as $subject_data) {
                                        $avg = $subject_data['stats']['average_percentage'];
                                        if ($avg > $best_percentage) {
                                            $best_percentage = $avg;
                                            $best_subject = $subject_data['subject_info'];
                                        }
                                        if ($avg < $worst_percentage && $avg > 0) {
                                            $worst_percentage = $avg;
                                            $worst_subject = $subject_data['subject_info'];
                                        }
                                    }
                                    ?>
                                    
                                    <?php if ($best_subject): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">Strongest Subject:</small><br>
                                            <strong class="text-success"><?= htmlspecialchars($best_subject['subject_code']) ?></strong>
                                            <span class="text-success">(<?= number_format($best_percentage, 1) ?>%)</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($worst_subject && count($subjects_grades) > 1): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">Needs Improvement:</small><br>
                                            <strong class="text-warning"><?= htmlspecialchars($worst_subject['subject_code']) ?></strong>
                                            <span class="text-warning">(<?= number_format($worst_percentage, 1) ?>%)</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <hr>
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle"></i> 
                                        Keep up the excellent work! Regular study and practice lead to consistent improvement.
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">
                                        No test data available for performance analysis.
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>