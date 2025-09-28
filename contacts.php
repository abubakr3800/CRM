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
            $contactData = [
                'contact_name' => $_POST['contact_name'],
                'phone_number' => $_POST['phone_number'] ?? null,
                'email' => $_POST['email'] ?? null,
                'department' => $_POST['department'] ?? null,
                'job_title' => $_POST['job_title'] ?? null
            ];
            
            $contactId = $db->insert('contacts', $contactData);
            
            echo json_encode(['success' => true, 'message' => 'Contact created successfully']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-users"></i> Contacts Management</h2>
                <p class="text-muted mb-0">Manage your business contacts and relationships</p>
            </div>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
                    <i class="fas fa-plus"></i> Add New Contact
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Contacts Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list"></i> All Contacts
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="contactsTable" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Contact Name</th>
                                <th>Phone</th>
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

<!-- Add Contact Modal -->
<div class="modal fade" id="addContactModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus"></i> Add New Contact
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addContactForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Contact Name *</label>
                                <input type="text" class="form-control" name="contact_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone_number">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-control" name="department">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Job Title</label>
                                <input type="text" class="form-control" name="job_title">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Contact
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
        var table = $('#contactsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api/contacts_data.php',
                type: 'GET',
                error: function(xhr, error, thrown) {
                    console.error('DataTables AJAX Error:', error, thrown);
                    alert('Error loading data. Please refresh the page.');
                }
            },
            columns: [
                { data: 0, title: 'ID', width: '60px' },
                { data: 1, title: 'Contact Name' },
                { data: 2, title: 'Phone', width: '120px' },
                { data: 3, title: 'Email', width: '150px' },
                { data: 4, title: 'Department', width: '120px' },
                { data: 5, title: 'Job Title', width: '120px' },
                { data: 6, title: 'Created', width: '100px' },
                { data: 7, title: 'Actions', orderable: false, searchable: false, width: '150px' }
            ],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[0, 'desc']],
            language: {
                processing: "Loading contacts...",
                emptyTable: "No contacts found",
                zeroRecords: "No matching contacts found"
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });

        // Handle add contact form
        $('#addContactForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'create');
            
            $.ajax({
                url: 'contacts.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        $('#addContactModal').modal('hide');
                        $('#addContactForm')[0].reset();
                        table.ajax.reload();
                        alert('Contact created successfully!');
                    } else {
                        alert('Error: ' + result.message);
                    }
                },
                error: function() {
                    alert('Error creating contact. Please try again.');
                }
            });
        });
    });
}

</script>

<?php require_once 'includes/footer.php'; ?>
