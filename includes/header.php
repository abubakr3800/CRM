<?php
// Get the directory of the CRM root
$crmRoot = dirname(__DIR__);

require_once $crmRoot . '/config/database.php';
require_once $crmRoot . '/includes/Auth.php';
require_once $crmRoot . '/includes/Permissions.php';

$auth = new Auth();
// $auth->requireLogin(); // Temporarily disabled for testing

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
$permissions = new Permissions($auth->getDatabase(), $auth);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'view') ? '../' : ''; ?>assets/css/style.css" rel="stylesheet">
    
    <!-- jQuery (with integrity and crossorigin for security) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-bolt"></i> <?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="accounts.php">
                            <i class="fas fa-building"></i> Accounts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">
                            <i class="fas fa-project-diagram"></i> Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tasks.php">
                            <i class="fas fa-tasks"></i> Tasks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="price_offers.php">
                            <i class="fas fa-dollar-sign"></i> Price Offers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contacts.php">
                            <i class="fas fa-users"></i> Contacts
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($currentUser['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                            <?php if ($permissions->hasPermission('users.view')): ?>
                            <li><a class="dropdown-item" href="users.php"><i class="fas fa-users"></i> User Management</a></li>
                            <?php endif; ?>
                            <?php if ($permissions->hasPermission('admin.settings')): ?>
                            <li><a class="dropdown-item" href="admin.php"><i class="fas fa-cog"></i> Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Notifications Section - Only show on dashboard -->
    <?php if (basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <h6><i class="fas fa-bell"></i> Notifications</h6>
                    <div id="notifications-content">
                        <div class="text-muted">Loading notifications...</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container-fluid mt-3">
