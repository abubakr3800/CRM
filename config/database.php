<?php
// Database configuration
define('DB_HOST', 'localhost:3309');
define('DB_NAME', 'shortcircuit_crm');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'ShortCircuit CRM');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/crm');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);

// File upload settings
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Logging settings
define('LOG_PATH', 'logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Backup settings
define('BACKUP_PATH', 'backups/');
define('BACKUP_RETENTION_DAYS', 30);

// Timezone
date_default_timezone_set('UTC');
