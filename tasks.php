<?php
require_once 'includes/header.php';
require_once 'includes/Database.php';
require_once 'includes/Logger.php';

$db = new Database();
$logger = new Logger();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create') {
            $taskData = [
                'task_name' => $_POST['task_name'],
                'description' => $_POST['description'] ?? null,
                'assigned_to' => $_POST['assigned_to'] ?? null,
                'project_id' => $_POST['project_id'] ?? null,
                'priority' => $_POST['priority'] ?? 'Medium',
                'due_date' => $_POST['due_date'] ?? null,
                'status' => $_POST['status'] ?? 'Pending'
            ];
            
            $taskId = $db->insert('tasks', $taskData);
            
            $logger->activity('Task created', 'tasks', $taskId, null, $taskData);
            $success = 'Task created successfully!';
            
        } elseif ($action === 'update') {
            $taskId = $_POST['task_id'];
            
            $oldData = $db->fetchOne("SELECT * FROM tasks WHERE id = ?", [$taskId]);
            
            $taskData = [
                'task_name' => $_POST['task_name'],
                'description' => $_POST['description'] ?? null,
                'assigned_to' => $_POST['assigned_to'] ?? null,
                'project_id' => $_POST['project_id'] ?? null,
                'priority' => $_POST['priority'] ?? 'Medium',
                'due_date' => $_POST['due_date'] ?? null,
                'status' => $_POST['status'] ?? 'Pending'
            ];
            
            $db->update('tasks', $taskData, 'id = ?', [$taskId]);
            
            $logger->activity('Task updated', 'tasks', $taskId, $oldData, $taskData);
            $success = 'Task updated successfully!';
            
        } elseif ($action === 'delete') {
            $taskId = $_POST['task_id'];
            $oldData = $db->fetchOne("SELECT * FROM tasks WHERE id = ?", [$taskId]);
            
            $db->delete('tasks', 'id = ?', [$taskId]);
            
            $logger->activity('Task deleted', 'tasks', $taskId, $oldData, null);
            $success = 'Task deleted successfully!';
        }
        
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
        $logger->error('Task operation failed', ['error' => $e->getMessage()]);
    }
}

// Get all tasks with related data
$tasks = $db->fetchAll("
    SELECT t.*, u.full_name as assigned_to_name, p.project_name, a.account_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN accounts a ON p.account_id = a.id
    ORDER BY t.created_at DESC
");

// Get all users and projects for dropdowns
$users = $db->fetchAll("SELECT * FROM users ORDER BY full_name");
$projects = $db->fetchAll("SELECT * FROM projects ORDER BY project_name");

// Get statistics for reports
$taskStats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks")['count'],
    'pending' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE status = 'Pending'")['count'],
    'in_progress' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE status = 'In Progress'")['count'],
    'done' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE status = 'Done'")['count'],
    'overdue' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE due_date < CURDATE() AND status != 'Done'")['count']
];

