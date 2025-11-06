# College Management System

A comprehensive college management system with two implementation approaches:

## 📁 Project Structure

```
CollegeManagementSystem/
├── laravel/              # Laravel REST API implementation (Modern)
├── traditional-php/      # Traditional PHP implementation (Simple)
├── docs/                 # Project documentation
│   ├── PRD.md           # Product Requirements Document
│   └── SQL_Tables.md    # Database schema
└── setup.sql            # Database initialization script
```

## 🚀 Two Implementations Available

### 1️⃣ Laravel REST API (Port 8008)

**Modern framework-based approach with REST API**

**Features:**
- ✅ Laravel 11 framework
- ✅ REST API architecture
- ✅ Sanctum authentication (JWT tokens)
- ✅ API resources for JSON responses
- ✅ Eloquent ORM
- ✅ Scalable & suitable for mobile apps

**Use when:**
- Building mobile applications
- Need third-party API integrations
- Building SPA (React/Vue/Angular)
- Require microservices architecture

**Quick Start:**
```bash
cd laravel
php artisan serve --host=0.0.0.0 --port=8008
# Access: http://localhost:8008
# Login: admin / password
```

### 2️⃣ Traditional PHP (Port 8080)

**Simple, easy-to-maintain approach without frameworks**

**Features:**
- ✅ Pure PHP (no frameworks)
- ✅ Server-side rendering
- ✅ Direct database access (PDO)
- ✅ Session-based authentication
- ✅ Bootstrap 5 UI
- ✅ 90% less code than Laravel

**Use when:**
- Building internal tools
- Small to medium projects
- Team prefers simple PHP
- Quick development needed
- No mobile app required

**Quick Start:**
```bash
cd traditional-php
php -S localhost:8080
# Access: http://localhost:8080
# Login: admin / password
```

## 🎯 Unified Dashboard (Traditional PHP)

The traditional PHP implementation now features a comprehensive **unified dashboard** that adapts to user roles and provides centralized access to all college management features.

### Role-Based Dashboard Customization

#### 👑 Management/Leadership
- High-level analytics and KPIs
- Resource utilization tracking
- Compliance and audit reports
- Strategic decision support

#### 👨‍💼 Administrators
- Full system access and control
- User management and permissions
- System configuration
- Comprehensive reporting

#### 👨‍🏫 Faculty
- Assigned subjects and students
- Test creation and management
- Grade entry and approval
- Performance analytics

#### 👨‍🎓 Students
- Personal academic records
- Grade viewing and tracking
- Fee payment and history
- Library book borrowing

### Dashboard Features

#### � Core Widgets
- **Quick Stats**: Total students, faculty, subjects, pending items
- **Recent Activity**: Latest system transactions and updates
- **Action Center**: Pending approvals, reminders, urgent tasks
- **Upcoming Events**: Test schedules, deadlines, important dates

#### 🔍 Global Search
Search across all modules simultaneously:
- Students (names, IDs, emails)
- Faculty (names, departments)
- Subjects (codes, names)
- Books (titles, authors, ISBNs)
- Companies (names, industries)

#### � Smart Notifications
- **Email**: Comprehensive notifications
- **SMS**: Critical alerts
- **In-App**: Real-time dashboard notifications

#### 📱 Mobile Responsive
- Fully optimized for smartphones and tablets
- Touch-friendly interface
- Responsive charts and analytics

#### 🎨 Personalization
- **Themes**: Light, Dark, Auto (system preference)
- **Layouts**: Default (spacious), Compact (dense)
- **Preferences**: Items per page, auto-refresh, language

### Quick Access Modules

| Module | Management | Admin | Faculty | Student |
|--------|------------|-------|---------|---------|
| Dashboard | ✅ Full | ✅ Full | ✅ Role-based | ✅ Personal |
| Students | ✅ View | ✅ Full CRUD | ✅ Assigned | ❌ N/A |
| Faculty | ✅ View | ✅ Full CRUD | ❌ N/A | ❌ N/A |
| Subjects | ✅ View | ✅ Full CRUD | ✅ Assigned | ✅ Enrolled |
| Tests | ✅ View | ✅ Full CRUD | ✅ Create/Manage | ✅ View Results |
| Grades | ✅ Analytics | ✅ Full CRUD | ✅ Enter/Approve | ✅ View Only |
| Admissions | ✅ Analytics | ✅ Full CRUD | ❌ N/A | ❌ N/A |
| Fees | ✅ Analytics | ✅ Full CRUD | ❌ N/A | ✅ Pay/View |
| Library | ✅ Analytics | ✅ Full CRUD | ✅ Access | ✅ Borrow |
| Placements | ✅ Analytics | ✅ Full CRUD | ❌ N/A | ✅ Apply/View |

