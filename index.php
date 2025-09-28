<?php
require_once 'includes/header.php';
require_once 'includes/Database.php';

$db = new Database();
$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    // Provide default user when authentication is disabled
    $currentUser = [
        'id' => 1,
        'username' => 'admin',
        'full_name' => 'Administrator',
        'email' => 'admin@example.com',
        'role' => 'admin'
    ];
}

// Get dashboard statistics with error handling
$stats = [
    'total_accounts' => 0,
    'total_projects' => 0,
    'total_tasks' => 0,
    'pending_tasks' => 0,
    'in_progress_tasks' => 0,
    'total_contacts' => 0,
    'total_offers' => 0,
    'pending_offers' => 0
];

try {
    $stats['total_accounts'] = $db->fetchOne("SELECT COUNT(*) as count FROM accounts")['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error getting total accounts: " . $e->getMessage());
}

try {
    $stats['total_projects'] = $db->fetchOne("SELECT COUNT(*) as count FROM projects")['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error getting total projects: " . $e->getMessage());
}

try {
    $stats['total_tasks'] = $db->fetchOne("SELECT COUNT(*) as count FROM tasks")['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error getting total tasks: " . $e->getMessage());
}

try {
    $stats['pending_tasks'] = $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE status = 'Pending'")['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error getting pending tasks: " . $e->getMessage());
}

try {
    $stats['in_progress_tasks'] = $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE status = 'In Progress'")['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error getting in progress tasks: " . $e->getMessage());
}

try {
    $stats['total_contacts'] = $db->fetchOne("SELECT COUNT(*) as count FROM contacts")['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error getting total contacts: " . $e->getMessage());
}

try {
    $stats['total_offers'] = $db->fetchOne("SELECT COUNT(*) as count FROM price_offers")['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error getting total offers: " . $e->getMessage());
}

try {
    $stats['pending_offers'] = $db->fetchOne("SELECT COUNT(*) as count FROM price_offers WHERE status = 'Draft'")['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error getting pending offers: " . $e->getMessage());
}

// Get recent activities with error handling
$recent_activities = [];
try {
    $recent_activities = $db->fetchAll("
        SELECT al.*, u.full_name as user_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
} catch (Exception $e) {
    error_log("Error getting recent activities: " . $e->getMessage());
}

// Get upcoming visits with error handling
$upcoming_visits = [];
try {
    $upcoming_visits = $db->fetchAll("
        SELECT p.*, a.account_name, c.contact_name 
        FROM projects p 
        LEFT JOIN accounts a ON p.account_id = a.id 
        LEFT JOIN contacts c ON p.contact_id = c.id 
        WHERE p.need_visit = 1 AND p.visit_date >= CURDATE() 
        ORDER BY p.visit_date ASC 
        LIMIT 5
    ");
} catch (Exception $e) {
    error_log("Error getting upcoming visits: " . $e->getMessage());
}

// Get overdue visits with error handling
$overdue_visits = [];
try {
    $overdue_visits = $db->fetchAll("
        SELECT p.*, a.account_name, c.contact_name 
        FROM projects p 
        LEFT JOIN accounts a ON p.account_id = a.id 
        LEFT JOIN contacts c ON p.contact_id = c.id 
        WHERE p.need_visit = 1 AND p.visit_date < CURDATE() 
        ORDER BY p.visit_date ASC 
        LIMIT 5
    ");
} catch (Exception $e) {
    error_log("Error getting overdue visits: " . $e->getMessage());
}

// Get recent projects with error handling
$recent_projects = [];
try {
    $recent_projects = $db->fetchAll("
        SELECT p.*, a.account_name, c.contact_name 
        FROM projects p 
        LEFT JOIN accounts a ON p.account_id = a.id 
        LEFT JOIN contacts c ON p.contact_id = c.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
} catch (Exception $e) {
    error_log("Error getting recent projects: " . $e->getMessage());
}

// Get global messages with error handling
$global_messages = [];
try {
    $global_messages = $db->fetchAll("
        SELECT m.*, u.full_name as sender_name 
        FROM messages m 
        LEFT JOIN users u ON m.sender_id = u.id 
        WHERE m.is_global = 1 
        ORDER BY m.created_at DESC 
        LIMIT 3
    ");
} catch (Exception $e) {
    error_log("Error getting global messages: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-tachometer-alt"></i> Dashboard
            <small class="text-muted">Welcome back, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</small>
        </h1>
    </div>
</div>

<!-- Quick Analysis Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-building fa-2x text-primary mb-3"></i>
                <h3 class="stats-number"><?php echo $stats['total_accounts']; ?></h3>
                <p class="card-text">Total Accounts</p>
                <a href="accounts.php" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-project-diagram fa-2x text-success mb-3"></i>
                <h3 class="stats-number"><?php echo $stats['total_projects']; ?></h3>
                <p class="card-text">Total Projects</p>
                <a href="projects.php" class="btn btn-outline-success btn-sm">View All</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-tasks fa-2x text-warning mb-3"></i>
                <h3 class="stats-number"><?php echo $stats['pending_tasks']; ?></h3>
                <p class="card-text">Pending Tasks</p>
                <a href="tasks.php" class="btn btn-outline-warning btn-sm">View All</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-dollar-sign fa-2x text-info mb-3"></i>
                <h3 class="stats-number"><?php echo $stats['pending_offers']; ?></h3>
                <p class="card-text">Draft Offers</p>
                <a href="price_offers.php" class="btn btn-outline-info btn-sm">View All</a>
            </div>
        </div>
    </div>
</div>

<!-- Task Status Overview -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-clock"></i> Pending Tasks</h6>
            </div>
            <div class="card-body">
                <h4 class="text-warning"><?php echo $stats['pending_tasks']; ?></h4>
                <p class="text-muted">Tasks waiting to be started</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-play"></i> In Progress</h6>
            </div>
            <div class="card-body">
                <h4 class="text-info"><?php echo $stats['in_progress_tasks']; ?></h4>
                <p class="text-muted">Tasks currently being worked on</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-check"></i> Completed</h6>
            </div>
            <div class="card-body">
                <h4 class="text-success"><?php echo $stats['total_tasks'] - $stats['pending_tasks'] - $stats['in_progress_tasks']; ?></h4>
                <p class="text-muted">Tasks completed</p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row">
    <!-- Upcoming Visits -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-calendar-check"></i> Upcoming Visits</h6>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_visits)): ?>
                    <p class="text-muted">No upcoming visits scheduled</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcoming_visits as $visit): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($visit['project_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($visit['account_name']); ?> - 
                                        <?php echo htmlspecialchars($visit['contact_name']); ?>
                                    </small>
                                </div>
                                <span class="badge bg-primary">
                                    <?php echo date('M j', strtotime($visit['visit_date'])); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Overdue Visits -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-exclamation-triangle text-danger"></i> Overdue Visits</h6>
            </div>
            <div class="card-body">
                <?php if (empty($overdue_visits)): ?>
                    <p class="text-muted">No overdue visits</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($overdue_visits as $visit): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($visit['project_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($visit['account_name']); ?> - 
                                        <?php echo htmlspecialchars($visit['contact_name']); ?>
                                    </small>
                                </div>
                                <span class="badge bg-danger">
                                    <?php echo date('M j', strtotime($visit['visit_date'])); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Projects and Messages -->
<div class="row">
    <!-- Recent Projects -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-project-diagram"></i> Recent Projects</h6>
            </div>
            <div class="card-body">
                <?php if (empty($recent_projects)): ?>
                    <p class="text-muted">No recent projects</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_projects as $project): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($project['project_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($project['account_name']); ?> - 
                                        <?php echo htmlspecialchars($project['contact_name']); ?>
                                    </small>
                                </div>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($project['project_state']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Messages from Admin -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-envelope"></i> Messages from Admin</h6>
            </div>
            <div class="card-body">
                <?php if (empty($global_messages)): ?>
                    <p class="text-muted">No messages from admin</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($global_messages as $message): ?>
                            <div class="list-group-item">
                                <h6 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                <p class="mb-1"><?php echo htmlspecialchars(substr($message['message'], 0, 100)); ?>...</p>
                                <small class="text-muted">
                                    From: <?php echo htmlspecialchars($message['sender_name']); ?> - 
                                    <?php echo date('M j, Y', strtotime($message['created_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h6>
            </div>
            <div class="card-body">
                <?php if (empty($recent_activities)): ?>
                    <p class="text-muted">No recent activity</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></td>
                                        <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['table_name'] ?? '-'); ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></td>
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

<?php require_once 'includes/footer.php'; ?>
