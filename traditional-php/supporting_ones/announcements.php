<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);
$notifications = getNotifications($userRole, $user);

// Check permissions for announcements
$canCreateAnnouncements = in_array($userRole, ['Admin', 'Administrative Staff', 'Faculty']);

// Handle create/edit announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_announcement') {
    if (!$canCreateAnnouncements) {
        $_SESSION['error'] = 'You do not have permission to create announcements.';
        redirect('/announcements.php');
    }

    $data = [
        'title' => $_POST['title'] ?? '',
        'content' => $_POST['content'] ?? '',
        'announcement_type' => $_POST['announcement_type'] ?? 'General',
        'target_audience' => $_POST['target_audience'] ?? 'All',
        'priority' => $_POST['priority'] ?? 'Medium',
        'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
        'posted_by' => $user['user_id']
    ];

    if (empty($data['title']) || empty($data['content'])) {
        $_SESSION['error'] = 'Title and content are required.';
    } else {
        try {
            if (isset($_POST['announcement_id']) && $_POST['announcement_id']) {
                // Update existing announcement
                $stmt = $pdo->prepare("
                    UPDATE announcements SET 
                        title = ?, content = ?, announcement_type = ?, target_audience = ?, 
                        priority = ?, expires_at = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE announcement_id = ? AND posted_by = ?
                ");
                $stmt->execute([
                    $data['title'], $data['content'], $data['announcement_type'], 
                    $data['target_audience'], $data['priority'], $data['expires_at'],
                    $_POST['announcement_id'], $user['user_id']
                ]);
                $_SESSION['success'] = 'Announcement updated successfully.';
            } else {
                // Create new announcement
                $stmt = $pdo->prepare("
                    INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by, expires_at, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $data['title'], $data['content'], $data['announcement_type'], 
                    $data['target_audience'], $data['priority'], $data['posted_by'], $data['expires_at']
                ]);

                // Create notifications for all users based on target audience
                $announcement_id = $pdo->lastInsertId();
                
                // Get users based on target audience
                $notification_query = "SELECT user_id FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.is_active = 1";
                if ($data['target_audience'] !== 'All') {
                    $notification_query .= " AND r.role_name = ?";
                    $stmt = $pdo->prepare($notification_query);
                    $stmt->execute([$data['target_audience'] === 'Students' ? 'Student' : $data['target_audience']]);
                } else {
                    $stmt = $pdo->prepare($notification_query);
                    $stmt->execute();
                }
                $target_users = $stmt->fetchAll();

                // Create notification for each target user
                $notification_stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message, notification_type, related_id, related_type)
                    VALUES (?, ?, ?, 'Info', ?, 'announcement')
                ");

                foreach ($target_users as $target_user) {
                    if ($target_user['user_id'] != $user['user_id']) { // Don't notify the creator
                        $notification_stmt->execute([
                            $target_user['user_id'],
                            'New Announcement: ' . $data['title'],
                            substr($data['content'], 0, 100) . (strlen($data['content']) > 100 ? '...' : ''),
                            $announcement_id,
                            'announcement'
                        ]);
                    }
                }

                $_SESSION['success'] = 'Announcement created successfully and notifications sent.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error saving announcement: ' . $e->getMessage();
        }
    }

    redirect('/announcements.php');
}

// Handle delete announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_announcement') {
    if (!$canCreateAnnouncements) {
        $_SESSION['error'] = 'You do not have permission to delete announcements.';
        redirect('/announcements.php');
    }

    $announcement_id = $_POST['announcement_id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ? AND posted_by = ?");
        $stmt->execute([$announcement_id, $user['user_id']]);
        $_SESSION['success'] = 'Announcement deleted successfully.';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error deleting announcement: ' . $e->getMessage();
    }

    redirect('/announcements.php');
}

// Handle toggle active status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_active') {
    if (!$canCreateAnnouncements) {
        $_SESSION['error'] = 'You do not have permission to modify announcements.';
        redirect('/announcements.php');
    }

    $announcement_id = $_POST['announcement_id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE announcements SET is_active = NOT is_active WHERE announcement_id = ? AND posted_by = ?");
        $stmt->execute([$announcement_id, $user['user_id']]);
        $_SESSION['success'] = 'Announcement status updated successfully.';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error updating announcement: ' . $e->getMessage();
    }

    redirect('/announcements.php');
}

// Get announcements based on user role and permissions
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$audience_filter = $_GET['audience'] ?? '';

$announcements_query = "
    SELECT a.*, u.username as posted_by_name, r.role_name as posted_by_role
    FROM announcements a
    JOIN users u ON a.posted_by = u.user_id
    JOIN roles r ON u.role_id = r.role_id
    WHERE a.is_active = 1 
    AND (a.expires_at IS NULL OR a.expires_at > NOW())
    AND (a.target_audience = 'All' OR a.target_audience = ? OR ? IN ('Admin', 'Administrative Staff'))
