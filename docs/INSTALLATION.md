# CRM System Installation Guide

## Prerequisites

- XAMPP (or similar) with PHP 7.4+ and MySQL
- Web browser
- Basic knowledge of PHP and MySQL

## Installation Steps

### 1. Database Setup

1. Start XAMPP and ensure Apache and MySQL are running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create a new database called `shortcircuit_crm` (optional - the installer will create it)

### 2. File Configuration

1. Extract the CRM files to your XAMPP htdocs folder (e.g., `C:\xampp\htdocs\crm`)
2. Update the database configuration in `config/database.php` if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'shortcircuit_crm');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

### 3. Run Installation

1. Open your browser and navigate to: `http://localhost/crm/install.php`
2. Click "Install CRM System" button
3. Wait for the installation to complete

### 4. Access the System

1. Navigate to: `http://localhost/crm/login.php`
2. Login with default credentials:
   - **Username:** admin
   - **Password:** password

### 5. Initial Setup

1. Change the default admin password
2. Create additional users if needed
3. Configure system settings in the Admin Panel

## Features Included

### ✅ Core Modules
- **Accounts Management** - Company profiles with contact relationships
- **Projects Management** - Project tracking with phases and states
- **Tasks & Reports** - Task assignment and performance reporting
- **Price Offers** - Quotation management with itemized pricing
- **Contacts Management** - Individual contact profiles

### ✅ Technical Features
- **Bootstrap DataTables** - All tables with sorting, filtering, and pagination
- **Native Bootstrap 5** - Modern, responsive UI
- **Font Awesome Icons** - Professional iconography
- **JavaScript API** - No AJAX, pure JavaScript for API calls
- **PHP Backend** - Secure, object-oriented PHP code
- **MySQL Database** - Relational database with proper indexing
- **Activity Logging** - Complete audit trail of all changes
- **Weekly Backup System** - Automated database backups

### ✅ Security Features
- User authentication and authorization
- Role-based access control (Admin/Worker)
- Session management with timeout
- SQL injection prevention
- XSS protection
- Activity logging for audit trails

## File Structure

```
crm/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── Auth.php             # Authentication system
│   ├── Database.php         # Database connection class
│   ├── Logger.php           # Logging system
│   ├── Backup.php           # Backup system
│   ├── header.php           # Common header
│   └── footer.php           # Common footer
├── assets/
│   ├── css/
│   │   └── style.css        # Custom styles
│   └── js/
│       └── app.js           # JavaScript functions
├── api/
│   ├── notifications.php    # Notifications API
│   ├── get_contacts.php     # Contacts API
│   └── get_projects.php     # Projects API
├── database/
│   └── shortcircuit_crm.sql # Database schema
├── logs/                    # Log files directory
├── backups/                 # Backup files directory
├── uploads/                 # File uploads directory
├── index.php               # Dashboard/Homepage
├── login.php               # Login page
├── accounts.php            # Accounts module
├── projects.php            # Projects module
├── tasks.php               # Tasks & Reports module
├── price_offers.php        # Price Offers module
├── contacts.php            # Contacts module
├── admin.php               # Admin panel
├── install.php             # Installation script
└── backup_cron.php         # Backup cron job
```

## Default Login Credentials

- **Username:** admin
- **Password:** password

**⚠️ Important:** Change these credentials immediately after installation!

## Backup System

### Automatic Backups
- Weekly backups are automatically created
- Backups are stored in the `backups/` directory
- Old backups are automatically cleaned up (30-day retention)

### Manual Backups
- Access the Admin Panel to create manual backups
- Restore from existing backups through the Admin Panel

### Cron Job Setup
To enable automatic weekly backups, add this cron job:
```bash
0 2 * * 0 cd /path/to/your/crm && php -f backup_cron.php
```

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check MySQL is running
   - Verify database credentials in `config/database.php`
   - Ensure database exists

2. **Permission Errors**
   - Make sure `logs/`, `backups/`, and `uploads/` directories are writable
   - Set proper file permissions (755 for directories, 644 for files)

3. **Login Issues**
   - Use default credentials: admin/password
   - Check if user exists in database
   - Clear browser cache and cookies

4. **DataTables Not Working**
   - Check internet connection (CDN resources)
   - Verify JavaScript is enabled
   - Check browser console for errors

### Support

For technical support or questions:
1. Check the log files in the `logs/` directory
2. Review the activity logs in the Admin Panel
3. Ensure all requirements are met

## System Requirements

- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **Web Server:** Apache/Nginx
- **Browser:** Modern browser with JavaScript enabled
- **Disk Space:** At least 100MB for installation

## Security Recommendations

1. Change default passwords immediately
2. Use HTTPS in production
3. Regular database backups
4. Keep PHP and MySQL updated
5. Monitor activity logs regularly
6. Use strong passwords for all users

---

**Note:** This CRM system is designed for internal use. For production deployment, consider additional security measures and performance optimizations.
