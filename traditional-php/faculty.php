<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);
$notifications = getNotifications($userRole, $user);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $faculty_id = $_POST['faculty_id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM faculty WHERE faculty_id = ?");
    $stmt->execute([$faculty_id]);
    $_SESSION['message'] = 'Faculty member deleted successfully';
    redirect('/faculty.php');
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $data = [
        'full_name' => $_POST['full_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'contact_number' => $_POST['contact_number'] ?? '',
        'department' => $_POST['department'] ?? '',
        'employment_role' => $_POST['employment_role'] ?? '',
        'highest_degree' => $_POST['highest_degree'] ?? '',
        'primary_subject' => $_POST['primary_subject'] ?? '',
        'is_active' => $_POST['is_active'] ?? 1,
        'gender' => $_POST['gender'] ?? 'Other',
        'profile_picture' => null
    ];

    $pictureOption = $_POST['picture_option'] ?? 'avatar';

    if ($pictureOption === 'upload') {
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadProfilePicture('profile_picture', 'faculty', $_POST['faculty_id'] ?? null);
            if ($uploadResult['success']) {
                $data['profile_picture'] = $uploadResult['path'];

                // Delete old profile picture if updating
                if (isset($_POST['faculty_id']) && $_POST['faculty_id']) {
                    $stmt = $pdo->prepare("SELECT profile_picture FROM faculty WHERE faculty_id = ?");
                    $stmt->execute([$_POST['faculty_id']]);
                    $oldFaculty = $stmt->fetch();
                    if ($oldFaculty && $oldFaculty['profile_picture']) {
                        deleteProfilePicture($oldFaculty['profile_picture']);
                    }
                }
            } else {
                $_SESSION['error'] = $uploadResult['message'];
                redirect('/faculty.php');
            }
        }
    } elseif ($pictureOption === 'avatar') {
        // Use selected avatar (set profile_picture to null, gender will determine avatar)
        $data['profile_picture'] = null;
    }
    
    if (isset($_POST['faculty_id']) && $_POST['faculty_id']) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE faculty SET 
                full_name = ?,
                email = ?,
                contact_number = ?,
                department = ?,
                employment_role = ?,
                highest_degree = ?,
                primary_subject = ?,
                is_active = ?,
                gender = ?,
                profile_picture = ?
            WHERE faculty_id = ?
        ");
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['contact_number'],
            $data['department'],
            $data['employment_role'],
            $data['highest_degree'],
            $data['primary_subject'],
            $data['is_active'],
            $data['gender'],
            $data['profile_picture'],
            $_POST['faculty_id']
        ]);
        $_SESSION['message'] = 'Faculty member updated successfully';
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO faculty (full_name, email, contact_number, department, employment_role, highest_degree, primary_subject, is_active, gender, profile_picture)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['contact_number'],
            $data['department'],
            $data['employment_role'],
            $data['highest_degree'],
            $data['primary_subject'],
            $data['is_active'],
            $data['gender'],
            $data['profile_picture']
        ]);
        $_SESSION['message'] = 'Faculty member added successfully';
    }
    redirect('/faculty.php');
}

// Handle paper delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_paper') {
    if (!canWrite('papers')) {
        $_SESSION['error'] = 'You do not have permission to delete papers';
        redirect('/faculty_papers.php');
    }

    $paper_id = $_POST['paper_id'] ?? 0;

    // Check if user can delete this paper (admin/staff or own paper)
    if ($userRole !== 'Admin' && $userRole !== 'Administrative Staff') {
        $stmt = $pdo->prepare("SELECT faculty_id FROM faculty_papers WHERE paper_id = ?");
        $stmt->execute([$paper_id]);
        $paper = $stmt->fetch();
        if (!$paper || $paper['faculty_id'] != $user['faculty_id']) {
            $_SESSION['error'] = 'You can only delete your own papers';
            redirect('/faculty_papers.php');
        }
    }

    $stmt = $pdo->prepare("DELETE FROM faculty_papers WHERE paper_id = ?");
    $stmt->execute([$paper_id]);
    $_SESSION['message'] = 'Paper deleted successfully';
    redirect('/faculty_papers.php');
}

