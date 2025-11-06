<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);
$notifications = getNotifications($userRole, $user);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_paper') {
    if (!canWrite('papers')) {
        $_SESSION['error'] = 'You do not have permission to delete papers';
        redirect('/faculty_papers.php');
    }

    $paper_id = $_POST['paper_id'] ?? 0;
    $paper_type = $_POST['paper_type'] ?? 'faculty'; // 'faculty' or 'student'

    // Check if user can delete this paper (admin/staff or own paper)
    if ($userRole !== 'Admin' && $userRole !== 'Administrative Staff') {
        if ($paper_type === 'faculty') {
            $stmt = $pdo->prepare("SELECT faculty_id FROM faculty_papers WHERE paper_id = ?");
            $stmt->execute([$paper_id]);
            $paper = $stmt->fetch();
            if (!$paper || $paper['faculty_id'] != ($user['faculty_id'] ?? 0)) {
                $_SESSION['error'] = 'You can only delete your own papers';
                redirect('/faculty_papers.php');
            }
        } elseif ($paper_type === 'student') {
            $stmt = $pdo->prepare("SELECT student_id FROM student_papers WHERE paper_id = ?");
            $stmt->execute([$paper_id]);
            $paper = $stmt->fetch();
            if (!$paper || $paper['student_id'] != ($user['student_id'] ?? 0)) {
                $_SESSION['error'] = 'You can only delete your own papers';
                redirect('/faculty_papers.php');
            }
        }
    }

    // Delete from appropriate table
    if ($paper_type === 'faculty') {
        $stmt = $pdo->prepare("DELETE FROM faculty_papers WHERE paper_id = ?");
    } else {
        $stmt = $pdo->prepare("DELETE FROM student_papers WHERE paper_id = ?");
    }
    $stmt->execute([$paper_id]);
    $_SESSION['message'] = 'Paper deleted successfully';
    redirect('/faculty_papers.php');
}

// Handle search and filter parameters
$search = $_GET['search'] ?? '';
$author_filter = $_GET['author'] ?? ''; // Changed from faculty_filter to author_filter
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$year_filter = $_GET['year'] ?? '';
$paper_type = $_GET['paper_type'] ?? 'all'; // faculty, student, or all

// Build query - Union of faculty and student papers
$facultyConditions = "";
$studentConditions = "";
$facultyParams = [];
$studentParams = [];

if ($search) {
    $searchParam = "%$search%";
    $facultyConditions .= " AND (fp.title LIKE ? OR fp.authors LIKE ? OR fp.journal_name LIKE ? OR fp.keywords LIKE ? OR fp.abstract LIKE ?)";
    $studentConditions .= " AND (sp.title LIKE ? OR sp.authors LIKE ? OR sp.journal_name LIKE ? OR sp.keywords LIKE ? OR sp.abstract LIKE ?)";
    $facultyParams = array_fill(0, 5, $searchParam);
    $studentParams = array_fill(0, 5, $searchParam);
}

if ($author_filter) {
    $facultyConditions .= " AND f.full_name LIKE ?";
    $studentConditions .= " AND s.full_name LIKE ?";
    $authorParam = "%$author_filter%";
    $facultyParams[] = $authorParam;
    $studentParams[] = $authorParam;
}

if ($type_filter) {
    $facultyConditions .= " AND fp.publication_type = ?";
    $studentConditions .= " AND sp.publication_type = ?";
    $facultyParams[] = $type_filter;
    $studentParams[] = $type_filter;
}

if ($status_filter) {
    $facultyConditions .= " AND fp.status = ?";
    $studentConditions .= " AND sp.status = ?";
    $facultyParams[] = $status_filter;
    $studentParams[] = $status_filter;
}

if ($year_filter) {
    $facultyConditions .= " AND YEAR(fp.publication_date) = ?";
    $studentConditions .= " AND YEAR(sp.publication_date) = ?";
    $facultyParams[] = $year_filter;
    $studentParams[] = $year_filter;
}

$query = "
    SELECT 
        fp.paper_id,
        fp.title,
        fp.authors,
        fp.journal_name,
        fp.publication_date,
        fp.doi,
        fp.abstract,
        fp.keywords,
        fp.paper_url,
        fp.citation_count,
        fp.publication_type,
        fp.status,
        fp.created_at,
        f.full_name as author_name,
        f.department,
        'faculty' as author_type,
        f.faculty_id as author_id
    FROM faculty_papers fp
    LEFT JOIN faculty f ON fp.faculty_id = f.faculty_id
    WHERE 1=1" . $facultyConditions . "
    
    UNION ALL
    
    SELECT 
        sp.paper_id,
        sp.title,
        sp.authors,
        sp.journal_name,
        sp.publication_date,
        sp.doi,
        sp.abstract,
        sp.keywords,
        sp.paper_url,
        sp.citation_count,
        sp.publication_type,
        sp.status,
        sp.created_at,
        s.full_name as author_name,
        CONCAT('Student - ', c.course_name) as department,
        'student' as author_type,
        s.student_pk_id as author_id
    FROM student_papers sp
    LEFT JOIN students s ON sp.student_id = s.student_pk_id
    LEFT JOIN courses c ON s.course_id = c.course_id
    WHERE 1=1" . $studentConditions;

