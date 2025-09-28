<?php
require_once 'includes/header.php';
require_once 'includes/Database.php';
require_once 'includes/Logger.php';

$db = new Database();
$logger = new Logger();

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

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All password fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        $error = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
    } else {
        if ($auth->changePassword($currentUser['id'], $oldPassword, $newPassword)) {
            $success = 'Password changed successfully!';
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-user-edit"></i> Profile
            <small class="text-muted">Manage your account settings</small>
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

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-user"></i> User Information</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Username:</strong></div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Full Name:</strong></div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Role:</strong></div>
                    <div class="col-sm-8">
                        <span class="badge bg-<?php echo $currentUser['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                            <?php echo ucfirst($currentUser['role']); ?>
                        </span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Login Time:</strong></div>
                    <div class="col-sm-8"><?php echo date('M j, Y H:i:s', $_SESSION['login_time'] ?? time()); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-lock"></i> Change Password</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="old_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="old_password" name="old_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                        <small class="text-muted">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-save"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
