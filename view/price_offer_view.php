<?php
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/Database.php';

$db = new Database();

// Get price offer ID from URL
$offerId = $_GET['id'] ?? null;

if (!$offerId) {
    header('Location: ../price_offers.php');
    exit;
}

try {
    // Get price offer details
    $offer = $db->fetchOne("
        SELECT po.*, a.account_name, p.project_name 
        FROM price_offers po 
        LEFT JOIN accounts a ON po.account_id = a.id 
        LEFT JOIN projects p ON po.project_id = p.id 
        WHERE po.id = ?
    ", [$offerId]);
    
    if (!$offer) {
        header('Location: ../price_offers.php');
        exit;
    }
    
    // Get price offer items
    $items = $db->fetchAll("
        SELECT * FROM price_offer_items 
        WHERE offer_id = ? 
        ORDER BY id
    ", [$offerId]);
    
} catch (Exception $e) {
    error_log("Error loading price offer view: " . $e->getMessage());
    header('Location: ../price_offers.php');
    exit;
}
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-dollar-sign"></i> Price Offer Details</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="../price_offers.php">Price Offers</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($offer['offer_code']); ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="../price_offers.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Price Offers
                </a>
                <a href="../price_offers.php?action=edit&id=<?php echo $offerId; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Offer
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Price Offer Information -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle"></i> Price Offer Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Offer Code:</strong></td>
                                <td><?php echo htmlspecialchars($offer['offer_code']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Account:</strong></td>
                                <td>
                                    <?php if ($offer['account_name']): ?>
                                        <a href="account_view.php?id=<?php echo $offer['account_id']; ?>">
                                            <?php echo htmlspecialchars($offer['account_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        Not linked to any account
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Project:</strong></td>
                                <td>
                                    <?php if ($offer['project_name']): ?>
                                        <a href="project_view.php?id=<?php echo $offer['project_id']; ?>">
                                            <?php echo htmlspecialchars($offer['project_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        Not linked to any project
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Offer Date:</strong></td>
                                <td><?php echo date('M j, Y', strtotime($offer['offer_date'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $offer['status'] === 'Accepted' ? 'success' : 
                                            ($offer['status'] === 'Rejected' ? 'danger' : 
                                            ($offer['status'] === 'Sent' ? 'info' : 'secondary')); 
                                    ?> fs-6">
                                        <?php echo htmlspecialchars($offer['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Total Amount:</strong></td>
                                <td class="h5 text-success">$<?php echo number_format($offer['total_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td><?php echo date('M j, Y H:i:s', strtotime($offer['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Last Updated:</strong></td>
                                <td><?php echo date('M j, Y H:i:s', strtotime($offer['updated_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if ($offer['notes']): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <strong>Notes:</strong>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($offer['notes'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Price Offer Items -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list"></i> Offer Items (<?php echo count($items); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($items) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $grandTotal = 0;
                                foreach ($items as $item): 
                                    $grandTotal += $item['subtotal'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo number_format($item['quantity']); ?></td>
                                    <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="fw-bold">$<?php echo number_format($item['subtotal'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-success">
                                    <th colspan="3" class="text-end">Grand Total:</th>
                                    <th class="h5">$<?php echo number_format($grandTotal, 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-list fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No items found for this price offer.</p>
                        <a href="../price_offers.php?action=edit&id=<?php echo $offerId; ?>" class="btn btn-primary">Add Items</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="card-title">Quick Actions</h6>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-success" onclick="updateStatus('Accepted')">
                        <i class="fas fa-check"></i> Mark as Accepted
                    </button>
                    <button type="button" class="btn btn-warning" onclick="updateStatus('Sent')">
                        <i class="fas fa-paper-plane"></i> Mark as Sent
                    </button>
                    <button type="button" class="btn btn-danger" onclick="updateStatus('Rejected')">
                        <i class="fas fa-times"></i> Mark as Rejected
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="updateStatus('Draft')">
                        <i class="fas fa-edit"></i> Mark as Draft
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(status) {
    if (confirm('Are you sure you want to update the status to "' + status + '"?')) {
        // For now, just show an alert. You can implement AJAX call to update status
        alert('Status would be updated to: ' + status + '\n\nThis feature can be implemented with AJAX to update the database.');
    }
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
