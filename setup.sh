# College Management System - Setup Guide

## Overview
This guide provides complete setup instructions for deploying the College Management System on a new machine for testing and development.

## Prerequisites
- **PHP**: 7.4+ with PDO and MySQL extensions
- **MySQL/MariaDB**: 5.7+ with InnoDB support
- **Web Server**: Apache/Nginx with URL rewriting
- **Composer**: Optional, for dependency management

## Quick Setup (Automated)

### Option 1: One-Command Setup (Linux/Mac)
```bash
# Clone repository
git clone https://github.com/your-repo/college-management-system.git
cd college-management-system

# Run automated setup
chmod +x setup.sh
./setup.sh
```

### Option 2: Manual Setup

## 1. Database Setup

### Create Database
```sql
CREATE DATABASE college_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Import Schema and Data
```bash
# Import complete database schema with sample data
mysql -u your_username -p college_management < setup_database_complete.sql

# Import test user accounts
mysql -u your_username -p college_management < test_users_setup.sql
```

## 2. Application Configuration

### Copy Configuration Template
```bash
cp config_template.php config.php
```

### Edit Configuration (`config.php`)
```php
// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'college_management');
define('DB_USER', 'your_mysql_username');
define('DB_PASS', 'your_mysql_password');

// Application URL (adjust for your setup)
define('BASE_URL', 'http://localhost/college-management-system/traditional-php');
```

## 3. Web Server Configuration

### Apache (.htaccess)
```apache
RewriteEngine On
RewriteBase /college-management-system/traditional-php/

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/college-management-system/traditional-php;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 4. File Permissions

```bash
# Set proper permissions
chmod 755 /path/to/project/traditional-php
chmod 644 /path/to/project/traditional-php/*.php
chmod 755 /path/to/project/traditional-php/uploads/
chmod 755 /path/to/project/traditional-php/uploads/
```

## 5. Test Installation

### Access the Application
- Open: `http://localhost/college-management-system/traditional-php/`
- Login with test accounts (see below)

### Test Accounts

| Role | Username | Password | Access Level |
|------|----------|----------|--------------|
| Admin | `admin` | `password123` | Full system access |
| Staff | `staff1` | `password123` | Administrative functions |
| Faculty | `faculty1` | `password123` | Teaching & grading |
| Faculty | `sarah.johnson` | `password123` | Teaching & research |
| Student | `student1` | `password123` | Student portal |
| Student | `alice.johnson` | `password123` | Student portal |

## 6. Sample Data Included

The setup includes:
- ✅ 4 User roles (Admin, Staff, Faculty, Student)
- ✅ 5 Courses (CS, IT, ME, EE, CE)
- ✅ 6 Batches (2023-2024 intakes)
- ✅ 8 Sample students across courses
- ✅ 5 Faculty members with publications
- ✅ 10 Subjects with faculty assignments
- ✅ Sample tests and grades
- ✅ Fee payments and library transactions
- ✅ Placement offers and announcements

## 7. Key Features Available

### Administrative Modules
- **Course Management**: Add/edit/delete courses
- **Student Management**: Enrollment and profiles
- **Faculty Management**: Staff and publications
- **Subject Management**: Curriculum setup
- **Admissions**: Application processing

### Academic Modules
- **Test Management**: Create and manage assessments
- **Grade Management**: Automated grading system
- **Announcement System**: Multi-audience notifications

### Student Services
- **Library Management**: Book issue/return
- **Fee Management**: Payment tracking
- **Placement Services**: Job offers and tracking

## 8. Troubleshooting

### Common Issues

**Database Connection Failed**
```bash
# Check MySQL service
sudo systemctl status mysql

# Test connection
mysql -u your_username -p -e "SELECT 1"
```

**Permission Errors**
```bash
# Fix upload directory permissions
chmod 755 uploads/
chown www-data:www-data uploads/
```

**PHP Extensions Missing**
```bash
# Install required extensions
sudo apt-get install php-mysql php-gd php-mbstring
```

**URL Rewriting Issues**
- Ensure `mod_rewrite` is enabled (Apache)
- Check nginx configuration syntax

### Debug Mode
Enable debug mode in `config.php`:
```php
define('DEBUG_MODE', true);
```

## 9. Production Deployment

### Security Checklist
- [ ] Change default passwords
- [ ] Use HTTPS in production
- [ ] Set secure session settings
- [ ] Configure proper file permissions
- [ ] Set up log rotation
- [ ] Configure backup scripts

### Performance Optimization
- [ ] Enable opcode caching (OPcache)
- [ ] Set up database connection pooling
- [ ] Configure proper indexes
- [ ] Enable compression
- [ ] Set up CDN for static assets

## 10. Support

### Documentation
- Complete API documentation in `/docs/`
- Database schema in `docs/SQL_Tables.md`
- Setup guide in `COMPLETE_SETUP_GUIDE.md`

### Getting Help
1. Check the troubleshooting section above
2. Review error logs in `/logs/`
3. Test with provided sample data
4. Check GitHub issues for known problems

---

**Setup Complete!** 🎉

Your College Management System is now ready for testing and development.</content>
<parameter name="filePath">/Users/kgt/Desktop/Projects/PHP/CollegeManagementSystem/README_SETUP.md