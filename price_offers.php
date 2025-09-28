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
            // Generate offer code
            $lastOffer = $db->fetchOne("SELECT offer_code FROM price_offers ORDER BY id DESC LIMIT 1");
            $nextNumber = $lastOffer ? (intval(substr($lastOffer['offer_code'], 1)) + 1) : 1;
            $offerCode = 'O' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            $offerData = [
                'offer_code' => $offerCode,
                'account_id' => $_POST['account_id'] ?? null,
                'project_id' => $_POST['project_id'] ?? null,
                'offer_date' => $_POST['offer_date'] ?? date('Y-m-d'),
                'total_amount' => $_POST['total_amount'] ?? 0,
                'status' => $_POST['status'] ?? 'Draft',
                'valid_until' => $_POST['valid_until'] ?? null,
                'notes' => $_POST['notes'] ?? null
            ];
            
            $offerId = $db->insert('price_offers', $offerData);
            
            echo json_encode(['success' => true, 'message' => 'Price offer created successfully']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// Get accounts and projects for dropdowns
$accounts = [];
$projects = [];
try {
    $accounts = $db->fetchAll("SELECT id, account_name FROM accounts ORDER BY account_name");
    $projects = $db->fetchAll("SELECT id, project_name FROM projects ORDER BY project_name");
} catch (Exception $e) {
    error_log("Error getting accounts/projects: " . $e->getMessage());
}
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-dollar-sign"></i> Price Offers Management</h2>
                <p class="text-muted mb-0">Create and manage price offers for your customers</p>
            </div>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOfferModal">
                    <i class="fas fa-plus"></i> Add New Offer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Price Offers Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list"></i> All Price Offers
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="priceOffersTable" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Offer Code</th>
                                <th>Account</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Valid Until</th>
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

<!-- Add Price Offer Modal -->
<div class="modal fade" id="addOfferModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus"></i> Add New Price Offer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addOfferForm">
                <div class="modal-body">
                    <div class="row">
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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Project</label>
                                <select class="form-control" name="project_id">
                                    <option value="">Select Project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['project_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Offer Date</label>
                                <input type="date" class="form-control" name="offer_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Valid Until</label>
                                <input type="date" class="form-control" name="valid_until">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Total Amount</label>
                                <input type="number" class="form-control" name="total_amount" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-control" name="status">
                                    <option value="Draft">Draft</option>
                                    <option value="Sent">Sent</option>
                                    <option value="Accepted">Accepted</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Offer
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
        var table = $('#priceOffersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api/price_offers_data.php',
                type: 'GET',
                error: function(xhr, error, thrown) {
                    console.error('DataTables AJAX Error:', error, thrown);
                    alert('Error loading data. Please refresh the page.');
                }
            },
            columns: [
                { data: 0, title: 'ID', width: '60px' },
                { data: 1, title: 'Offer Code', width: '100px' },
                { data: 2, title: 'Account' },
                { data: 3, title: 'Total Amount', width: '120px' },
                { data: 4, title: 'Status', width: '100px' },
                { data: 5, title: 'Valid Until', width: '100px' },
                { data: 6, title: 'Created', width: '100px' },
                { data: 7, title: 'Actions', orderable: false, searchable: false, width: '150px' }
            ],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[0, 'desc']],
            language: {
                processing: "Loading price offers...",
                emptyTable: "No price offers found",
                zeroRecords: "No matching price offers found"
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });

        // Handle add offer form
        $('#addOfferForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'create');
            
            $.ajax({
                url: 'price_offers.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        $('#addOfferModal').modal('hide');
                        $('#addOfferForm')[0].reset();
                        table.ajax.reload();
                        alert('Price offer created successfully!');
                    } else {
                        alert('Error: ' + result.message);
                    }
                },
                error: function() {
                    alert('Error creating price offer. Please try again.');
                }
            });
        });
    });
}

</script>

<?php require_once 'includes/footer.php'; ?>
