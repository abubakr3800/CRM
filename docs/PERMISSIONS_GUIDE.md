# 🔐 CRM Permissions System Guide

## Overview

The CRM system now includes a comprehensive role-based permissions system that provides granular access control for different user types. This ensures that users only have access to the features and data they need for their role.

## 🎭 User Roles

### 1. **Administrator (admin)**
- **Full System Access**: Complete control over all features
- **User Management**: Create, edit, delete users
- **System Administration**: Backup management, system settings
- **All Permissions**: Can perform any action in the system

### 2. **Manager (manager)**
- **Account Management**: Full access to accounts and contacts
- **Project Management**: Create and manage projects
- **Task Management**: Assign and manage tasks
- **Price Offers**: Create, edit, and approve offers
- **Reports**: View and export reports
- **Messages**: Send global messages to all users

### 3. **Worker (worker)**
- **Data Access**: View accounts, contacts, projects, tasks, offers
- **Data Creation**: Create new records in most modules
- **Data Editing**: Edit existing records (except user management)
- **Personal Messages**: Send messages to other users
- **Limited Reports**: View reports (no export)

### 4. **Viewer (viewer)**
- **Read-Only Access**: View most data without editing
- **Profile Management**: Edit own profile and change password
- **Messages**: View messages sent to them
- **No Creation/Editing**: Cannot create or modify records

## 🔑 Permission Categories

### User Management
- `users.view` - View user list
- `users.create` - Create new users
- `users.edit` - Edit existing users
- `users.delete` - Delete users

### Account Management
- `accounts.view` - View accounts
- `accounts.create` - Create new accounts
- `accounts.edit` - Edit existing accounts
- `accounts.delete` - Delete accounts

### Contact Management
- `contacts.view` - View contacts
- `contacts.create` - Create new contacts
- `contacts.edit` - Edit existing contacts
- `contacts.delete` - Delete contacts

### Project Management
- `projects.view` - View projects
- `projects.create` - Create new projects
- `projects.edit` - Edit existing projects
- `projects.delete` - Delete projects

### Task Management
- `tasks.view` - View tasks
- `tasks.create` - Create new tasks
- `tasks.edit` - Edit existing tasks
- `tasks.delete` - Delete tasks
- `tasks.assign` - Assign tasks to users

### Price Offers
- `offers.view` - View price offers
- `offers.create` - Create new offers
- `offers.edit` - Edit existing offers
- `offers.delete` - Delete offers
- `offers.approve` - Approve offers

### Reports
- `reports.view` - View reports
- `reports.export` - Export reports

### System Administration
- `admin.backup` - Manage backups
- `admin.settings` - System settings
- `admin.logs` - View activity logs
- `admin.users` - User management

### Messages
- `messages.view` - View messages
- `messages.send` - Send messages
- `messages.global` - Send global messages

### Profile
- `profile.view` - View own profile
- `profile.edit` - Edit own profile
- `profile.password` - Change own password

## 🚀 Getting Started

### 1. Update Your System
Run the role update script to enable the new permissions system:

```
http://localhost/crm/update_roles.php
```

### 2. Default Users
After updating, you'll have these users available:

| Username | Password | Role | Description |
|----------|----------|------|-------------|
| admin | password | Administrator | Full system access |
| manager | manager123 | Manager | Project and team management |
| worker | worker123 | Worker | Data entry and management |
| viewer | viewer123 | Viewer | Read-only access |

### 3. Access User Management
- Login as admin
- Go to User Management (in user dropdown menu)
- Create, edit, or delete users
- Assign appropriate roles

## 💻 Implementation Examples

### Checking Permissions in PHP

```php
// Check if user has permission
if ($permissions->hasPermission('users.create')) {
    // Show create user button
}

// Require permission (redirects if not authorized)
$permissions->requirePermission('admin.settings');

// Check if user can edit a specific record
if ($permissions->canEditRecord('accounts', $accountId)) {
    // Show edit button
}
```

### Role-Based Navigation

```php
<?php if ($permissions->hasPermission('users.view')): ?>
<li><a href="users.php">User Management</a></li>
<?php endif; ?>

<?php if ($permissions->hasPermission('admin.settings')): ?>
<li><a href="admin.php">Admin Panel</a></li>
<?php endif; ?>
```

