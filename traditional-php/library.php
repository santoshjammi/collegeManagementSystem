<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);
$notifications = getNotifications($userRole, $user);

// Handle delete book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_book') {
    $book_id = $_POST['book_id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM books WHERE book_id = ?");
    $stmt->execute([$book_id]);
    $_SESSION['message'] = 'Book deleted successfully';
    redirect('/library.php');
}

// Handle issue book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'issue') {
    $book_id = $_POST['book_id'] ?? 0;
    $student_id = $_POST['student_id'] ?? 0;
    $issue_date = date('Y-m-d');
    $expected_return_date = date('Y-m-d', strtotime('+14 days')); // 2 weeks from now
    
    // Check if book is available
    $stmt = $pdo->prepare("SELECT available_copies FROM books WHERE book_id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    
    if ($book && $book['available_copies'] > 0) {
        // Create issue record
        $stmt = $pdo->prepare("
            INSERT INTO library_issues (book_id, student_id, issue_date, expected_return_date, is_returned)
            VALUES (?, ?, ?, ?, 0)
        ");
        $stmt->execute([$book_id, $student_id, $issue_date, $expected_return_date]);
        
        // Decrease available copies
        $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?");
        $stmt->execute([$book_id]);
        
        $_SESSION['message'] = 'Book issued successfully';
    } else {
        $_SESSION['error'] = 'Book is not available for issue';
    }
    redirect('/library.php');
}

// Handle return book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return') {
    $issue_id = $_POST['issue_id'] ?? 0;
    $book_id = $_POST['book_id'] ?? 0;
    $return_date = date('Y-m-d');
    
    // Update issue record
    $stmt = $pdo->prepare("
        UPDATE library_issues 
        SET is_returned = 1, return_date = ? 
        WHERE issue_id = ?
    ");
    $stmt->execute([$return_date, $issue_id]);
    
    // Increase available copies
    $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE book_id = ?");
    $stmt->execute([$book_id]);
    
    $_SESSION['message'] = 'Book returned successfully';
    redirect('/library.php');
}

// Handle add/edit book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_book') {
    $data = [
        'title' => $_POST['title'] ?? '',
        'author' => $_POST['author'] ?? '',
        'isbn' => $_POST['isbn'] ?? '',
        'total_copies' => $_POST['total_copies'] ?? 1,
    ];
    
    if (isset($_POST['book_id']) && $_POST['book_id']) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE books SET 
                title = ?,
                author = ?,
                isbn = ?,
                total_copies = ?,
                available_copies = ?
            WHERE book_id = ?
        ");
        $stmt->execute([
            $data['title'],
            $data['author'],
            $data['isbn'],
            $data['total_copies'],
            $data['total_copies'], // Reset available copies to total
            $_POST['book_id']
        ]);
        $_SESSION['message'] = 'Book updated successfully';
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO books (title, author, isbn, total_copies, available_copies)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['author'],
            $data['isbn'],
            $data['total_copies'],
            $data['total_copies'] // Available copies = total copies initially
        ]);
        $_SESSION['message'] = 'Book added successfully';
    }
    redirect('/library.php');
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$tab = $_GET['tab'] ?? 'books'; // books, issues, returns

// Books query
$books_query = "SELECT * FROM books WHERE 1=1";
$books_params = [];

if ($search && $tab === 'books') {
    $books_query .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $searchParam = "%$search%";
    $books_params[] = $searchParam;
    $books_params[] = $searchParam;
    $books_params[] = $searchParam;
}

$books_query .= " ORDER BY title ASC";

// Issues query
$issues_query = "
    SELECT li.*, b.title, b.author, s.full_name as student_name, s.student_id as student_code
    FROM library_issues li
    LEFT JOIN books b ON li.book_id = b.book_id
    LEFT JOIN students s ON li.student_id = s.student_pk_id
    WHERE li.is_returned = 0
";
$issues_params = [];

if ($search && $tab === 'issues') {
    $issues_query .= " AND (b.title LIKE ? OR s.full_name LIKE ? OR s.student_id LIKE ?)";
    $searchParam = "%$search%";
    $issues_params[] = $searchParam;
    $issues_params[] = $searchParam;
    $issues_params[] = $searchParam;
}

$issues_query .= " ORDER BY li.issue_date DESC";

// Returns query
$returns_query = "
    SELECT li.*, b.title, b.author, s.full_name as student_name, s.student_id as student_code
    FROM library_issues li
    LEFT JOIN books b ON li.book_id = b.book_id
    LEFT JOIN students s ON li.student_id = s.student_pk_id
    WHERE li.is_returned = 1
