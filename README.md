# CRM System

A comprehensive Customer Relationship Management system built with PHP, MySQL, and Bootstrap.

## 🚀 Quick Start

1. **Start XAMPP** (Apache + MySQL)
2. **Open** `http://localhost/crm`
3. **Login** with:
   - Username: `admin`
   - Password: `password`

## 📁 Project Structure

```
crm/
├── 📄 Core Files
│   ├── index.php              # Dashboard/Homepage
│   ├── login.php              # Login page
│   ├── logout.php             # Logout handler
│   └── 404.php                # Error page
│
├── 📊 Main Modules
│   ├── accounts.php           # Account management
│   ├── account_details.php    # Account details view
│   ├── contacts.php           # Contact management
│   ├── projects.php           # Project management
│   ├── tasks.php              # Tasks & Reports
│   ├── price_offers.php       # Price quotations
│   ├── users.php              # User management
│   ├── profile.php            # User profile
│   └── admin.php              # Admin panel
│
├── 📁 API Endpoints
│   └── api/
│       ├── get_contacts.php   # Get contacts API
│       ├── get_projects.php   # Get projects API
│       └── notifications.php  # Notifications API
│
├── 📁 Configuration
│   ├── config/
│   │   └── database.php       # Database configuration
│   └── includes/
│       ├── Auth.php           # Authentication system
│       ├── Database.php       # Database connection
│       ├── Logger.php         # Activity logging
│       ├── Backup.php         # Backup system
│       ├── Permissions.php    # Role-based permissions
│       ├── header.php         # Common header
│       └── footer.php         # Common footer
│
├── 📁 Assets
│   └── assets/
│       ├── css/
│       │   └── style.css      # Custom styles
│       └── js/
│           └── app.js         # JavaScript functions
│
├── 📁 Database
│   └── database/
│       ├── shortcircuit_crm.sql      # Main database schema
│       ├── shortcircuit_crm_old.sql  # Backup schema
│       ├── database_fixes.sql        # Database enhancements
│       ├── safe_database_migration.sql # Safe migration script
│       └── update_roles.sql          # Role updates
│
├── 📁 Documentation
│   └── docs/
│       ├── INSTALLATION.md           # Installation guide
│       ├── STARTUP_GUIDE.md          # Quick start guide
│       ├── SYSTEM_DOCUMENTATION.md   # System documentation
│       ├── PERMISSIONS_GUIDE.md      # Permissions guide
│       ├── database_diagram.md       # Database diagram
│       └── readme.md                 # Original requirements
│
├── 📁 Scripts
│   └── scripts/
│       ├── quick_start.bat           # Start XAMPP and open CRM
│       ├── fix_internal_error.bat    # Fix server errors
│       ├── fix_login_issue.bat       # Fix login problems
│       └── [other utility scripts]
│
├── 📁 Temporary Files
│   └── temp_files/
│       ├── [debugging scripts]
│       ├── [migration tools]
│       └── [testing utilities]
│
└── 📁 System Folders
    ├── logs/                         # Activity logs
    ├── backups/                      # Database backups
    └── uploads/                      # File uploads
```

## 🎯 Features

### ✅ Core Modules
- **Accounts Management** - Company/client accounts
- **Contacts Management** - Individual contacts
- **Projects Management** - Project tracking
- **Tasks & Reports** - Task management and reporting
- **Price Offers** - Quotation management
- **User Management** - Multi-user system

### ✅ Technical Features
- **Bootstrap DataTables** - Advanced table functionality
- **Export Capabilities** - Copy, Excel, PDF, Print
- **Search & Filtering** - Advanced search options
- **Responsive Design** - Mobile-friendly interface
- **Activity Logging** - Complete audit trail
- **Automated Backups** - Weekly database backups
- **Role-based Permissions** - Granular access control

### ✅ User Roles
- **Admin** - Full system access
- **Manager** - Management-level access
- **Worker** - Standard user access
- **Viewer** - Read-only access

## 🔧 Installation

1. **Install XAMPP** (Apache + MySQL + PHP)
2. **Start XAMPP** services
3. **Import Database**:
   ```sql
   -- Import database/shortcircuit_crm.sql into MySQL
   ```
4. **Configure Database**:
   ```php
   // Edit config/database.php with your MySQL credentials
   ```
5. **Access System**:
   ```
   http://localhost/crm
   ```

## 📊 Database

The system uses MySQL with the following main tables:
- `users` - System users
- `accounts` - Company accounts
- `contacts` - Individual contacts
- `projects` - Project information
- `tasks` - Task management
- `price_offers` - Quotations
- `messages` - Internal messaging
- `activity_logs` - System activity

## 🛠️ Development

### Adding New Features
1. Create PHP files in root directory
2. Add database tables to `database/` folder
3. Update permissions in `includes/Permissions.php`
4. Add navigation links in `includes/header.php`

### Customization
- **Styling**: Edit `assets/css/style.css`
- **JavaScript**: Edit `assets/js/app.js`
- **Database**: Modify files in `database/` folder
- **Configuration**: Update `config/database.php`

## 📝 Logs & Backups

- **Activity Logs**: `logs/` folder (daily rotation)
- **Database Backups**: `backups/` folder (weekly)
- **File Uploads**: `uploads/` folder

## 🔒 Security

- **Authentication**: Session-based login system
- **Authorization**: Role-based access control
- **Input Validation**: SQL injection protection
- **File Security**: Upload restrictions
- **Error Handling**: Secure error messages

## 📞 Support

For issues or questions:
1. Check `docs/` folder for documentation
2. Review `temp_files/` for debugging tools
3. Use `scripts/` for common fixes

---

**Version**: 1.0  
**Last Updated**: September 2025  
**Compatibility**: PHP 7.4+, MySQL 5.7+, Bootstrap 5.3