### Dashboard URLs
- **Main Dashboard**: `http://localhost:8080/dashboard.php`
- **Global Search**: `http://localhost:8080/search.php`
- **User Profile**: `http://localhost:8080/profile.php`
- **Settings**: `http://localhost:8080/settings.php`

## 🔧 Database Setup

Both implementations use the same MySQL database:

```bash
# 1. Create database
mysql -u root -p
CREATE DATABASE college_management;

# 2. Import schema
mysql -u root -p college_management < setup.sql

# 3. Update configuration
# Laravel: .env file
# Traditional PHP: config.php
```

## 📖 Default Credentials

```
Username: admin
Password: password
Role: Admin
```

## 🎯 Comparison

| Feature | Traditional PHP | Laravel REST API |
|---------|----------------|------------------|
| **Setup Time** | 5 minutes | 15 minutes |
| **Code Complexity** | Simple | Complex |
| **File Count** | ~10 files | 50+ files |
| **Learning Curve** | Easy | Moderate |
| **Maintenance** | Very Easy | Moderate |
| **Mobile Support** | ❌ No | ✅ Yes |
| **API Access** | ❌ No | ✅ Yes |
| **Performance** | Fast | Fast |
| **Scalability** | Medium | High |

## 📚 Documentation

- `docs/PRD.md` - Complete product requirements
- `docs/SQL_Tables.md` - Detailed database schema
- `traditional-php/README.md` - Traditional PHP guide
- `laravel/README.md` - Laravel implementation guide

## 🛠️ Technology Stack

### Traditional PHP
- PHP 7.4+
- MySQL 5.7+
- Bootstrap 5
- PDO (Database)
- Session Authentication

### Laravel
- PHP 8.1+
- Laravel 11
- MySQL 5.7+
- Sanctum (Authentication)
- Bootstrap 5 + Tailwind CSS

## 💡 Which One to Choose?

**Choose Traditional PHP if:**
- ✅ You want simplicity
- ✅ Building internal tools
- ✅ Quick development needed
- ✅ Team knows basic PHP
- ✅ No API requirements

**Choose Laravel REST API if:**
- ✅ Need mobile app support
- ✅ Building SPA frontend
- ✅ Require REST API
- ✅ Team knows Laravel
- ✅ Long-term scalability important

## 📝 License

MIT License - Feel free to use for educational purposes.

## 🤝 Contributing

Both implementations are complete and functional. Choose the one that fits your requirements!

4. **Access the System**:
   - Login page: `index.php`
   - Online application: `apply.php`

## Project Structure

```text
/
├── index.php              # Login page
├── apply.php              # Online application form
├── setup.sql              # Database setup script
├── includes/
│   ├── db.php            # Database connection
│   ├── auth_check.php    # Authentication check
│   ├── header.php        # Common header with navigation
│   └── footer.php        # Common footer
├── admin/
│   ├── dashboard.php     # Admin dashboard
│   └── faculty.php       # Faculty management
├── staff/
│   ├── dashboard.php     # Staff dashboard
│   ├── students.php      # Student management
│   ├── admissions.php    # Admissions management
│   ├── fees.php          # Fee management
│   ├── library.php       # Library management
│   └── placements.php    # Placement management
├── faculty/
│   ├── dashboard.php     # Faculty dashboard
│   └── profile.php       # Faculty profile
├── student/
│   ├── dashboard.php     # Student dashboard
│   ├── profile.php       # Student profile
│   ├── fees.php          # Student fee status
│   └── library.php       # Student library history
└── docs/
    ├── PRD.md            # Product Requirements Document
    └── SQL_Tables.md     # Database schema
```

## Security Features

- Parameterized queries to prevent SQL injection
- Password hashing using PHP's password_hash()
- Role-based access control
- Input validation

## Technologies Used

- **Backend**: PHP (Native)
- **Database**: MySQL
- **Frontend**: HTML5, Bootstrap 5, Tailwind CSS
- **JavaScript**: Bootstrap JS for interactive components

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile responsive design

## Future Enhancements

- Email/SMS notifications
- Online payment gateway
- Advanced reporting and analytics
- Parent portal
- Digital library integration
