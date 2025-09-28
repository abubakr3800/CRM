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
            // Generate offer code
            $lastOffer = $db->fetchOne("SELECT offer_code FROM price_offers ORDER BY id DESC LIMIT 1");
            $nextNumber = $lastOffer ? (intval(substr($lastOffer['offer_code'], 1)) + 1) : 1;
            $offerCode = 'O' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            $offerData = [
                'offer_code' => $offerCode,
                'account_id' => $_POST['account_id'],
                'project_id' => $_POST['project_id'] ?? null,
                'offer_date' => $_POST['offer_date'] ?? date('Y-m-d'),
                'total_amount' => 0, // Will be calculated from items
                'status' => $_POST['status'] ?? 'Draft',
                'notes' => $_POST['notes'] ?? null
            ];
            
            $offerId = $db->insert('price_offers', $offerData);
            
            // Handle offer items
            $totalAmount = 0;
            if (!empty($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['item_name']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                        $subtotal = $item['quantity'] * $item['unit_price'];
                        $totalAmount += $subtotal;
                        
                        $db->insert('price_offer_items', [
                            'offer_id' => $offerId,
                            'item_name' => $item['item_name'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'subtotal' => $subtotal
                        ]);
                    }
                }
            }
            
            // Update total amount
            $db->update('price_offers', ['total_amount' => $totalAmount], 'id = ?', [$offerId]);
            
            $logger->activity('Price offer created', 'price_offers', $offerId, null, $offerData);
            $success = 'Price offer created successfully!';
            
        } elseif ($action === 'update') {
            $offerId = $_POST['offer_id'];
            
            $oldData = $db->fetchOne("SELECT * FROM price_offers WHERE id = ?", [$offerId]);
            
            $offerData = [
                'account_id' => $_POST['account_id'],
                'project_id' => $_POST['project_id'] ?? null,
                'offer_date' => $_POST['offer_date'] ?? date('Y-m-d'),
                'status' => $_POST['status'] ?? 'Draft',
                'notes' => $_POST['notes'] ?? null
            ];
            
            $db->update('price_offers', $offerData, 'id = ?', [$offerId]);
            
            // Update items
            $db->delete('price_offer_items', 'offer_id = ?', [$offerId]);
            
            $totalAmount = 0;
            if (!empty($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['item_name']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                        $subtotal = $item['quantity'] * $item['unit_price'];
                        $totalAmount += $subtotal;
                        
                        $db->insert('price_offer_items', [
                            'offer_id' => $offerId,
                            'item_name' => $item['item_name'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'subtotal' => $subtotal
                        ]);
                    }
                }
            }
            
            // Update total amount
            $db->update('price_offers', ['total_amount' => $totalAmount], 'id = ?', [$offerId]);
            
            $logger->activity('Price offer updated', 'price_offers', $offerId, $oldData, $offerData);
            $success = 'Price offer updated successfully!';
            
        } elseif ($action === 'delete') {
            $offerId = $_POST['offer_id'];
            $oldData = $db->fetchOne("SELECT * FROM price_offers WHERE id = ?", [$offerId]);
            
            $db->delete('price_offers', 'id = ?', [$offerId]);
            
            $logger->activity('Price offer deleted', 'price_offers', $offerId, $oldData, null);
            $success = 'Price offer deleted successfully!';
        }
        
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
        $logger->error('Price offer operation failed', ['error' => $e->getMessage()]);
    }
}

