<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'college_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('BASE_URL', 'http://localhost:8080');
define('SITE_NAME', 'College Management System');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Allow non-HTTPS for development
ini_set('session.cookie_samesite', 'Lax');
session_start();

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper functions
function redirect($path) {
    // Use relative redirect instead of absolute to avoid BASE_URL issues
    header("Location: " . $path);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }

    try {
        // First try the complex query with all joins
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_name,
                   CASE
                       WHEN r.role_name = 'Faculty' THEN f.faculty_id
                       WHEN r.role_name = 'Student' THEN s.student_pk_id
                       ELSE NULL
                   END as faculty_id,
                   CASE
                       WHEN r.role_name = 'Faculty' THEN f.faculty_id
                       WHEN r.role_name = 'Student' THEN s.student_pk_id
                       ELSE NULL
                   END as student_id
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN faculty f ON u.user_id = f.user_id
            LEFT JOIN students s ON u.user_id = s.user_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $user;
        }

        // If complex query fails, try simple query
        error_log("getCurrentUser: Complex query failed for user_id: " . $_SESSION['user_id'] . ", trying simple query");
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_name, NULL as faculty_id, NULL as student_id
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $user;
        }

        error_log("getCurrentUser: Simple query also failed for user_id: " . $_SESSION['user_id']);
        return null;

    } catch (PDOException $e) {
        error_log("getCurrentUser: Database error: " . $e->getMessage());
        // Last resort: return basic user info from session
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? 'unknown',
            'role_name' => $_SESSION['role'] ?? 'Student',
            'faculty_id' => null,
            'student_id' => null,
            'full_name' => $_SESSION['username'] ?? 'Unknown User'
        ];
    }
}

function hasRole($roles) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    // Map old role names to new role names for backward compatibility
    $roleMapping = [
        'Management' => 'Administrative Staff',
        'Staff' => 'Administrative Staff',
        'Admin' => 'Admin',
        'Faculty' => 'Faculty',
        'Student' => 'Student'
    ];
    
    $mappedRoles = [];
    foreach ($roles as $role) {
        if (isset($roleMapping[$role])) {
            $mappedRoles[] = $roleMapping[$role];
        } else {
            $mappedRoles[] = $role;
        }
    }
    
    return in_array($user['role_name'], $mappedRoles);
}

function getUserRole($userId = null) {
    if ($userId === null) {
        $user = getCurrentUser();
        return $user ? $user['role_name'] : null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT r.role_name 
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result ? $result['role_name'] : null;
}

// Permission matrix defining what each role can access and modify
function getPermissions() {
    return [
        'Admin' => [
            'read' => ['all'],
            'write' => ['all'],
            'modules' => ['dashboard', 'students', 'faculty', 'courses', 'subjects', 'tests', 'grades', 'fees', 'library', 'placements', 'admissions', 'announcements', 'settings', 'papers']
        ],
        'Administrative Staff' => [
            'read' => ['all'],
            'write' => ['all'],
            'modules' => ['dashboard', 'students', 'faculty', 'courses', 'subjects', 'tests', 'grades', 'fees', 'library', 'placements', 'admissions', 'announcements', 'settings', 'papers']
        ],
        'Faculty' => [
            'read' => ['students', 'library', 'placements', 'dashboard', 'subjects', 'tests', 'grades', 'papers'],
            'write' => ['announcements', 'grades', 'tests', 'papers'],
            'modules' => ['dashboard', 'students', 'library', 'placements', 'announcements', 'grades', 'tests', 'subjects', 'papers']
        ],
        'Student' => [
            'read' => ['all'],
            'write' => [], // No write permissions
            'modules' => ['dashboard', 'grades', 'library', 'placements', 'announcements', 'papers', 'faculty']
        ]
    ];
}

function canRead($module) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    $permissions = getPermissions();
    $role = $user['role_name'];
    
    if (!isset($permissions[$role])) return false;
    
    return in_array('all', $permissions[$role]['read']) || in_array($module, $permissions[$role]['read']);
}

function canWrite($module) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    $permissions = getPermissions();
    $role = $user['role_name'];
    
    if (!isset($permissions[$role])) return false;
    
    return in_array('all', $permissions[$role]['write']) || in_array($module, $permissions[$role]['write']);
}

function canAccessModule($module) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    $permissions = getPermissions();
    $role = $user['role_name'];
    
    if (!isset($permissions[$role])) return false;
    
    return in_array($module, $permissions[$role]['modules']);
}

