<?php
/**
 * College Management System - Setup Test Script
 * This script validates that the system is properly configured and ready for use
 */

// Include configuration
require_once 'config.php';

// Test results array
$tests = [
    'config_loaded' => false,
    'database_connection' => false,
    'database_tables' => false,
    'sample_data' => false,
    'file_permissions' => false,
    'php_extensions' => false
];

$errors = [];
$warnings = [];

// Test 1: Configuration loaded
try {
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
        $tests['config_loaded'] = true;
    } else {
        $errors[] = "Configuration constants not properly defined";
    }
} catch (Exception $e) {
    $errors[] = "Configuration loading failed: " . $e->getMessage();
}

// Test 2: Database connection
if ($tests['config_loaded']) {
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
        $tests['database_connection'] = true;
    } catch (PDOException $e) {
        $errors[] = "Database connection failed: " . $e->getMessage();
    }
}

// Test 3: Database tables exist
if ($tests['database_connection']) {
    try {
        $required_tables = [
            'users', 'roles', 'courses', 'batches', 'students', 'faculty',
            'subjects', 'tests', 'grades', 'announcements', 'publications',
            'fee_payments', 'library_transactions', 'placements'
        ];

        $stmt = $pdo->query("SHOW TABLES");
        $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $missing_tables = array_diff($required_tables, $existing_tables);

        if (empty($missing_tables)) {
            $tests['database_tables'] = true;
        } else {
            $warnings[] = "Missing tables: " . implode(', ', $missing_tables);
        }
    } catch (Exception $e) {
        $errors[] = "Table check failed: " . $e->getMessage();
    }
}

// Test 4: Sample data exists
if ($tests['database_connection']) {
    try {
        // Check for sample users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $user_count = $stmt->fetch()['count'];

        // Check for sample courses
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
        $course_count = $stmt->fetch()['count'];

        // Check for sample students
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
        $student_count = $stmt->fetch()['count'];

        if ($user_count > 0 && $course_count > 0 && $student_count > 0) {
            $tests['sample_data'] = true;
        } else {
            $warnings[] = "Sample data may be incomplete (Users: $user_count, Courses: $course_count, Students: $student_count)";
        }
    } catch (Exception $e) {
        $errors[] = "Sample data check failed: " . $e->getMessage();
    }
}

// Test 5: File permissions
$directories_to_check = ['uploads', 'logs'];
foreach ($directories_to_check as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        $warnings[] = "Created missing directory: $dir";
    }

    if (!is_writable($dir)) {
        $errors[] = "Directory not writable: $dir";
    } else {
        $tests['file_permissions'] = true;
    }
}

// Test 6: PHP extensions
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'gd', 'zip'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (empty($missing_extensions)) {
    $tests['php_extensions'] = true;
} else {
    $errors[] = "Missing PHP extensions: " . implode(', ', $missing_extensions);
}