";

$announcements_params = [];

// Students can only see announcements targeted to them or 'All'
if ($userRole === 'Student') {
    $announcements_params[] = 'Students';
    $announcements_params[] = $userRole;
} else {
    // Faculty and Admin can see all announcements
    $announcements_params[] = $userRole;
    $announcements_params[] = $userRole;
}

if ($search) {
    $announcements_query .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $searchParam = "%$search%";
    $announcements_params[] = $searchParam;
    $announcements_params[] = $searchParam;
}

if ($type_filter) {
    $announcements_query .= " AND a.announcement_type = ?";
    $announcements_params[] = $type_filter;
}

if ($audience_filter) {
    $announcements_query .= " AND a.target_audience = ?";
    $announcements_params[] = $audience_filter;
}

$announcements_query .= " ORDER BY a.priority DESC, a.created_at DESC";

try {
    $stmt = $pdo->prepare($announcements_query);
    $stmt->execute($announcements_params);
    $announcements = $stmt->fetchAll();

    // Get summary statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_announcements,
            COUNT(CASE WHEN priority = 'Critical' THEN 1 END) as critical_announcements,
            COUNT(CASE WHEN priority = 'High' THEN 1 END) as high_priority_announcements,
            COUNT(CASE WHEN target_audience = 'All' THEN 1 END) as general_announcements
        FROM announcements 
        WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())
    ";
    
    $stmt = $pdo->prepare($stats_query);
    $stmt->execute();
    $stats = $stmt->fetch();

} catch (Exception $e) {
    $announcements = [];
    $stats = ['total_announcements' => 0, 'critical_announcements' => 0, 'high_priority_announcements' => 0, 'general_announcements' => 0];
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .announcement-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .announcement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .priority-critical { border-left-color: #dc3545 !important; }
        .priority-high { border-left-color: #fd7e14 !important; }
        .priority-medium { border-left-color: #0d6efd !important; }
        .priority-low { border-left-color: #6c757d !important; }
        
        .announcement-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">📢 Announcements</h1>
                <p class="text-muted mb-0">Stay updated with important college announcements</p>
            </div>
            <?php if ($canCreateAnnouncements): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#announcementModal">
                <i class="bi bi-plus"></i> New Announcement
            </button>
            <?php endif; ?>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo escape($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo escape($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h3 class="mb-1"><?= $stats['total_announcements'] ?></h3>
                        <p class="mb-0 small">Total Announcements</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-1"><?= $stats['critical_announcements'] ?></h3>
                        <p class="mb-0 small">Critical Priority</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-1"><?= $stats['high_priority_announcements'] ?></h3>
                        <p class="mb-0 small">High Priority</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-1"><?= $stats['general_announcements'] ?></h3>
                        <p class="mb-0 small">General Announcements</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search announcements..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="General" <?= $type_filter === 'General' ? 'selected' : '' ?>>General</option>
                            <option value="Academic" <?= $type_filter === 'Academic' ? 'selected' : '' ?>>Academic</option>
                            <option value="Event" <?= $type_filter === 'Event' ? 'selected' : '' ?>>Event</option>
                            <option value="Important" <?= $type_filter === 'Important' ? 'selected' : '' ?>>Important</option>
                            <option value="Emergency" <?= $type_filter === 'Emergency' ? 'selected' : '' ?>>Emergency</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Audience</label>
                        <select name="audience" class="form-select">
                            <option value="">All Audiences</option>
                            <option value="All" <?= $audience_filter === 'All' ? 'selected' : '' ?>>All Users</option>
                            <option value="Students" <?= $audience_filter === 'Students' ? 'selected' : '' ?>>Students</option>
                            <option value="Faculty" <?= $audience_filter === 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                            <option value="Admin" <?= $audience_filter === 'Admin' ? 'selected' : '' ?>>Administration</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary me-2">
                            <i class="bi bi-search"></i>
                        </button>
                        <a href="announcements.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Announcements List -->
        <?php if (empty($announcements)): ?>
            <div class="text-center py-5">
                <i class="bi bi-megaphone text-muted" style="font-size: 4rem;"></i>
                <h5 class="mt-3 text-muted">No Announcements Found</h5>
                <p class="text-muted">
                    <?php if ($canCreateAnnouncements): ?>
                        Start by creating your first announcement.
                    <?php else: ?>
                        Check back later for new announcements.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($announcements as $announcement): ?>
                    <div class="col-12 mb-3">
                        <div class="card announcement-card priority-<?= strtolower($announcement['priority']) ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            <?= htmlspecialchars($announcement['title']) ?>
                                            <span class="badge bg-<?= 
                                                $announcement['priority'] === 'Critical' ? 'danger' : 
                                                ($announcement['priority'] === 'High' ? 'warning' : 
                                                ($announcement['priority'] === 'Medium' ? 'primary' : 'secondary')) 
                                            ?> ms-2">
                                                <?= $announcement['priority'] ?>
                                            </span>
                                            <span class="badge bg-info ms-1">
                                                <?= $announcement['announcement_type'] ?>
                                            </span>
                                        </h5>
                                        <div class="announcement-meta mb-2">
                                            <i class="bi bi-person"></i> <?= htmlspecialchars($announcement['posted_by_name']) ?>
                                            (<?= htmlspecialchars($announcement['posted_by_role']) ?>)
                                            <i class="bi bi-calendar ms-3"></i> <?= date('M d, Y g:i A', strtotime($announcement['created_at'])) ?>
                                            <i class="bi bi-people ms-3"></i> <?= htmlspecialchars($announcement['target_audience']) ?>
                                            <?php if ($announcement['expires_at']): ?>
                                                <i class="bi bi-clock ms-3 text-warning"></i> 
                                                Expires: <?= date('M d, Y', strtotime($announcement['expires_at'])) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($canCreateAnnouncements && $announcement['posted_by'] == $user['user_id']): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <button class="dropdown-item" onclick="editAnnouncement(<?= htmlspecialchars(json_encode($announcement)) ?>)">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                            </li>
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="announcement_id" value="<?= $announcement['announcement_id'] ?>">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-<?= $announcement['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                                        <?= $announcement['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_announcement">
                                                    <input type="hidden" name="announcement_id" value="<?= $announcement['announcement_id'] ?>">
                                                    <button type="submit" class="dropdown-item text-danger"
                                                            onclick="return confirm('Are you sure you want to delete this announcement?')">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-text">
                                    <?= nl2br(htmlspecialchars($announcement['content'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($canCreateAnnouncements): ?>
    <!-- Announcement Modal -->
    <div class="modal fade" id="announcementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-megaphone"></i> <span id="modalTitle">Create Announcement</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_announcement">
                        <input type="hidden" name="announcement_id" id="announcementId">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Title *</label>
                                    <input type="text" name="title" id="announcementTitle" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Priority</label>
                                    <select name="priority" id="announcementPriority" class="form-select">
                                        <option value="Low">Low</option>
                                        <option value="Medium" selected>Medium</option>
                                        <option value="High">High</option>
                                        <option value="Critical">Critical</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Type</label>
                                    <select name="announcement_type" id="announcementType" class="form-select">
                                        <option value="General">General</option>
                                        <option value="Academic">Academic</option>
                                        <option value="Event">Event</option>
                                        <option value="Important">Important</option>
                                        <option value="Emergency">Emergency</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Target Audience</label>
                                    <select name="target_audience" id="targetAudience" class="form-select">
                                        <option value="All">All Users</option>
                                        <option value="Students">Students Only</option>
                                        <option value="Faculty">Faculty Only</option>
                                        <?php if (in_array($userRole, ['Admin', 'Administrative Staff'])): ?>
                                        <option value="Admin">Administration Only</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Content *</label>
                            <textarea name="content" id="announcementContent" class="form-control" rows="6" required
                                      placeholder="Enter the announcement content..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Expiration Date (Optional)</label>
                            <input type="datetime-local" name="expires_at" id="expiresAt" class="form-control">
                            <div class="form-text">Leave empty if the announcement should not expire</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check"></i> Save Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($canCreateAnnouncements): ?>
    <script>
        function editAnnouncement(announcement) {
            document.getElementById('modalTitle').textContent = 'Edit Announcement';
            document.getElementById('announcementId').value = announcement.announcement_id;
            document.getElementById('announcementTitle').value = announcement.title;
            document.getElementById('announcementContent').value = announcement.content;
            document.getElementById('announcementType').value = announcement.announcement_type;
            document.getElementById('targetAudience').value = announcement.target_audience;
            document.getElementById('announcementPriority').value = announcement.priority;
            
            if (announcement.expires_at) {
                // Convert MySQL datetime to HTML datetime-local format
                const date = new Date(announcement.expires_at);
                const localDateTime = date.getFullYear() + '-' + 
                    String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(date.getDate()).padStart(2, '0') + 'T' + 
                    String(date.getHours()).padStart(2, '0') + ':' + 
                    String(date.getMinutes()).padStart(2, '0');
                document.getElementById('expiresAt').value = localDateTime;
            } else {
                document.getElementById('expiresAt').value = '';
            }
            
            new bootstrap.Modal(document.getElementById('announcementModal')).show();
        }

        // Reset modal when closed
        document.getElementById('announcementModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitle').textContent = 'Create Announcement';
            document.getElementById('announcementId').value = '';
            document.querySelector('#announcementModal form').reset();
            document.getElementById('announcementPriority').value = 'Medium';
            document.getElementById('announcementType').value = 'General';
            document.getElementById('targetAudience').value = 'All';
        });
    </script>
    <?php endif; ?>
</body>
</html>