function getNotifications($userRole, $user) {
    global $pdo;

    $notifications = [];

    try {
        switch ($userRole) {
            case 'Management':
            case 'Admin':
                // System notifications
                $pendingAdmissions = $pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'Pending'")->fetchColumn();
                if ($pendingAdmissions > 0) {
                    $notifications[] = [
                        'type' => 'warning',
                        'message' => "$pendingAdmissions admission(s) pending approval",
                        'url' => 'admissions.php?status=pending'
                    ];
                }
                break;

            case 'Faculty':
                // Faculty notifications
                $facultyId = $user['faculty_id'] ?? null;
                if ($facultyId) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_test_grades WHERE faculty_id = ? AND status = 'Pending'");
                    $stmt->execute([$facultyId]);
                    $pendingGrades = $stmt->fetchColumn();
                    if ($pendingGrades > 0) {
                        $notifications[] = [
                            'type' => 'info',
                            'message' => "$pendingGrades student grade(s) pending",
                            'url' => 'grades.php'
                        ];
                    }
                }
                break;

            case 'Student':
                // Student notifications
                $studentId = $user['student_id'] ?? null;
                if ($studentId) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_payments WHERE student_id = ? AND payment_status = 'Pending'");
                    $stmt->execute([$studentId]);
                    $pendingFees = $stmt->fetchColumn();
                    if ($pendingFees > 0) {
                        $notifications[] = [
                            'type' => 'danger',
                            'message' => "You have $pendingFees pending fee payment(s)",
                            'url' => 'fees.php'
                        ];
                    }

                    // Upcoming tests
                    $stmt2 = $pdo->prepare("
                        SELECT COUNT(*) FROM tests t
                        JOIN student_test_grades stg ON t.test_id = stg.test_id
                        WHERE stg.student_id = ? AND t.test_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    ");
                    $stmt2->execute([$studentId]);
                    $upcomingTests = $stmt2->fetchColumn();
                    if ($upcomingTests > 0) {
                        $notifications[] = [
                            'type' => 'info',
                            'message' => "$upcomingTests test(s) scheduled this week",
                            'url' => 'tests.php'
                        ];
                    }
                }
                break;
        }
    } catch (Exception $e) {
        // Return empty array if error
    }

    return $notifications;
}

function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    if (!$date) return '-';
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return '-';
    return date('M d, Y g:i A', strtotime($datetime));
}

// Profile Picture Upload Functions
function uploadProfilePicture($fileInput, $entityType = 'student', $entityId = null) {
    // Validate file input
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }

    $file = $_FILES[$fileInput];

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
    }

    // Validate file size (max 2MB)
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size too large. Maximum size is 2MB.'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $entityType . '_' . ($entityId ?: 'temp') . '_' . time() . '_' . uniqid() . '.' . $extension;

    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/profile_pictures/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filepath = $uploadDir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Resize image if too large (optional - for better performance)
        resizeImage($filepath, 300, 300); // Resize to 300x300 max

        return [
            'success' => true,
            'message' => 'Profile picture uploaded successfully',
            'filename' => $filename,
            'path' => 'uploads/profile_pictures/' . $filename
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to save uploaded file'];
    }
}

function resizeImage($filepath, $maxWidth = 300, $maxHeight = 300) {
    // Get image info
    $imageInfo = getimagesize($filepath);
    if (!$imageInfo) {
        return false;
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];

    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    if ($ratio >= 1) {
        return true; // Image is already small enough
    }

    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);

    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // Load original image
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filepath);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($filepath);
            break;
        default:
            return false;
    }

    if (!$source) {
        return false;
    }

    // Resize
    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Save resized image
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $filepath, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $filepath, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($newImage, $filepath);
            break;
    }

    // Clean up memory
    imagedestroy($source);
    imagedestroy($newImage);

    return true;
}

function getProfilePictureUrl($profilePicturePath, $gender = 'Other') {
    if ($profilePicturePath) {
        // Check if file exists
        $fullPath = __DIR__ . '/' . $profilePicturePath;
        if (file_exists($fullPath)) {
            return $profilePicturePath;
        }
    }

    // Return gender-based default avatar
    $gender = $gender ? strtolower($gender) : 'other';
    if ($gender === 'male') {
        return 'uploads/avatars/male.svg';
    } elseif ($gender === 'female') {
        return 'uploads/avatars/female.svg';
    } else {
        return 'uploads/avatars/other.svg';
    }
}

function deleteProfilePicture($profilePicturePath) {
    if (!$profilePicturePath) {
        return true;
    }

    $fullPath = __DIR__ . '/' . $profilePicturePath;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }

    return true; // File doesn't exist, consider it "deleted"
}

function getAvailableAvatars() {
    return [
        'male' => 'uploads/avatars/male.svg',
        'female' => 'uploads/avatars/female.svg',
        'other' => 'uploads/avatars/other.svg'
    ];
}

function setUserAvatar($entityType, $entityId, $gender) {
    global $pdo;

    $table = $entityType === 'student' ? 'students' : 'faculty';
    $idColumn = $entityType === 'student' ? 'student_pk_id' : 'faculty_id';

    // Set profile_picture to null to use avatar, and update gender
    $stmt = $pdo->prepare("UPDATE $table SET profile_picture = NULL, gender = ? WHERE $idColumn = ?");
    return $stmt->execute([$gender, $entityId]);
}
