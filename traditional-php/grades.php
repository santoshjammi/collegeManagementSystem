<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);
$notifications = getNotifications($userRole, $user);

// Get test ID if specified
$test_id = $_GET['test_id'] ?? null;
$selected_test = null;

// Handle grade entry/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_grades') {
        $test_id = $_POST['test_id'];
        $grades = $_POST['grades'] ?? [];
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($grades as $student_id => $grade_data) {
            $marks_obtained = $grade_data['marks_obtained'] ?? null;
            $is_absent = isset($grade_data['is_absent']) ? 1 : 0;
            $remarks = $grade_data['remarks'] ?? '';
            
            // Skip if no marks entered and not marked absent
            if ($marks_obtained === '' && !$is_absent) {
                continue;
            }
            
            // Set marks to 0 if absent
            if ($is_absent) {
                $marks_obtained = 0;
            }
            
            try {
                // Check if grade already exists
                $stmt = $pdo->prepare("SELECT grade_id FROM student_test_grades WHERE test_id = ? AND student_id = ?");
                $stmt->execute([$test_id, $student_id]);
                $existing_grade = $stmt->fetch();
                
                if ($existing_grade) {
                    // Update existing grade
                    $stmt = $pdo->prepare("
                        UPDATE student_test_grades 
                        SET marks_obtained = ?, is_absent = ?, remarks = ?, 
                            graded_by = ?, graded_at = CURRENT_TIMESTAMP
                        WHERE test_id = ? AND student_id = ?
                    ");
                    $stmt->execute([
                        $marks_obtained, $is_absent, $remarks, 
                        $user['linked_entity_id'], $test_id, $student_id
                    ]);
                } else {
                    // Insert new grade
                    $stmt = $pdo->prepare("
                        INSERT INTO student_test_grades 
                        (test_id, student_id, marks_obtained, is_absent, remarks, graded_by, graded_at)
                        VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                    ");
                    $stmt->execute([
                        $test_id, $student_id, $marks_obtained, $is_absent, $remarks, 
                        $user['linked_entity_id']
                    ]);
                }
                
                // Calculate percentage and update grade label based on marks
                $stmt = $pdo->prepare("
                    UPDATE student_test_grades stg
                    JOIN tests t ON stg.test_id = t.test_id
                    SET stg.percentage = CASE 
                        WHEN stg.is_absent = 1 OR stg.marks_obtained IS NULL THEN NULL 
                        ELSE (stg.marks_obtained * 100.0 / t.max_marks)
                    END
                    WHERE stg.test_id = ? AND stg.student_id = ?
                ");
                $stmt->execute([$test_id, $student_id]);
                
                // Update grade letter based on percentage using standard scale
                $stmt = $pdo->prepare("
                    UPDATE student_test_grades 
                    SET letter_grade = CASE 
                        WHEN status = 'Absent' OR percentage IS NULL THEN NULL
                        WHEN percentage >= 90 THEN 'A+'
                        WHEN percentage >= 85 THEN 'A'
                        WHEN percentage >= 80 THEN 'A-'
                        WHEN percentage >= 75 THEN 'B+'
                        WHEN percentage >= 70 THEN 'B'
                        WHEN percentage >= 65 THEN 'B-'
                        WHEN percentage >= 60 THEN 'C+'
                        WHEN percentage >= 55 THEN 'C'
                        WHEN percentage >= 50 THEN 'C-'
                        WHEN percentage >= 45 THEN 'D+'
                        WHEN percentage >= 40 THEN 'D'
                        ELSE 'F'
                    END
                    WHERE test_id = ? AND student_id = ?
                ");
                $stmt->execute([$test_id, $student_id]);
                
                $success_count++;
            } catch (Exception $e) {
                $error_count++;
            }
        }
        
        if ($success_count > 0) {
            $_SESSION['message'] = "Successfully saved grades for {$success_count} students.";
        }
        if ($error_count > 0) {
            $_SESSION['error'] = "Failed to save grades for {$error_count} students.";
        }
        
        redirect("/grades.php?test_id={$test_id}");
    }
    
    if ($_POST['action'] === 'delete_grade') {
        $grade_id = $_POST['grade_id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM student_test_grades WHERE grade_id = ?");
        $stmt->execute([$grade_id]);
        $_SESSION['message'] = 'Grade deleted successfully';
        redirect('/grades.php' . ($test_id ? "?test_id={$test_id}" : ''));
    }
    
    if ($_POST['action'] === 'bulk_absent') {
        $test_id = $_POST['test_id'];
        $student_ids = $_POST['student_ids'] ?? [];
        
        foreach ($student_ids as $student_id) {
            try {
                // Check if grade already exists
                $stmt = $pdo->prepare("SELECT grade_id FROM student_test_grades WHERE test_id = ? AND student_id = ?");
                $stmt->execute([$test_id, $student_id]);
                $existing_grade = $stmt->fetch();
                
                if ($existing_grade) {
                    $stmt = $pdo->prepare("
                        UPDATE student_test_grades 
                        SET marks_obtained = 0, is_absent = 1, graded_by = ?, graded_at = CURRENT_TIMESTAMP,
                            percentage = NULL, letter_grade = NULL
                        WHERE test_id = ? AND student_id = ?
                    ");
                    $stmt->execute([$user['linked_entity_id'], $test_id, $student_id]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO student_test_grades 
                        (test_id, student_id, marks_obtained, is_absent, graded_by, graded_at)
                        VALUES (?, ?, 0, 1, ?, CURRENT_TIMESTAMP)
                    ");
                    $stmt->execute([$test_id, $student_id, $user['linked_entity_id']]);
                }
            } catch (Exception $e) {
                // Continue with other students if one fails
            }
        }
        
        $_SESSION['message'] = 'Bulk absent marking completed';
        redirect("/grades.php?test_id={$test_id}");
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$subject_filter = $_GET['subject'] ?? '';
$batch_filter = $_GET['batch'] ?? '';
$status_filter = $_GET['status'] ?? '';

try {
    // Get test details if test_id is provided
    if ($test_id) {
        $stmt = $pdo->prepare("
            SELECT t.*, s.subject_code, s.subject_name, t.test_type as type_name, 
                   s.semester, s.credits, f.full_name as faculty_name
            FROM tests t
            JOIN subjects s ON t.subject_id = s.subject_id
            JOIN faculty f ON t.faculty_id = f.faculty_id
            WHERE t.test_id = ?
        ");
        $stmt->execute([$test_id]);
        $selected_test = $stmt->fetch();
        
        if (!$selected_test) {
            $_SESSION['error'] = 'Test not found';
            redirect('/grades.php');
        }
    }
    
    // Get students for the selected test with their grades
    $students_with_grades = [];
    if ($test_id) {
        $stmt = $pdo->prepare("
            SELECT s.student_pk_id, s.student_id, s.full_name,
                   stg.grade_id, stg.marks_obtained, stg.percentage, stg.letter_grade, 
                   stg.is_absent, stg.remarks, stg.graded_at,
                   gf.full_name as graded_by
            FROM students s
            LEFT JOIN student_test_grades stg ON s.student_pk_id = stg.student_id AND stg.test_id = ?
            LEFT JOIN faculty gf ON stg.graded_by = gf.faculty_id
            WHERE s.academic_status = 'Active'
            ORDER BY s.full_name
        ");
        $stmt->execute([$test_id]);
        $students_with_grades = $stmt->fetchAll();
    }
    
    // Get available tests for selection
    $tests_query = "
        SELECT t.*, s.subject_code, s.subject_name, s.semester, s.credits,
               COUNT(stg.student_id) as graded_count,
               (SELECT COUNT(*) FROM students WHERE academic_status = 'Active') as total_students
        FROM tests t
        JOIN subjects s ON t.subject_id = s.subject_id
        JOIN faculty f ON t.faculty_id = f.faculty_id
        LEFT JOIN student_test_grades stg ON t.test_id = stg.test_id
        WHERE 1=1
    ";
    $tests_params = [];
    
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
    
    if ($status_filter === 'graded') {
        $tests_query .= " HAVING graded_count = total_students AND total_students > 0";
    } elseif ($status_filter === 'partial') {
        $tests_query .= " HAVING graded_count > 0 AND graded_count < total_students";
    } elseif ($status_filter === 'ungraded') {
        $tests_query .= " HAVING graded_count = 0";
    }
    
    $tests_query .= " GROUP BY t.test_id ORDER BY t.test_date DESC, t.created_date DESC";
    
    $stmt = $pdo->prepare($tests_query);
    $stmt->execute($tests_params);
    $available_tests = $stmt->fetchAll();
    
    // Get subjects and batches for filters
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.subject_id, s.subject_code, s.subject_name
        FROM subjects s 
        JOIN tests t ON s.subject_id = t.subject_id
        ORDER BY s.subject_name
    ");
    $stmt->execute();
    $subjects = $stmt->fetchAll();
    
    // Batches not needed in simplified structure
    $batches = [];
    
    // Calculate summary statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT t.test_id) as total_tests,
            COUNT(DISTINCT stg.grade_id) as total_grades,
            COUNT(DISTINCT CASE WHEN stg.grade_id IS NOT NULL THEN stg.student_id END) as students_graded,
            AVG(stg.marks_obtained) as avg_marks
        FROM tests t
        LEFT JOIN student_test_grades stg ON t.test_id = stg.test_id
    ");
    $stmt->execute();
    $summary = $stmt->fetch();
    
    // Define standard grade ranges for reference
    $grade_ranges = [
        ['grade_label' => 'A+', 'min_percentage' => 90, 'max_percentage' => 100, 'grade_points' => 4.0, 'description' => 'Excellent'],
        ['grade_label' => 'A', 'min_percentage' => 85, 'max_percentage' => 89, 'grade_points' => 4.0, 'description' => 'Very Good'],
        ['grade_label' => 'A-', 'min_percentage' => 80, 'max_percentage' => 84, 'grade_points' => 3.7, 'description' => 'Good'],
        ['grade_label' => 'B+', 'min_percentage' => 75, 'max_percentage' => 79, 'grade_points' => 3.3, 'description' => 'Above Average'],
        ['grade_label' => 'B', 'min_percentage' => 70, 'max_percentage' => 74, 'grade_points' => 3.0, 'description' => 'Average'],
        ['grade_label' => 'B-', 'min_percentage' => 65, 'max_percentage' => 69, 'grade_points' => 2.7, 'description' => 'Below Average'],
        ['grade_label' => 'C+', 'min_percentage' => 60, 'max_percentage' => 64, 'grade_points' => 2.3, 'description' => 'Satisfactory'],
        ['grade_label' => 'C', 'min_percentage' => 55, 'max_percentage' => 59, 'grade_points' => 2.0, 'description' => 'Acceptable'],
        ['grade_label' => 'C-', 'min_percentage' => 50, 'max_percentage' => 54, 'grade_points' => 1.7, 'description' => 'Marginal'],
        ['grade_label' => 'D+', 'min_percentage' => 45, 'max_percentage' => 49, 'grade_points' => 1.3, 'description' => 'Poor'],
        ['grade_label' => 'D', 'min_percentage' => 40, 'max_percentage' => 44, 'grade_points' => 1.0, 'description' => 'Very Poor'],
        ['grade_label' => 'F', 'min_percentage' => 0, 'max_percentage' => 39, 'grade_points' => 0.0, 'description' => 'Fail']
    ];
    
} catch (Exception $e) {
    $available_tests = [];
    $students_with_grades = [];
    $subjects = [];
    $batches = [];
    $summary = ['total_tests' => 0, 'total_grades' => 0, 'students_graded' => 0, 'avg_marks' => 0];
    $grade_ranges = [
        ['grade_label' => 'A+', 'min_percentage' => 90, 'max_percentage' => 100, 'grade_points' => 4.0, 'description' => 'Excellent'],
        ['grade_label' => 'A', 'min_percentage' => 85, 'max_percentage' => 89, 'grade_points' => 4.0, 'description' => 'Very Good'],
        ['grade_label' => 'B+', 'min_percentage' => 75, 'max_percentage' => 84, 'grade_points' => 3.3, 'description' => 'Good'],
        ['grade_label' => 'C+', 'min_percentage' => 60, 'max_percentage' => 74, 'grade_points' => 2.3, 'description' => 'Average'],
        ['grade_label' => 'D', 'min_percentage' => 40, 'max_percentage' => 59, 'grade_points' => 1.0, 'description' => 'Poor'],
        ['grade_label' => 'F', 'min_percentage' => 0, 'max_percentage' => 39, 'grade_points' => 0.0, 'description' => 'Fail']
    ];
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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

        .stat-card, .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
        }

        .stat-card:hover, .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.5px;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .grade-input { width: 80px; }
        .student-row:hover { background-color: #f8f9fa; }
        .graded { background-color: #d4edda; }
        .absent { background-color: #f8d7da; }
        .grade-scale { font-size: 0.8rem; }
        .quick-actions { position: sticky; top: 20px; }

        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem;
            }
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
                    <i class="bi bi-trophy me-2"></i>Grades Management
                    <?php if ($selected_test): ?>
                        <small class="text-muted">- <?= htmlspecialchars($selected_test['test_name']) ?></small>
                    <?php endif; ?>
                </h1>
            </div>
            <div class="col-auto">
                <?php if ($selected_test): ?>
                    <a href="grades.php" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Tests
                    </a>
                    <a href="tests.php" class="btn btn-primary">
                        <i class="bi bi-plus"></i> Create Test
                    </a>
                <?php else: ?>
                    <a href="tests.php" class="btn btn-primary">
                        <i class="bi bi-clipboard-check"></i> Manage Tests
                    </a>
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
                                <h6 class="card-title">Total Grades</h6>
                                <h4><?= number_format($summary['total_grades'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-trophy" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Students Graded</h6>
                                <h4><?= number_format($summary['students_graded'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-people" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Average Marks</h6>
                                <h4><?= number_format($summary['avg_marks'] ?? 0, 1) ?></h4>
                            </div>
                            <i class="bi bi-graph-up" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$selected_test): ?>
            <!-- Test Selection View -->
            <!-- Search and Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3" id="searchForm">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" id="searchInput"
                                   placeholder="Search tests, subjects, batches..." 
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
                        <!-- Batch filter removed for simplified structure -->
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" id="statusSelect">
                                <option value="">All Status</option>
                                <option value="graded" <?= $status_filter === 'graded' ? 'selected' : '' ?>>Fully Graded</option>
                                <option value="partial" <?= $status_filter === 'partial' ? 'selected' : '' ?>>Partially Graded</option>
                                <option value="ungraded" <?= $status_filter === 'ungraded' ? 'selected' : '' ?>>Not Graded</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="grades.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tests List -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($available_tests)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-trophy text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 text-muted">No Tests Available</h5>
                            <p class="text-muted">Create tests first to start grading students.</p>
                            <a href="tests.php" class="btn btn-primary">
                                <i class="bi bi-plus"></i> Create Test
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Test Details</th>
                                        <th>Subject & Batch</th>
                                        <th>Date</th>
                                        <th>Grading Progress</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($available_tests as $test): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($test['test_name']) ?></strong><br>
                                                    <small class="text-muted">Total Marks: <?= $test['max_marks'] ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <?= htmlspecialchars($test['subject_code']) ?><br>
                                                    <small class="text-muted">Semester <?= $test['semester'] ?> • <?= $test['credits'] ?> credits</small>
                                                </div>
                                            </td>
                                            <td>
                                                <?= date('M j, Y', strtotime($test['test_date'])) ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $progress = $test['total_students'] > 0 ? ($test['graded_count'] / $test['total_students']) * 100 : 0;
                                                $status_class = $progress == 100 ? 'bg-success' : ($progress > 0 ? 'bg-warning' : 'bg-danger');
                                                ?>
                                                <div class="progress mb-1" style="height: 10px;">
                                                    <div class="progress-bar <?= $status_class ?>" style="width: <?= $progress ?>%"></div>
                                                </div>
                                                <small class="text-muted">
                                                    <?= $test['graded_count'] ?>/<?= $test['total_students'] ?> students
                                                    (<?= number_format($progress, 1) ?>%)
                                                </small>
                                            </td>
                                            <td>
                                                <a href="grades.php?test_id=<?= $test['test_id'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i> Grade Students
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Grade Entry View -->
            <div class="row">
                <!-- Test Information -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Test Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Subject:</strong> <?= htmlspecialchars($selected_test['subject_code'] . ' - ' . $selected_test['subject_name']) ?><br>
                                    <strong>Semester:</strong> <?= $selected_test['semester'] ?> (<?= $selected_test['credits'] ?> credits)<br>
                                    <strong>Faculty:</strong> <?= htmlspecialchars($selected_test['faculty_name']) ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Test Date:</strong> <?= date('M j, Y', strtotime($selected_test['test_date'])) ?><br>
                                    <strong>Total Marks:</strong> <?= $selected_test['max_marks'] ?><br>
                                    <strong>Duration:</strong> <?= $selected_test['duration'] ?> minutes
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grade Entry Form -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Student Grades</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($students_with_grades)): ?>
                                <div class="text-center py-4">
                                    <p class="text-muted">No active students found in this batch.</p>
                                </div>
                            <?php else: ?>
                                <form method="POST" id="gradeForm">
                                    <input type="hidden" name="action" value="save_grades">
                                    <input type="hidden" name="test_id" value="<?= $test_id ?>">
                                    
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>
                                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                                    </th>
                                                    <th>Student ID</th>
                                                    <th>Student Name</th>
                                                    <th>Marks</th>
                                                    <th>%</th>
                                                    <th>Grade</th>
                                                    <th>Absent</th>
                                                    <th>Remarks</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students_with_grades as $student): ?>
                                                    <?php 
                                                    $row_class = '';
                                                    if ($student['is_absent']) {
                                                        $row_class = 'absent';
                                                    } elseif ($student['grade_id']) {
                                                        $row_class = 'graded';
                                                    }
                                                    ?>
                                                    <tr class="student-row <?= $row_class ?>">
                                                        <td>
                                                            <input type="checkbox" name="student_ids[]" 
                                                                   value="<?= $student['student_pk_id'] ?>" 
                                                                   class="form-check-input student-checkbox">
                                                        </td>
                                                        <td><?= htmlspecialchars($student['student_id']) ?></td>
                                                        <td><?= htmlspecialchars($student['full_name']) ?></td>
                                                        <td>
                                                            <input type="number" 
                                                                   name="grades[<?= $student['student_pk_id'] ?>][marks_obtained]" 
                                                                   class="form-control grade-input" 
                                                                   min="0" max="<?= $selected_test['max_marks'] ?>" 
                                                                   step="0.01"
                                                                   value="<?= $student['marks_obtained'] ?? '' ?>"
                                                                   <?= $student['is_absent'] ? 'disabled' : '' ?>>
                                                        </td>
                                                        <td>
                                                            <span class="percentage-display">
                                                                <?= $student['percentage'] ? number_format($student['percentage'], 1) . '%' : '' ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="grade-display">
                                                                <?= $student['letter_grade'] ?? '' ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <input type="checkbox" 
                                                                   name="grades[<?= $student['student_pk_id'] ?>][is_absent]" 
                                                                   class="form-check-input absent-checkbox"
                                                                   <?= $student['is_absent'] ? 'checked' : '' ?>>
                                                        </td>
                                                        <td>
                                                            <input type="text" 
                                                                   name="grades[<?= $student['student_pk_id'] ?>][remarks]" 
                                                                   class="form-control" 
                                                                   placeholder="Optional"
                                                                   value="<?= htmlspecialchars($student['remarks'] ?? '') ?>">
                                                        </td>
                                                        <td>
                                                            <?php if ($student['grade_id']): ?>
                                                                <small class="text-success">
                                                                    <i class="bi bi-check-circle"></i> Graded
                                                                    <?php if ($student['graded_at']): ?>
                                                                        <br><?= date('M j, g:i A', strtotime($student['graded_at'])) ?>
                                                                    <?php endif; ?>
                                                                </small>
                                                            <?php else: ?>
                                                                <small class="text-muted">
                                                                    <i class="bi bi-clock"></i> Pending
                                                                </small>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-save"></i> Save All Grades
                                        </button>
                                        <button type="button" class="btn btn-warning ms-2" id="bulkAbsentBtn">
                                            <i class="bi bi-x-circle"></i> Mark Selected as Absent
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & Grade Scale -->
                <div class="col-md-4">
                    <div class="quick-actions">
                        <!-- Grade Scale Reference -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Grade Scale Reference</h6>
                            </div>
                            <div class="card-body grade-scale">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Grade</th>
                                            <th>Range</th>
                                            <th>Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grade_ranges as $range): ?>
                                            <tr>
                                                <td><strong><?= $range['grade_label'] ?></strong></td>
                                                <td><?= $range['min_percentage'] ?>-<?= $range['max_percentage'] ?>%</td>
                                                <td><?= $range['grade_points'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Quick Statistics</h6>
                            </div>
                            <div class="card-body">
                                <div id="quickStats">
                                    <small class="text-muted">Enter grades to see statistics</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bulk Absent Modal -->
    <div class="modal fade" id="bulkAbsentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Students as Absent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="bulkAbsentForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="bulk_absent">
                        <input type="hidden" name="test_id" value="<?= $test_id ?>">
                        <p>Are you sure you want to mark the selected students as absent? This will set their marks to 0.</p>
                        <div id="selectedStudentsList"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Mark as Absent</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-calculate percentage and grade when marks are entered
        document.addEventListener('input', function(e) {
            if (e.target.matches('input[name*="[marks_obtained]"]')) {
                const marks = parseFloat(e.target.value) || 0;
                const totalMarks = <?= $selected_test['max_marks'] ?? 100 ?>;
                const percentage = (marks / totalMarks) * 100;
                
                const row = e.target.closest('tr');
                const percentageDisplay = row.querySelector('.percentage-display');
                const gradeDisplay = row.querySelector('.grade-display');
                
                percentageDisplay.textContent = percentage > 0 ? percentage.toFixed(1) + '%' : '';
                
                // Calculate grade based on percentage
                const gradeRanges = <?= json_encode($grade_ranges) ?>;
                let grade = '';
                for (let range of gradeRanges) {
                    if (percentage >= range.min_percentage && percentage <= range.max_percentage) {
                        grade = range.grade_label;
                        break;
                    }
                }
                gradeDisplay.textContent = grade;
                
                // Update quick stats
                updateQuickStats();
            }
        });

        // Handle absent checkbox
        document.addEventListener('change', function(e) {
            if (e.target.matches('.absent-checkbox')) {
                const row = e.target.closest('tr');
                const marksInput = row.querySelector('input[name*="[marks_obtained]"]');
                const percentageDisplay = row.querySelector('.percentage-display');
                const gradeDisplay = row.querySelector('.grade-display');
                
                if (e.target.checked) {
                    marksInput.disabled = true;
                    marksInput.value = '';
                    percentageDisplay.textContent = '';
                    gradeDisplay.textContent = '';
                    row.classList.add('absent');
                    row.classList.remove('graded');
                } else {
                    marksInput.disabled = false;
                    row.classList.remove('absent');
                }
                
                updateQuickStats();
            }
        });

        // Select all functionality
        document.getElementById('selectAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Bulk absent functionality
        document.getElementById('bulkAbsentBtn')?.addEventListener('click', function() {
            const selectedStudents = document.querySelectorAll('.student-checkbox:checked');
            if (selectedStudents.length === 0) {
                alert('Please select students to mark as absent.');
                return;
            }
            
            // Populate the modal with selected student names
            const studentsList = Array.from(selectedStudents).map(cb => {
                const row = cb.closest('tr');
                const name = row.querySelector('td:nth-child(3)').textContent;
                return `<input type="hidden" name="student_ids[]" value="${cb.value}"><li>${name}</li>`;
            });
            
            document.getElementById('selectedStudentsList').innerHTML = 
                '<ul>' + studentsList.map(s => s.replace(/^.*<li>/, '<li>')).join('') + '</ul>';
            
            // Add hidden inputs to the form
            const form = document.getElementById('bulkAbsentForm');
            const existingInputs = form.querySelectorAll('input[name="student_ids[]"]');
            existingInputs.forEach(input => input.remove());
            
            selectedStudents.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'student_ids[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            
            new bootstrap.Modal(document.getElementById('bulkAbsentModal')).show();
        });

        function updateQuickStats() {
            const marksInputs = document.querySelectorAll('input[name*="[marks_obtained]"]');
            const totalMarks = <?= $selected_test['max_marks'] ?? 100 ?>;
            let totalStudents = 0;
            let gradedStudents = 0;
            let absentStudents = 0;
            let totalMarksObtained = 0;
            
            marksInputs.forEach(input => {
                const row = input.closest('tr');
                const isAbsent = row.querySelector('.absent-checkbox').checked;
                const marks = parseFloat(input.value) || 0;
                
                totalStudents++;
                if (isAbsent) {
                    absentStudents++;
                    gradedStudents++;
                } else if (input.value !== '') {
                    gradedStudents++;
                    totalMarksObtained += marks;
                }
            });
            
            const presentStudents = gradedStudents - absentStudents;
            const avgMarks = presentStudents > 0 ? totalMarksObtained / presentStudents : 0;
            const avgPercentage = (avgMarks / totalMarks) * 100;
            
            document.getElementById('quickStats').innerHTML = `
                <small>
                    <strong>Total Students:</strong> ${totalStudents}<br>
                    <strong>Graded:</strong> ${gradedStudents}/${totalStudents}<br>
                    <strong>Absent:</strong> ${absentStudents}<br>
                    <strong>Average Marks:</strong> ${avgMarks.toFixed(1)}<br>
                    <strong>Average %:</strong> ${avgPercentage.toFixed(1)}%
                </small>
            `;
        }

        // Initial stats update
        <?php if ($selected_test): ?>
            updateQuickStats();
        <?php endif; ?>

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
        document.getElementById('statusSelect').addEventListener('change', submitSearch);
    </script>
</body>
</html>