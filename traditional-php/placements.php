<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);
$notifications = getNotifications($userRole, $user);

// Handle delete placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_placement') {
    $placement_id = $_POST['placement_id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM placements WHERE placement_id = ?");
    $stmt->execute([$placement_id]);
    $_SESSION['message'] = 'Placement record deleted successfully';
    redirect('/placements.php');
}

// Handle delete company
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_company') {
    $company_id = $_POST['company_id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM companies WHERE company_id = ?");
    $stmt->execute([$company_id]);
    $_SESSION['message'] = 'Company deleted successfully';
    redirect('/placements.php');
}

// Handle add/edit placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_placement') {
    $data = [
        'student_id' => $_POST['student_id'] ?? null,
        'company_id' => $_POST['company_id'] ?? null,
        'placement_status' => $_POST['placement_status'] ?? 'Not Placed',
        'placement_date' => $_POST['placement_date'] ?? null,
    ];
    
    // If status is not "Placed", set company_id to null
    if ($data['placement_status'] !== 'Placed') {
        $data['company_id'] = null;
        $data['placement_date'] = null;
    }
    
    if (isset($_POST['placement_id']) && $_POST['placement_id']) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE placements SET 
                student_id = ?,
                company_id = ?,
                placement_status = ?,
                placement_date = ?
            WHERE placement_id = ?
        ");
        $stmt->execute([
            $data['student_id'],
            $data['company_id'],
            $data['placement_status'],
            $data['placement_date'],
            $_POST['placement_id']
        ]);
        $_SESSION['message'] = 'Placement record updated successfully';
    } else {
        // Check if student already has a placement record
        $stmt = $pdo->prepare("SELECT placement_id FROM placements WHERE student_id = ?");
        $stmt->execute([$data['student_id']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $_SESSION['error'] = 'This student already has a placement record';
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO placements (student_id, company_id, placement_status, placement_date)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['student_id'],
                $data['company_id'],
                $data['placement_status'],
                $data['placement_date']
            ]);
            $_SESSION['message'] = 'Placement record added successfully';
        }
    }
    redirect('/placements.php');
}

// Handle add/edit company
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_company') {
    $data = [
        'name' => $_POST['name'] ?? '',
        'contact_person' => $_POST['contact_person'] ?? '',
        'contact_email' => $_POST['contact_email'] ?? '',
        'industry' => $_POST['industry'] ?? '',
    ];
    
    if (isset($_POST['company_id']) && $_POST['company_id']) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE companies SET 
                name = ?,
                contact_person = ?,
                contact_email = ?,
                industry = ?
            WHERE company_id = ?
        ");
        $stmt->execute([
            $data['name'],
            $data['contact_person'],
            $data['contact_email'],
            $data['industry'],
            $_POST['company_id']
        ]);
        $_SESSION['message'] = 'Company updated successfully';
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO companies (name, contact_person, contact_email, industry)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['contact_person'],
            $data['contact_email'],
            $data['industry']
        ]);
        $_SESSION['message'] = 'Company added successfully';
    }
    redirect('/placements.php');
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$tab = $_GET['tab'] ?? 'placements'; // placements, companies

// Placements query
$placements_query = "
    SELECT p.*, s.full_name as student_name, s.student_id as student_code, c.name as company_name
    FROM placements p
    LEFT JOIN students s ON p.student_id = s.student_pk_id
    LEFT JOIN companies c ON p.company_id = c.company_id
    WHERE 1=1
";
$placements_params = [];

if ($search && $tab === 'placements') {
    $placements_query .= " AND (s.student_id LIKE ? OR s.full_name LIKE ? OR c.name LIKE ?)";
    $searchParam = "%$search%";
    $placements_params[] = $searchParam;
    $placements_params[] = $searchParam;
    $placements_params[] = $searchParam;
}

if ($status_filter) {
    $placements_query .= " AND p.placement_status = ?";
    $placements_params[] = $status_filter;
}

