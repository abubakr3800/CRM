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
            // Generate account code
            $lastAccount = $db->fetchOne("SELECT code FROM accounts ORDER BY id DESC LIMIT 1");
            $nextNumber = $lastAccount ? (intval(substr($lastAccount['code'], 1)) + 1) : 1;
            $accountCode = 'A' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            $accountData = [
                'code' => $accountCode,
                'account_name' => $_POST['account_name'],
                'phone' => $_POST['phone'] ?? null,
                'email' => $_POST['email'] ?? null,
                'address' => $_POST['address'] ?? null,
                'region' => $_POST['region'] ?? null,
                'city' => $_POST['city'] ?? null,
                'country' => $_POST['country'] ?? null
            ];
            
            $accountId = $db->insert('accounts', $accountData);
            
            // Create initial project if provided
            if (!empty($_POST['project_name'])) {
                $projectData = [
                    'project_name' => $_POST['project_name'],
                    'account_id' => $accountId,
                    'contact_id' => $_POST['contact_id'] ?? null,
                    'address' => $_POST['project_address'] ?? null,
                    'start_date' => $_POST['start_date'] ?? null,
                    'closing_date' => $_POST['closing_date'] ?? null,
                    'feedback' => $_POST['feedback'] ?? null,
                    'need_visit' => isset($_POST['need_visit']) ? 1 : 0,
                    'visit_date' => $_POST['visit_date'] ?? null,
                    'visit_reason' => $_POST['visit_reason'] ?? null,
                    'project_phase' => $_POST['project_phase'] ?? 'Planning',
                    'project_state' => $_POST['project_state'] ?? 'Pre-started'
                ];
                
                $db->insert('projects', $projectData);
            }
            
            $logger->activity('Account created', 'accounts', $accountId, null, $accountData);
            $success = 'Account created successfully!';
            
        } elseif ($action === 'update') {
            $accountId = $_POST['account_id'];
            
            $oldData = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$accountId]);
            
            $accountData = [
                'account_name' => $_POST['account_name'],
                'phone' => $_POST['phone'] ?? null,
                'email' => $_POST['email'] ?? null,
                'address' => $_POST['address'] ?? null,
                'region' => $_POST['region'] ?? null,
                'city' => $_POST['city'] ?? null,
                'country' => $_POST['country'] ?? null
            ];
            
            $db->update('accounts', $accountData, 'id = ?', [$accountId]);
            
            $logger->activity('Account updated', 'accounts', $accountId, $oldData, $accountData);
            $success = 'Account updated successfully!';
            
        } elseif ($action === 'delete') {
            $accountId = $_POST['account_id'];
            $oldData = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$accountId]);
            
            $db->delete('accounts', 'id = ?', [$accountId]);
            
            $logger->activity('Account deleted', 'accounts', $accountId, $oldData, null);
            $success = 'Account deleted successfully!';
        }
        
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
        $logger->error('Account operation failed', ['error' => $e->getMessage()]);
    }
}

// Get all accounts with related data
$accounts = $db->fetchAll("
    SELECT a.*, 
           COUNT(DISTINCT p.id) as project_count,
           COUNT(DISTINCT ac.contact_id) as contact_count
    FROM accounts a
    LEFT JOIN projects p ON a.id = p.account_id
    LEFT JOIN account_contacts ac ON a.id = ac.account_id
    GROUP BY a.id
    ORDER BY a.created_at DESC
");

// Get all contacts for dropdown
$contacts = $db->fetchAll("SELECT * FROM contacts ORDER BY contact_name");
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-building"></i> Accounts</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                <i class="fas fa-plus"></i> Create Account
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

<!-- Accounts Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-list"></i> All Accounts</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="accountsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Account Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>City</th>
                                <th>Country</th>
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

<!-- Create Account Modal -->
<div class="modal fade" id="createAccountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Create New Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <!-- Top Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="account_code" class="form-label">Code</label>
                            <input type="text" class="form-control" id="account_code" disabled 
                                   placeholder="Auto-generated">
                        </div>
                        <div class="col-md-6">
                            <label for="account_name" class="form-label">Account Name *</label>
                            <input type="text" class="form-control" id="account_name" name="account_name" required>
                        </div>
                    </div>
                    
                    <!-- Details Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="region" class="form-label">Region</label>
                            <input type="text" class="form-control" id="region" name="region">
                        </div>
                        <div class="col-md-4">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city">
                        </div>
                        <div class="col-md-4">
                            <label for="country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="country" name="country">
                        </div>
                    </div>
                    
                    <hr>
                    <h6><i class="fas fa-project-diagram"></i> Initial Project (Optional)</h6>
                    
                    <div class="mb-3">
                        <label for="project_name" class="form-label">Project Name</label>
                        <input type="text" class="form-control" id="project_name" name="project_name">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="contact_id" class="form-label">Related Contact</label>
                            <select class="form-select" id="contact_id" name="contact_id">
                                <option value="">Select Contact</option>
                                <?php foreach ($contacts as $contact): ?>
                                    <option value="<?php echo $contact['id']; ?>">
                                        <?php echo htmlspecialchars($contact['contact_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="project_address" class="form-label">Project Address</label>
                            <input type="text" class="form-control" id="project_address" name="project_address">
                        </div>
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
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Account Modal -->
<div class="modal fade" id="editAccountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="account_id" id="edit_account_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_account_code" class="form-label">Code</label>
                            <input type="text" class="form-control" id="edit_account_code" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_account_name" class="form-label">Account Name *</label>
                            <input type="text" class="form-control" id="edit_account_name" name="account_name" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="edit_region" class="form-label">Region</label>
                            <input type="text" class="form-control" id="edit_region" name="region">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_city" class="form-label">City</label>
                            <input type="text" class="form-control" id="edit_city" name="city">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="edit_country" name="country">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this account? This action cannot be undone and will also delete all related projects and tasks.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="account_id" id="delete_account_id">
                    <button type="submit" class="btn btn-danger">Delete Account</button>
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

function editAccount(account) {
    document.getElementById('edit_account_id').value = account.id;
    document.getElementById('edit_account_code').value = account.code;
    document.getElementById('edit_account_name').value = account.account_name;
    document.getElementById('edit_phone').value = account.phone || '';
    document.getElementById('edit_email').value = account.email || '';
    document.getElementById('edit_address').value = account.address || '';
    document.getElementById('edit_region').value = account.region || '';
    document.getElementById('edit_city').value = account.city || '';
    document.getElementById('edit_country').value = account.country || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editAccountModal'));
    modal.show();
}

function deleteAccount(accountId) {
    document.getElementById('delete_account_id').value = accountId;
    const modal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
    modal.show();
}

// Initialize DataTable with server-side processing
document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery to be available
    function initDataTable() {
        if (typeof $ !== 'undefined') {
            $('#accountsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'api/accounts_data.php',
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
            { data: 1 }, // Code
            { data: 2 }, // Account Name
            { data: 3 }, // Phone
            { data: 4 }, // Email
            { data: 5 }, // City
            { data: 6 }, // Country
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
                className: 'btn btn-sm '
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-sm btn-outline-success'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-sm '
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-sm '
            },
            {
                extend: 'colvis',
                text: '<i class="fas fa-eye"></i> Columns',
                className: 'btn btn-sm '
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