";
$returns_params = [];

if ($search && $tab === 'returns') {
    $returns_query .= " AND (b.title LIKE ? OR s.full_name LIKE ? OR s.student_id LIKE ?)";
    $searchParam = "%$search%";
    $returns_params[] = $searchParam;
    $returns_params[] = $searchParam;
    $returns_params[] = $searchParam;
}

$returns_query .= " ORDER BY li.return_date DESC";

try {
    // Execute queries
    $stmt = $pdo->prepare($books_query);
    $stmt->execute($books_params);
    $books = $stmt->fetchAll();
    
    $stmt = $pdo->prepare($issues_query);
    $stmt->execute($issues_params);
    $issues = $stmt->fetchAll();
    
    $stmt = $pdo->prepare($returns_query);
    $stmt->execute($returns_params);
    $returns = $stmt->fetchAll();
    
    // Get students for dropdown
    $stmt = $pdo->prepare("SELECT student_pk_id, student_id, full_name FROM students ORDER BY full_name");
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    // Calculate summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_books,
            COALESCE(SUM(total_copies), 0) as total_copies,
            COALESCE(SUM(available_copies), 0) as available_copies,
            COUNT(CASE WHEN available_copies = 0 THEN 1 END) as out_of_stock
        FROM books
    ");
    $stmt->execute();
    $books_summary = $stmt->fetch();
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_issued,
            COUNT(CASE WHEN expected_return_date < CURDATE() THEN 1 END) as overdue
        FROM library_issues 
        WHERE is_returned = 0
    ");
    $stmt->execute();
    $issues_summary = $stmt->fetch();
    
} catch (Exception $e) {
    $books = [];
    $issues = [];
    $returns = [];
    $students = [];
    $books_summary = ['total_books' => 0, 'total_copies' => 0, 'available_copies' => 0, 'out_of_stock' => 0];
    $issues_summary = ['total_issued' => 0, 'overdue' => 0];
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management - <?php echo SITE_NAME; ?></title>
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
                    <i class="bi bi-book me-2"></i>Library Management
                </h1>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bookModal">
                    <i class="bi bi-plus"></i> Add Book
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Books</h6>
                                <h4><?= number_format($books_summary['total_books'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-book" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Available</h6>
                                <h4><?= number_format($books_summary['available_copies'] ?? 0) ?></h4>
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
                                <h6 class="card-title">Issued</h6>
                                <h4><?= number_format($issues_summary['total_issued'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-bookmark" style="font-size: 2rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Overdue</h6>
                                <h4><?= number_format($issues_summary['overdue'] ?? 0) ?></h4>
                            </div>
                            <i class="bi bi-exclamation-triangle" style="font-size: 2rem; opacity: 0.7;"></i>
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
                    <div class="col-md-8">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" id="searchInput"
                               placeholder="Search books, students, or ISBNs" 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="library.php?tab=<?= htmlspecialchars($tab) ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'books' ? 'active' : '' ?>" 
                   href="?tab=books<?= $search ? '&search=' . urlencode($search) : '' ?>">
                    <i class="bi bi-book"></i> Books (<?= count($books) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'issues' ? 'active' : '' ?>" 
                   href="?tab=issues<?= $search ? '&search=' . urlencode($search) : '' ?>">
                    <i class="bi bi-bookmark"></i> Current Issues (<?= count($issues) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'returns' ? 'active' : '' ?>" 
                   href="?tab=returns<?= $search ? '&search=' . urlencode($search) : '' ?>">
                    <i class="bi bi-arrow-return-left"></i> Return History (<?= count($returns) ?>)
                </a>
            </li>
        </ul>

        <!-- Books Tab -->
        <?php if ($tab === 'books'): ?>
            <div class="card">
                <div class="card-body">
                    <?php if (empty($books)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-book text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 text-muted">No Books Found</h5>
                            <p class="text-muted">Start by adding your first book to the library.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bookModal">
                                <i class="bi bi-plus"></i> Add Book
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title & Author</th>
                                        <th>ISBN</th>
                                        <th>Copies</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($book['title']) ?></strong><br>
                                                    <small class="text-muted">by <?= htmlspecialchars($book['author']) ?></small>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($book['isbn']) ?: 'N/A' ?></td>
                                            <td>
                                                <span class="badge bg-info"><?= $book['available_copies'] ?></span>
                                                / <?= $book['total_copies'] ?>
                                            </td>
                                            <td>
                                                <?php if ($book['available_copies'] > 0): ?>
                                                    <span class="badge bg-success">Available</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Out of Stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($book['available_copies'] > 0): ?>
                                                    <button class="btn btn-sm btn-success me-1" 
                                                            onclick="issueBook(<?= $book['book_id'] ?>)">
                                                        <i class="bi bi-bookmark-plus"></i> Issue
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editBook(<?= htmlspecialchars(json_encode($book)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_book">
                                                    <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to delete this book?')">
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

        <!-- Issues Tab -->
        <?php elseif ($tab === 'issues'): ?>
            <div class="card">
                <div class="card-body">
                    <?php if (empty($issues)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bookmark text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 text-muted">No Active Issues</h5>
                            <p class="text-muted">No books are currently issued.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Book</th>
                                        <th>Student</th>
                                        <th>Issue Date</th>
                                        <th>Expected Return</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($issues as $issue): ?>
                                        <?php 
                                        $is_overdue = strtotime($issue['expected_return_date']) < time();
                                        ?>
                                        <tr class="<?= $is_overdue ? 'table-warning' : '' ?>">
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($issue['title']) ?></strong><br>
                                                    <small class="text-muted">by <?= htmlspecialchars($issue['author']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($issue['student_name']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($issue['student_code']) ?></small>
                                                </div>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($issue['issue_date'])) ?></td>
                                            <td><?= date('M j, Y', strtotime($issue['expected_return_date'])) ?></td>
                                            <td>
                                                <?php if ($is_overdue): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="return">
                                                    <input type="hidden" name="issue_id" value="<?= $issue['issue_id'] ?>">
                                                    <input type="hidden" name="book_id" value="<?= $issue['book_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-arrow-return-left"></i> Return
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

        <!-- Returns Tab -->
        <?php elseif ($tab === 'returns'): ?>
            <div class="card">
                <div class="card-body">
                    <?php if (empty($returns)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-arrow-return-left text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 text-muted">No Return History</h5>
                            <p class="text-muted">No books have been returned yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Book</th>
                                        <th>Student</th>
                                        <th>Issue Date</th>
                                        <th>Return Date</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($returns as $return): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($return['title']) ?></strong><br>
                                                    <small class="text-muted">by <?= htmlspecialchars($return['author']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($return['student_name']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($return['student_code']) ?></small>
                                                </div>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($return['issue_date'])) ?></td>
                                            <td><?= date('M j, Y', strtotime($return['return_date'])) ?></td>
                                            <td>
                                                <?php 
                                                $days = floor((strtotime($return['return_date']) - strtotime($return['issue_date'])) / 86400);
                                                echo $days . ' days';
                                                ?>
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

    <!-- Book Modal -->
    <div class="modal fade" id="bookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookModalTitle">Add Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="bookForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_book">
                        <input type="hidden" name="book_id" id="bookId">
                        
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" id="bookTitle" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Author *</label>
                            <input type="text" name="author" id="bookAuthor" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ISBN</label>
                            <input type="text" name="isbn" id="bookIsbn" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Total Copies *</label>
                            <input type="number" name="total_copies" id="totalCopies" class="form-control" 
                                   required min="1" value="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Issue Book Modal -->
    <div class="modal fade" id="issueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Issue Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="issue">
                        <input type="hidden" name="book_id" id="issueBookId">
                        
                        <div class="mb-3">
                            <label class="form-label">Student *</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['student_pk_id'] ?>">
                                        <?= htmlspecialchars($student['full_name'] . ' (' . $student['student_id'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            The book will be issued for 14 days from today.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Issue Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBook(book) {
            document.getElementById('bookModalTitle').textContent = 'Edit Book';
            document.getElementById('bookId').value = book.book_id;
            document.getElementById('bookTitle').value = book.title;
            document.getElementById('bookAuthor').value = book.author;
            document.getElementById('bookIsbn').value = book.isbn || '';
            document.getElementById('totalCopies').value = book.total_copies;
            
            new bootstrap.Modal(document.getElementById('bookModal')).show();
        }

        function issueBook(bookId) {
            document.getElementById('issueBookId').value = bookId;
            new bootstrap.Modal(document.getElementById('issueModal')).show();
        }

        // Reset form when modal is closed
        document.getElementById('bookModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('bookModalTitle').textContent = 'Add Book';
            document.getElementById('bookForm').reset();
            document.getElementById('bookId').value = '';
            document.getElementById('totalCopies').value = '1';
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
    </script>
</body>
</html>