// Handle paper add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_paper') {
    if (!canWrite('papers')) {
        $_SESSION['error'] = 'You do not have permission to manage papers';
        redirect('/faculty_papers.php');
    }

    $paper_type = $_POST['paper_type'] ?? 'faculty';
    $table = ($paper_type === 'faculty') ? 'faculty_papers' : 'student_papers';
    $id_field = ($paper_type === 'faculty') ? 'faculty_id' : 'student_id';

    $paperData = [
        'title' => trim($_POST['title'] ?? ''),
        'authors' => trim($_POST['authors'] ?? ''),
        'journal_name' => trim($_POST['journal_name'] ?? ''),
        'publication_date' => $_POST['publication_date'] ?: null,
        'doi' => trim($_POST['doi'] ?? ''),
        'abstract' => trim($_POST['abstract'] ?? ''),
        'keywords' => trim($_POST['keywords'] ?? ''),
        'paper_url' => trim($_POST['paper_url'] ?? ''),
        'citation_count' => (int)($_POST['citation_count'] ?? 0),
        'publication_type' => $_POST['publication_type'] ?? 'Journal Article',
        'status' => $_POST['status'] ?? 'Published'
    ];

    // Validate required fields
    if (empty($paperData['title']) || empty($paperData['authors'])) {
        $_SESSION['error'] = 'Title and authors are required';
        redirect('/faculty.php?action=add_paper&type=' . $paper_type);
    }

    // Set author_id based on user role and paper type
    if ($userRole === 'Admin' || $userRole === 'Administrative Staff') {
        $paperData[$id_field] = $_POST[$id_field] ?? 0;
        if (!$paperData[$id_field]) {
            $_SESSION['error'] = 'Please select an author';
            redirect('/faculty.php?action=add_paper&type=' . $paper_type);
        }
    } else {
        // Regular users can only add papers for themselves
        if ($paper_type === 'faculty' && isset($user['faculty_id'])) {
            $paperData[$id_field] = $user['faculty_id'];
        } elseif ($paper_type === 'student' && isset($user['student_id'])) {
            $paperData[$id_field] = $user['student_id'];
        } else {
            $_SESSION['error'] = 'You do not have permission to add this type of paper';
            redirect('/faculty_papers.php');
        }
    }

    if (isset($_POST['paper_id']) && $_POST['paper_id']) {
        // Update
        if ($userRole !== 'Admin' && $userRole !== 'Administrative Staff') {
            // Check if user owns this paper
            $stmt = $pdo->prepare("SELECT $id_field FROM $table WHERE paper_id = ?");
            $stmt->execute([$_POST['paper_id']]);
            $existingPaper = $stmt->fetch();
            if (!$existingPaper || $existingPaper[$id_field] != $user[$id_field]) {
                $_SESSION['error'] = 'You can only edit your own papers';
                redirect('/faculty_papers.php');
            }
        }

        $stmt = $pdo->prepare("
            UPDATE $table SET
                $id_field = ?,
                title = ?,
                authors = ?,
                journal_name = ?,
                publication_date = ?,
                doi = ?,
                abstract = ?,
                keywords = ?,
                paper_url = ?,
                citation_count = ?,
                publication_type = ?,
                status = ?
            WHERE paper_id = ?
        ");
        $stmt->execute([
            $paperData[$id_field],
            $paperData['title'],
            $paperData['authors'],
            $paperData['journal_name'],
            $paperData['publication_date'],
            $paperData['doi'],
            $paperData['abstract'],
            $paperData['keywords'],
            $paperData['paper_url'],
            $paperData['citation_count'],
            $paperData['publication_type'],
            $paperData['status'],
            $_POST['paper_id']
        ]);
        $_SESSION['message'] = 'Paper updated successfully';
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO $table ($id_field, title, authors, journal_name, publication_date, doi, abstract, keywords, paper_url, citation_count, publication_type, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $paperData[$id_field],
            $paperData['title'],
            $paperData['authors'],
            $paperData['journal_name'],
            $paperData['publication_date'],
            $paperData['doi'],
            $paperData['abstract'],
            $paperData['keywords'],
            $paperData['paper_url'],
            $paperData['citation_count'],
            $paperData['publication_type'],
            $paperData['status']
        ]);
        $_SESSION['message'] = 'Paper added successfully';
    }
    redirect('/faculty_papers.php');
}

// Handle GET actions for papers
$show_paper_form = false;
$paper_data = null;
$paper_action = $_GET['action'] ?? '';
$paper_type = $_GET['type'] ?? (($userRole === 'Student') ? 'student' : 'faculty'); // Default based on user role

