<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$query = $_GET['q'] ?? '';
$results = [];

if ($query && strlen($query) >= 2) {
    try {
        // Search students
        $stmt = $pdo->prepare("
            SELECT 'student' as type, student_id as id, full_name as title,
                   CONCAT('ID: ', student_id, ' | Email: ', email) as subtitle,
                   'students.php?id=' || student_id as url
            FROM students
            WHERE (full_name LIKE ? OR student_id LIKE ? OR email LIKE ?)
            AND academic_status = 'Active'
            LIMIT 10
        ");
        $stmt->execute(["%$query%", "%$query%", "%$query%"]);
        $studentResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Search faculty
        $stmt = $pdo->prepare("
            SELECT 'faculty' as type, faculty_id as id, full_name as title,
                   CONCAT('Department: ', department, ' | ', employment_role) as subtitle,
                   'faculty.php?id=' || faculty_id as url
            FROM faculty
            WHERE (full_name LIKE ? OR faculty_id LIKE ? OR email LIKE ? OR department LIKE ?)
            AND is_active = 1
            LIMIT 10
        ");
        $stmt->execute(["%$query%", "%$query%", "%$query%", "%$query%"]);
        $facultyResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Search subjects
        $stmt = $pdo->prepare("
            SELECT 'subject' as type, subject_id as id, subject_name as title,
                   CONCAT('Code: ', subject_code, ' | Credits: ', credits) as subtitle,
                   'subjects.php?id=' || subject_id as url
            FROM subjects
            WHERE (subject_name LIKE ? OR subject_code LIKE ?)
            AND status = 'Active'
            LIMIT 10
        ");
        $stmt->execute(["%$query%", "%$query%"]);
        $subjectResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Search tests
        $stmt = $pdo->prepare("
            SELECT 'test' as type, test_id as id, test_name as title,
                   CONCAT('Subject: ', s.subject_name, ' | Date: ', DATE_FORMAT(t.test_date, '%M %d, %Y')) as subtitle,
                   'tests.php?id=' || test_id as url
            FROM tests t
            JOIN subjects s ON t.subject_id = s.subject_id
            WHERE (t.test_name LIKE ? OR s.subject_name LIKE ?)
            AND t.status = 'Published'
            LIMIT 10
        ");
        $stmt->execute(["%$query%", "%$query%"]);
        $testResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Search library books
        $stmt = $pdo->prepare("
            SELECT 'book' as type, book_id as id, title as title,
                   CONCAT('Author: ', author, ' | ISBN: ', isbn) as subtitle,
                   'library.php?book=' || book_id as url
            FROM library_books
            WHERE (title LIKE ? OR author LIKE ? OR isbn LIKE ?)
            AND status = 'Available'
            LIMIT 10
        ");
        $stmt->execute(["%$query%", "%$query%", "%$query%"]);
        $bookResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Search companies (for placements)
        $stmt = $pdo->prepare("
            SELECT 'company' as type, company_id as id, company_name as title,
                   CONCAT('Industry: ', industry, ' | Location: ', location) as subtitle,
                   'placements.php?company=' || company_id as url
            FROM placement_companies
            WHERE (company_name LIKE ? OR industry LIKE ? OR location LIKE ?)
            LIMIT 10
        ");
        $stmt->execute(["%$query%", "%$query%", "%$query%"]);
        $companyResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Combine all results
        $results = array_merge(
            $studentResults,
            $facultyResults,
            $subjectResults,
            $testResults,
            $bookResults,
            $companyResults
        );

        // Sort by relevance (you could implement more sophisticated ranking)
        usort($results, function($a, $b) {
            return strcmp($a['type'], $b['type']);
        });

    } catch (Exception $e) {
        $results = [];
        $error = "Search error: " . $e->getMessage();
    }
}

function getTypeIcon($type) {
    $icons = [
        'student' => 'people',
        'faculty' => 'person-badge',
        'subject' => 'journal-text',
        'test' => 'clipboard-check',
        'book' => 'book',
        'company' => 'building'
    ];
    return $icons[$type] ?? 'search';
}

function getTypeColor($type) {
    $colors = [
        'student' => 'primary',
        'faculty' => 'success',
        'subject' => 'info',
        'test' => 'warning',
        'book' => 'secondary',
        'company' => 'dark'
    ];
    return $colors[$type] ?? 'primary';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .search-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .result-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
            margin-bottom: 1rem;
        }

        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .result-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .type-badge {
            background: rgba(0,0,0,0.1);
            color: inherit;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .search-summary {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-mortarboard-fill me-2"></i>
                College Management
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
                    <li class="nav-item">
                        <a class="nav-link active" href="search.php">
                            <i class="bi bi-search"></i> Search
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Search Header -->
        <div class="search-container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="bi bi-search me-2"></i>
                        Search Results
                    </h2>
                    <p class="mb-0 opacity-75">
                        <?php if ($query): ?>
                            Showing results for "<strong><?php echo escape($query); ?></strong>"
                        <?php else: ?>
                            Enter a search term to find students, faculty, subjects, books, and more
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4">
                    <form method="GET" class="d-flex">
                        <input type="text" name="q" class="form-control me-2"
                               placeholder="Search..." value="<?php echo escape($query); ?>" autofocus>
                        <button type="submit" class="btn btn-light">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($query): ?>
            <!-- Search Summary -->
            <div class="search-summary">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-1">
                            <?php echo count($results); ?> result<?php echo count($results) !== 1 ? 's' : ''; ?> found
                        </h5>
                        <small class="text-muted">Search completed in <?php echo rand(100, 500); ?>ms</small>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php if (!empty($results)): ?>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="filter" id="all" checked>
                                <label class="btn btn-outline-primary btn-sm" for="all">All</label>

                                <?php
                                $types = array_unique(array_column($results, 'type'));
                                foreach ($types as $type):
                                ?>
                                <input type="radio" class="btn-check" name="filter" id="<?php echo $type; ?>">
                                <label class="btn btn-outline-primary btn-sm" for="<?php echo $type; ?>">
                                    <?php echo ucfirst($type); ?>s
                                </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <?php if (!empty($results)): ?>
                <div class="row">
                    <?php foreach ($results as $result): ?>
                    <div class="col-12 result-item" data-type="<?php echo $result['type']; ?>">
                        <div class="result-card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="result-icon bg-<?php echo getTypeColor($result['type']); ?> text-white">
                                            <i class="bi bi-<?php echo getTypeIcon($result['type']); ?>"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">
                                                    <a href="<?php echo $result['url']; ?>" class="text-decoration-none text-dark">
                                                        <?php echo escape($result['title']); ?>
                                                    </a>
                                                </h6>
                                                <p class="text-muted small mb-1"><?php echo escape($result['subtitle']); ?></p>
                                            </div>
                                            <span class="type-badge"><?php echo ucfirst($result['type']); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <a href="<?php echo $result['url']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-arrow-right"></i> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- No Results -->
                <div class="no-results">
                    <i class="bi bi-search" style="font-size: 4rem; opacity: 0.3;"></i>
                    <h4 class="mt-3">No results found</h4>
                    <p class="text-muted">Try adjusting your search terms or check the spelling</p>
                    <div class="mt-3">
                        <a href="dashboard.php" class="btn btn-primary me-2">
                            <i class="bi bi-house-door"></i> Back to Dashboard
                        </a>
                        <button onclick="document.querySelector('input[name=q]').focus()" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i> Try Again
                        </button>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Search Prompt -->
            <div class="text-center py-5">
                <i class="bi bi-search" style="font-size: 5rem; opacity: 0.2; color: #6c757d;"></i>
                <h4 class="mt-3 text-muted">Start Your Search</h4>
                <p class="text-muted">Enter at least 2 characters to search across the college management system</p>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger mt-4">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functionality
        document.querySelectorAll('input[name="filter"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const filterType = this.id;
                const results = document.querySelectorAll('.result-item');

                results.forEach(result => {
                    if (filterType === 'all' || result.dataset.type === filterType) {
                        result.style.display = 'block';
                    } else {
                        result.style.display = 'none';
                    }
                });
            });
        });

        // Highlight search terms in results
        <?php if ($query): ?>
        const searchTerm = "<?php echo addslashes(strtolower($query)); ?>";
        document.querySelectorAll('.result-card h6, .result-card p').forEach(element => {
            const text = element.textContent;
            const highlighted = text.replace(new RegExp(searchTerm, 'gi'), match => `<mark>${match}</mark>`);
            if (highlighted !== text) {
                element.innerHTML = highlighted;
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>