// Get worker performance
$workerPerformance = $db->fetchAll("
    SELECT u.full_name,
           COUNT(t.id) as total_tasks,
           COUNT(CASE WHEN t.status = 'Done' THEN 1 END) as completed_tasks,
           COUNT(CASE WHEN t.status = 'Pending' THEN 1 END) as pending_tasks,
           COUNT(CASE WHEN t.status = 'In Progress' THEN 1 END) as in_progress_tasks
    FROM users u
    LEFT JOIN tasks t ON u.id = t.assigned_to
    GROUP BY u.id, u.full_name
    ORDER BY completed_tasks DESC
");
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-tasks"></i> Tasks & Reports</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i class="fas fa-plus"></i> Create Task
            </button>
        </div>
        
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

<!-- Task Statistics -->
<div class="row mb-4">
    <div class="col-md-2 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h4 class="text-primary"><?php echo $taskStats['total']; ?></h4>
                <small class="text-muted">Total Tasks</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h4 class="text-warning"><?php echo $taskStats['pending']; ?></h4>
                <small class="text-muted">Pending</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h4 class="text-info"><?php echo $taskStats['in_progress']; ?></h4>
                <small class="text-muted">In Progress</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h4 class="text-success"><?php echo $taskStats['done']; ?></h4>
                <small class="text-muted">Completed</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h4 class="text-danger"><?php echo $taskStats['overdue']; ?></h4>
                <small class="text-muted">Overdue</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 mb-3">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <h4 class="text-secondary">
                    <?php echo $taskStats['total'] > 0 ? round(($taskStats['done'] / $taskStats['total']) * 100, 1) : 0; ?>%
                </h4>
                <small class="text-muted">Completion Rate</small>
            </div>
        </div>
    </div>
</div>

<!-- Tasks Table -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-list"></i> All Tasks</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped data-table" id="tasksTable">
                        <thead>
                            <tr>
                                <th>Task Name</th>
                                <th>Assigned To</th>
                                <th>Related Project</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($task['task_name']); ?>
                                        <?php if ($task['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($task['description'], 0, 50)); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['assigned_to_name'] ?? 'Unassigned'); ?></td>
                                    <td>
                                        <?php if ($task['project_name']): ?>
                                            <?php echo htmlspecialchars($task['project_name']); ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($task['account_name']); ?></small>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge priority-<?php echo strtolower($task['priority']); ?>">
                                            <?php echo htmlspecialchars($task['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($task['due_date']): ?>
                                            <?php 
                                            $dueDate = strtotime($task['due_date']);
                                            $isOverdue = $dueDate < time() && $task['status'] !== 'Done';
                                            ?>
                                            <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : ''; ?>">
                                                <?php echo date('M j, Y', $dueDate); ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge status-<?php echo strtolower(str_replace(' ', '-', $task['status'])); ?>">
                                            <?php echo htmlspecialchars($task['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($task['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary" 
                                                    onclick="editTask(<?php echo htmlspecialchars(json_encode($task)); ?>)" 
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="deleteTask(<?php echo $task['id']; ?>)" 
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

<!-- Reports Section -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Worker Performance</h6>
            </div>
            <div class="card-body">
                <?php if (empty($workerPerformance)): ?>
                    <p class="text-muted">No worker performance data available</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Total</th>
                                    <th>Completed</th>
                                    <th>Pending</th>
                                    <th>In Progress</th>
                                    <th>Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($workerPerformance as $worker): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($worker['full_name']); ?></td>
                                        <td><?php echo $worker['total_tasks']; ?></td>
                                        <td><span class="text-success"><?php echo $worker['completed_tasks']; ?></span></td>
                                        <td><span class="text-warning"><?php echo $worker['pending_tasks']; ?></span></td>
                                        <td><span class="text-info"><?php echo $worker['in_progress_tasks']; ?></span></td>
                                        <td>
                                            <?php 
                                            $rate = $worker['total_tasks'] > 0 ? round(($worker['completed_tasks'] / $worker['total_tasks']) * 100, 1) : 0;
                                            echo $rate . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-download"></i> Export Reports</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Export task data in various formats:</p>
                
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-success" onclick="exportTable('tasksTable', 'csv')">
                        <i class="fas fa-file-csv"></i> Export as CSV
                    </button>
                    <button class="btn btn-outline-primary" onclick="exportTable('tasksTable', 'excel')">
                        <i class="fas fa-file-excel"></i> Export as Excel
                    </button>
                    <button class="btn btn-outline-danger" onclick="exportTable('tasksTable', 'pdf')">
                        <i class="fas fa-file-pdf"></i> Export as PDF
                    </button>
                </div>
                
                <hr>
                
                <h6>Quick Reports</h6>
                <div class="d-grid gap-2">
                    <a href="reports.php?type=overdue" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-exclamation-triangle"></i> Overdue Tasks Report
                    </a>
                    <a href="reports.php?type=completion" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-chart-line"></i> Completion Rate Report
                    </a>
                    <a href="reports.php?type=worker" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-users"></i> Worker Performance Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Create New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="task_name" class="form-label">Task Name *</label>
                        <input type="text" class="form-control" id="task_name" name="task_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="assigned_to" class="form-label">Assign To</label>
                            <select class="form-select" id="assigned_to" name="assigned_to">
                                <option value="">Select User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="project_id" class="form-label">Related Project</label>
                            <select class="form-select" id="project_id" name="project_id">
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>">
                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Pending" selected>Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Done">Done</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="task_id" id="edit_task_id">
                    
                    <div class="mb-3">
                        <label for="edit_task_name" class="form-label">Task Name *</label>
                        <input type="text" class="form-control" id="edit_task_name" name="task_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_assigned_to" class="form-label">Assign To</label>
                            <select class="form-select" id="edit_assigned_to" name="assigned_to">
                                <option value="">Select User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_project_id" class="form-label">Related Project</label>
                            <select class="form-select" id="edit_project_id" name="project_id">
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>">
                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_priority" class="form-label">Priority</label>
                            <select class="form-select" id="edit_priority" name="priority">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="edit_due_date" name="due_date">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status">
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Done">Done</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this task? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="task_id" id="delete_task_id">
                    <button type="submit" class="btn btn-danger">Delete Task</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editTask(task) {
    document.getElementById('edit_task_id').value = task.id;
    document.getElementById('edit_task_name').value = task.task_name;
    document.getElementById('edit_description').value = task.description || '';
    document.getElementById('edit_assigned_to').value = task.assigned_to || '';
    document.getElementById('edit_project_id').value = task.project_id || '';
    document.getElementById('edit_priority').value = task.priority;
    document.getElementById('edit_due_date').value = task.due_date || '';
    document.getElementById('edit_status').value = task.status;
    
    const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
    modal.show();
}

function deleteTask(taskId) {
    document.getElementById('delete_task_id').value = taskId;
    const modal = new bootstrap.Modal(document.getElementById('deleteTaskModal'));
    modal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
