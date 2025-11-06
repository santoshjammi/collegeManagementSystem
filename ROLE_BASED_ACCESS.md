# College Management System - Role-Based Access Control

## User Roles and Permissions

The system now supports four distinct user roles with specific permissions:

### 1. Admin

- **Access**: Full system access
- **Permissions**: Read and write access to all modules
- **Navigation**: All menu items visible
- **Use Case**: System administrators who need complete control

### 2. Administrative Staff

- **Access**: Read/Write access to all modules
- **Permissions**:
  - Read: All modules
  - Write: All modules (students, faculty, subjects, tests, grades, fees, library, placements, admissions, announcements, settings)
- **Navigation**: Administration dropdown + Academic modules
- **Use Case**: Office staff who manage day-to-day operations

### 3. Faculty

- **Access**: Limited read/write access
- **Permissions**:
  - Read: Students, Library, Placements, Dashboard, Subjects, Tests, Grades
  - Write: Announcements, Grades, Tests
- **Navigation**: Academic modules (Subjects, Tests, Grades) + Library + Placements
- **Use Case**: Teachers who need to manage their subjects, create tests, and grade students

### 4. Student

- **Access**: Read-only access
- **Permissions**:
  - Read: All modules (view-only)
  - Write: None
- **Navigation**: Dashboard, Grades, Library, Placements, Announcements
- **Use Case**: Students who need to view their grades, library books, and placement information

## Permission Functions

The system uses the following permission checking functions:

- `hasRole($roles)` - Check if user has one of the specified roles
- `canRead($module)` - Check if user can read/view a specific module
- `canWrite($module)` - Check if user can create/edit in a specific module
- `canAccessModule($module)` - Check if user can access a specific module at all

## Database Changes

### Updated Roles Table

```sql
INSERT INTO roles (role_name) VALUES
('Admin'),
('Administrative Staff'),
('Faculty'),
('Student')
ON DUPLICATE KEY UPDATE role_name = role_name;
```

### Backward Compatibility

The `hasRole()` function includes backward compatibility mapping:
- 'Management' → 'Administrative Staff'
- 'Staff' → 'Administrative Staff'
- 'Admin' → 'Admin'
- 'Faculty' → 'Faculty'
- 'Student' → 'Student'

## Testing

Run the test user creation script to populate the database with sample users:

```bash
php create_test_users.php
```

Test accounts (Email-based login, username also supported):

- **admin@college.edu** OR **admin** / **admin123** - Full system access
- **staff@college.edu** OR **staff** / **staff123** - Administrative staff access
- **john.smith@college.edu** OR **faculty1** / **john.smith123** - Faculty access
- **alice.johnson@college.edu** OR **student1** OR **alice.johnson** / **alice.johnson123** - Student access

**Password Format**: Default password is `emailPrefix123` where emailPrefix is the part before @ in the email address.

## Profile Management

Users can change their password by accessing their profile page:

- Click on "Profile" in the navigation menu
- Enter current password and new password
- Password must be at least 6 characters long
- Users are encouraged to change their default password upon first login

## Implementation Details

### Navigation Updates

- Admin/Administrative Staff: See Administration dropdown
- Faculty: See Academic modules they can access
- Students: See limited navigation (Grades, Library, etc.)

### Action Buttons

- Add/Edit/Delete buttons are only shown when `canWrite($module)` returns true
- Read-only users see data but no modification options

### Dashboard Customization

- Each role sees different statistics and quick actions based on their permissions
- Content is dynamically generated based on user role

## Security Features

- Role-based access control at both navigation and action levels
- Permission checks prevent unauthorized access attempts
- Secure password hashing using PHP's `password_hash()`
- Session-based authentication with role validation

## Future Enhancements

- Granular permissions per module (e.g., faculty can only edit their own subjects)
- Audit logging for security tracking
- Role assignment management interface
- Permission inheritance system