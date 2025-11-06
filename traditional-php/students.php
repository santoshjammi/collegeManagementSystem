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
            $data['profile_picture'],
            $_POST['student_pk_id']
        ]);
        $_SESSION['message'] = 'Student updated successfully';
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO students (student_id, full_name, contact_number, email, course_id, batch_id, academic_status, anticipated_graduation_year, gender, profile_picture, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
        .table-actions { white-space: nowrap; }
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
                                        <th>Course</th>
                                        <th>Batch</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No students found</td>
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
                                            <td><?php echo escape($student['batch_name'] ?? '-'); ?></td>
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
                                <label class="form-label">Course</label>
                                <select class="form-select" name="course_id" id="course_id">
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>">
                                            <?php echo escape($course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Batch</label>
                                <select class="form-select" name="batch_id" id="batch_id">
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
                                <label class="form-label">Academic Status</label>
                                <select class="form-select" name="academic_status" id="academic_status">
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
    </script>
</body>
</html>
