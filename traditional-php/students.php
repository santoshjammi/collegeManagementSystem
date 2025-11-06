<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = $user['role_name'] ?? 'Student';

// Check if user can access this module
if (!canRead('students')) {
    $_SESSION['error'] = 'You do not have permission to access this module.';
    redirect('/dashboard.php');
}

// Get notifications
$notifications = getNotifications($userRole, $user);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $student_id = $_POST['student_id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM students WHERE student_pk_id = ?");
    $stmt->execute([$student_id]);
    $_SESSION['message'] = 'Student deleted successfully';
    redirect('/students.php');
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $data = [
        'student_id' => $_POST['student_id'] ?? '',
        'full_name' => $_POST['full_name'] ?? '',
        'contact_number' => $_POST['contact_number'] ?? '',
        'email' => $_POST['email'] ?? '',
        'course_id' => $_POST['course_id'] ?? null,
        'batch_id' => $_POST['batch_id'] ?? null,
        'academic_status' => $_POST['academic_status'] ?? 'Active',
        'anticipated_graduation_year' => $_POST['anticipated_graduation_year'] ?? null,
        'gender' => $_POST['gender'] ?? 'Other',
        'date_of_birth' => $_POST['date_of_birth'] ?? null,
        'date_of_joining' => $_POST['date_of_joining'] ?? null,
        'address_line1' => $_POST['address_line1'] ?? '',
        'address_line2' => $_POST['address_line2'] ?? '',
        'city' => $_POST['city'] ?? '',
        'state' => $_POST['state'] ?? '',
        'postal_code' => $_POST['postal_code'] ?? '',
        'country' => $_POST['country'] ?? 'India',
        'guardian_name' => $_POST['guardian_name'] ?? '',
        'guardian_relation' => $_POST['guardian_relation'] ?? '',
        'guardian_contact_number' => $_POST['guardian_contact_number'] ?? '',
        'guardian_email' => $_POST['guardian_email'] ?? '',
        'guardian_occupation' => $_POST['guardian_occupation'] ?? '',
        'guardian_address_line1' => $_POST['guardian_address_line1'] ?? '',
        'guardian_address_line2' => $_POST['guardian_address_line2'] ?? '',
        'guardian_city' => $_POST['guardian_city'] ?? '',
        'guardian_state' => $_POST['guardian_state'] ?? '',
        'guardian_postal_code' => $_POST['guardian_postal_code'] ?? '',
        'guardian_country' => $_POST['guardian_country'] ?? 'India',
        'profile_picture' => null
    ];

    $pictureOption = $_POST['picture_option'] ?? 'avatar';

    if ($pictureOption === 'upload') {
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadProfilePicture('profile_picture', 'student', $_POST['student_pk_id'] ?? null);
            if ($uploadResult['success']) {
                $data['profile_picture'] = $uploadResult['path'];

                // Delete old profile picture if updating
                if (isset($_POST['student_pk_id']) && $_POST['student_pk_id']) {
                    $stmt = $pdo->prepare("SELECT profile_picture FROM students WHERE student_pk_id = ?");
                    $stmt->execute([$_POST['student_pk_id']]);
                    $oldStudent = $stmt->fetch();
                    if ($oldStudent && $oldStudent['profile_picture']) {
                        deleteProfilePicture($oldStudent['profile_picture']);
                    }
                }
            } else {
                $_SESSION['error'] = $uploadResult['message'];
                redirect('/students.php');
            }
        }
    } elseif ($pictureOption === 'avatar') {
        // Use selected avatar (set profile_picture to null, gender will determine avatar)
        $data['profile_picture'] = null;
    }
    
    if (isset($_POST['student_pk_id']) && $_POST['student_pk_id']) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE students SET 
                student_id = ?,
                full_name = ?,
                contact_number = ?,
                email = ?,
                course_id = ?,
                batch_id = ?,
                academic_status = ?,
                anticipated_graduation_year = ?,
                gender = ?,
                date_of_birth = ?,
                date_of_joining = ?,
                address_line1 = ?,
                address_line2 = ?,
                city = ?,
                state = ?,
                postal_code = ?,
                country = ?,
                guardian_name = ?,
                guardian_relation = ?,
                guardian_contact_number = ?,
                guardian_email = ?,
                guardian_occupation = ?,
                guardian_address_line1 = ?,
                guardian_address_line2 = ?,
                guardian_city = ?,
                guardian_state = ?,
                guardian_postal_code = ?,
                guardian_country = ?,
                profile_picture = ?
            WHERE student_pk_id = ?
        ");
        $stmt->execute([
            $data['student_id'],
            $data['full_name'],
            $data['contact_number'],
            $data['email'],
            $data['course_id'],
            $data['batch_id'],
            $data['academic_status'],
            $data['anticipated_graduation_year'],
            $data['gender'],
            $data['date_of_birth'],
            $data['date_of_joining'],
            $data['address_line1'],
            $data['address_line2'],
            $data['city'],
            $data['state'],
            $data['postal_code'],
            $data['country'],
            $data['guardian_name'],
            $data['guardian_relation'],
            $data['guardian_contact_number'],
            $data['guardian_email'],
            $data['guardian_occupation'],
            $data['guardian_address_line1'],
            $data['guardian_address_line2'],
            $data['guardian_city'],
            $data['guardian_state'],
            $data['guardian_postal_code'],
            $data['guardian_country'],
            $data['profile_picture'],
            $_POST['student_pk_id']
        ]);
        $_SESSION['message'] = 'Student updated successfully';
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO students (student_id, full_name, contact_number, email, course_id, batch_id, academic_status, anticipated_graduation_year, gender, date_of_birth, date_of_joining, address_line1, address_line2, city, state, postal_code, country, guardian_name, guardian_relation, guardian_contact_number, guardian_email, guardian_occupation, guardian_address_line1, guardian_address_line2, guardian_city, guardian_state, guardian_postal_code, guardian_country, profile_picture, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['student_id'],
            $data['full_name'],
            $data['contact_number'],
            $data['email'],
            $data['course_id'],
            $data['batch_id'],
            $data['academic_status'],
            $data['anticipated_graduation_year'],
            $data['gender'],
            $data['date_of_birth'],
            $data['date_of_joining'],
            $data['address_line1'],
            $data['address_line2'],
            $data['city'],
            $data['state'],
            $data['postal_code'],
            $data['country'],
            $data['guardian_name'],
            $data['guardian_relation'],
            $data['guardian_contact_number'],
            $data['guardian_email'],
            $data['guardian_occupation'],
            $data['guardian_address_line1'],
            $data['guardian_address_line2'],
            $data['guardian_city'],
            $data['guardian_state'],
            $data['guardian_postal_code'],
            $data['guardian_country'],
            $data['profile_picture']
        ]);
        $_SESSION['message'] = 'Student added successfully';
    }
    redirect('/students.php');
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$course_filter = $_GET['course'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$query = "
    SELECT s.*, c.course_name, c.course_code, b.batch_name 
    FROM students s
    LEFT JOIN courses c ON s.course_id = c.course_id
    LEFT JOIN batches b ON s.batch_id = b.batch_id
    WHERE 1=1
";

$params = [];

if ($search) {
    $query .= " AND (s.student_id LIKE ? OR s.full_name LIKE ? OR s.email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($course_filter) {
    $query .= " AND s.course_id = ?";
    $params[] = $course_filter;
}

if ($status_filter) {
    $query .= " AND s.academic_status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get courses for filter
$courses = $pdo->query("SELECT * FROM courses ORDER BY course_name")->fetchAll();
$batches = $pdo->query("SELECT * FROM batches ORDER BY batch_year DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - <?php echo SITE_NAME; ?></title>
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

        .table-actions { 
            white-space: nowrap; 
        }

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
                <?php echo escape($_SESSION['message']); unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-3">
                    <i class="bi bi-people me-2"></i>
                    <?php echo hasRole(['Faculty']) ? 'Student Directory' : 'Student Management'; ?>
                </h1>
                <?php if (hasRole(['Faculty'])): ?>
                <p class="text-muted">View student information and academic details</p>
                <?php endif; ?>
            </div>
            <div class="col-auto">
                <?php if (canWrite('students')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal" onclick="resetForm()">
                    <i class="bi bi-plus"></i> Add Student
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="searchForm">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" id="searchInput"
                               placeholder="Search by ID, Name, Email..."
                               value="<?php echo escape($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Course</label>
                        <select class="form-select" name="course" id="courseSelect">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>"
                                    <?php echo $course_filter == $course['course_id'] ? 'selected' : ''; ?>>
                                    <?php echo escape($course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="statusSelect">
                            <option value="">All Status</option>
                            <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Graduated" <?php echo $status_filter === 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                            <option value="Suspended" <?php echo $status_filter === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="students.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Students Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                                    <tr>
                                        <th>Photo</th>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>Course</th>
                                        <th>Date of Joining</th>
                                        <th>Guardian</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No students found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo getProfilePictureUrl($student['profile_picture'] ?? null, $student['gender'] ?? 'Other'); ?>"
                                                     alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                            </td>
                                            <td><?php echo escape($student['student_id']); ?></td>
                                            <td><?php echo escape($student['full_name']); ?></td>
                                            <td><?php echo escape($student['email'] ?? '-'); ?></td>
                                            <td><?php echo escape($student['contact_number'] ?? '-'); ?></td>
                                            <td>
                                                <?php if ($student['course_name']): ?>
                                                    <span class="badge bg-info">
                                                        <?php echo escape($student['course_code']); ?>
                                                    </span>
                                                    <?php echo escape($student['course_name']); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo escape($student['date_of_joining'] ? date('M d, Y', strtotime($student['date_of_joining'])) : '-'); ?></td>
                                            <td><?php echo escape($student['guardian_name'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $student['academic_status'] === 'Active' ? 'success' : 
                                                        ($student['academic_status'] === 'Graduated' ? 'primary' : 'warning'); 
                                                ?>">
                                                    <?php echo escape($student['academic_status']); ?>
                                                </span>
                                            </td>
                            <td class="table-actions">
                                <?php if (canWrite('students')): ?>
                                <button class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editStudent(<?php echo htmlspecialchars(json_encode($student), ENT_QUOTES); ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" style="display:inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete this student?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_id" value="<?php echo $student['student_pk_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php elseif (hasRole(['Faculty'])): ?>
                                <button class="btn btn-sm btn-outline-info" 
                                        onclick="viewStudentDetails(<?php echo $student['student_pk_id']; ?>)">
                                    <i class="bi bi-eye"></i> View
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="student_pk_id" id="student_pk_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Student ID *</label>
                                <input type="text" class="form-control" name="student_id" id="student_id" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" id="full_name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" class="form-control" name="contact_number" id="contact_number">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course *</label>
                                <select class="form-select" name="course_id" id="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>">
                                            <?php echo escape($course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Batch *</label>
                                <select class="form-select" name="batch_id" id="batch_id" required>
                                    <option value="">Select Batch</option>
                                    <?php foreach ($batches as $batch): ?>
                                        <option value="<?php echo $batch['batch_id']; ?>">
                                            <?php echo escape($batch['batch_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Academic Status *</label>
                                <select class="form-select" name="academic_status" id="academic_status" required>
                                    <option value="Active">Active</option>
                                    <option value="Graduated">Graduated</option>
                                    <option value="Suspended">Suspended</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender" id="gender">
                                    <option value="Other">Other</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth" id="date_of_birth">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Joining</label>
                                <input type="date" class="form-control" name="date_of_joining" id="date_of_joining">
                            </div>
                        </div>

                        <h6 class="mt-4 mb-3">Address Information</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address Line 1</label>
                                <input type="text" class="form-control" name="address_line1" id="address_line1" placeholder="Street address">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address Line 2</label>
                                <input type="text" class="form-control" name="address_line2" id="address_line2" placeholder="Apartment, suite, etc.">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city" id="city">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="state" id="state">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Postal Code</label>
                                <input type="text" class="form-control" name="postal_code" id="postal_code">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control" name="country" id="country" value="India">
                            </div>
                        </div>

                        <h6 class="mt-4 mb-3">Guardian Information</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guardian Name</label>
                                <input type="text" class="form-control" name="guardian_name" id="guardian_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Relationship</label>
                                <select class="form-select" name="guardian_relation" id="guardian_relation">
                                    <option value="">Select Relationship</option>
                                    <option value="Father">Father</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Guardian">Guardian</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guardian Contact Number</label>
                                <input type="text" class="form-control" name="guardian_contact_number" id="guardian_contact_number">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guardian Email</label>
                                <input type="email" class="form-control" name="guardian_email" id="guardian_email">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guardian Occupation</label>
                                <input type="text" class="form-control" name="guardian_occupation" id="guardian_occupation">
                            </div>
                        </div>

                        <h6 class="mt-4 mb-3">Guardian Address</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guardian Address Line 1</label>
                                <input type="text" class="form-control" name="guardian_address_line1" id="guardian_address_line1" placeholder="Street address">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guardian Address Line 2</label>
                                <input type="text" class="form-control" name="guardian_address_line2" id="guardian_address_line2" placeholder="Apartment, suite, etc.">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Guardian City</label>
                                <input type="text" class="form-control" name="guardian_city" id="guardian_city">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Guardian State</label>
                                <input type="text" class="form-control" name="guardian_state" id="guardian_state">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Guardian Postal Code</label>
                                <input type="text" class="form-control" name="guardian_postal_code" id="guardian_postal_code">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guardian Country</label>
                                <input type="text" class="form-control" name="guardian_country" id="guardian_country" value="India">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Anticipated Graduation Year</label>
                                <input type="number" class="form-control" name="anticipated_graduation_year"
                                       id="anticipated_graduation_year" min="2020" max="2050">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Profile Picture Option</label>
                                <select class="form-select" name="picture_option" id="picture_option" onchange="togglePictureOptions()">
                                    <option value="avatar">Use Default Avatar</option>
                                    <option value="upload">Upload Picture</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <div id="avatarSelection" style="display: block;">
                                    <label class="form-label">Choose Avatar</label>
                                    <div class="d-flex gap-3">
                                        <?php $avatars = getAvailableAvatars(); ?>
                                        <?php foreach ($avatars as $gender => $path): ?>
                                        <div class="text-center">
                                            <img src="<?php echo $path; ?>" alt="<?php echo ucfirst($gender); ?> Avatar"
                                                 class="rounded-circle border avatar-option"
                                                 style="width: 60px; height: 60px; cursor: pointer; object-fit: cover;"
                                                 onclick="selectAvatar('<?php echo $gender; ?>')">
                                            <br><small class="text-muted"><?php echo ucfirst($gender); ?></small>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="selected_avatar" id="selected_avatar" value="other">
                                </div>

                                <div id="pictureUpload" style="display: none;">
                                    <label class="form-label">Upload Profile Picture</label>
                                    <input type="file" class="form-control" name="profile_picture" id="profile_picture"
                                           accept="image/jpeg,image/png,image/gif">
                                    <div class="form-text">Accepted formats: JPG, PNG, GIF. Maximum size: 2MB</div>
                                </div>

                                <div id="currentPicture" class="mt-2" style="display: none;">
                                    <small class="text-muted">Current picture:</small><br>
                                    <img id="currentPictureImg" src="" alt="Current profile picture" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Add Student';
            document.querySelector('#studentModal form').reset();
            document.getElementById('student_pk_id').value = '';
            document.getElementById('currentPicture').style.display = 'none';
            document.getElementById('picture_option').value = 'avatar';
            togglePictureOptions();
            selectAvatar('other');
        }

        function togglePictureOptions() {
            const option = document.getElementById('picture_option').value;
            const avatarDiv = document.getElementById('avatarSelection');
            const uploadDiv = document.getElementById('pictureUpload');

            if (option === 'avatar') {
                avatarDiv.style.display = 'block';
                uploadDiv.style.display = 'none';
            } else {
                avatarDiv.style.display = 'none';
                uploadDiv.style.display = 'block';
            }
        }

        function selectAvatar(gender) {
            document.getElementById('selected_avatar').value = gender;
            // Remove selected class from all avatars
            document.querySelectorAll('.avatar-option').forEach(img => {
                img.classList.remove('border-primary');
                img.classList.add('border');
            });
            // Add selected class to clicked avatar
            event.target.classList.remove('border');
            event.target.classList.add('border-primary');
        }

        function editStudent(student) {
            document.getElementById('modalTitle').textContent = 'Edit Student';
            document.getElementById('student_pk_id').value = student.student_pk_id;
            document.getElementById('student_id').value = student.student_id;
            document.getElementById('full_name').value = student.full_name;
            document.getElementById('email').value = student.email || '';
            document.getElementById('contact_number').value = student.contact_number || '';
            document.getElementById('course_id').value = student.course_id || '';
            document.getElementById('batch_id').value = student.batch_id || '';
            document.getElementById('academic_status').value = student.academic_status;
            document.getElementById('anticipated_graduation_year').value = student.anticipated_graduation_year || '';
            document.getElementById('gender').value = student.gender || 'Other';
            
            // New fields
            document.getElementById('date_of_birth').value = student.date_of_birth || '';
            document.getElementById('date_of_joining').value = student.date_of_joining || '';
            document.getElementById('address_line1').value = student.address_line1 || '';
            document.getElementById('address_line2').value = student.address_line2 || '';
            document.getElementById('city').value = student.city || '';
            document.getElementById('state').value = student.state || '';
            document.getElementById('postal_code').value = student.postal_code || '';
            document.getElementById('country').value = student.country || 'India';
            document.getElementById('guardian_name').value = student.guardian_name || '';
            document.getElementById('guardian_relation').value = student.guardian_relation || '';
            document.getElementById('guardian_contact_number').value = student.guardian_contact_number || '';
            document.getElementById('guardian_email').value = student.guardian_email || '';
            document.getElementById('guardian_occupation').value = student.guardian_occupation || '';
            document.getElementById('guardian_address_line1').value = student.guardian_address_line1 || '';
            document.getElementById('guardian_address_line2').value = student.guardian_address_line2 || '';
            document.getElementById('guardian_city').value = student.guardian_city || '';
            document.getElementById('guardian_state').value = student.guardian_state || '';
            document.getElementById('guardian_postal_code').value = student.guardian_postal_code || '';
            document.getElementById('guardian_country').value = student.guardian_country || 'India';

            // Handle profile picture options
            const hasPicture = student.profile_picture;
            const pictureOption = hasPicture ? 'upload' : 'avatar';
            document.getElementById('picture_option').value = pictureOption;
            togglePictureOptions();

            if (hasPicture) {
                document.getElementById('currentPictureImg').src = student.profile_picture;
                document.getElementById('currentPicture').style.display = 'block';
            } else {
                document.getElementById('currentPicture').style.display = 'none';
                // Set selected avatar based on gender
                selectAvatar((student.gender || 'Other').toLowerCase());
            }

            new bootstrap.Modal(document.getElementById('studentModal')).show();
        }

        // Real-time search functionality
        let searchTimeout;
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const courseSelect = document.getElementById('courseSelect');
        const statusSelect = document.getElementById('statusSelect');

        // Auto-submit on search input (debounced)
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchForm.submit();
            }, 500); // 500ms delay
        });

        // Auto-submit on select changes
        courseSelect.addEventListener('change', function() {
            searchForm.submit();
        });

        statusSelect.addEventListener('change', function() {
            searchForm.submit();
        });

        // Clear search timeout on form submit
        searchForm.addEventListener('submit', function() {
            clearTimeout(searchTimeout);
        });

        // View student details function for faculty
        function viewStudentDetails(studentId) {
            window.location.href = 'student_details.php?id=' + studentId;
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('#studentModal form');
            const requiredFields = [
                { id: 'student_id', name: 'Student ID' },
                { id: 'full_name', name: 'Full Name' },
                { id: 'course_id', name: 'Course' },
                { id: 'batch_id', name: 'Batch' },
                { id: 'academic_status', name: 'Academic Status' }
            ];

            // Add blur event listeners for real-time validation
            requiredFields.forEach(field => {
                const element = document.getElementById(field.id);
                if (element) {
                    element.addEventListener('blur', function() {
                        validateField(field.id, field.name);
                    });
                }
            });

            // Email validation
            const emailField = document.getElementById('email');
            if (emailField) {
                emailField.addEventListener('blur', function() {
                    validateEmail();
                });
            }

            // Date validation
            const dateOfBirthField = document.getElementById('date_of_birth');
            const dateOfJoiningField = document.getElementById('date_of_joining');

            if (dateOfBirthField) {
                dateOfBirthField.addEventListener('blur', function() {
                    validateDateOfBirth();
                });
            }

            if (dateOfJoiningField) {
                dateOfJoiningField.addEventListener('blur', function() {
                    validateDateOfJoining();
                });
            }

            // Form submit validation
            form.addEventListener('submit', function(e) {
                let isValid = true;

                // Clear previous errors
                clearAllErrors();

                // Validate required fields
                requiredFields.forEach(field => {
                    if (!validateField(field.id, field.name)) {
                        isValid = false;
                    }
                });

                // Validate email if provided
                if (emailField && emailField.value.trim() !== '') {
                    if (!validateEmail()) {
                        isValid = false;
                    }
                }

                // Validate dates if provided
                if (dateOfBirthField && dateOfBirthField.value) {
                    if (!validateDateOfBirth()) {
                        isValid = false;
                    }
                }

                if (dateOfJoiningField && dateOfJoiningField.value) {
                    if (!validateDateOfJoining()) {
                        isValid = false;
                    }
                }

                // Validate contact number format if provided
                const contactField = document.getElementById('contact_number');
                if (contactField && contactField.value.trim() !== '') {
                    if (!validateContactNumber()) {
                        isValid = false;
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    const firstError = document.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                }
            });
        });

        function validateField(fieldId, fieldName) {
            const field = document.getElementById(fieldId);
            const value = field.value.trim();
            let isValid = true;
            let errorMessage = '';

            // Clear previous error
            clearFieldError(fieldId);

            if (!value) {
                isValid = false;
                errorMessage = `${fieldName} is required.`;
            } else {
                // Additional validation based on field type
                switch(fieldId) {
                    case 'student_id':
                        if (value.length < 3) {
                            isValid = false;
                            errorMessage = 'Student ID must be at least 3 characters long.';
                        }
                        break;
                    case 'full_name':
                        if (value.length < 2) {
                            isValid = false;
                            errorMessage = 'Full Name must be at least 2 characters long.';
                        }
                        break;
                }
            }

            if (!isValid) {
                showFieldError(fieldId, errorMessage);
            }

            return isValid;
        }

        function validateEmail() {
            const emailField = document.getElementById('email');
            const email = emailField.value.trim();
            clearFieldError('email');

            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showFieldError('email', 'Please enter a valid email address.');
                return false;
            }
            return true;
        }

        function validateDateOfBirth() {
            const dobField = document.getElementById('date_of_birth');
            const dob = new Date(dobField.value);
            const today = new Date();
            clearFieldError('date_of_birth');

            if (dobField.value && dob > today) {
                showFieldError('date_of_birth', 'Date of Birth cannot be in the future.');
                return false;
            }

            // Check if age is reasonable (not more than 100 years old)
            const age = today.getFullYear() - dob.getFullYear();
            if (dobField.value && age > 100) {
                showFieldError('date_of_birth', 'Please check the date of birth.');
                return false;
            }

            return true;
        }

        function validateDateOfJoining() {
            const dojField = document.getElementById('date_of_joining');
            const doj = new Date(dojField.value);
            const today = new Date();
            clearFieldError('date_of_joining');

            if (dojField.value && doj > today) {
                showFieldError('date_of_joining', 'Date of Joining cannot be in the future.');
                return false;
            }

            return true;
        }

        function validateContactNumber() {
            const contactField = document.getElementById('contact_number');
            const contact = contactField.value.trim();
            clearFieldError('contact_number');

            // Basic phone number validation (allows digits, spaces, hyphens, parentheses, plus)
            if (contact && !/^[\d\s\-\(\)\+]{10,15}$/.test(contact.replace(/\s/g, ''))) {
                showFieldError('contact_number', 'Please enter a valid contact number (10-15 digits).');
                return false;
            }
            return true;
        }

        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            field.classList.add('is-invalid');

            // Create or update error message
            let errorElement = document.getElementById(fieldId + '_error');
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.id = fieldId + '_error';
                errorElement.className = 'invalid-feedback';
                field.parentNode.appendChild(errorElement);
            }
            errorElement.textContent = message;
        }

        function clearFieldError(fieldId) {
            const field = document.getElementById(fieldId);
            field.classList.remove('is-invalid');

            const errorElement = document.getElementById(fieldId + '_error');
            if (errorElement) {
                errorElement.remove();
            }
        }

        function clearAllErrors() {
            // Clear all field errors
            document.querySelectorAll('.is-invalid').forEach(field => {
                field.classList.remove('is-invalid');
            });

            // Remove all error messages
            document.querySelectorAll('.invalid-feedback').forEach(error => {
                error.remove();
            });
        }
    </script>
</body>
</html>
