<?php
// =====================================================
// COLLEGE MANAGEMENT SYSTEM - CONFIGURATION TEMPLATE
// =====================================================
// Copy this file to config.php and update the settings below

// Database configuration
define('DB_HOST', 'localhost');           // MySQL server hostname
define('DB_NAME', 'college_management'); // Database name (create this first)
define('DB_USER', 'your_mysql_username'); // MySQL username
define('DB_PASS', 'your_mysql_password'); // MySQL password

// Application configuration
define('BASE_URL', 'http://localhost/college-management-system/traditional-php');
// Update this to match your web server setup
// Examples:
// - XAMPP: 'http://localhost/college-management-system/traditional-php'
// - WAMP: 'http://localhost/college-management-system/traditional-php'
// - Linux: 'http://your-domain.com/traditional-php'
// - With subdirectory: 'http://localhost/subfolder/traditional-php'

define('SITE_NAME', 'College Management System');
define('SITE_VERSION', '1.0.0');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600); // 1 hour session timeout
session_start();

// File upload configuration
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Email configuration (optional - for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@university.edu');
define('SMTP_FROM_NAME', 'College Management System');

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes in seconds

// Feature flags (enable/disable modules)
define('ENABLE_ANNOUNCEMENTS', true);
define('ENABLE_LIBRARY', true);
define('ENABLE_PLACEMENTS', true);
define('ENABLE_FEES', true);
define('ENABLE_ADMISSIONS', true);
define('ENABLE_PAPERS', true);

// Development settings
define('DEBUG_MODE', false); // Set to true for development
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', __DIR__ . '/logs/error.log');

// Timezone
date_default_timezone_set('Asia/Kolkata'); // Change to your timezone

// =====================================================
// DATABASE CONNECTION
// =====================================================

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ]
    );

    if (DEBUG_MODE) {
        echo "Database connection successful!<br>";
    }

} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

// =====================================================
// HELPER FUNCTIONS
// =====================================================

// Redirect helper
function redirect($path) {
    header("Location: " . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

// Login requirement
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Sanitize output
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// =====================================================
// USER MANAGEMENT FUNCTIONS
// =====================================================

// Get current user with full profile
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
                   END as profile_id,
                   CASE
                       WHEN r.role_name = 'Faculty' THEN f.full_name
                       WHEN r.role_name = 'Student' THEN s.full_name
                       ELSE u.username
                   END as display_name
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

        // Fallback query
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_name, u.username as display_name, NULL as profile_id
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("getCurrentUser error: " . $e->getMessage());
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? 'unknown',
            'role_name' => $_SESSION['role'] ?? 'Student',
            'display_name' => $_SESSION['username'] ?? 'Unknown User',
            'profile_id' => null
        ];
    }
}

// Check user role permissions
function hasRole($roles) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    // Role mapping for backward compatibility
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

// Permission matrix
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
            'write' => [],
            'modules' => ['dashboard', 'grades', 'library', 'placements', 'announcements', 'papers']
        ]
    ];
}

// Check module access
function canAccessModule($module) {
    $user = getCurrentUser();
    if (!$user) return false;

    $permissions = getPermissions();
    $role = $user['role_name'];

    if (!isset($permissions[$role])) return false;

    return in_array($module, $permissions[$role]['modules']);
}

// Get user notifications
function getNotifications($userRole, $user) {
    global $pdo;

    $notifications = [];

    try {
        switch ($userRole) {
            case 'Admin':
                // Pending admissions
                $stmt = $pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'Pending'");
                $pendingAdmissions = $stmt->fetchColumn();
                if ($pendingAdmissions > 0) {
                    $notifications[] = [
                        'type' => 'warning',
                        'message' => "$pendingAdmissions admission(s) pending approval",
                        'url' => 'admissions.php?status=pending'
                    ];
                }
                break;

            case 'Faculty':
                // Faculty-specific notifications
                $facultyId = $user['faculty_id'] ?? null;
                if ($facultyId) {
                    // Pending grades to submit
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM tests t
                        WHERE t.faculty_id = ? AND t.status = 'Published'
                        AND NOT EXISTS (
                            SELECT 1 FROM student_test_grades stg
                            WHERE stg.test_id = t.test_id AND stg.student_id IN (
                                SELECT student_pk_id FROM students WHERE batch_id = t.batch_id
                            )
                        )
                    ");
                    $stmt->execute([$facultyId]);
                    $pendingGrades = $stmt->fetchColumn();

                    if ($pendingGrades > 0) {
                        $notifications[] = [
                            'type' => 'info',
                            'message' => "$pendingGrades test(s) need grading",
                            'url' => 'grades.php'
                        ];
                    }
                }
                break;
        }

        // Common notifications for all users
        // Unread announcements
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$user['user_id']]);
        $unreadNotifications = $stmt->fetchColumn();

        if ($unreadNotifications > 0) {
            $notifications[] = [
                'type' => 'info',
                'message' => "You have $unreadNotifications unread notification(s)",
                'url' => 'dashboard.php'
            ];
        }

    } catch (Exception $e) {
        error_log("getNotifications error: " . $e->getMessage());
    }

    return $notifications;
}

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

// Format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Format date
function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

// Get file extension
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Validate file upload
function validateFileUpload($file, $allowedExtensions = null, $maxSize = null) {
    if (!$allowedExtensions) {
        $allowedExtensions = ALLOWED_EXTENSIONS;
    }
    if (!$maxSize) {
        $maxSize = MAX_FILE_SIZE;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "File upload error: " . $file['error'];
    }

    if ($file['size'] > $maxSize) {
        return "File size exceeds maximum allowed size (" . ($maxSize / 1024 / 1024) . "MB)";
    }

    $extension = getFileExtension($file['name']);
    if (!in_array($extension, $allowedExtensions)) {
        return "File type not allowed. Allowed types: " . implode(', ', $allowedExtensions);
    }

    return true;
}

// =====================================================
// END OF CONFIGURATION
// =====================================================
?></content>
<parameter name="filePath">/Users/kgt/Desktop/Projects/PHP/CollegeManagementSystem/config_template.php