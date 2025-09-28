<?php
require_once 'includes/header.php';
require_once 'includes/Database.php';

$db = new Database();
$currentUser = $auth->getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create') {
            $projectData = [
                'project_name' => $_POST['project_name'],
                'account_id' => $_POST['account_id'] ?? null,
                'start_date' => $_POST['start_date'] ?? null,
                'closing_date' => $_POST['closing_date'] ?? null,
                'project_phase' => $_POST['project_phase'] ?? 'Planning',
                'project_state' => $_POST['project_state'] ?? 'Pre-started'
            ];
            
            $projectId = $db->insert('projects', $projectData);
            
            echo json_encode(['success' => true, 'message' => 'Project created successfully']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// Get accounts for dropdown
$accounts = [];
try {
    $accounts = $db->fetchAll("SELECT id, account_name FROM accounts ORDER BY account_name");
} catch (Exception $e) {
    error_log("Error getting accounts: " . $e->getMessage());
}
?>

    <!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-project-diagram"></i> Projects Management</h2>
                <p class="text-muted mb-0">Track and manage your business projects</p>
                </div>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                        <i class="fas fa-plus"></i> Add New Project
                    </button>
                </div>
            </div>
        </div>
    </div>

<!-- Projects Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list"></i> All Projects
                </h5>
            </div>
            <div class="card-body">
            <div class="table-responsive">
                <table id="projectsTable" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Project Name</th>
                            <th>Account</th>
                            <th>Start Date</th>
                            <th>Closing Date</th>
                            <th>Phase</th>
                            <th>State</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
                </div>
            </div>
            </div>
        </div>
    </div>

    <!-- Add Project Modal -->
    <div class="modal fade" id="addProjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i> Add New Project
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addProjectForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Project Name *</label>
                                    <input type="text" class="form-control" name="project_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Account</label>
                                    <select class="form-control" name="account_id">
                                        <option value="">Select Account</option>
                                    <?php foreach ($accounts as $account): ?>
                                        <option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['account_name']); ?></option>
                                    <?php endforeach; ?>
                                    </select>
                            </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Closing Date</label>
                                    <input type="date" class="form-control" name="closing_date">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Project Phase</label>
                                    <select class="form-control" name="project_phase">
                                        <option value="Planning">Planning</option>
                                        <option value="Development">Development</option>
                                        <option value="Testing">Testing</option>
                                        <option value="Deployment">Deployment</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Project State</label>
                                    <select class="form-control" name="project_state">
                                        <option value="Pre-started">Pre-started</option>
                                        <option value="Active">Active</option>
                                        <option value="On Hold">On Hold</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
// Ensure jQuery is loaded before initializing DataTables
if (typeof $ === 'undefined') {
    console.error('jQuery is not loaded. Please check the script loading order.');
} else {
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#projectsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api/projects_data.php',
                type: 'GET',
                error: function(xhr, error, thrown) {
                    console.error('DataTables AJAX Error:', error, thrown);
                    alert('Error loading data. Please refresh the page.');
                }
            },
            columns: [
                { data: 0, title: 'ID', width: '60px' },
                { data: 1, title: 'Project Name' },
                { data: 2, title: 'Account', width: '120px' },
                { data: 3, title: 'Start Date', width: '100px' },
                { data: 4, title: 'Closing Date', width: '100px' },
                { data: 5, title: 'Phase', width: '100px' },
                { data: 6, title: 'State', width: '100px' },
                { data: 7, title: 'Created', width: '100px' },
                { data: 8, title: 'Actions', orderable: false, searchable: false, width: '150px' }
            ],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[0, 'desc']],
            language: {
                processing: "Loading projects...",
                emptyTable: "No projects found",
                zeroRecords: "No matching projects found"
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });

        // Handle add project form
        $('#addProjectForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'create');
            
            $.ajax({
                url: 'projects.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                    $('#addProjectModal').modal('hide');
                    $('#addProjectForm')[0].reset();
                    table.ajax.reload();
                    alert('Project created successfully!');
                    } else {
                        alert('Error: ' + result.message);
                    }
                },
                error: function() {
                    alert('Error creating project. Please try again.');
                }
            });
        });
    });
}

    </script>

<?php require_once 'includes/footer.php'; ?>