$placements_query .= " ORDER BY p.placement_date DESC, s.full_name ASC";

// Companies query
$companies_query = "SELECT * FROM companies WHERE 1=1";
$companies_params = [];

if ($search && $tab === 'companies') {
    $companies_query .= " AND (name LIKE ? OR industry LIKE ? OR contact_person LIKE ?)";
    $searchParam = "%$search%";
    $companies_params[] = $searchParam;
    $companies_params[] = $searchParam;
    $companies_params[] = $searchParam;
}

$companies_query .= " ORDER BY name ASC";

try {
    // Execute queries
    $stmt = $pdo->prepare($placements_query);
    $stmt->execute($placements_params);
    $placements = $stmt->fetchAll();
    
    $stmt = $pdo->prepare($companies_query);
    $stmt->execute($companies_params);
    $companies = $stmt->fetchAll();
    
    // Get students for dropdown (only those without placement records)
    $stmt = $pdo->prepare("
        SELECT s.student_pk_id, s.student_id, s.full_name 
        FROM students s 
        LEFT JOIN placements p ON s.student_pk_id = p.student_id 
        WHERE p.student_id IS NULL 
        ORDER BY s.full_name
    ");
    $stmt->execute();
    $available_students = $stmt->fetchAll();
    
    // Get all students for editing
    $stmt = $pdo->prepare("SELECT student_pk_id, student_id, full_name FROM students ORDER BY full_name");
    $stmt->execute();
    $all_students = $stmt->fetchAll();
    
    // Calculate summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_records,
            COUNT(CASE WHEN placement_status = 'Placed' THEN 1 END) as placed_count,
            COUNT(CASE WHEN placement_status = 'Interviewing' THEN 1 END) as interviewing_count,
            COUNT(CASE WHEN placement_status = 'Not Placed' THEN 1 END) as not_placed_count
        FROM placements
    ");
    $stmt->execute();
    $summary = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_companies FROM companies");
    $stmt->execute();
    $companies_summary = $stmt->fetch();
    
} catch (Exception $e) {
    $placements = [];
    $companies = [];
    $available_students = [];
    $all_students = [];
    $summary = ['total_records' => 0, 'placed_count' => 0, 'interviewing_count' => 0, 'not_placed_count' => 0];
    $companies_summary = ['total_companies' => 0];
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Placement Management - College Management System</title>
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
                    <i class="bi bi-briefcase me-2"></i>Placement Management
                </h1>
            </div>
            <div class="col-auto">
                <?php if ($tab === 'placements'): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#placementModal">
                        <i class="bi bi-plus"></i> Add Placement
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#companyModal">
                        <i class="bi bi-plus"></i> Add Company
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Placed</h6>
                                <h4><?= number_format($summary['placed_count']) ?></h4>
                            </div>
                            <i class="bi bi-check-circle" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Interviewing</h6>
                                <h4><?= number_format($summary['interviewing_count']) ?></h4>
                            </div>
                            <i class="bi bi-clock" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Not Placed</h6>
                                <h4><?= number_format($summary['not_placed_count']) ?></h4>
                            </div>
                            <i class="bi bi-x-circle" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Companies</h6>
                                <h4><?= number_format($companies_summary['total_companies']) ?></h4>
                            </div>
                            <i class="bi bi-building" style="font-size: 2rem; opacity: 0.7;"></i>
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
                    <div class="col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" id="searchInput"
                               placeholder="<?= $tab === 'placements' ? 'Search students or companies' : 'Search companies or industries' ?>" 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <?php if ($tab === 'placements'): ?>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" id="statusSelect">
                                <option value="">All Statuses</option>
                                <option value="Placed" <?= $status_filter === 'Placed' ? 'selected' : '' ?>>Placed</option>
                                <option value="Interviewing" <?= $status_filter === 'Interviewing' ? 'selected' : '' ?>>Interviewing</option>
                                <option value="Not Placed" <?= $status_filter === 'Not Placed' ? 'selected' : '' ?>>Not Placed</option>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="col-md-4"></div>
                    <?php endif; ?>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="placements.php?tab=<?= htmlspecialchars($tab) ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'placements' ? 'active' : '' ?>" 
                   href="?tab=placements<?= $search ? '&search=' . urlencode($search) : '' ?>">
                    <i class="bi bi-briefcase"></i> Placements (<?= count($placements) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'companies' ? 'active' : '' ?>" 
                   href="?tab=companies<?= $search ? '&search=' . urlencode($search) : '' ?>">
                    <i class="bi bi-building"></i> Companies (<?= count($companies) ?>)
                </a>
            </li>
        </ul>

        <!-- Placements Tab -->
        <?php if ($tab === 'placements'): ?>
            <div class="card">
                <div class="card-body">
                    <?php if (empty($placements)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-briefcase text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 text-muted">No Placement Records Found</h5>
                            <p class="text-muted">Start by adding placement records for students.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#placementModal">
                                <i class="bi bi-plus"></i> Add Placement
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Company</th>
                                        <th>Status</th>
                                        <th>Placement Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($placements as $placement): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($placement['student_name']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($placement['student_code']) ?></small>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($placement['company_name'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php 
                                                $badge_class = [
                                                    'Placed' => 'bg-success',
                                                    'Interviewing' => 'bg-warning',
                                                    'Not Placed' => 'bg-secondary'
                                                ][$placement['placement_status']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($placement['placement_status']) ?></span>
                                            </td>
                                            <td><?= $placement['placement_date'] ? date('M j, Y', strtotime($placement['placement_date'])) : 'N/A' ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editPlacement(<?= htmlspecialchars(json_encode($placement)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_placement">
                                                    <input type="hidden" name="placement_id" value="<?= $placement['placement_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to delete this placement record?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <!-- Companies Tab -->
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <?php if (empty($companies)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-building text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 text-muted">No Companies Found</h5>
                            <p class="text-muted">Start by adding companies for placement tracking.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#companyModal">
                                <i class="bi bi-plus"></i> Add Company
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Company Name</th>
                                        <th>Industry</th>
                                        <th>Contact Person</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($companies as $company): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($company['name']) ?></strong></td>
                                            <td><?= htmlspecialchars($company['industry']) ?: 'N/A' ?></td>
                                            <td><?= htmlspecialchars($company['contact_person']) ?: 'N/A' ?></td>
                                            <td><?= htmlspecialchars($company['contact_email']) ?: 'N/A' ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editCompany(<?= htmlspecialchars(json_encode($company)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_company">
                                                    <input type="hidden" name="company_id" value="<?= $company['company_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to delete this company?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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

    <!-- Placement Modal -->
    <div class="modal fade" id="placementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="placementModalTitle">Add Placement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="placementForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_placement">
                        <input type="hidden" name="placement_id" id="placementId">
                        
                        <div class="mb-3">
                            <label class="form-label">Student *</label>
                            <select name="student_id" id="studentId" class="form-select" required>
                                <option value="">Select Student</option>
                                <optgroup label="Available Students" id="availableStudents">
                                    <?php foreach ($available_students as $student): ?>
                                        <option value="<?= $student['student_pk_id'] ?>">
                                            <?= htmlspecialchars($student['full_name'] . ' (' . $student['student_id'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="All Students (for editing)" id="allStudents" style="display: none;">
                                    <?php foreach ($all_students as $student): ?>
                                        <option value="<?= $student['student_pk_id'] ?>">
                                            <?= htmlspecialchars($student['full_name'] . ' (' . $student['student_id'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Placement Status *</label>
                            <select name="placement_status" id="placementStatus" class="form-select" required>
                                <option value="Not Placed">Not Placed</option>
                                <option value="Interviewing">Interviewing</option>
                                <option value="Placed">Placed</option>
                            </select>
                        </div>

                        <div class="mb-3" id="companyField">
                            <label class="form-label">Company</label>
                            <select name="company_id" id="companyId" class="form-select">
                                <option value="">Select Company</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?= $company['company_id'] ?>">
                                        <?= htmlspecialchars($company['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Required when status is "Placed"</small>
                        </div>

                        <div class="mb-3" id="dateField">
                            <label class="form-label">Placement Date</label>
                            <input type="date" name="placement_date" id="placementDate" class="form-control">
                            <small class="form-text text-muted">Required when status is "Placed"</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Placement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Company Modal -->
    <div class="modal fade" id="companyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="companyModalTitle">Add Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="companyForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_company">
                        <input type="hidden" name="company_id" id="companyEditId">
                        
                        <div class="mb-3">
                            <label class="form-label">Company Name *</label>
                            <input type="text" name="name" id="companyName" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Industry</label>
                            <input type="text" name="industry" id="companyIndustry" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" id="contactPerson" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Email</label>
                            <input type="email" name="contact_email" id="contactEmail" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Company</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editPlacement(placement) {
            document.getElementById('placementModalTitle').textContent = 'Edit Placement';
            document.getElementById('placementId').value = placement.placement_id;
            document.getElementById('studentId').value = placement.student_id;
            document.getElementById('placementStatus').value = placement.placement_status;
            document.getElementById('companyId').value = placement.company_id || '';
            document.getElementById('placementDate').value = placement.placement_date || '';
            
            // Show all students for editing
            document.getElementById('availableStudents').style.display = 'none';
            document.getElementById('allStudents').style.display = 'block';
            
            updatePlacementFields();
            new bootstrap.Modal(document.getElementById('placementModal')).show();
        }

        function editCompany(company) {
            document.getElementById('companyModalTitle').textContent = 'Edit Company';
            document.getElementById('companyEditId').value = company.company_id;
            document.getElementById('companyName').value = company.name;
            document.getElementById('companyIndustry').value = company.industry || '';
            document.getElementById('contactPerson').value = company.contact_person || '';
            document.getElementById('contactEmail').value = company.contact_email || '';
            
            new bootstrap.Modal(document.getElementById('companyModal')).show();
        }

        function updatePlacementFields() {
            const status = document.getElementById('placementStatus').value;
            const companyField = document.getElementById('companyField');
            const dateField = document.getElementById('dateField');
            const companySelect = document.getElementById('companyId');
            const dateInput = document.getElementById('placementDate');
            
            if (status === 'Placed') {
                companyField.style.display = 'block';
                dateField.style.display = 'block';
                companySelect.required = true;
                dateInput.required = true;
            } else {
                companyField.style.display = 'none';
                dateField.style.display = 'none';
                companySelect.required = false;
                dateInput.required = false;
                companySelect.value = '';
                dateInput.value = '';
            }
        }

        // Update fields when status changes
        document.getElementById('placementStatus').addEventListener('change', updatePlacementFields);

        // Reset placement form when modal is closed
        document.getElementById('placementModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('placementModalTitle').textContent = 'Add Placement';
            document.getElementById('placementForm').reset();
            document.getElementById('placementId').value = '';
            
            // Show available students for new placements
            document.getElementById('availableStudents').style.display = 'block';
            document.getElementById('allStudents').style.display = 'none';
            
            updatePlacementFields();
        });

        // Reset company form when modal is closed
        document.getElementById('companyModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('companyModalTitle').textContent = 'Add Company';
            document.getElementById('companyForm').reset();
            document.getElementById('companyEditId').value = '';
        });

        // Initialize fields on page load
        updatePlacementFields();

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
        const statusSelect = document.getElementById('statusSelect');
        if (statusSelect) {
            statusSelect.addEventListener('change', submitSearch);
        }
    </script>
</body>
</html>