// Output results
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Management System - Setup Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-pass { color: #198754; }
        .test-fail { color: #dc3545; }
        .test-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-check-circle"></i> College Management System - Setup Test
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="lead">Testing system configuration and readiness...</p>

                        <!-- Test Results -->
                        <div class="mb-4">
                            <h5>Test Results:</h5>
                            <ul class="list-group">
                                <?php foreach ($tests as $test => $passed): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php
                                        $test_names = [
                                            'config_loaded' => 'Configuration Loaded',
                                            'database_connection' => 'Database Connection',
                                            'database_tables' => 'Database Tables',
                                            'sample_data' => 'Sample Data',
                                            'file_permissions' => 'File Permissions',
                                            'php_extensions' => 'PHP Extensions'
                                        ];
                                        $icon = $passed ? '✓' : '✗';
                                        $class = $passed ? 'test-pass' : 'test-fail';
                                        ?>
                                        <span><?php echo $test_names[$test]; ?></span>
                                        <span class="<?php echo $class; ?>"><strong><?php echo $icon; ?></strong></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Errors -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-triangle"></i> Errors Found:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Warnings -->
                        <?php if (!empty($warnings)): ?>
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-circle"></i> Warnings:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($warnings as $warning): ?>
                                        <li><?php echo htmlspecialchars($warning); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Overall Status -->
                        <div class="mt-4">
                            <?php
                            $all_passed = !in_array(false, $tests);
                            $critical_errors = count($errors) > 0;
                            ?>
                            <?php if ($all_passed && !$critical_errors): ?>
                                <div class="alert alert-success">
                                    <h5><i class="fas fa-check-circle"></i> Setup Complete!</h5>
                                    <p>Your College Management System is properly configured and ready to use.</p>
                                    <a href="login.php" class="btn btn-success">Go to Login</a>
                                </div>
                            <?php elseif (!$critical_errors): ?>
                                <div class="alert alert-warning">
                                    <h5><i class="fas fa-exclamation-triangle"></i> Setup Mostly Complete</h5>
                                    <p>The system should work but some features may be limited. Check the warnings above.</p>
                                    <a href="login.php" class="btn btn-warning">Try Login Anyway</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <h5><i class="fas fa-times-circle"></i> Setup Issues Found</h5>
                                    <p>Please resolve the errors above before using the system.</p>
                                    <a href="COMPLETE_SETUP_GUIDE.md" class="btn btn-danger">View Setup Guide</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- System Information -->
                        <div class="mt-4">
                            <h6>System Information:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
                                        <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
                                        <strong>Database:</strong> <?php echo $tests['database_connection'] ? 'Connected' : 'Not Connected'; ?>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <strong>Test Run:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                                        <strong>Config File:</strong> <?php echo file_exists('config.php') ? 'Found' : 'Missing'; ?><br>
                                        <strong>Base URL:</strong> <?php echo defined('BASE_URL') ? BASE_URL : 'Not defined'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```bash
# Import schema and sample data
mysql -u your_username -p college_management < setup_database_complete.sql

# Import test user accounts
mysql -u your_username -p college_management < test_users_setup.sql
```

**What gets installed:**
- ✅ Complete database schema (18 tables)
- ✅ Sample courses, batches, students, faculty
- ✅ Test user accounts with proper permissions
- ✅ Sample subjects, tests, grades, and publications
- ✅ Fee payments, library transactions, placements

## 2. Application Configuration

### Copy Configuration Template
```bash
cp config_template.php config.php
```

### Update Database Settings
Edit `config.php` and update these lines:
```php
define('DB_HOST', 'localhost');           // Your MySQL host
define('DB_NAME', 'college_management'); // Database name
define('DB_USER', 'your_mysql_username'); // MySQL username
define('DB_PASS', 'your_mysql_password'); // MySQL password

// Update BASE_URL for your setup
define('BASE_URL', 'http://localhost/college-management-system/traditional-php');
```

### Configuration Options
```php
// Security settings
define('DEBUG_MODE', false);        // Set to true for development
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Feature flags
define('ENABLE_ANNOUNCEMENTS', true);
define('ENABLE_LIBRARY', true);
define('ENABLE_PLACEMENTS', true);
```

## 3. Web Server Configuration

### Apache Setup (.htaccess)
The `.htaccess` file is already configured. Place it in the `traditional-php/` directory.

**Key features:**
- URL rewriting for clean URLs
- Security headers
- File access restrictions
- Compression and caching

### Nginx Setup
Copy `nginx.conf` to your Nginx sites directory:
```bash
sudo cp nginx.conf /etc/nginx/sites-available/college-management
sudo ln -s /etc/nginx/sites-available/college-management /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### File Permissions
```bash
# Set proper permissions
chmod 755 traditional-php/
chmod 644 traditional-php/*.php
chmod 755 traditional-php/uploads/
chmod 755 traditional-php/logs/

# For Nginx (Ubuntu/Debian)
sudo chown -R www-data:www-data traditional-php/uploads/
sudo chown -R www-data:www-data traditional-php/logs/
```

## 4. Test User Accounts

After setup, use these accounts to test the system:

| Role | Username | Password | Permissions |
|------|----------|----------|-------------|
| **Admin** | `admin` | `password123` | Full system access |
| **Staff** | `staff1` | `password123` | Administrative functions |
| **Faculty** | `faculty1` | `password123` | Teaching & grading |
| **Faculty** | `sarah.johnson` | `password123` | Teaching & research |
| **Student** | `student1` | `password123` | Student portal |
| **Student** | `alice.johnson` | `password123` | Student portal |

## 5. Sample Data Overview

### Academic Structure
- **5 Courses**: Computer Science, IT, Mechanical, Electrical, Civil Engineering
- **6 Batches**: 2023-2024 intakes for each course
- **10 Subjects**: Distributed across courses with faculty assignments
- **Sample Tests**: Quizzes, midterms, finals with grading

### User Data
- **8 Students**: Across different courses and batches
- **5 Faculty**: With research publications and teaching assignments
- **Publications**: 5 faculty papers + 3 student papers

### Administrative Data
- **Fee Payments**: Sample transactions for students
- **Library Transactions**: Book issue/return records
- **Placement Offers**: Job offers for graduated students
- **Announcements**: System-wide notifications

## 6. System Features

### Administrative Modules
- **Course Management**: Add/edit/delete courses with statistics
- **Student Management**: Enrollment, profiles, academic status
- **Faculty Management**: Staff records and publications
- **Subject Management**: Curriculum setup and assignments
- **Admissions**: Application processing and tracking

### Academic Modules
- **Test Management**: Create assessments with auto-grading
- **Grade Management**: Comprehensive grading system
- **Announcement System**: Multi-audience notifications
- **Research Portal**: Faculty and student publications

### Student Services
- **Library System**: Book borrowing and returns
- **Fee Portal**: Payment tracking and due dates
- **Placement Services**: Job offers and career support

## 7. API Endpoints

The system includes AJAX endpoints for dynamic operations:

### Announcement Management
- `POST /ajax/create_announcement.php` - Create announcements
- `GET /ajax/get_announcements.php` - Fetch announcements

### User Management
- `POST /ajax/update_profile.php` - Profile updates
- `POST /ajax/change_password.php` - Password changes

### Academic Operations
- `POST /ajax/submit_grades.php` - Grade submissions
- `POST /ajax/create_test.php` - Test creation

## 8. Security Features

### Authentication & Authorization
- **bcrypt password hashing**
- **Role-based access control (RBAC)**
- **Session management with security headers**
- **CSRF protection on forms**

### Data Protection
- **SQL injection prevention** (PDO prepared statements)
- **XSS protection** (input sanitization)
- **File upload validation**
- **Secure file permissions**

### Audit Trail
- **Login attempt logging**
- **User action tracking**
- **Error logging system**

## 9. Performance Optimization

### Database Optimization
- **Proper indexing** on frequently queried columns
- **Connection pooling** support
- **Query optimization** with EXPLAIN plans

### Caching Strategy
- **Browser caching** for static assets
- **Database query caching**
- **Session storage optimization**

### File Management
- **Efficient file uploads** with validation
- **Image optimization** for profile pictures
- **Secure file storage** with access controls

## 10. Troubleshooting

### Common Issues & Solutions

#### Database Connection Failed
```bash
# Check MySQL service
sudo systemctl status mysql

# Test connection
mysql -u username -p -e "SELECT 1"

# Verify credentials in config.php
php -r "require 'config.php'; echo 'Connection OK';"
```

#### Permission Errors
```bash
# Fix upload directory permissions
chmod 755 uploads/
chown www-data:www-data uploads/

# For SELinux systems
chcon -R -t httpd_sys_rw_content_t uploads/
```

#### PHP Extension Missing
```bash
# Ubuntu/Debian
sudo apt-get install php-mysql php-gd php-mbstring php-xml php-zip

# CentOS/RHEL
sudo yum install php-mysql php-gd php-mbstring php-xml php-zip

# Restart web server
sudo systemctl restart apache2  # or nginx
```

#### URL Rewriting Issues
- **Apache**: Ensure `mod_rewrite` is enabled
- **Nginx**: Check configuration syntax with `nginx -t`
- **IIS**: Install URL Rewrite module

#### Session Issues
```php
// Check session configuration in config.php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // 1 for HTTPS
ini_set('session.cookie_samesite', 'Lax');
```

### Debug Mode
Enable debug mode for development:
```php
define('DEBUG_MODE', true);
```

Check logs in `traditional-php/logs/error.log`

## 11. Production Deployment

### Pre-deployment Checklist
- [ ] Change default passwords
- [ ] Set up HTTPS certificates
- [ ] Configure proper backup scripts
- [ ] Set up monitoring and alerts
- [ ] Configure log rotation
- [ ] Test all user roles and permissions

### Security Hardening
```bash
# Disable directory listing
<Directory "/var/www/html">
    Options -Indexes
</Directory>

# Restrict access to sensitive files
<FilesMatch "\.(htaccess|htpasswd|ini|log|sh|sql)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

### Backup Strategy
```bash
# Database backup
mysqldump -u username -p college_management > backup_$(date +%Y%m%d).sql

# File backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz traditional-php/uploads/

# Automated backup script
crontab -e
# Add: 0 2 * * * /path/to/backup-script.sh
```

## 12. Development Guidelines

### Code Standards
- **PSR-12** coding standards
- **PDO** for database operations
- **Bootstrap 5** for UI consistency
- **jQuery** for DOM manipulation

### Testing Strategy
- **Unit tests** for business logic
- **Integration tests** for database operations
- **UI tests** for critical user flows
- **Security testing** for authentication

### Version Control
```bash
# Branching strategy
git checkout -b feature/new-module
git checkout -b bugfix/login-issue

# Commit standards
git commit -m "feat: add course management module
- Add CRUD operations for courses
- Implement role-based permissions
- Add statistics dashboard"
```

## 13. Support & Documentation

### Documentation Files
- `README_SETUP.md` - This setup guide
- `docs/SQL_Tables.md` - Database schema documentation
- `docs/` - Additional technical documentation

### Getting Help
1. Check this troubleshooting section
2. Review error logs in `logs/error.log`
3. Test with provided sample data
4. Check GitHub issues for known problems

### Community Support
- **GitHub Issues**: Report bugs and request features
- **Wiki**: User guides and best practices
- **Discussions**: Community Q&A and tips

---

## Quick Reference

### URLs
- **Application**: `http://localhost/college-management-system/traditional-php/`
- **phpMyAdmin**: `http://localhost:8081` (Docker only)

### Important Files
- `config.php` - Application configuration
- `setup_database_complete.sql` - Complete database schema
- `test_users_setup.sql` - Test user accounts
- `.htaccess` - Apache configuration
- `nginx.conf` - Nginx configuration

### Default Credentials
- **Admin**: admin / password123
- **Faculty**: faculty1 / password123
- **Student**: student1 / password123

---

**🎉 Setup Complete!** Your College Management System is ready for testing and development.</content>
<parameter name="filePath">/Users/kgt/Desktop/Projects/PHP/CollegeManagementSystem/COMPLETE_SETUP_GUIDE.md