if ($paper_type !== 'all') {
    if ($paper_type === 'faculty') {
        // Only show faculty papers - remove student part
        $query = preg_replace("/\s+UNION ALL.*$/s", "", $query);
        $studentParams = [];
    } elseif ($paper_type === 'student') {
        // Only show student papers - remove faculty part
        $query = preg_replace("/.*UNION ALL\s+/s", "", $query);
        $facultyParams = [];
    }
}

$query .= " ORDER BY COALESCE(publication_date, '9999-12-31') DESC, created_at DESC";
$params = array_merge($facultyParams, $studentParams);

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $papers = $stmt->fetchAll();

    // Get filter options - combine faculty and student authors
    $stmt = $pdo->prepare("
        SELECT faculty_id as author_id, full_name as author_name, 'faculty' as author_type FROM faculty WHERE is_active = 1
        UNION ALL
        SELECT student_pk_id as author_id, full_name as author_name, 'student' as author_type FROM students WHERE academic_status = 'Active'
        ORDER BY author_name
    ");
    $stmt->execute();
    $author_options = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT DISTINCT publication_type FROM faculty_papers 
        UNION 
        SELECT DISTINCT publication_type FROM student_papers 
        ORDER BY publication_type
    ");
    $stmt->execute();
    $type_options = $stmt->fetchAll();

    $status_options = ['Published', 'Accepted', 'Submitted', 'Draft'];

    $stmt = $pdo->prepare("
        SELECT DISTINCT YEAR(publication_date) as year FROM faculty_papers WHERE publication_date IS NOT NULL
        UNION
        SELECT DISTINCT YEAR(publication_date) as year FROM student_papers WHERE publication_date IS NOT NULL
        ORDER BY year DESC
    ");
    $stmt->execute();
    $year_options = $stmt->fetchAll();

} catch (PDOException $e) {
    $papers = [];
    $author_options = [];
    $type_options = [];
    $year_options = [];
    $_SESSION['error'] = 'Error loading papers: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papers & Publications - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .paper-card {
            transition: transform 0.2s;
        }
        .paper-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .citation-count {
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-mortarboard"></i> <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house"></i> Dashboard
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

                    <?php if (canAccessModule('subjects') || canAccessModule('tests') || canAccessModule('grades') || canAccessModule('students') || canAccessModule('faculty') || canAccessModule('papers')): ?>
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user['username'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu">
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

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-file-earmark-text"></i> Faculty Papers & Publications</h2>
                        <p class="text-muted">Browse research papers and publications by faculty members</p>
                    </div>
                    <?php if (canWrite('papers')): ?>
                    <div class="btn-group" role="group">
                        <a href="faculty.php?action=add_paper&type=faculty" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Faculty Paper
                        </a>
                        <a href="faculty.php?action=add_paper&type=student" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Add Student Paper
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Search and Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search"
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       placeholder="Title, authors, journal, keywords...">
                            </div>
                            <div class="col-md-2">
                                <label for="author" class="form-label">Author</label>
                                <select class="form-select" id="author" name="author">
                                    <option value="">All Authors</option>
                                    <?php foreach ($author_options as $author): ?>
                                    <option value="<?php echo htmlspecialchars($author['author_name']); ?>"
                                            <?php echo $author_filter == $author['author_name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($author['author_name']); ?>
                                        <small class="text-muted">(<?php echo ucfirst($author['author_type']); ?>)</small>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="paper_type" class="form-label">Paper Type</label>
                                <select class="form-select" id="paper_type" name="paper_type">
                                    <option value="all" <?php echo $paper_type == 'all' ? 'selected' : ''; ?>>All Papers</option>
                                    <option value="faculty" <?php echo $paper_type == 'faculty' ? 'selected' : ''; ?>>Faculty Papers</option>
                                    <option value="student" <?php echo $paper_type == 'student' ? 'selected' : ''; ?>>Student Papers</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="type" class="form-label">Publication Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">All Types</option>
                                    <?php foreach ($type_options as $type): ?>
                                    <option value="<?php echo $type['publication_type']; ?>"
                                            <?php echo $type_filter == $type['publication_type'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['publication_type']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <?php foreach ($status_options as $status): ?>
                                    <option value="<?php echo $status; ?>"
                                            <?php echo $status_filter == $status ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="year" class="form-label">Year</label>
                                <select class="form-select" id="year" name="year">
                                    <option value="">All Years</option>
                                    <?php foreach ($year_options as $year): ?>
                                    <option value="<?php echo $year['year']; ?>"
                                            <?php echo $year_filter == $year['year'] ? 'selected' : ''; ?>>
                                        <?php echo $year['year']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <a href="faculty_papers.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Papers List -->
                <div class="row">
                    <?php if (empty($papers)): ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-file-earmark-x display-1 text-muted"></i>
                                <h4 class="mt-3">No Papers Found</h4>
                                <p class="text-muted">No papers match your current search criteria.</p>
                                <?php if (canWrite('papers')): ?>
                                <div class="btn-group" role="group">
                                    <a href="faculty.php?action=add_paper&type=faculty" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Add Faculty Paper
                                    </a>
                                    <a href="faculty.php?action=add_paper&type=student" class="btn btn-success">
                                        <i class="bi bi-plus-circle"></i> Add Student Paper
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($papers as $paper): ?>
                    <div class="col-12 mb-3">
                        <div class="card paper-card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 class="card-title">
                                            <?php echo htmlspecialchars($paper['title']); ?>
                                            <?php if ($paper['status'] !== 'Published'): ?>
                                            <span class="badge bg-warning status-badge"><?php echo htmlspecialchars($paper['status']); ?></span>
                                            <?php endif; ?>
                                        </h5>
                                        <p class="card-text text-muted mb-2">
                                            <strong>Authors:</strong> <?php echo htmlspecialchars($paper['authors']); ?>
                                        </p>
                                        <?php if ($paper['journal_name']): ?>
                                        <p class="card-text text-muted mb-2">
                                            <strong>Published in:</strong> <?php echo htmlspecialchars($paper['journal_name']); ?>
                                            <?php if ($paper['publication_date']): ?>
                                            (<?php echo date('M Y', strtotime($paper['publication_date'])); ?>)
                                            <?php endif; ?>
                                        </p>
                                        <?php endif; ?>
                                        <p class="card-text text-muted mb-2">
                                            <strong>Author:</strong> <?php echo htmlspecialchars($paper['author_name']); ?>
                                            <span class="badge bg-<?php echo $paper['author_type'] === 'faculty' ? 'primary' : 'success'; ?> ms-1">
                                                <?php echo ucfirst($paper['author_type']); ?>
                                            </span>
                                            <?php if ($paper['department']): ?>
                                            <br><strong>Department:</strong> <?php echo htmlspecialchars($paper['department']); ?>
                                            <?php endif; ?>
                                        </p>
                                        <?php if ($paper['abstract']): ?>
                                        <p class="card-text">
                                            <?php echo htmlspecialchars(substr($paper['abstract'], 0, 200)); ?>
                                            <?php if (strlen($paper['abstract']) > 200): ?>...<?php endif; ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if ($paper['keywords']): ?>
                                        <p class="card-text">
                                            <strong>Keywords:</strong>
                                            <?php
                                            $keywords = explode(',', $paper['keywords']);
                                            foreach ($keywords as $keyword):
                                            ?>
                                            <span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars(trim($keyword)); ?></span>
                                            <?php endforeach; ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="mb-3">
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($paper['publication_type']); ?></span>
                                        </div>
                                        <?php if ($paper['citation_count'] > 0): ?>
                                        <div class="citation-count mb-3">
                                            <i class="bi bi-quote"></i> <?php echo $paper['citation_count']; ?> citations
                                        </div>
                                        <?php endif; ?>
                                        <div class="btn-group" role="group">
                                            <?php if ($paper['paper_url']): ?>
                                            <a href="<?php echo htmlspecialchars($paper['paper_url']); ?>" target="_blank"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-link-45deg"></i> View Paper
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($paper['doi']): ?>
                                            <a href="https://doi.org/<?php echo htmlspecialchars($paper['doi']); ?>" target="_blank"
                                               class="btn btn-outline-info btn-sm">
                                                <i class="bi bi-upc"></i> DOI
                                            </a>
                                            <?php endif; ?>
                                            <?php 
                                            $canEdit = false;
                                            if ($userRole === 'Admin' || $userRole === 'Administrative Staff') {
                                                $canEdit = true;
                                            } elseif ($paper['author_type'] === 'faculty' && $paper['author_id'] == ($user['faculty_id'] ?? 0)) {
                                                $canEdit = true;
                                            } elseif ($paper['author_type'] === 'student' && $paper['author_id'] == ($user['student_id'] ?? 0)) {
                                                $canEdit = true;
                                            }
                                            ?>
                                            <?php if (canWrite('papers') && $canEdit): ?>
                                            <a href="faculty.php?action=edit_paper&id=<?php echo $paper['paper_id']; ?>&type=<?php echo $paper['author_type']; ?>"
                                               class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                    onclick="deletePaper(<?php echo $paper['paper_id']; ?>, '<?php echo $paper['author_type']; ?>')">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this paper? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_paper">
                        <input type="hidden" name="paper_id" id="delete_paper_id">
                        <input type="hidden" name="paper_type" id="delete_paper_type">
                        <button type="submit" class="btn btn-danger">Delete Paper</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deletePaper(paperId, paperType) {
            document.getElementById('delete_paper_id').value = paperId;
            document.getElementById('delete_paper_type').value = paperType;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>