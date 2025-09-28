<?php
/**
 * Weekly Backup Cron Job
 * This script should be run by a cron job every week
 * Example cron entry: 0 2 * * 0 cd /path/to/your/crm && php -f backup_cron.php
 */

require_once 'config/database.php';
require_once 'includes/Backup.php';
require_once 'includes/Logger.php';

try {
    $backup = new Backup();
    $logger = new Logger();
    
    // Create backup
    $filename = $backup->createBackup();
    
    // Log success
    $logger->info("Weekly backup completed successfully", ['filename' => $filename]);
    
    // Send notification email (optional)
    // You can implement email notification here if needed
    
    echo "Backup completed successfully: {$filename}\n";
    
} catch (Exception $e) {
    $logger->error("Weekly backup failed", ['error' => $e->getMessage()]);
    echo "Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
