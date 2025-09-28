# ShortCircuit CRM System - Complete Documentation

## 📋 Table of Contents
1. [System Overview](#system-overview)
2. [Architecture & Technology Stack](#architecture--technology-stack)
3. [Database Design](#database-design)
4. [Module Documentation](#module-documentation)
5. [API Documentation](#api-documentation)
6. [Security Features](#security-features)
7. [Logging & Audit System](#logging--audit-system)
8. [Backup System](#backup-system)
9. [User Interface](#user-interface)
10. [File Structure](#file-structure)
11. [Configuration](#configuration)
12. [Deployment Guide](#deployment-guide)

---

## 🎯 System Overview

The ShortCircuit CRM System is a comprehensive Customer Relationship Management solution designed for managing business relationships, projects, tasks, and sales processes. The system provides a complete workflow from initial contact to project completion and sales closure.

### Key Features
- **Multi-tenant Account Management** - Manage multiple company accounts with detailed profiles
- **Project Lifecycle Tracking** - Complete project management from planning to closure
- **Task Management & Reporting** - Assign, track, and report on tasks with performance metrics
- **Price Offer System** - Create and manage detailed quotations with itemized pricing
- **Contact Management** - Comprehensive contact database with relationship mapping
- **Real-time Notifications** - Automated alerts for visits, deadlines, and important events
- **Activity Logging** - Complete audit trail of all system changes
- **Automated Backups** - Weekly database backups with retention management

---

## 🏗️ Architecture & Technology Stack

### Frontend Technologies
- **Bootstrap 5.3.0** - Modern, responsive UI framework
- **Font Awesome 6.4.0** - Professional iconography
- **DataTables 1.13.6** - Advanced table functionality with sorting, filtering, pagination
- **Vanilla JavaScript** - No external frameworks, pure JavaScript for API calls
- **CSS3** - Custom styling with CSS variables and modern features

### Backend Technologies
- **PHP 7.4+** - Server-side scripting with object-oriented design
- **MySQL 5.7+** - Relational database with proper indexing
- **PDO** - Database abstraction layer for security
- **Session Management** - Secure user session handling

### System Requirements
- **Web Server**: Apache/Nginx
- **PHP**: 7.4 or higher with PDO MySQL extension
- **MySQL**: 5.7 or higher
- **Browser**: Modern browser with JavaScript enabled
- **Disk Space**: Minimum 100MB

---

## 🗄️ Database Design

### Core Tables

#### Users Table
```sql
users (
    id, username, email, password, full_name, role, created_at, updated_at
)
```
- **Purpose**: User authentication and authorization
- **Roles**: admin, worker
- **Security**: Password hashing with PHP password_hash()

#### Accounts Table
```sql
accounts (
    id, code, account_name, phone, email, address, region, city, country, created_at, updated_at
)
```
- **Purpose**: Company/client account management
- **Features**: Auto-generated account codes, geographic information

#### Contacts Table
```sql
contacts (
    id, contact_name, phone_number, email, department, job_title, address, created_at, updated_at
)
```
- **Purpose**: Individual contact management
- **Integration**: Linked to accounts via account_contacts table

#### Projects Table
```sql
projects (
    id, project_name, account_id, contact_id, address, start_date, closing_date, 
    feedback, need_visit, visit_date, visit_reason, project_phase, project_state, 
    created_at, updated_at
)
```
- **Purpose**: Project lifecycle management
- **Phases**: Planning, Execution, Monitoring, Closure
- **States**: Pre-started, Started, Finished

#### Tasks Table
```sql
tasks (
    id, task_name, description, assigned_to, project_id, priority, due_date, 
    status, created_at, updated_at
)
```
- **Purpose**: Task assignment and tracking
- **Priorities**: High, Medium, Low
- **Status**: Pending, In Progress, Done

#### Price Offers Table
```sql
price_offers (
    id, offer_code, account_id, project_id, offer_date, total_amount, 
    status, notes, created_at, updated_at
)
```
- **Purpose**: Quotation management
- **Status**: Draft, Sent, Accepted, Rejected

#### Activity Logs Table
```sql
activity_logs (
    id, user_id, action, table_name, record_id, old_values, new_values, 
    ip_address, user_agent, created_at
)
```
- **Purpose**: Complete audit trail
- **Features**: JSON storage for old/new values, IP tracking

### Relationship Design
- **One-to-Many**: Accounts → Projects, Projects → Tasks
- **Many-to-Many**: Accounts ↔ Contacts (via account_contacts)
- **One-to-Many**: Price Offers → Price Offer Items

---

## 📱 Module Documentation

### 1. Dashboard/Homepage (`index.php`)

**Purpose**: Central hub providing system overview and quick access to key information.

**Features**:
- **Quick Analysis Cards**: Total accounts, projects, tasks, offers
- **Task Status Overview**: Pending, in-progress, completed statistics
- **Upcoming Visits**: Next 7 days of scheduled visits
- **Overdue Visits**: Past due visits requiring attention
- **Recent Projects**: Latest project updates
- **Messages from Admin**: Global announcements
- **Recent Activity**: Latest system changes

**Real-time Updates**: Notifications refresh every 30 seconds

### 2. Accounts Module (`accounts.php`, `account_details.php`)

**Purpose**: Manage company accounts and their relationships.

**Features**:
- **Account Creation**: Auto-generated codes, comprehensive forms
- **Account Details**: Full profile management with statistics
- **Related Contacts**: Manage contact relationships
- **Related Projects**: View and create projects for accounts
- **Geographic Integration**: Google Maps links for addresses

**Workflow**:
1. Create account with basic information
2. Add related contacts (Primary/Secondary relationships)
3. Create initial project (optional)
4. Track project progress and visits

### 3. Projects Module (`projects.php`)

**Purpose**: Track project lifecycle from initiation to completion.

**Features**:
- **Project Creation**: Link to accounts and contacts
- **Visit Management**: Schedule and track site visits
- **Phase Tracking**: Planning → Execution → Monitoring → Closure
- **State Management**: Pre-started → Started → Finished
- **Feedback System**: Notes and project updates

**Visit System**:
- **Need Visit Toggle**: Enable/disable visit requirements
- **Visit Scheduling**: Date and reason tracking
- **Overdue Alerts**: Automatic notifications for missed visits

### 4. Tasks & Reports Module (`tasks.php`)

**Purpose**: Task management with comprehensive reporting.

**Features**:
- **Task Assignment**: Assign to users with priority levels
- **Due Date Tracking**: Automatic overdue detection
- **Status Management**: Pending → In Progress → Done
- **Performance Reporting**: Worker productivity metrics
- **Export Functionality**: CSV, Excel, PDF exports

**Reporting Features**:
- **Task Completion Rates**: Overall and per-worker statistics
- **Overdue Task Reports**: Detailed overdue analysis
- **Worker Performance**: Tasks completed vs. pending ratios
- **Project Progress**: Task completion by project

### 5. Price Offers Module (`price_offers.php`)

**Purpose**: Create and manage detailed price quotations.

**Features**:
- **Offer Creation**: Auto-generated offer codes
- **Itemized Pricing**: Add multiple items with quantities and prices
- **Automatic Calculations**: Real-time subtotal and total calculations
- **Status Tracking**: Draft → Sent → Accepted/Rejected
- **Account Integration**: Link to accounts and projects

**Pricing System**:
- **Dynamic Items**: Add/remove items with real-time calculations
- **Quantity Management**: Support for different quantities
- **Unit Pricing**: Flexible pricing per unit
- **Total Calculation**: Automatic total amount computation

### 6. Contacts Module (`contacts.php`)

**Purpose**: Manage individual contacts and their relationships.

**Features**:
- **Contact Profiles**: Complete contact information
- **Account Relationships**: Link contacts to multiple accounts
- **Department Tracking**: Organize by departments and job titles
- **Geographic Data**: Address management with map integration

### 7. Admin Panel (`admin.php`)

**Purpose**: System administration and maintenance.

**Features**:
- **System Statistics**: Overview of all system data
- **Backup Management**: Create, restore, and manage backups
- **Log Management**: View and clean system logs
- **Activity Monitoring**: Recent system activity
- **Maintenance Tools**: Database cleanup and optimization

---

## 🔌 API Documentation

### Authentication
All API endpoints require user authentication via session management.

### Endpoints

#### GET `/api/notifications.php`
**Purpose**: Retrieve system notifications and alerts.

**Response**:
```json
[
    {
        "title": "Upcoming Visit",
        "message": "Visit scheduled for Company ABC - Project XYZ on Dec 15, 2023",
        "time": "2023-12-15",
        "type": "info"
    }
]
```

#### GET `/api/get_contacts.php?account_id={id}`
**Purpose**: Get contacts related to a specific account.

**Parameters**:
- `account_id` (required): Account ID

**Response**:
```json
[
    {
        "id": 1,
        "contact_name": "John Doe",
        "phone_number": "+1234567890",
        "email": "john@example.com"
    }
]
```

#### GET `/api/get_projects.php?account_id={id}`
**Purpose**: Get projects related to a specific account.

**Parameters**:
- `account_id` (required): Account ID

**Response**:
```json
[
    {
        "id": 1,
        "project_name": "Website Development",
        "start_date": "2023-12-01",
        "project_phase": "Execution"
    }
]
```

---

## 🔒 Security Features

### Authentication & Authorization
- **Session-based Authentication**: Secure session management
- **Role-based Access Control**: Admin and Worker roles
- **Session Timeout**: Automatic logout after inactivity
- **Password Security**: Bcrypt hashing with salt

### Data Protection
- **SQL Injection Prevention**: PDO prepared statements
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Form token validation
- **File Access Control**: .htaccess restrictions

### System Security
- **Directory Protection**: Sensitive directories blocked
- **File Type Restrictions**: Only allowed file types in uploads
- **Error Handling**: Secure error messages
- **Activity Logging**: Complete audit trail

### Security Headers
```apache
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
```

---

## 📊 Logging & Audit System

### Activity Logging
Every system change is automatically logged with:
- **User Information**: Who made the change
- **Action Details**: What action was performed
- **Table/Record**: Which data was affected
- **Old/New Values**: Complete change history (JSON format)
- **Metadata**: IP address, user agent, timestamp

### Log Types
1. **Activity Logs**: Database changes and user actions
2. **System Logs**: Application errors and warnings
3. **Access Logs**: Login attempts and security events

### Log Management
- **Automatic Cleanup**: Configurable retention periods
- **Log Rotation**: Daily log files
- **Admin Interface**: View and manage logs through admin panel
- **Export Functionality**: Download logs for analysis

### Log File Structure
```
logs/
├── 2023-12-15.log    # Daily activity logs
├── error.log         # System errors
└── access.log        # Access attempts
```

---

## 💾 Backup System

### Automated Backups
- **Weekly Schedule**: Automatic backups every Sunday at 2:00 AM
- **Compression**: Gzip compression to save space
- **Retention Policy**: 30-day retention with automatic cleanup
- **Database Dump**: Complete mysqldump with all data

### Manual Backup Features
- **On-demand Backups**: Create backups through admin panel
- **Backup Restoration**: Restore from any available backup
- **Backup Management**: View, download, and delete backups
- **Backup Verification**: Validate backup integrity

### Backup Process
1. **Database Export**: mysqldump command execution
2. **Compression**: Gzip compression for storage efficiency
3. **Storage**: Save to backups/ directory with timestamp
4. **Cleanup**: Remove old backups based on retention policy
5. **Logging**: Record backup operations in activity logs

### Cron Job Setup
```bash
# Weekly backup (Sundays at 2:00 AM)
0 2 * * 0 cd /path/to/your/crm && php -f backup_cron.php
```

---

## 🎨 User Interface

### Design Principles
- **Responsive Design**: Mobile-first approach with Bootstrap
- **Consistent Styling**: Unified color scheme and typography
- **Accessibility**: ARIA labels and keyboard navigation
- **Performance**: Optimized loading with CDN resources

### Bootstrap DataTables Integration
All tables feature:
- **Sorting**: Click column headers to sort
- **Filtering**: Global search and column-specific filters
- **Pagination**: Configurable page sizes
- **Responsive**: Mobile-friendly table display
- **Export**: CSV, Excel, PDF export options

### Color Scheme
- **Primary**: Bootstrap blue (#0d6efd)
- **Success**: Green for completed items
- **Warning**: Yellow for pending/overdue items
- **Danger**: Red for errors and critical items
- **Info**: Light blue for informational items

### Interactive Elements
- **Modals**: Bootstrap modals for forms and confirmations
- **Tooltips**: Helpful tooltips on buttons and icons
- **Alerts**: Dismissible alerts for notifications
- **Progress Indicators**: Loading states and progress bars

---

## 📁 File Structure

```
crm/
├── config/
│   └── database.php              # Database configuration
├── includes/
│   ├── Auth.php                 # Authentication system
│   ├── Database.php             # Database connection class
│   ├── Logger.php               # Logging system
│   ├── Backup.php               # Backup system
│   ├── header.php               # Common header
│   └── footer.php               # Common footer
├── assets/
│   ├── css/
│   │   └── style.css            # Custom styles
│   └── js/
│       └── app.js               # JavaScript functions
├── api/
│   ├── notifications.php        # Notifications API
│   ├── get_contacts.php         # Contacts API
│   └── get_projects.php         # Projects API
├── database/
│   └── shortcircuit_crm.sql     # Database schema
├── logs/                        # Log files directory
├── backups/                     # Backup files directory
├── uploads/                     # File uploads directory
├── index.php                    # Dashboard/Homepage
├── login.php                    # Login page
├── logout.php                   # Logout handler
├── accounts.php                 # Accounts module
├── account_details.php          # Account details page
├── projects.php                 # Projects module
├── tasks.php                    # Tasks module
├── reports.php                  # Reports module
├── price_offers.php             # Price Offers module
├── contacts.php                 # Contacts module
├── profile.php                  # User profile page
├── admin.php                    # Admin panel
├── install.php                  # Installation script
├── backup_cron.php              # Backup cron job
├── 404.php                      # Error page
├── .htaccess                    # Apache configuration
├── INSTALLATION.md              # Installation guide
└── SYSTEM_DOCUMENTATION.md      # This documentation
```

---

## ⚙️ Configuration

### Database Configuration (`config/database.php`)
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'shortcircuit_crm');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
```

### Application Settings
```php
define('APP_NAME', 'ShortCircuit CRM');
define('APP_VERSION', '1.0.0');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('BACKUP_RETENTION_DAYS', 30);
```

### Security Settings
- **Session Timeout**: 1 hour of inactivity
- **Password Requirements**: Minimum 8 characters
- **File Upload Limits**: 5MB maximum
- **Log Retention**: Configurable per log type

---

## 🚀 Deployment Guide

### Development Environment
1. **XAMPP Setup**: Install XAMPP with PHP 7.4+ and MySQL
2. **File Placement**: Extract to `htdocs/crm/` directory
3. **Database Creation**: Run `install.php` for automatic setup
4. **Configuration**: Update database settings if needed

### Production Environment
1. **Web Server**: Apache/Nginx with PHP 7.4+
2. **Database**: MySQL 5.7+ with proper user permissions
3. **SSL Certificate**: Enable HTTPS for security
4. **File Permissions**: Set appropriate directory permissions
5. **Cron Jobs**: Set up automated backup schedule

### Performance Optimization
- **CDN Usage**: Bootstrap and Font Awesome from CDN
- **Database Indexing**: Optimized indexes on frequently queried columns
- **Caching**: Browser caching for static assets
- **Compression**: Gzip compression for text files

### Security Hardening
- **HTTPS**: Force SSL in production
- **File Permissions**: Restrict access to sensitive files
- **Database Security**: Use dedicated database user with limited privileges
- **Regular Updates**: Keep PHP and MySQL updated

---

## 🔧 Maintenance

### Regular Tasks
1. **Monitor Logs**: Check for errors and security issues
2. **Backup Verification**: Ensure backups are created successfully
3. **Database Optimization**: Regular maintenance and cleanup
4. **Security Updates**: Keep system components updated

### Troubleshooting
- **Database Issues**: Check connection settings and permissions
- **Performance Problems**: Monitor query performance and optimize
- **Login Issues**: Verify user credentials and session settings
- **Backup Failures**: Check disk space and file permissions

### Support Information
- **Log Files**: Check `logs/` directory for error details
- **Activity Logs**: Review user actions in admin panel
- **System Status**: Monitor through admin dashboard
- **Backup Status**: Verify backup creation and retention

---

## 📈 Future Enhancements

### Planned Features
- **Email Integration**: Automated email notifications
- **Document Management**: File upload and management system
- **Advanced Reporting**: Custom report builder
- **API Expansion**: RESTful API for external integrations
- **Mobile App**: Native mobile application
- **Multi-language Support**: Internationalization

### Scalability Considerations
- **Database Optimization**: Query optimization and indexing
- **Caching Layer**: Redis/Memcached integration
- **Load Balancing**: Multiple server deployment
- **Microservices**: Service-oriented architecture

---

## 📞 Support & Documentation

### Documentation Files
- **INSTALLATION.md**: Complete installation guide
- **SYSTEM_DOCUMENTATION.md**: This comprehensive documentation
- **README.md**: Quick start guide

### Getting Help
1. **Check Logs**: Review error logs for issues
2. **Admin Panel**: Use system monitoring tools
3. **Documentation**: Refer to this documentation
4. **Activity Logs**: Review user actions and system changes

---

*This documentation covers the complete ShortCircuit CRM System. For installation instructions, please refer to INSTALLATION.md. For technical support, check the system logs and admin panel.*
