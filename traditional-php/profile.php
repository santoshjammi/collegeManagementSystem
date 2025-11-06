<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = $user['role_name'] ?? 'Student';

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($full_name && $email) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users
                SET full_name = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ");
            $stmt->execute([$full_name, $email, $phone, $user['user_id']]);

            // Update session data
            $_SESSION['full_name'] = $full_name;
            $user['full_name'] = $full_name;
            $user['email'] = $email;
            $user['phone'] = $phone;

            $message = "Profile updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    $entityType = $userRole === 'Faculty' ? 'faculty' : 'student';
    $entityId = $userRole === 'Faculty' ? $user['faculty_id'] : $user['student_id'];
    
    if ($entityId && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadProfilePicture('profile_picture', $entityType, $entityId);
        
        if ($uploadResult['success']) {
            // Update the profile picture in the database
            $table = $userRole === 'Faculty' ? 'faculty' : 'students';
            $idColumn = $userRole === 'Faculty' ? 'faculty_id' : 'student_pk_id';
            
            // Delete old profile picture if exists
            $stmt = $pdo->prepare("SELECT profile_picture FROM $table WHERE $idColumn = ?");
            $stmt->execute([$entityId]);
            $oldRecord = $stmt->fetch();
            if ($oldRecord && $oldRecord['profile_picture']) {
                deleteProfilePicture($oldRecord['profile_picture']);
            }
            
            // Update with new picture
            $stmt = $pdo->prepare("UPDATE $table SET profile_picture = ? WHERE $idColumn = ?");
            $stmt->execute([$uploadResult['path'], $entityId]);
            
            $message = "Profile picture uploaded successfully!";
            
            // Refresh user details
            $userDetails = [];
            try {
                switch ($userRole) {
                    case 'Student':
                        $stmt = $pdo->prepare("
                            SELECT s.*, c.course_name
                            FROM students s
                            LEFT JOIN courses c ON s.course_id = c.course_id
                            WHERE s.student_id = ?
                        ");
                        $stmt->execute([$user['student_id'] ?? null]);
                        $userDetails = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
                        break;

                    case 'Faculty':
                        $stmt = $pdo->prepare("
                            SELECT * FROM faculty WHERE faculty_id = ?
                        ");
                        $stmt->execute([$user['faculty_id'] ?? null]);
                        $userDetails = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
                        break;
                }
            } catch (Exception $e) {
                // Ignore errors
            }
        } else {
            $error = $uploadResult['message'];
        }
    } else {
        $error = "Unable to upload profile picture. Please ensure you have the correct permissions.";
    }
}

// Handle profile picture removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    $entityType = $userRole === 'Faculty' ? 'faculty' : 'student';
    $entityId = $userRole === 'Faculty' ? $user['faculty_id'] : $user['student_id'];
    
    if ($entityId) {
        $table = $userRole === 'Faculty' ? 'faculty' : 'students';
        $idColumn = $userRole === 'Faculty' ? 'faculty_id' : 'student_pk_id';
        
        // Delete old profile picture if exists
        $stmt = $pdo->prepare("SELECT profile_picture FROM $table WHERE $idColumn = ?");
        $stmt->execute([$entityId]);
        $oldRecord = $stmt->fetch();
        if ($oldRecord && $oldRecord['profile_picture']) {
            deleteProfilePicture($oldRecord['profile_picture']);
        }
        
        // Set profile_picture to null
        $stmt = $pdo->prepare("UPDATE $table SET profile_picture = NULL WHERE $idColumn = ?");
        $stmt->execute([$entityId]);
        
        $message = "Profile picture removed successfully!";
        
        // Refresh user details
        $userDetails = [];
        try {
            switch ($userRole) {
                case 'Student':
                    $stmt = $pdo->prepare("
                        SELECT s.*, c.course_name
                        FROM students s
                        LEFT JOIN courses c ON s.course_id = c.course_id
                        WHERE s.student_id = ?
                    ");
                    $stmt->execute([$user['student_id'] ?? null]);
                    $userDetails = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
                    break;

                case 'Faculty':
                    $stmt = $pdo->prepare("
                        SELECT * FROM faculty WHERE faculty_id = ?
                    ");
                    $stmt->execute([$user['faculty_id'] ?? null]);
                    $userDetails = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
                    break;
            }
        } catch (Exception $e) {
            // Ignore errors
        }
    }
}

// Get additional user details based on role
$userDetails = [];
try {
    switch ($userRole) {
        case 'Student':
            $stmt = $pdo->prepare("
                SELECT s.*, c.course_name
                FROM students s
                LEFT JOIN courses c ON s.course_id = c.course_id
                WHERE s.student_id = ?
            ");
            $stmt->execute([$user['student_id'] ?? null]);
            $userDetails = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
            break;

        case 'Faculty':
            $stmt = $pdo->prepare("
                SELECT * FROM faculty WHERE faculty_id = ?
            ");
            $stmt->execute([$user['faculty_id'] ?? null]);
            $userDetails = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
            break;
    }
} catch (Exception $e) {
    // Ignore errors for user details
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1rem;
        }

        .role-badge {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 2rem;
        }

        .detail-row {
            padding: 1rem;
            border-bottom: 1px solid #f8f9fa;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            min-width: 120px;
        }

        .tab-content {
            padding: 2rem 0;
        }

        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 500;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
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
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <div class="profile-avatar">
                        <?php if (($userRole === 'Faculty' || $userRole === 'Student') && !empty($userDetails['profile_picture'])): ?>
                            <img src="<?php echo getProfilePictureUrl($userDetails['profile_picture'], $userDetails['gender'] ?? 'Other'); ?>" 
                                 alt="Profile Picture" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                        <?php else: ?>
                            <i class="bi bi-person"></i>
                        <?php endif; ?>
                    </div>
                    <?php if ($userRole === 'Faculty' || $userRole === 'Student'): ?>
                    <div class="mt-2">
                        <form method="POST" enctype="multipart/form-data" class="d-inline">
                            <input type="file" name="profile_picture" accept="image/*" class="d-none" id="pictureUpload">
                            <label for="pictureUpload" class="btn btn-sm btn-outline-light me-1">
                                <i class="bi bi-camera"></i> Upload
                            </label>
                            <input type="hidden" name="upload_picture" value="1">
                            <button type="submit" class="btn btn-sm btn-outline-light d-none" id="uploadBtn">Upload</button>
                        </form>
                        <?php if (!empty($userDetails['profile_picture'])): ?>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="remove_picture" value="1" class="btn btn-sm btn-outline-light"
                                    onclick="return confirm('Are you sure you want to remove your profile picture?')">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-center text-md-start">
                    <h2 class="mb-2"><?php echo escape($user['full_name'] ?? $user['username']); ?></h2>
                    <p class="mb-2 opacity-75"><?php echo escape($user['email'] ?? ''); ?></p>
                    <span class="role-badge"><?php echo $userRole; ?> Account</span>
                </div>
                <div class="col-md-3 text-center text-md-end">
                    <div class="d-flex flex-column align-items-center align-items-md-end">
                        <small class="opacity-75">Member since</small>
                        <strong><?php echo formatDate($user['created_at'] ?? null); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Details -->
            <div class="col-lg-8">
                <div class="profile-card">
                    <div class="card-header bg-primary text-white d-flex align-items-center">
                        <i class="bi bi-person-lines-fill me-2"></i>
                        <h5 class="mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <!-- Basic Information -->
                        <div class="detail-row">
                            <div class="row align-items-center">
                                <div class="col-sm-3">
                                    <span class="detail-label">Username:</span>
                                </div>
                                <div class="col-sm-9">
                                    <span><?php echo escape($user['username']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="row align-items-center">
                                <div class="col-sm-3">
                                    <span class="detail-label">Role:</span>
                                </div>
                                <div class="col-sm-9">
                                    <span class="badge bg-primary"><?php echo $userRole; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="row align-items-center">
                                <div class="col-sm-3">
                                    <span class="detail-label">Email:</span>
                                </div>
                                <div class="col-sm-9">
                                    <span><?php echo escape($user['email'] ?? 'Not set'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="row align-items-center">
                                <div class="col-sm-3">
                                    <span class="detail-label">Phone:</span>
                                </div>
                                <div class="col-sm-9">
                                    <span><?php echo escape($user['phone'] ?? 'Not set'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="row align-items-center">
                                <div class="col-sm-3">
                                    <span class="detail-label">Last Login:</span>
                                </div>
                                <div class="col-sm-9">
                                    <span><?php echo formatDate($user['last_login'] ?? null); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Role-specific information -->
                        <?php if ($userRole === 'Student' && !empty($userDetails)): ?>
                        <div class="detail-row">
                            <div class="row align-items-center">
                                <div class="col-sm-3">
                                    <span class="detail-label">Student ID:</span>
                                </div>
                                <div class="col-sm-9">
                                    <span><?php echo escape($userDetails['student_id'] ?? ''); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="row align-items-center">
                                <div class="col-sm-3">
                                    <span class="detail-label">Course:</span>
                                </div>
                                <div class="col-sm-9">
                                    <span><?php echo escape($userDetails['course_name'] ?? 'Not assigned'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="row align-items-center">
                                <div class="col-sm-3">
                                    <span class="detail-label">Academic Status:</span>
                                </div>
                                <div class="col-sm-9">
                                    <span class="badge bg-<?php echo $userDetails['academic_status'] === 'Active' ? 'success' : 'warning'; ?>">
                                        <?php echo escape($userDetails['academic_status'] ?? 'Unknown'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($userRole === 'Faculty' && !empty($userDetails)): ?>
                        <div class="detail-row">
                            <div class="row align-items-center">
                                <div class="col-sm-3">
                                    <span class="detail-label">Faculty ID:</span>
                                </div>
                                <div class="col-sm-9">
                                    <span><?php echo escape($userDetails['faculty_id'] ?? ''); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="row align-items-center">
                                <div class="col-sm-3">
                                    <span class="detail-label">Department:</span>
                                </div>
                                <div class="col-sm-9">
                                    <span><?php echo escape($userDetails['department'] ?? 'Not assigned'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="row align-items-center">
                                <div class="col-sm-3">
                                    <span class="detail-label">Position:</span>
                                </div>
                                <div class="col-sm-9">
                                    <span><?php echo escape($userDetails['employment_role'] ?? ''); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Settings Sidebar -->
            <div class="col-lg-4">
                <!-- Edit Profile -->
                <div class="profile-card">
                    <div class="card-header bg-success text-white d-flex align-items-center">
                        <i class="bi bi-pencil-square me-2"></i>
                        <h6 class="mb-0">Edit Profile</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                       value="<?php echo escape($user['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo escape($user['email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo escape($user['phone'] ?? ''); ?>">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-save w-100">
                                <i class="bi bi-check-circle me-2"></i>Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="profile-card">
                    <div class="card-header bg-warning text-dark d-flex align-items-center">
                        <i class="bi bi-key me-2"></i>
                        <h6 class="mb-0">Change Password</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-warning w-100">
                                <i class="bi bi-shield-check me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="profile-card">
                    <div class="card-header bg-info text-white d-flex align-items-center">
                        <i class="bi bi-gear me-2"></i>
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="bi bi-house-door me-2"></i>Back to Dashboard
                            </a>
                            <a href="settings.php" class="btn btn-outline-secondary">
                                <i class="bi bi-sliders me-2"></i>Preferences
                            </a>
                            <a href="help.php" class="btn btn-outline-info">
                                <i class="bi bi-question-circle me-2"></i>Help & Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>

    <!-- Profile Picture Upload Script -->
    <script>
        document.getElementById('pictureUpload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, or GIF).');
                    e.target.value = '';
                    return;
                }
                
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB.');
                    e.target.value = '';
                    return;
                }
                
                // Auto-submit the form
                e.target.form.submit();
            }
        });
    </script>
</body>
</html>