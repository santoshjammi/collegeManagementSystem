# Traditional PHP College Management System

A simple, easy-to-maintain College Management System built with traditional PHP (no frameworks).

## Features

✅ **Simple Architecture** - Traditional PHP with server-side rendering
✅ **No REST API** - Direct database access for faster development
✅ **Easy to Understand** - Straightforward code without framework complexity
✅ **Complete CRUD** - Create, Read, Update, Delete for all modules
✅ **Modern UI** - Bootstrap 5 with responsive design
✅ **Secure** - Password hashing, prepared statements, session management

## Modules

- 📊 **Dashboard** - Statistics and recent activity
- 👥 **Students** - Student management with search and filters
- 👨‍🏫 **Faculty** - Faculty member management
- 📝 **Admissions** - Application tracking
- 💰 **Fees** - Fee structure and payments
- 📚 **Library** - Book inventory and issue tracking
- 💼 **Placements** - Placement records

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) with mod_rewrite

## Installation

### 1. Database Setup

Use the existing database from the Laravel application:
```bash
# Database: college_management
# Already created with the Laravel setup
```

### 2. Configure Database Connection

Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'college_management');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Start PHP Development Server

```bash
cd traditional-php
php -S localhost:8080
```

### 4. Login

Open browser: `http://localhost:8080`

**Credentials:**
- Username: `admin`
- Password: `password`

## File Structure

```
traditional-php/
├── config.php          # Database & app configuration
├── login.php           # Login page
├── logout.php          # Logout handler
├── index.php           # Dashboard with statistics
├── students.php        # Student management (COMPLETE)
├── faculty.php         # Faculty management (COMPLETE)
├── admissions.php      # Admissions management (COMPLETE)
├── fees.php            # Fees management (COMPLETE)
├── library.php         # Library management (COMPLETE)
└── placements.php      # Placements management (COMPLETE)
```

## Advantages Over REST API Approach

| Traditional PHP | REST API (Laravel) |
|----------------|-------------------|
| ✅ Simpler codebase | ❌ More complex |
| ✅ Faster development | ❌ Slower setup |
| ✅ Less code to maintain | ❌ More files |
| ✅ Direct DB access | ❌ API overhead |
| ✅ Server-rendered HTML | ❌ JSON + frontend rendering |
| ❌ Less scalable | ✅ Better for APIs |
| ❌ No mobile app support | ✅ Reusable APIs |

## Security Features

- ✅ Password hashing with `password_hash()`
- ✅ Prepared statements (PDO) to prevent SQL injection
- ✅ Session-based authentication
- ✅ HTTP-only session cookies
- ✅ HTML escaping to prevent XSS
- ✅ CSRF protection (to be added)

## Next Steps

To complete the application, create similar files for:
- `faculty.php`
- `admissions.php`
- `fees.php`
- `library.php`
- `placements.php`

Each follows the same pattern as `students.php`:
1. Authentication check
2. Handle POST actions (add/edit/delete)
3. Fetch data with filters
4. Display in table
5. Modal form for add/edit

## Support

This is a simplified version for easier maintenance. Use this if:
- ✅ Building internal tools
- ✅ Small to medium applications
- ✅ Team prefers simple PHP
- ✅ Don't need mobile app support

Use Laravel REST API version if:
- ✅ Need mobile app
- ✅ Building SPA frontend
- ✅ Require third-party API access
- ✅ Need microservices architecture
