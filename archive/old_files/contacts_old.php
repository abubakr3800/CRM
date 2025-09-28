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
            $contactData = [
                'contact_name' => $_POST['contact_name'],
                'phone_number' => $_POST['phone_number'] ?? null,
                'email' => $_POST['email'] ?? null,
                'department' => $_POST['department'] ?? null,
                'job_title' => $_POST['job_title'] ?? null,
                'address' => $_POST['address'] ?? null
            ];
            
            $contactId = $db->insert('contacts', $contactData);
            
            // Handle related accounts
            if (!empty($_POST['related_accounts'])) {
                foreach ($_POST['related_accounts'] as $accountId) {
                    $db->insert('account_contacts', [
                        'account_id' => $accountId,
                        'contact_id' => $contactId,
                        'relationship_type' => 'Primary'
                    ]);
                }
            }
            
            $logger->activity('Contact created', 'contacts', $contactId, null, $contactData);
            $success = 'Contact created successfully!';
            
        } elseif ($action === 'update') {
            $contactId = $_POST['contact_id'];
            
            $oldData = $db->fetchOne("SELECT * FROM contacts WHERE id = ?", [$contactId]);
            
            $contactData = [
                'contact_name' => $_POST['contact_name'],
                'phone_number' => $_POST['phone_number'] ?? null,
                'email' => $_POST['email'] ?? null,
                'department' => $_POST['department'] ?? null,
                'job_title' => $_POST['job_title'] ?? null,
                'address' => $_POST['address'] ?? null
            ];
            
            $db->update('contacts', $contactData, 'id = ?', [$contactId]);
            
            $logger->activity('Contact updated', 'contacts', $contactId, $oldData, $contactData);
            $success = 'Contact updated successfully!';
            
        } elseif ($action === 'delete') {
            $contactId = $_POST['contact_id'];
            $oldData = $db->fetchOne("SELECT * FROM contacts WHERE id = ?", [$contactId]);
            
            $db->delete('contacts', 'id = ?', [$contactId]);
            
            $logger->activity('Contact deleted', 'contacts', $contactId, $oldData, null);
            $success = 'Contact deleted successfully!';
        }
        
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
        $logger->error('Contact operation failed', ['error' => $e->getMessage()]);
    }
}

// Get all contacts with related data
$contacts = $db->fetchAll("
    SELECT c.*, 
           COUNT(DISTINCT ac.account_id) as account_count,
           COUNT(DISTINCT p.id) as project_count
    FROM contacts c
    LEFT JOIN account_contacts ac ON c.id = ac.contact_id
    LEFT JOIN projects p ON c.id = p.contact_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");

// Get all accounts for dropdown
$accounts = $db->fetchAll("SELECT * FROM accounts ORDER BY account_name");
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users"></i> Contacts</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createContactModal">
                <i class="fas fa-plus"></i> Create Contact
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

<!-- Contacts Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-list"></i> All Contacts</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="contactsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Contact Name</th>
                                <th>Phone Number</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Job Title</th>
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

<!-- Create Contact Modal -->
<div class="modal fade" id="createContactModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Create New Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="contact_name" class="form-label">Contact Name *</label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone_number" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="col-md-6">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="job_title" class="form-label">Job Title</label>
                            <input type="text" class="form-control" id="job_title" name="job_title">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="related_accounts" class="form-label">Related Accounts</label>
                        <select class="form-select" id="related_accounts" name="related_accounts[]" multiple>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>">
                                    <?php echo htmlspecialchars($account['account_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple accounts</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Contact Modal -->
<div class="modal fade" id="editContactModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="contact_id" id="edit_contact_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_contact_name" class="form-label">Contact Name *</label>
                            <input type="text" class="form-control" id="edit_contact_name" name="contact_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_phone_number" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="edit_phone_number" name="phone_number">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="edit_department" name="department">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_job_title" class="form-label">Job Title</label>
                            <input type="text" class="form-control" id="edit_job_title" name="job_title">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this contact? This action cannot be undone and will also remove all relationships with accounts and projects.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="contact_id" id="delete_contact_id">
                    <button type="submit" class="btn btn-danger">Delete Contact</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editContact(contact) {
    document.getElementById('edit_contact_id').value = contact.id;
    document.getElementById('edit_contact_name').value = contact.contact_name;
    document.getElementById('edit_phone_number').value = contact.phone_number || '';
    document.getElementById('edit_email').value = contact.email || '';
    document.getElementById('edit_department').value = contact.department || '';
    document.getElementById('edit_job_title').value = contact.job_title || '';
    document.getElementById('edit_address').value = contact.address || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editContactModal'));
    modal.show();
}

function deleteContact(contactId) {
    document.getElementById('delete_contact_id').value = contactId;
    const modal = new bootstrap.Modal(document.getElementById('deleteContactModal'));
    modal.show();
}

// Initialize DataTable with server-side processing
document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery to be available
    function initDataTable() {
        if (typeof $ !== 'undefined') {
            $('#contactsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'api/contacts_data.php',
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
            { data: 1 }, // Contact Name
            { data: 2 }, // Phone Number
            { data: 3 }, // Email
            { data: 4 }, // Department
            { data: 5 }, // Job Title
            { data: 6 }, // Created
            { data: 7, orderable: false } // Actions
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
