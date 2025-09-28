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
            $projectData = [
                'project_name' => $_POST['project_name'],
                'account_id' => $_POST['account_id'],
                'contact_id' => $_POST['contact_id'] ?? null,
                'address' => $_POST['address'] ?? null,
                'start_date' => $_POST['start_date'] ?? null,
                'closing_date' => $_POST['closing_date'] ?? null,
                'feedback' => $_POST['feedback'] ?? null,
                'need_visit' => isset($_POST['need_visit']) ? 1 : 0,
                'visit_date' => $_POST['visit_date'] ?? null,
                'visit_reason' => $_POST['visit_reason'] ?? null,
                'project_phase' => $_POST['project_phase'] ?? 'Planning',
                'project_state' => $_POST['project_state'] ?? 'Pre-started'
            ];
            
            $projectId = $db->insert('projects', $projectData);
            
            $logger->activity('Project created', 'projects', $projectId, null, $projectData);
            $success = 'Project created successfully!';
            
        } elseif ($action === 'update') {
            $projectId = $_POST['project_id'];
            
            $oldData = $db->fetchOne("SELECT * FROM projects WHERE id = ?", [$projectId]);
            
            $projectData = [
                'project_name' => $_POST['project_name'],
                'account_id' => $_POST['account_id'],
                'contact_id' => $_POST['contact_id'] ?? null,
                'address' => $_POST['address'] ?? null,
                'start_date' => $_POST['start_date'] ?? null,
                'closing_date' => $_POST['closing_date'] ?? null,
                'feedback' => $_POST['feedback'] ?? null,
                'need_visit' => isset($_POST['need_visit']) ? 1 : 0,
                'visit_date' => $_POST['visit_date'] ?? null,
                'visit_reason' => $_POST['visit_reason'] ?? null,
                'project_phase' => $_POST['project_phase'] ?? 'Planning',
                'project_state' => $_POST['project_state'] ?? 'Pre-started'
            ];
            
            $db->update('projects', $projectData, 'id = ?', [$projectId]);
            
            $logger->activity('Project updated', 'projects', $projectId, $oldData, $projectData);
            $success = 'Project updated successfully!';
            
        } elseif ($action === 'delete') {
            $projectId = $_POST['project_id'];
            $oldData = $db->fetchOne("SELECT * FROM projects WHERE id = ?", [$projectId]);
            
            $db->delete('projects', 'id = ?', [$projectId]);
            
            $logger->activity('Project deleted', 'projects', $projectId, $oldData, null);
            $success = 'Project deleted successfully!';
        }
        
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
        $logger->error('Project operation failed', ['error' => $e->getMessage()]);
    }
}

