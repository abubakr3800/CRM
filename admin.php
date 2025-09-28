<?php
require_once 'includes/header.php';
require_once 'includes/Database.php';
require_once 'includes/Logger.php';
require_once 'includes/Backup.php';

$auth->requireRole('admin');

$db = new Database();
$logger = new Logger();
$backup = new Backup();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create_backup') {
            $filename = $backup->createBackup();
            $success = "Backup created successfully: {$filename}";
            
        } elseif ($action === 'restore_backup') {
            $filename = $_POST['backup_file'];
            $backup->restoreBackup($filename);
            $success = "Database restored from backup: {$filename}";
            
        } elseif ($action === 'clean_logs') {
            $days = $_POST['log_days'] ?? 30;
            $logFiles = glob(LOG_PATH . '*.log');
            $deletedCount = 0;
            
            foreach ($logFiles as $logFile) {
                if (filemtime($logFile) < (time() - ($days * 24 * 60 * 60))) {
                    unlink($logFile);
                    $deletedCount++;
                }
            }
            
            $success = "Cleaned {$deletedCount} log files older than {$days} days";
            
        } elseif ($action === 'clean_activity_logs') {
            $days = $_POST['activity_days'] ?? 90;
            $db->query("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)", [$days]);
            $success = "Cleaned activity logs older than {$days} days";
        }
        
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
        $logger->error('Admin operation failed', ['error' => $e->getMessage()]);
    }
}

// Get system statistics
$stats = [
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'total_accounts' => $db->fetchOne("SELECT COUNT(*) as count FROM accounts")['count'],
    'total_projects' => $db->fetchOne("SELECT COUNT(*) as count FROM projects")['count'],
    'total_tasks' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks")['count'],
    'total_contacts' => $db->fetchOne("SELECT COUNT(*) as count FROM contacts")['count'],
    'total_offers' => $db->fetchOne("SELECT COUNT(*) as count FROM price_offers")['count'],
    'total_messages' => $db->fetchOne("SELECT COUNT(*) as count FROM messages")['count'],
    'total_activity_logs' => $db->fetchOne("SELECT COUNT(*) as count FROM activity_logs")['count']
];

// Get backup list
$backups = $backup->getBackupList();

// Get last backup info
$lastBackup = $db->fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = 'last_backup'");

// Get recent activity logs
$recentLogs = $db->fetchAll("
    SELECT al.*, u.full_name as user_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 20
");

// Get log file sizes
$logFiles = glob(LOG_PATH . '*.log');
$logSizes = [];
foreach ($logFiles as $logFile) {
    $logSizes[] = [
        'filename' => basename($logFile),
        'size' => filesize($logFile),
        'modified' => date('Y-m-d H:i:s', filemtime($logFile))
    ];
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-cog"></i> Admin Panel
            <small class="text-muted">System Administration</small>
        </h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- System Statistics -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x text-primary mb-3"></i>
                <h3 class="stats-number"><?php echo $stats['total_users']; ?></h3>
                <p class="card-text">Total Users</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-building fa-2x text-success mb-3"></i>
                <h3 class="stats-number"><?php echo $stats['total_accounts']; ?></h3>
                <p class="card-text">Total Accounts</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-project-diagram fa-2x text-info mb-3"></i>
                <h3 class="stats-number"><?php echo $stats['total_projects']; ?></h3>
                <p class="card-text">Total Projects</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-tasks fa-2x text-warning mb-3"></i>
                <h3 class="stats-number"><?php echo $stats['total_tasks']; ?></h3>
                <p class="card-text">Total Tasks</p>
            </div>
        </div>
    </div>
</div>

<!-- Backup Management -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-database"></i> Database Backup</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    <strong>Last Backup:</strong> 
                    <?php echo $lastBackup ? date('M j, Y H:i:s', strtotime($lastBackup['setting_value'])) : 'Never'; ?>
                </p>
                
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="create_backup">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> Create Backup Now
                    </button>
                </form>
                
                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-info-circle"></i> 
                        Automatic weekly backups are scheduled. Manual backups are recommended before major changes.
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-upload"></i> Restore Backup</h6>
            </div>
            <div class="card-body">
                <?php if (empty($backups)): ?>
                    <p class="text-muted">No backup files available</p>
                <?php else: ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to restore this backup? This will overwrite all current data!')">
                        <input type="hidden" name="action" value="restore_backup">
                        <div class="mb-3">
                            <label for="backup_file" class="form-label">Select Backup File</label>
                            <select class="form-select" id="backup_file" name="backup_file" required>
                                <option value="">Choose backup file...</option>
                                <?php foreach ($backups as $backupFile): ?>
                                    <option value="<?php echo htmlspecialchars($backupFile['filename']); ?>">
                                        <?php echo htmlspecialchars($backupFile['filename']); ?> 
                                        (<?php echo date('M j, Y H:i', strtotime($backupFile['created'])); ?>, 
                                        <?php echo number_format($backupFile['size'] / 1024, 1); ?> KB)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-upload"></i> Restore Backup
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Log Management -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-file-alt"></i> Log Files</h6>
            </div>
            <div class="card-body">
                <?php if (empty($logSizes)): ?>
                    <p class="text-muted">No log files found</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Size</th>
                                    <th>Modified</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logSizes as $logFile): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($logFile['filename']); ?></td>
                                        <td><?php echo number_format($logFile['size'] / 1024, 1); ?> KB</td>
                                        <td><?php echo $logFile['modified']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="action" value="clean_logs">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="number" class="form-control" name="log_days" value="30" min="1" max="365">
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-trash"></i> Clean Logs
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Delete log files older than specified days</small>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-history"></i> Activity Logs</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    <strong>Total Activity Logs:</strong> <?php echo number_format($stats['total_activity_logs']); ?>
                </p>
                
                <form method="POST" onsubmit="return confirm('Are you sure you want to clean activity logs?')">
                    <input type="hidden" name="action" value="clean_activity_logs">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="number" class="form-control" name="activity_days" value="90" min="1" max="365">
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash"></i> Clean Activity Logs
                            </button>
                        </div>
                    </div>
                    <small class="text-muted">Delete activity logs older than specified days</small>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-history"></i> Recent Activity Logs</h6>
            </div>
            <div class="card-body">
                <?php if (empty($recentLogs)): ?>
                    <p class="text-muted">No recent activity</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Record ID</th>
                                    <th>IP Address</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLogs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
                                        <td><?php echo $log['record_id'] ?? '-'; ?></td>
                                        <td><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                                        <td><?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Weekly Backup Cron Job Setup Instructions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-clock"></i> Automatic Backup Setup</h6>
            </div>
            <div class="card-body">
                <p>To set up automatic weekly backups, add this cron job to your server:</p>
                <div class="bg-light p-3 rounded">
                    <code>0 2 * * 0 cd /path/to/your/crm && php -f backup_cron.php</code>
                </div>
                <p class="mt-2 text-muted">
                    <small>This will run every Sunday at 2:00 AM. Make sure to update the path to match your CRM installation.</small>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