// Get all price offers with related data
$offers = $db->fetchAll("
    SELECT po.*, a.account_name, p.project_name
    FROM price_offers po
    LEFT JOIN accounts a ON po.account_id = a.id
    LEFT JOIN projects p ON po.project_id = p.id
    ORDER BY po.created_at DESC
");

// Get all accounts and projects for dropdowns
$accounts = $db->fetchAll("SELECT * FROM accounts ORDER BY account_name");
$projects = $db->fetchAll("SELECT * FROM projects ORDER BY project_name");
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-dollar-sign"></i> Price Offers</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createOfferModal">
                <i class="fas fa-plus"></i> Create Price Offer
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

<!-- Price Offers Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-list"></i> All Price Offers</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="offersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Offer Code</th>
                                <th>Client/Account</th>
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

<!-- Create Price Offer Modal -->
<div class="modal fade" id="createOfferModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Create New Price Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <!-- Top Section -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="offer_code" class="form-label">Offer Code</label>
                            <input type="text" class="form-control" id="offer_code" disabled 
                                   placeholder="Auto-generated">
                        </div>
                        <div class="col-md-3">
                            <label for="account_id" class="form-label">Related Account *</label>
                            <select class="form-select" id="account_id" name="account_id" required onchange="loadProjects()">
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo htmlspecialchars($account['account_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="project_id" class="form-label">Related Project</label>
                            <select class="form-select" id="project_id" name="project_id">
                                <option value="">Select Project</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="offer_date" class="form-label">Offer Date</label>
                            <input type="date" class="form-control" id="offer_date" name="offer_date" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <!-- Items Section -->
                    <div class="mb-3">
                        <h6><i class="fas fa-list"></i> Items</h6>
                        <div id="items-container">
                            <div class="row item-row mb-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="items[0][item_name]" 
                                           placeholder="Item Name" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control" name="items[0][quantity]" 
                                           placeholder="Qty" min="1" value="1" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control" name="items[0][unit_price]" 
                                           placeholder="Unit Price" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control subtotal" readonly placeholder="Subtotal">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addItem()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                    
                    <!-- Total Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="notes" class="form-label">Notes / Terms</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Total Amount</h6>
                                    <h3 id="total-amount">$0.00</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Draft" selected>Draft</option>
                            <option value="Sent">Sent</option>
                            <option value="Accepted">Accepted</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Price Offer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Price Offer Modal -->
<div class="modal fade" id="editOfferModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Price Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="offer_id" id="edit_offer_id">
                    
                    <!-- Top Section -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="edit_offer_code" class="form-label">Offer Code</label>
                            <input type="text" class="form-control" id="edit_offer_code" disabled>
                        </div>
                        <div class="col-md-3">
                            <label for="edit_account_id" class="form-label">Related Account *</label>
                            <select class="form-select" id="edit_account_id" name="account_id" required onchange="loadEditProjects()">
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo htmlspecialchars($account['account_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="edit_project_id" class="form-label">Related Project</label>
                            <select class="form-select" id="edit_project_id" name="project_id">
                                <option value="">Select Project</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="edit_offer_date" class="form-label">Offer Date</label>
                            <input type="date" class="form-control" id="edit_offer_date" name="offer_date">
                        </div>
                    </div>
                    
                    <!-- Items Section -->
                    <div class="mb-3">
                        <h6><i class="fas fa-list"></i> Items</h6>
                        <div id="edit-items-container">
                            <!-- Items will be loaded here -->
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addEditItem()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                    
                    <!-- Total Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_notes" class="form-label">Notes / Terms</label>
                            <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Total Amount</h6>
                                    <h3 id="edit-total-amount">$0.00</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status">
                            <option value="Draft">Draft</option>
                            <option value="Sent">Sent</option>
                            <option value="Accepted">Accepted</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Price Offer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteOfferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this price offer? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="offer_id" id="delete_offer_id">
                    <button type="submit" class="btn btn-danger">Delete Price Offer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let itemIndex = 1;

function addItem() {
    const container = document.getElementById('items-container');
    const newRow = document.createElement('div');
    newRow.className = 'row item-row mb-2';
    newRow.innerHTML = `
        <div class="col-md-4">
            <input type="text" class="form-control" name="items[${itemIndex}][item_name]" 
                   placeholder="Item Name" required>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="items[${itemIndex}][quantity]" 
                   placeholder="Qty" min="1" value="1" required onchange="calculateSubtotal(this)">
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="items[${itemIndex}][unit_price]" 
                   placeholder="Unit Price" step="0.01" min="0" required onchange="calculateSubtotal(this)">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control subtotal" readonly placeholder="Subtotal">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
    itemIndex++;
}

function removeItem(button) {
    button.closest('.item-row').remove();
    calculateTotal();
}

function calculateSubtotal(input) {
    const row = input.closest('.item-row');
    const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
    const unitPrice = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
    const subtotal = quantity * unitPrice;
    
    row.querySelector('.subtotal').value = subtotal.toFixed(2);
    calculateTotal();
}

function calculateTotal() {
    const subtotals = document.querySelectorAll('.subtotal');
    let total = 0;
    
    subtotals.forEach(subtotal => {
        total += parseFloat(subtotal.value) || 0;
    });
    
    document.getElementById('total-amount').textContent = '$' + total.toFixed(2);
}

function loadProjects() {
    const accountId = document.getElementById('account_id').value;
    const projectSelect = document.getElementById('project_id');
    
    projectSelect.innerHTML = '<option value="">Select Project</option>';
    
    if (accountId) {
        fetch(`api/get_projects.php?account_id=${accountId}`)
            .then(response => response.json())
            .then(projects => {
                projects.forEach(project => {
                    const option = document.createElement('option');
                    option.value = project.id;
                    option.textContent = project.project_name;
                    projectSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading projects:', error);
            });
    }
}

function loadEditProjects() {
    const accountId = document.getElementById('edit_account_id').value;
    const projectSelect = document.getElementById('edit_project_id');
    
    projectSelect.innerHTML = '<option value="">Select Project</option>';
    
    if (accountId) {
        fetch(`api/get_projects.php?account_id=${accountId}`)
            .then(response => response.json())
            .then(projects => {
                projects.forEach(project => {
                    const option = document.createElement('option');
                    option.value = project.id;
                    option.textContent = project.project_name;
                    projectSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading projects:', error);
            });
    }
}

function editOffer(offer) {
    document.getElementById('edit_offer_id').value = offer.id;
    document.getElementById('edit_offer_code').value = offer.offer_code;
    document.getElementById('edit_account_id').value = offer.account_id;
    document.getElementById('edit_project_id').value = offer.project_id || '';
    document.getElementById('edit_offer_date').value = offer.offer_date;
    document.getElementById('edit_notes').value = offer.notes || '';
    document.getElementById('edit_status').value = offer.status;
    
    loadEditProjects();
    
    const modal = new bootstrap.Modal(document.getElementById('editOfferModal'));
    modal.show();
}

function deleteOffer(offerId) {
    document.getElementById('delete_offer_id').value = offerId;
    const modal = new bootstrap.Modal(document.getElementById('deleteOfferModal'));
    modal.show();
}

// Initialize DataTable with server-side processing
document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery to be available
    function initDataTable() {
        if (typeof $ !== 'undefined') {
            $('#offersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'api/price_offers_data.php',
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
            { data: 1 }, // Offer Code
            { data: 2 }, // Client/Account
            { data: 3 }, // Total Amount
            { data: 4 }, // Status
            { data: 5 }, // Valid Until
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

// Initialize calculation on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to existing inputs
    document.querySelectorAll('input[name*="[quantity]"], input[name*="[unit_price]"]').forEach(input => {
        input.addEventListener('change', function() {
            calculateSubtotal(this);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
