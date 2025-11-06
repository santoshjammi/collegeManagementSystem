# College Management System - Complete Setup Guide

## Overview
This comprehensive guide provides everything needed to set up the College Management System on a new machine for testing and development.

## Prerequisites
- **PHP**: 7.4+ with PDO and MySQL extensions enabled
- **MySQL/MariaDB**: 5.7+ with InnoDB support
- **Web Server**: Apache/Nginx with URL rewriting support
- **Git**: For cloning the repository (optional)

## Quick Start Options

### Option 1: Automated Setup (Recommended)
```bash
# Clone repository
git clone https://github.com/your-repo/college-management-system.git
cd college-management-system

# Run automated setup
chmod +x setup.sh
./setup.sh
```

### Option 2: Docker Setup
```bash
# Ensure Docker and Docker Compose are installed
docker --version
docker-compose --version

# Start the application
docker-compose up -d

# Access at: http://localhost:8080
```

### Option 3: Manual Setup
Follow the detailed steps below.

## 1. Database Setup

### Create Database
```sql
-- Create database with proper charset
CREATE DATABASE college_management
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

### Import Complete Schema and Data
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

**🎉 Setup Complete!** Your College Management System is ready for testing and development.

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
    sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start Apache
CMD ["apache2-foreground"]</content>
<parameter name="filePath">/Users/kgt/Desktop/Projects/PHP/CollegeManagementSystem/Dockerfile