if ($paper_action === 'add_paper' && canWrite('papers')) {
    $show_paper_form = true;
    $paper_data = [
        'paper_id' => '',
        'author_id' => ($paper_type === 'faculty') ? (($userRole === 'Faculty') ? $user['faculty_id'] : '') : (($userRole === 'Student') ? $user['student_id'] : ''),
        'title' => '',
        'authors' => '',
        'journal_name' => '',
        'publication_date' => '',
        'doi' => '',
        'abstract' => '',
        'keywords' => '',
        'paper_url' => '',
        'citation_count' => 0,
        'publication_type' => 'Journal Article',
        'status' => 'Published',
        'paper_type' => $paper_type
    ];
} elseif ($paper_action === 'edit_paper' && canWrite('papers') && isset($_GET['id'])) {
    $paper_id = (int)$_GET['id'];
    $paper_type = $_GET['type'] ?? 'faculty'; // Get paper type from URL parameter

    $table = ($paper_type === 'faculty') ? 'faculty_papers' : 'student_papers';
    $id_field = ($paper_type === 'faculty') ? 'faculty_id' : 'student_id';
    $user_id_field = ($paper_type === 'faculty') ? 'faculty_id' : 'student_id';

    // Check permissions
    if ($userRole !== 'Admin' && $userRole !== 'Administrative Staff') {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE paper_id = ? AND $id_field = ?");
        $stmt->execute([$paper_id, $user[$user_id_field]]);
        $paper_data = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE paper_id = ?");
        $stmt->execute([$paper_id]);
        $paper_data = $stmt->fetch();
    }

    if ($paper_data) {
        $paper_data['paper_type'] = $paper_type;
        $show_paper_form = true;
    } else {
        $_SESSION['error'] = 'Paper not found or access denied';
        redirect('/faculty_papers.php');
    }
}

// Get faculty list for admin/staff when adding papers
$faculty_list = [];
if (($userRole === 'Admin' || $userRole === 'Administrative Staff') && canWrite('papers')) {
    $stmt = $pdo->prepare("SELECT faculty_id, full_name FROM faculty WHERE is_active = 1 ORDER BY full_name");
    $stmt->execute();
    $faculty_list = $stmt->fetchAll();
}

// Get student list for admin/staff when adding student papers
$student_list = [];
if (($userRole === 'Admin' || $userRole === 'Administrative Staff') && canWrite('papers')) {
    $stmt = $pdo->prepare("SELECT student_id, full_name FROM students WHERE academic_status = 'Active' ORDER BY full_name");
    $stmt->execute();
    $student_list = $stmt->fetchAll();
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$query = "SELECT * FROM faculty WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (faculty_id LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($department_filter) {
    $query .= " AND department = ?";
    $params[] = $department_filter;
}