### Conditional UI Elements

```php
<?php if ($permissions->hasPermission('accounts.create')): ?>
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
    Add New Account
</button>
<?php endif; ?>

<?php if ($permissions->hasPermission('accounts.delete')): ?>
<button class="btn btn-danger" onclick="deleteRecord(<?php echo $recordId; ?>)">
    Delete
</button>
<?php endif; ?>
```

## 🔧 Customizing Permissions

### Adding New Permissions

1. **Update Permissions Class**:
```php
// In includes/Permissions.php
const PERMISSIONS = [
    // ... existing permissions
    'custom.action' => 'Custom Action Description',
];
```

2. **Assign to Roles**:
```php
const ROLE_PERMISSIONS = [
    'admin' => ['all'], // Admins get everything
    'manager' => [
        // ... existing permissions
        'custom.action',
    ],
];
```

### Creating New Roles

1. **Add to Database**:
```sql
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'manager', 'worker', 'viewer', 'newrole') DEFAULT 'worker';
```

2. **Define Permissions**:
```php
const ROLE_PERMISSIONS = [
    'newrole' => [
        'accounts.view',
        'contacts.view',
        // ... specific permissions
    ],
];
```

## 🛡️ Security Features

### Session Management
- Automatic session timeout
- Secure session handling
- IP address tracking in logs

### Password Security
- Bcrypt password hashing
- Password strength requirements
- Secure password reset

### Activity Logging
- All user actions are logged
- IP address and user agent tracking
- Before/after value tracking
- Audit trail for compliance

### Access Control
- Role-based page access
- Permission-based feature access
- Record-level permissions
- Secure API endpoints

## 📊 Permission Matrix

| Feature | Admin | Manager | Worker | Viewer |
|---------|-------|---------|--------|--------|
| User Management | ✅ Full | ❌ | ❌ | ❌ |
| Account Management | ✅ Full | ✅ Full | ✅ Edit | ✅ View |
| Contact Management | ✅ Full | ✅ Full | ✅ Edit | ✅ View |
| Project Management | ✅ Full | ✅ Full | ✅ Edit | ✅ View |
| Task Management | ✅ Full | ✅ Full | ✅ Edit | ✅ View |
| Price Offers | ✅ Full | ✅ Full | ✅ Edit | ✅ View |
| Reports | ✅ Full | ✅ Full | ✅ View | ✅ View |
| System Settings | ✅ Full | ❌ | ❌ | ❌ |
| Messages | ✅ Full | ✅ Global | ✅ Personal | ✅ View |

## 🚨 Troubleshooting

### Common Issues

1. **"Access Denied" Error**
   - Check if user has required permission
   - Verify role assignment
   - Check session status

2. **Missing Navigation Items**
   - Ensure user has appropriate permissions
   - Check header.php permission checks
   - Verify role configuration

3. **Cannot Create/Edit Records**
   - Check specific permission (e.g., 'accounts.create')
   - Verify user role
   - Check database connection

### Debug Permissions

```php
// Check current user permissions
$userPermissions = $permissions->getUserPermissions();
var_dump($userPermissions);

// Check specific permission
$hasPermission = $permissions->hasPermission('users.create');
var_dump($hasPermission);

// Check user role
$currentUser = $auth->getCurrentUser();
echo "Current role: " . $currentUser['role'];
```

## 📈 Best Practices

1. **Principle of Least Privilege**: Give users only the permissions they need
2. **Regular Audits**: Review user permissions periodically
3. **Role-Based Access**: Use roles instead of individual permissions when possible
4. **Secure Defaults**: Default to restrictive permissions
5. **Activity Monitoring**: Regularly check activity logs
6. **Password Policies**: Enforce strong password requirements

## 🔄 Future Enhancements

- **Department-Based Permissions**: Access based on organizational structure
- **Time-Based Permissions**: Temporary access grants
- **IP-Based Restrictions**: Limit access by location
- **Two-Factor Authentication**: Enhanced security
- **Permission Groups**: Custom permission sets
- **API Permissions**: Separate API access controls

---

**Need Help?** Check the system documentation or contact your system administrator.
