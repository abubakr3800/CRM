<?php
/**
 * CRM System Installation Script
 * Run this script once to set up the database and initial configuration
 */

require_once 'config/database.php';

// Check if already installed
if (file_exists('installed.lock')) {
    die('CRM System is already installed. Delete installed.lock file to reinstall.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Read and execute SQL file
        $sql = file_get_contents('database/shortcircuit_crm.sql');
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Create installed lock file
        file_put_contents('installed.lock', date('Y-m-d H:i:s'));
        
        $success = 'CRM System installed successfully! You can now <a href="login.php">login</a> with username: admin, password: password';
        
    } catch (Exception $e) {
        $error = 'Installation failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install CRM System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-bolt"></i> CRM System Installation</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            </div>
                        <?php else: ?>
                            <p>This will install the CRM system database and create the necessary tables.</p>
                            <p><strong>Requirements:</strong></p>
                            <ul>
                                <li>MySQL server running</li>
                                <li>Database user with CREATE privileges</li>
                                <li>PHP with PDO MySQL extension</li>
                            </ul>
                            
                            <form method="POST">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-download"></i> Install CRM System
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
