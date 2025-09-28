# CRM Project Organization

This document describes the organized structure of the CRM project after cleanup and reorganization.

## Directory Structure

### Root Directory
The root directory now contains only essential application files:
- `index.php` - Main application entry point
- `login.php` - User authentication
- `logout.php` - User logout
- `admin.php` - Admin panel
- `profile.php` - User profile management
- `404.php` - Error page

### Core Application Modules
- `accounts.php` - Account management
- `contacts.php` - Contact management
- `projects.php` - Project management
- `price_offers.php` - Price offers management
- `tasks.php` - Task management
- `reports.php` - Reports and analytics
- `users.php` - User management

### Configuration & Setup
- `install.php` - Installation script
- `backup_cron.php` - Automated backup script

## Organized Directories

### `/api/` - API Endpoints
Contains all API endpoints for data operations:
- `accounts_data.php` - Account data API
- `contacts_data.php` - Contact data API
- `projects_data.php` - Project data API
- `price_offers_data.php` - Price offers data API
- `notifications.php` - Notifications API
- `reports.php` - Reports API
- Various utility APIs

### `/assets/` - Static Assets
- `/css/` - Stylesheets
- `/js/` - JavaScript files

### `/config/` - Configuration Files
- `database.php` - Database configuration

### `/includes/` - Core Classes and Components
- `Auth.php` - Authentication class
- `Database.php` - Database operations class
- `Backup.php` - Backup functionality
- `Logger.php` - Logging system
- `Permissions.php` - Permission management
- `header.php` - Common header
- `footer.php` - Common footer

### `/database/` - Database Files
- `shortcircuit_crm.sql` - Main database schema
- `shortcircuit_crm_old.sql` - Previous database version
- `database_fixes.sql` - Database fixes
- `safe_database_migration.sql` - Migration scripts
- `update_roles.sql` - Role updates

### `/docs/` - Documentation
- `INSTALLATION.md` - Installation guide
- `STARTUP_GUIDE.md` - Startup instructions
- `SYSTEM_DOCUMENTATION.md` - System documentation
- `PERMISSIONS_GUIDE.md` - Permissions guide
- `database_diagram.md` - Database structure
- `PROJECT_ORGANIZATION.md` - This file

### `/logs/` - Log Files
- Daily log files (e.g., `2025-09-20.log`)

### `/scripts/` - Utility Scripts
#### `/scripts/maintenance/` - Maintenance Scripts
- `fix_*.bat` - Various fix scripts
- `enhance_*.bat` - Enhancement scripts
- `quick_fix.bat` - Quick fixes
- `quick_start.bat` - Quick startup

#### `/scripts/development/` - Development Scripts
- `debug_*.bat` - Debug scripts
- `import_*.bat` - Import scripts
- `migrate_*.bat` - Migration scripts
- `extract_*.bat` - Data extraction scripts
- `run_*.bat` - Execution scripts

### `/view/` - View Templates
- `account_view.php` - Account view template
- `contact_view.php` - Contact view template
- `project_view.php` - Project view template
- `price_offer_view.php` - Price offer view template
- `report_view.php` - Report view template
- `/logs/` - View-specific logs

### `/archive/` - Archived Files
#### `/archive/old_files/` - Old/Backup Files
- `accounts_old.php` - Old accounts module
- `contacts_old.php` - Old contacts module
- `price_offers_old.php` - Old price offers module
- `projects_old.php` - Old projects module

#### `/archive/development/` - Development Files
- `test_auth_web.php` - Authentication testing
- `restore_auth.php` - Auth restoration
- `fix_projects_web.php` - Project fixes
- `setup_project_contacts.php` - Setup scripts
- `setup_reports_system.php` - Reports setup
- All temporary development files from `/temp_files/`

## Benefits of This Organization

1. **Clean Root Directory**: Only essential application files in root
2. **Logical Grouping**: Related files grouped together
3. **Separation of Concerns**: Development files separated from production
4. **Easy Maintenance**: Scripts organized by purpose
5. **Clear Documentation**: Comprehensive documentation structure
6. **Archive System**: Old files preserved but not cluttering main structure

## Maintenance Guidelines

1. **New Files**: Place new files in appropriate directories based on their purpose
2. **Old Files**: Move deprecated files to `/archive/` instead of deleting
3. **Scripts**: Categorize new scripts into maintenance or development folders
4. **Documentation**: Update this file when making structural changes
5. **Cleanup**: Regularly review and clean up development files

## File Naming Conventions

- Use descriptive names for all files
- Use underscores for multi-word filenames
- Prefix development files with `dev_` or `test_`
- Use consistent naming for similar functionality
- Archive old files with `_old` suffix before moving to archive
