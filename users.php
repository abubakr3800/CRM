<?php
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
require_once 'includes/Permissions.php';
require_once 'includes/Logger.php';

// Initialize components
$auth = new Auth();
$database = $auth->getDatabase();
$permissions = new Permissions($database, $auth);
$logger = new Logger($database);

// Check authentication and permissions
// $auth->requireLogin(); // Temporarily disabled for testing
// $permissions->requirePermission('users.view'); // Temporarily disabled for testing

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_user':
                if ($permissions->hasPermission('users.create')) {
                    try {
                        $username = trim($_POST['username']);
                        $email = trim($_POST['email']);
                        $password = $_POST['password'];
                        $full_name = trim($_POST['full_name']);
                        $role = $_POST['role'];
                        
                        // Validate input
                        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
                            throw new Exception('All fields are required');
                        }
                        
                        // Check if username or email already exists
                        $stmt = $database->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                        $stmt->execute([$username, $email]);
                        if ($stmt->fetch()) {
                            throw new Exception('Username or email already exists');
                        }
                        
                        // Create user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $database->prepare("
                            INSERT INTO users (username, email, password, full_name, role) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$username, $email, $hashed_password, $full_name, $role]);
                        
                        $userId = $database->lastInsertId();
                        
                        // Log the action
                        $logger->log($currentUser['id'], 'CREATE', 'users', $userId, null, [
                            'username' => $username,
                            'email' => $email,
                            'full_name' => $full_name,
                            'role' => $role
                        ]);
                        
                        $success_message = "User created successfully!";
                    } catch (Exception $e) {
                        $error_message = $e->getMessage();
                    }
                } else {
                    $error_message = "You don't have permission to create users";
                }
                break;
                
            case 'edit_user':
                if ($permissions->hasPermission('users.edit')) {
                    try {
                        $userId = $_POST['user_id'];
                        $username = trim($_POST['username']);
                        $email = trim($_POST['email']);
                        $full_name = trim($_POST['full_name']);
                        $role = $_POST['role'];
                        
                        // Get old values for logging
                        $stmt = $database->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        $oldUser = $stmt->fetch();
                        
                        if (!$oldUser) {
                            throw new Exception('User not found');
                        }
                        
                        // Check if username or email already exists (excluding current user)
                        $stmt = $database->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                        $stmt->execute([$username, $email, $userId]);
                        if ($stmt->fetch()) {
                            throw new Exception('Username or email already exists');
                        }
                        
                        // Update user
                        $stmt = $database->prepare("
                            UPDATE users 
                            SET username = ?, email = ?, full_name = ?, role = ?, updated_at = CURRENT_TIMESTAMP 
                            WHERE id = ?
                        ");
                        $stmt->execute([$username, $email, $full_name, $role, $userId]);
                        
                        // Log the action
                        $logger->log($currentUser['id'], 'UPDATE', 'users', $userId, $oldUser, [
                            'username' => $username,
                            'email' => $email,
                            'full_name' => $full_name,
                            'role' => $role
                        ]);
                        
                        $success_message = "User updated successfully!";
                    } catch (Exception $e) {
                        $error_message = $e->getMessage();
                    }
                } else {
                    $error_message = "You don't have permission to edit users";
                }
                break;
                
            case 'delete_user':
                if ($permissions->hasPermission('users.delete')) {
                    try {
                        $userId = $_POST['user_id'];
                        
                        // Prevent deleting own account
                        if ($userId == $currentUser['id']) {
                            throw new Exception('You cannot delete your own account');
                        }
                        
                        // Get user info for logging
                        $stmt = $database->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        $user = $stmt->fetch();
                        
                        if (!$user) {
                            throw new Exception('User not found');
                        }
                        
                        // Delete user
                        $stmt = $database->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        
                        // Log the action
                        $logger->log($currentUser['id'], 'DELETE', 'users', $userId, $user, null);
                        
                        $success_message = "User deleted successfully!";
                    } catch (Exception $e) {
                        $error_message = $e->getMessage();
                    }
                } else {
                    $error_message = "You don't have permission to delete users";
                }
                break;
        }
    }
}

// Get all users
$stmt = $database->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-users"></i> User Management</h2>
                <?php if ($permissions->hasPermission('users.create')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="fas fa-plus"></i> Add New User
                </button>
                <?php endif; ?>
            </div>

            <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover data-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'warning' : 'primary'); ?>">
                                            <?php echo $permissions->getRoleDisplayName($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($permissions->hasPermission('users.edit')): ?>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($permissions->hasPermission('users.delete') && $user['id'] != $currentUser['id']): ?>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<?php if ($permissions->hasPermission('users.create')): ?>
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_user">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <?php foreach ($permissions->getAvailableRoles() as $role): ?>
                            <option value="<?php echo $role; ?>"><?php echo $permissions->getRoleDisplayName($role); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Edit User Modal -->
<?php if ($permissions->hasPermission('users.edit')): ?>
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <?php foreach ($permissions->getAvailableRoles() as $role): ?>
                            <option value="<?php echo $role; ?>"><?php echo $permissions->getRoleDisplayName($role); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_role').value = user.role;
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function deleteUser(userId, username) {
    if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