// Get all projects with related data
$projects = $db->fetchAll("
    SELECT p.*, a.account_name, c.contact_name,
           COUNT(DISTINCT t.id) as task_count
    FROM projects p
    LEFT JOIN accounts a ON p.account_id = a.id
    LEFT JOIN contacts c ON p.contact_id = c.id
    LEFT JOIN tasks t ON p.id = t.project_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");

// Get all accounts and contacts for dropdowns
$accounts = $db->fetchAll("SELECT * FROM accounts ORDER BY account_name");
$contacts = $db->fetchAll("SELECT * FROM contacts ORDER BY contact_name");
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-project-diagram"></i> Projects</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                <i class="fas fa-plus"></i> Create Project
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

<!-- Projects Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-list"></i> All Projects</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="projectsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Project Name</th>
                                <th>Related Account</th>
                                <th>Start Date</th>
                                <th>Closing Date</th>
                                <th>Project Phase</th>
                                <th>Project State</th>
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

<!-- Create Project Modal -->
<div class="modal fade" id="createProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Create New Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="project_name" class="form-label">Project Name *</label>
                        <input type="text" class="form-control" id="project_name" name="project_name" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="account_id" class="form-label">Related Account *</label>
                            <select class="form-select" id="account_id" name="account_id" required onchange="loadContacts()">
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo htmlspecialchars($account['account_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="contact_id" class="form-label">Contact Person</label>
                            <select class="form-select" id="contact_id" name="contact_id">
                                <option value="">Select Contact</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Project Address</label>
                        <input type="text" class="form-control" id="address" name="address">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date">
                        </div>
                        <div class="col-md-6">
                            <label for="closing_date" class="form-label">Closing Date</label>
                            <input type="date" class="form-control" id="closing_date" name="closing_date">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="feedback" class="form-label">Feedback/Notes</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="2"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="need_visit" name="need_visit" 
                                       onchange="toggleVisitFields()">
                                <label class="form-check-label" for="need_visit">
                                    Need a Visit
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="project_phase" class="form-label">Project Phase</label>
                            <select class="form-select" id="project_phase" name="project_phase">
                                <option value="Planning">Planning</option>
                                <option value="Execution">Execution</option>
                                <option value="Monitoring">Monitoring</option>
                                <option value="Closure">Closure</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="visit_fields" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="visit_date" class="form-label">Visit Date</label>
                                <input type="date" class="form-control" id="visit_date" name="visit_date">
                            </div>
                            <div class="col-md-6">
                                <label for="visit_reason" class="form-label">Visit Reason</label>
                                <input type="text" class="form-control" id="visit_reason" name="visit_reason">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="project_state" class="form-label">Project State</label>
                        <select class="form-select" id="project_state" name="project_state">
                            <option value="Pre-started">Pre-started</option>
                            <option value="Started">Started</option>
                            <option value="Finished">Finished</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="project_id" id="edit_project_id">
                    
                    <div class="mb-3">
                        <label for="edit_project_name" class="form-label">Project Name *</label>
                        <input type="text" class="form-control" id="edit_project_name" name="project_name" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_account_id" class="form-label">Related Account *</label>
                            <select class="form-select" id="edit_account_id" name="account_id" required onchange="loadEditContacts()">
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo htmlspecialchars($account['account_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_contact_id" class="form-label">Contact Person</label>
                            <select class="form-select" id="edit_contact_id" name="contact_id">
                                <option value="">Select Contact</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Project Address</label>
                        <input type="text" class="form-control" id="edit_address" name="address">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_closing_date" class="form-label">Closing Date</label>
                            <input type="date" class="form-control" id="edit_closing_date" name="closing_date">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_feedback" class="form-label">Feedback/Notes</label>
                        <textarea class="form-control" id="edit_feedback" name="feedback" rows="2"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_need_visit" name="need_visit" 
                                       onchange="toggleEditVisitFields()">
                                <label class="form-check-label" for="edit_need_visit">
                                    Need a Visit
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_project_phase" class="form-label">Project Phase</label>
                            <select class="form-select" id="edit_project_phase" name="project_phase">
                                <option value="Planning">Planning</option>
                                <option value="Execution">Execution</option>
                                <option value="Monitoring">Monitoring</option>
                                <option value="Closure">Closure</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="edit_visit_fields" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_visit_date" class="form-label">Visit Date</label>
                                <input type="date" class="form-control" id="edit_visit_date" name="visit_date">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_visit_reason" class="form-label">Visit Reason</label>
                                <input type="text" class="form-control" id="edit_visit_reason" name="visit_reason">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_project_state" class="form-label">Project State</label>
                        <select class="form-select" id="edit_project_state" name="project_state">
                            <option value="Pre-started">Pre-started</option>
                            <option value="Started">Started</option>
                            <option value="Finished">Finished</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteProjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this project? This action cannot be undone and will also delete all related tasks.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="project_id" id="delete_project_id">
                    <button type="submit" class="btn btn-danger">Delete Project</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleVisitFields() {
    const needVisit = document.getElementById('need_visit');
    const visitFields = document.getElementById('visit_fields');
    
    if (needVisit.checked) {
        visitFields.style.display = 'block';
    } else {
        visitFields.style.display = 'none';
    }
}

function toggleEditVisitFields() {
    const needVisit = document.getElementById('edit_need_visit');
    const visitFields = document.getElementById('edit_visit_fields');
    
    if (needVisit.checked) {
        visitFields.style.display = 'block';
    } else {
        visitFields.style.display = 'none';
    }
}

function loadContacts() {
    const accountId = document.getElementById('account_id').value;
    const contactSelect = document.getElementById('contact_id');
    
    contactSelect.innerHTML = '<option value="">Select Contact</option>';
    
    if (accountId) {
        fetch(`api/get_contacts.php?account_id=${accountId}`)
            .then(response => response.json())
            .then(contacts => {
                contacts.forEach(contact => {
                    const option = document.createElement('option');
                    option.value = contact.id;
                    option.textContent = contact.contact_name;
                    contactSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading contacts:', error);
            });
    }
}

function loadEditContacts() {
    const accountId = document.getElementById('edit_account_id').value;
    const contactSelect = document.getElementById('edit_contact_id');
    
    contactSelect.innerHTML = '<option value="">Select Contact</option>';
    
    if (accountId) {
        fetch(`api/get_contacts.php?account_id=${accountId}`)
            .then(response => response.json())
            .then(contacts => {
                contacts.forEach(contact => {
                    const option = document.createElement('option');
                    option.value = contact.id;
                    option.textContent = contact.contact_name;
                    contactSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading contacts:', error);
            });
    }
}

function editProject(project) {
    document.getElementById('edit_project_id').value = project.id;
    document.getElementById('edit_project_name').value = project.project_name;
    document.getElementById('edit_account_id').value = project.account_id;
    document.getElementById('edit_contact_id').value = project.contact_id || '';
    document.getElementById('edit_address').value = project.address || '';
    document.getElementById('edit_start_date').value = project.start_date || '';
    document.getElementById('edit_closing_date').value = project.closing_date || '';
    document.getElementById('edit_feedback').value = project.feedback || '';
    document.getElementById('edit_need_visit').checked = project.need_visit == 1;
    document.getElementById('edit_visit_date').value = project.visit_date || '';
    document.getElementById('edit_visit_reason').value = project.visit_reason || '';
    document.getElementById('edit_project_phase').value = project.project_phase;
    document.getElementById('edit_project_state').value = project.project_state;
    
    toggleEditVisitFields();
    loadEditContacts();
    
    const modal = new bootstrap.Modal(document.getElementById('editProjectModal'));
    modal.show();
}

function deleteProject(projectId) {
    document.getElementById('delete_project_id').value = projectId;
    const modal = new bootstrap.Modal(document.getElementById('deleteProjectModal'));
    modal.show();
}

// Initialize DataTable with server-side processing
document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery to be available
    function initDataTable() {
        if (typeof $ !== 'undefined') {
            $('#projectsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'api/projects_data.php',
            type: 'GET',
            xhrFields: {
                withCredentials: true
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables AJAX Error:', error, thrown);
                if (xhr.status === 401) {
                    alert('Session expired. Please login again.');
                    window.location.href = 'login.php';
                } else {
                    alert('Error loading data. Please refresh the page.');
                }
            }
        },
        columns: [
            { data: 0, visible: false }, // ID (hidden)
            { data: 1 }, // Project Name
            { data: 2 }, // Related Account
            { data: 3 }, // Start Date
            { data: 4 }, // Closing Date
            { data: 5 }, // Project Phase
            { data: 6 }, // Project State
            { data: 7 }, // Created
            { data: 8, orderable: false } // Actions
        ],
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                text: '<i class="fas fa-copy"></i> Copy',
                className: 'btn btn-sm btn-outline-secondary'
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-sm btn-outline-success'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-sm btn-outline-danger'
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-sm btn-outline-info'
            },
            {
                extend: 'colvis',
                text: '<i class="fas fa-eye"></i> Columns',
                className: 'btn btn-sm btn-outline-warning'
            }
        ],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            },
            buttons: {
                copy: 'Copy to clipboard',
                excel: 'Export to Excel',
                pdf: 'Export to PDF',
                print: 'Print table',
                colvis: 'Show/hide columns'
            },
            processing: "Loading data..."
        },
        exportOptions: {
            columns: ':visible'
        }
    });
        } else {
            // Retry after a short delay
            setTimeout(initDataTable, 100);
        }
    }
    initDataTable();
});
</script>

<?php require_once 'includes/footer.php'; ?>