if ($status_filter) {
    $query .= " AND is_active = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY full_name";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $faculty = $stmt->fetchAll();
    
    // Get departments for filter
    $stmt = $pdo->prepare("SELECT DISTINCT department FROM faculty WHERE department IS NOT NULL ORDER BY department");
    $stmt->execute();
    $departments = $stmt->fetchAll();
} catch (Exception $e) {
    $faculty = [];
    $departments = [];
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management - College Management System</title>
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

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($show_paper_form): ?>
        <!-- Paper Form -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="bi bi-file-earmark-text me-2"></i>
                            <?php echo $paper_action === 'edit_paper' ? 'Edit Paper' : 'Add New Paper'; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="save_paper">
                            <input type="hidden" name="paper_type" value="<?php echo htmlspecialchars($paper_data['paper_type']); ?>">
                            <?php if ($paper_data['paper_id']): ?>
                            <input type="hidden" name="paper_id" value="<?php echo htmlspecialchars($paper_data['paper_id']); ?>">
                            <?php endif; ?>

                            <?php if ($userRole === 'Admin' || $userRole === 'Administrative Staff'): ?>
                            <div class="mb-3">
                                <label for="author_select" class="form-label">
                                    <?php echo $paper_data['paper_type'] === 'faculty' ? 'Faculty Member' : 'Student'; ?> <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="author_select" name="<?php echo $paper_data['paper_type'] === 'faculty' ? 'faculty_id' : 'student_id'; ?>" required>
                                    <option value="">Select <?php echo $paper_data['paper_type'] === 'faculty' ? 'Faculty Member' : 'Student'; ?></option>
                                    <?php
                                    $author_table = $paper_data['paper_type'] === 'faculty' ? 'faculty' : 'students';
                                    $id_field = $paper_data['paper_type'] === 'faculty' ? 'faculty_id' : 'student_pk_id';
                                    $name_field = 'full_name';
                                    $active_condition = $paper_data['paper_type'] === 'faculty' ? 'is_active = 1' : "academic_status = 'Active'";

                                    $stmt = $pdo->prepare("SELECT $id_field as id, $name_field as name FROM $author_table WHERE $active_condition ORDER BY $name_field");
                                    $stmt->execute();
                                    $authors = $stmt->fetchAll();
                                    foreach ($authors as $author):
                                    ?>
                                    <option value="<?php echo $author['id']; ?>"
                                            <?php echo $paper_data['author_id'] == $author['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($author['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                            <input type="hidden" name="<?php echo $paper_data['paper_type'] === 'faculty' ? 'faculty_id' : 'student_id'; ?>" 
                                   value="<?php echo htmlspecialchars($paper_data['author_id']); ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Paper Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="title" name="title"
                                               value="<?php echo htmlspecialchars($paper_data['title']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="publication_type" class="form-label">Publication Type</label>
                                        <select class="form-select" id="publication_type" name="publication_type">
                                            <option value="Journal Article" <?php echo $paper_data['publication_type'] === 'Journal Article' ? 'selected' : ''; ?>>Journal Article</option>
                                            <option value="Conference Paper" <?php echo $paper_data['publication_type'] === 'Conference Paper' ? 'selected' : ''; ?>>Conference Paper</option>
                                            <option value="Book Chapter" <?php echo $paper_data['publication_type'] === 'Book Chapter' ? 'selected' : ''; ?>>Book Chapter</option>
                                            <option value="Book" <?php echo $paper_data['publication_type'] === 'Book' ? 'selected' : ''; ?>>Book</option>
                                            <option value="Thesis" <?php echo $paper_data['publication_type'] === 'Thesis' ? 'selected' : ''; ?>>Thesis</option>
                                            <option value="Working Paper" <?php echo $paper_data['publication_type'] === 'Working Paper' ? 'selected' : ''; ?>>Working Paper</option>
                                            <option value="Other" <?php echo $paper_data['publication_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="authors" class="form-label">Authors <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="authors" name="authors"
                                       value="<?php echo htmlspecialchars($paper_data['authors']); ?>"
                                       placeholder="e.g., John Doe, Jane Smith, Robert Johnson" required>
                                <div class="form-text">Separate multiple authors with commas</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="journal_name" class="form-label">Journal/Conference/Book Title</label>
                                        <input type="text" class="form-control" id="journal_name" name="journal_name"
                                               value="<?php echo htmlspecialchars($paper_data['journal_name']); ?>"
                                               placeholder="e.g., IEEE Transactions on Computers">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="publication_date" class="form-label">Publication Date</label>
                                        <input type="date" class="form-control" id="publication_date" name="publication_date"
                                               value="<?php echo htmlspecialchars($paper_data['publication_date']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="Published" <?php echo $paper_data['status'] === 'Published' ? 'selected' : ''; ?>>Published</option>
                                            <option value="Accepted" <?php echo $paper_data['status'] === 'Accepted' ? 'selected' : ''; ?>>Accepted</option>
                                            <option value="Submitted" <?php echo $paper_data['status'] === 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                                            <option value="Draft" <?php echo $paper_data['status'] === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="doi" class="form-label">DOI (Digital Object Identifier)</label>
                                        <input type="text" class="form-control" id="doi" name="doi"
                                               value="<?php echo htmlspecialchars($paper_data['doi']); ?>"
                                               placeholder="e.g., 10.1109/TSE.2023.1234567">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="citation_count" class="form-label">Citation Count</label>
                                        <input type="number" class="form-control" id="citation_count" name="citation_count"
                                               value="<?php echo htmlspecialchars($paper_data['citation_count']); ?>" min="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="paper_url" class="form-label">Paper URL</label>
                                        <input type="url" class="form-control" id="paper_url" name="paper_url"
                                               value="<?php echo htmlspecialchars($paper_data['paper_url']); ?>"
                                               placeholder="https://...">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="keywords" class="form-label">Keywords</label>
                                <input type="text" class="form-control" id="keywords" name="keywords"
                                       value="<?php echo htmlspecialchars($paper_data['keywords']); ?>"
                                       placeholder="e.g., machine learning, artificial intelligence, data mining">
                                <div class="form-text">Separate keywords with commas</div>
                            </div>

                            <div class="mb-3">
                                <label for="abstract" class="form-label">Abstract</label>
                                <textarea class="form-control" id="abstract" name="abstract" rows="4"
                                          placeholder="Brief summary of the paper..."><?php echo htmlspecialchars($paper_data['abstract']); ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Save Paper
                                </button>
                                <a href="faculty_papers.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-3">
                    <i class="bi bi-person-badge me-2"></i>Faculty Management
                </h1>
            </div>
            <div class="col-auto">
                <?php if (canWrite('faculty')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#facultyModal">
                    <i class="bi bi-plus"></i> Add Faculty
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="searchForm">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" id="searchInput"
                               placeholder="Search by ID, name, or email"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select" id="departmentSelect">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept['department']) ?>"
                                        <?= $department_filter === $dept['department'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['department']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" id="statusSelect">
                            <option value="">All Status</option>
                            <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="faculty.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Faculty Table -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($faculty)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-person-badge text-muted" style="font-size: 4rem;"></i>
                        <h5 class="mt-3 text-muted">No Faculty Members Found</h5>
                        <p class="text-muted">Start by adding your first faculty member.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#facultyModal">
                            <i class="bi bi-plus"></i> Add Faculty Member
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Photo</th>
                                    <th>Faculty ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Degree</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($faculty as $member): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo getProfilePictureUrl($member['profile_picture'] ?? null, $member['gender'] ?? 'Other'); ?>"
                                                 alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        </td>
                                        <td><?= htmlspecialchars($member['faculty_id']) ?></td>
                                        <td><?= htmlspecialchars($member['full_name']) ?></td>
                                        <td><?= htmlspecialchars($member['email']) ?></td>
                                        <td><?= htmlspecialchars($member['department'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($member['employment_role'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($member['highest_degree'] ?? '') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $member['is_active'] ? 'success' : 'secondary' ?>">
                                                <?= $member['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (canWrite('faculty')): ?>
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editFaculty(<?= htmlspecialchars(json_encode($member)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="faculty_id" value="<?= $member['faculty_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Are you sure you want to delete this faculty member?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            Showing <?= count($faculty) ?> faculty member(s)
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Faculty Modal -->
    <div class="modal fade" id="facultyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Faculty Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="facultyForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="faculty_id" id="facultyId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" id="fullName" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="is_active" id="isActive" class="form-select">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="contact_number" id="contactNumber" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <input type="text" name="department" id="department" class="form-control" 
                                       placeholder="e.g., Computer Science">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation</label>
                                <input type="text" name="employment_role" id="employmentRole" class="form-control"
                                       placeholder="e.g., Assistant Professor, Lecturer">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Highest Degree</label>
                                <input type="text" name="highest_degree" id="highestDegree" class="form-control"
                                       placeholder="e.g., PhD, Masters, Bachelors">
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
                                <label class="form-label">Primary Subject</label>
                                <input type="text" name="primary_subject" id="primarySubject" class="form-control"
                                       placeholder="e.g., Computer Science, Mathematics">
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
                        <button type="submit" class="btn btn-primary">Save Faculty</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editFaculty(faculty) {
            document.getElementById('modalTitle').textContent = 'Edit Faculty Member';
            document.getElementById('facultyId').value = faculty.faculty_id;
            document.getElementById('fullName').value = faculty.full_name;
            document.getElementById('email').value = faculty.email;
            document.getElementById('contactNumber').value = faculty.contact_number || '';
            document.getElementById('department').value = faculty.department || '';
            document.getElementById('employmentRole').value = faculty.employment_role || '';
            document.getElementById('highestDegree').value = faculty.highest_degree || '';
            document.getElementById('primarySubject').value = faculty.primary_subject || '';
            document.getElementById('gender').value = faculty.gender || 'Other';
            document.getElementById('isActive').value = faculty.is_active;

            // Handle profile picture options
            const hasPicture = faculty.profile_picture;
            const pictureOption = hasPicture ? 'upload' : 'avatar';
            document.getElementById('picture_option').value = pictureOption;
            togglePictureOptions();

            if (hasPicture) {
                document.getElementById('currentPictureImg').src = faculty.profile_picture;
                document.getElementById('currentPicture').style.display = 'block';
            } else {
                document.getElementById('currentPicture').style.display = 'none';
                // Set selected avatar based on gender
                selectAvatar((faculty.gender || 'Other').toLowerCase());
            }

            new bootstrap.Modal(document.getElementById('facultyModal')).show();
        }

        // Reset form when modal is closed
        document.getElementById('facultyModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitle').textContent = 'Add Faculty Member';
            document.getElementById('facultyForm').reset();
            document.getElementById('facultyId').value = '';
            document.getElementById('currentPicture').style.display = 'none';
            document.getElementById('picture_option').value = 'avatar';
            togglePictureOptions();
            selectAvatar('other');
        });

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
        document.getElementById('departmentSelect').addEventListener('change', submitSearch);
        document.getElementById('statusSelect').addEventListener('change', submitSearch);
    </script>
    <?php endif; ?>
</body>
</html>