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
            
            echo json_encode(['success' => true, 'message' => 'Account created successfully']);
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
                <h2><i class="fas fa-building"></i> Accounts Management</h2>
                <p class="text-muted mb-0">Manage your customer accounts and relationships</p>
            </div>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                    <i class="fas fa-plus"></i> Add New Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Accounts Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list"></i> All Accounts
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="accountsTable" class="table table-striped table-hover">
                        <thead class="table-dark">
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

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus"></i> Add New Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addAccountForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Account Name *</label>
                                <input type="text" class="form-control" name="account_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone">
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
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control" name="country">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Account
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
        var table = $('#accountsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api/accounts_data.php',
                type: 'GET',
                error: function(xhr, error, thrown) {
                    console.error('DataTables AJAX Error:', error, thrown);
                    alert('Error loading data. Please refresh the page.');
                }
            },
            columns: [
                { data: 0, title: 'ID', width: '60px' },
                { data: 1, title: 'Code', width: '80px' },
                { data: 2, title: 'Account Name' },
                { data: 3, title: 'Phone', width: '120px' },
                { data: 4, title: 'Email', width: '150px' },
                { data: 5, title: 'City', width: '100px' },
                { data: 6, title: 'Country', width: '100px' },
                { data: 7, title: 'Created', width: '100px' },
                { data: 8, title: 'Actions', orderable: false, searchable: false, width: '150px' }
            ],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[0, 'desc']],
            language: {
                processing: "Loading accounts...",
                emptyTable: "No accounts found",
                zeroRecords: "No matching accounts found"
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });

        // Handle add account form
        $('#addAccountForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'create');
            
            $.ajax({
                url: 'accounts.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        $('#addAccountModal').modal('hide');
                        $('#addAccountForm')[0].reset();
                        table.ajax.reload();
                        alert('Account created successfully!');
                    } else {
                        alert('Error: ' + result.message);
                    }
                },
                error: function() {
                    alert('Error creating account. Please try again.');
                }
            });
        });
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
