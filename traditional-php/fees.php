<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);
$notifications = getNotifications($userRole, $user);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $payment_id = $_POST['payment_id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM fee_payments WHERE payment_id = ?");
    $stmt->execute([$payment_id]);
    $_SESSION['message'] = 'Fee payment record deleted successfully';
    redirect('/fees.php');
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $data = [
        'student_id' => $_POST['student_id'] ?? null,
        'amount_paid' => $_POST['amount_paid'] ?? 0,
        'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
        'receipt_number' => $_POST['receipt_number'] ?? '',
        'recorded_by_user_id' => $user['user_id'], // Current user
    ];
    
    if (isset($_POST['payment_id']) && $_POST['payment_id']) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE fee_payments SET 
                student_id = ?,
                amount_paid = ?,
                payment_date = ?,
                receipt_number = ?
            WHERE payment_id = ?
        ");
        $stmt->execute([
            $data['student_id'],
            $data['amount_paid'],
            $data['payment_date'],
            $data['receipt_number'],
            $_POST['payment_id']
        ]);
        $_SESSION['message'] = 'Fee payment updated successfully';
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO fee_payments (student_id, amount_paid, payment_date, receipt_number, recorded_by_user_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['student_id'],
            $data['amount_paid'],
            $data['payment_date'],
            $data['receipt_number'],
            $data['recorded_by_user_id']
        ]);
        $_SESSION['message'] = 'Fee payment added successfully';
    }
    redirect('/fees.php');
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$student_filter = $_GET['student'] ?? '';

// Build query - use existing fee_payments table structure
$query = "
    SELECT fp.*, s.full_name as student_name, s.student_id as student_code
    FROM fee_payments fp
    LEFT JOIN students s ON fp.student_id = s.student_pk_id
    WHERE 1=1
";
$params = [];

if ($search) {
    $query .= " AND (s.student_id LIKE ? OR s.full_name LIKE ? OR fp.receipt_number LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($student_filter) {
    $query .= " AND fp.student_id = ?";
    $params[] = $student_filter;
}

$query .= " ORDER BY fp.payment_date DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
    
    // Get students for dropdown
    $stmt = $pdo->prepare("SELECT student_pk_id, student_id, full_name FROM students ORDER BY full_name");
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    // Calculate summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_records,
            COALESCE(SUM(amount_paid), 0) as total_amount,
            COUNT(DISTINCT student_id) as students_paid
        FROM fee_payments
    ");
    $stmt->execute();
    $summary = $stmt->fetch();
    
} catch (Exception $e) {
    $payments = [];
    $students = [];
    $summary = ['total_records' => 0, 'total_amount' => 0, 'students_paid' => 0];
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Management - <?php echo SITE_NAME; ?></title>
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

                    <?php if (hasRole(['Management', 'Admin'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Administration
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="students.php"><i class="bi bi-people"></i> Students</a></li>
                            <li><a class="dropdown-item" href="faculty.php"><i class="bi bi-person-badge"></i> Faculty</a></li>
                            <li><a class="dropdown-item" href="admissions.php"><i class="bi bi-file-earmark-text"></i> Admissions</a></li>
                            <li><a class="dropdown-item" href="fees.php"><i class="bi bi-currency-dollar"></i> Fees</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="library.php"><i class="bi bi-book"></i> Library</a></li>
                            <li><a class="dropdown-item" href="placements.php"><i class="bi bi-briefcase"></i> Placements</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

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
                        </ul>
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

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-3">
                    <i class="bi bi-currency-dollar me-2"></i>Fee Management
                </h1>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> This module uses the simplified fee_payments table structure from the existing database schema.
                </div>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#feeModal">
                    <i class="bi bi-plus"></i> Add Payment
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Payments</h6>
                                <h4><?= number_format($summary['total_records'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-receipt" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Amount</h6>
                                <h4>₹<?= number_format($summary['total_amount'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-currency-rupee" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Students Paid</h6>
                                <h4><?= number_format($summary['students_paid'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-people" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="searchForm">
                    <div class="col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" id="searchInput"
                               placeholder="Search by student ID, name, or receipt number" 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Student</label>
                        <select name="student" class="form-select" id="studentSelect">
                            <option value="">All Students</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['student_pk_id'] ?>" 
                                        <?= $student_filter == $student['student_pk_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($student['full_name'] . ' (' . $student['student_id'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="fees.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Fees Table -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($payments)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-currency-dollar text-muted" style="font-size: 4rem;"></i>
                        <h5 class="mt-3 text-muted">No Fee Records Found</h5>
                        <p class="text-muted">Start by adding your first fee payment record.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#feeModal">
                            <i class="bi bi-plus"></i> Add Fee Payment
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Amount</th>
                                    <th>Payment Date</th>
                                    <th>Receipt Number</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($payment['student_name'] ?? 'Unknown') ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($payment['student_code'] ?? 'N/A') ?></small>
                                            </div>
                                        </td>
                                        <td>₹<?= number_format($payment['amount_paid']) ?></td>
                                        <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                        <td><?= htmlspecialchars($payment['receipt_number']) ?: 'N/A' ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editPayment(<?= htmlspecialchars(json_encode($payment)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="payment_id" value="<?= $payment['payment_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Are you sure you want to delete this payment record?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            Showing <?= count($payments) ?> payment record(s)
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Fee Modal -->
    <div class="modal fade" id="feeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Fee Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="feeForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="payment_id" id="paymentId">
                        
                        <div class="mb-3">
                            <label class="form-label">Student *</label>
                            <select name="student_id" id="studentId" class="form-select" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['student_pk_id'] ?>">
                                        <?= htmlspecialchars($student['full_name'] . ' (' . $student['student_id'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount *</label>
                            <input type="number" name="amount_paid" id="amountPaid" class="form-control" required 
                                   min="0" step="0.01" placeholder="Enter amount">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Date *</label>
                            <input type="date" name="payment_date" id="paymentDate" class="form-control" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Receipt Number</label>
                            <input type="text" name="receipt_number" id="receiptNumber" class="form-control"
                                   placeholder="Receipt/Reference number">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editPayment(payment) {
            document.getElementById('modalTitle').textContent = 'Edit Fee Payment';
            document.getElementById('paymentId').value = payment.payment_id;
            document.getElementById('studentId').value = payment.student_id;
            document.getElementById('amountPaid').value = payment.amount_paid;
            document.getElementById('paymentDate').value = payment.payment_date;
            document.getElementById('receiptNumber').value = payment.receipt_number || '';
            
            new bootstrap.Modal(document.getElementById('feeModal')).show();
        }

        // Reset form when modal is closed
        document.getElementById('feeModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitle').textContent = 'Add Fee Payment';
            document.getElementById('feeForm').reset();
            document.getElementById('paymentId').value = '';
            document.getElementById('paymentDate').value = '<?= date('Y-m-d') ?>';
        });

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
        document.getElementById('studentSelect').addEventListener('change', submitSearch);
    </script>
</body>
</html>