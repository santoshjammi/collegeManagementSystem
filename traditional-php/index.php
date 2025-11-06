<?php
// Redirect to the new unified dashboard
header('Location: dashboard.php');
exit;
?>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - College Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card.students { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.faculty { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.courses { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card.admissions { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-card.payments { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-mortarboard-fill me-2"></i>
                College Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="students.php">
                            <i class="bi bi-people"></i> Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="faculty.php">
                            <i class="bi bi-person-badge"></i> Faculty
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="subjects.php">
                            <i class="bi bi-journal-text"></i> Subjects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tests.php">
                            <i class="bi bi-clipboard-check"></i> Tests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="grades.php">
                            <i class="bi bi-trophy"></i> Grades
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="student-grades.php">
                            <i class="bi bi-award"></i> Student Grades
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admissions.php">
                            <i class="bi bi-file-earmark-text"></i> Admissions
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
                    <li class="nav-item">
                        <a class="nav-link" href="placements.php">
                            <i class="bi bi-briefcase"></i> Placements
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['username']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-3">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </h1>
                <p class="text-muted">Welcome back, <?= htmlspecialchars($user['username']) ?>! Here's your college overview.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card stat-card students h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">Students</h6>
                                <h2 class="mb-0"><?= number_format($studentsCount) ?></h2>
                            </div>
                            <div class="text-white-50">
                                <i class="bi bi-people" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="students.php" class="btn btn-sm btn-light">
                                <i class="bi bi-arrow-right"></i> View All
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card stat-card faculty h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">Faculty</h6>
                                <h2 class="mb-0"><?= number_format($facultyCount) ?></h2>
                            </div>
                            <div class="text-white-50">
                                <i class="bi bi-person-badge" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="faculty.php" class="btn btn-sm btn-light">
                                <i class="bi bi-arrow-right"></i> View All
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card stat-card courses h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">Courses</h6>
                                <h2 class="mb-0"><?= number_format($coursesCount) ?></h2>
                            </div>
                            <div class="text-white-50">
                                <i class="bi bi-journal-bookmark" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="text-white-50 small">Active Programs</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card stat-card admissions h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">Recent Admissions</h6>
                                <h2 class="mb-0"><?= number_format($recentAdmissions) ?></h2>
                            </div>
                            <div class="text-white-50">
                                <i class="bi bi-file-earmark-plus" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="admissions.php" class="btn btn-sm btn-light">
                                <i class="bi bi-arrow-right"></i> View All
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Students -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person-plus me-2"></i>Recent Students
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentStudents)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-people" style="font-size: 3rem;"></i>
                                <p class="mt-2">No students found</p>
                                <a href="students.php" class="btn btn-primary">
                                    <i class="bi bi-plus"></i> Add First Student
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Course</th>
                                            <th>Batch</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentStudents as $student): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($student['student_id']) ?></td>
                                                <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                                                <td><?= htmlspecialchars($student['course_name'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($student['batch_name'] ?? 'N/A') ?></td>
                                                <td>
                                                    <span class="badge bg-success">Active</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center">
                                <a href="students.php" class="btn btn-outline-primary">
                                    View All Students <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-lightning me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="students.php" class="btn btn-outline-primary">
                                <i class="bi bi-person-plus me-2"></i>Add Student
                            </a>
                            <a href="faculty.php" class="btn btn-outline-success">
                                <i class="bi bi-person-badge me-2"></i>Add Faculty
                            </a>
                            <a href="admissions.php" class="btn btn-outline-info">
                                <i class="bi bi-file-earmark-plus me-2"></i>New Admission
                            </a>
                            <a href="library.php" class="btn btn-outline-warning">
                                <i class="bi bi-book me-2"></i>Library Management
                            </a>
                        </div>
                        
                        <?php if ($pendingPayments > 0): ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                <small>
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <?= $pendingPayments ?> pending payment(s)
                                </small>
                                <div class="mt-1">
                                    <a href="fees.php" class="btn btn-sm btn-warning">Review</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>