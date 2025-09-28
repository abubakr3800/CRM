<?php
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/Database.php';

$db = new Database();

// Get account ID from URL
$accountId = $_GET['id'] ?? null;

if (!$accountId) {
    header('Location: ../accounts.php');
    exit;
}

try {
    // Get account details
    $account = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$accountId]);
    
    if (!$account) {
        header('Location: ../accounts.php');
        exit;
    }
    
    // Get related contacts
    $contacts = $db->fetchAll("
        SELECT c.*, ac.relationship_type 
        FROM contacts c 
        LEFT JOIN account_contacts ac ON c.id = ac.contact_id 
        WHERE ac.account_id = ? OR c.id IN (
            SELECT contact_id FROM projects WHERE account_id = ?
        )
        ORDER BY ac.relationship_type DESC, c.contact_name
    ", [$accountId, $accountId]);
    
    // Get related projects
    $projects = $db->fetchAll("
        SELECT p.*, c.contact_name 
        FROM projects p 
        LEFT JOIN contacts c ON p.contact_id = c.id 
        WHERE p.account_id = ? 
        ORDER BY p.created_at DESC
    ", [$accountId]);
    
    // Get related price offers
    $priceOffers = $db->fetchAll("
        SELECT po.*, p.project_name 
        FROM price_offers po 
        LEFT JOIN projects p ON po.project_id = p.id 
        WHERE po.account_id = ? 
        ORDER BY po.created_at DESC
    ", [$accountId]);
    
} catch (Exception $e) {
    error_log("Error loading account view: " . $e->getMessage());
    header('Location: ../accounts.php');
    exit;
}
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-building"></i> Account Details</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="../accounts.php">Accounts</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($account['account_name']); ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="../accounts.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Accounts
                </a>
                <a href="../accounts.php?action=edit&id=<?php echo $accountId; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Account
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Account Information -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle"></i> Account Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Account Code:</strong></td>
                                <td><?php echo htmlspecialchars($account['code']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Account Name:</strong></td>
                                <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td><?php echo htmlspecialchars($account['phone'] ?? 'Not provided'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>
                                    <?php if ($account['email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($account['email']); ?>">
                                            <?php echo htmlspecialchars($account['email']); ?>
                                        </a>
                                    <?php else: ?>
                                        Not provided
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Region:</strong></td>
                                <td><?php echo htmlspecialchars($account['region'] ?? 'Not specified'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>City:</strong></td>
                                <td><?php echo htmlspecialchars($account['city'] ?? 'Not specified'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Country:</strong></td>
                                <td><?php echo htmlspecialchars($account['country'] ?? 'Not specified'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td><?php echo date('M j, Y H:i:s', strtotime($account['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php if ($account['address']): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <strong>Address:</strong>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($account['address'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Related Data Tabs -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="accountTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab">
                            <i class="fas fa-users"></i> Contacts (<?php echo count($contacts); ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="projects-tab" data-bs-toggle="tab" data-bs-target="#projects" type="button" role="tab">
                            <i class="fas fa-project-diagram"></i> Projects (<?php echo count($projects); ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="offers-tab" data-bs-toggle="tab" data-bs-target="#offers" type="button" role="tab">
                            <i class="fas fa-dollar-sign"></i> Price Offers (<?php echo count($priceOffers); ?>)
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="accountTabsContent">
                    <!-- Contacts Tab -->
                    <div class="tab-pane fade show active" id="contacts" role="tabpanel">
                        <?php if (count($contacts) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Job Title</th>
                                            <th>Phone</th>
                                            <th>Email</th>
                                            <th>Relationship</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contacts as $contact): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($contact['contact_name']); ?></td>
                                            <td><?php echo htmlspecialchars($contact['job_title'] ?? 'Not specified'); ?></td>
                                            <td><?php echo htmlspecialchars($contact['phone_number'] ?? 'Not provided'); ?></td>
                                            <td>
                                                <?php if ($contact['email']): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>">
                                                        <?php echo htmlspecialchars($contact['email']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    Not provided
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $contact['relationship_type'] === 'Primary' ? 'primary' : 'secondary'; ?>">
                                                    <?php echo $contact['relationship_type'] ?? 'Related'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="contact_view.php?id=<?php echo $contact['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No contacts found for this account.</p>
                                <a href="../contacts.php" class="btn btn-primary">Add Contact</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Projects Tab -->
                    <div class="tab-pane fade" id="projects" role="tabpanel">
                        <?php if (count($projects) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Project Name</th>
                                            <th>Contact</th>
                                            <th>Phase</th>
                                            <th>State</th>
                                            <th>Start Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($projects as $project): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                            <td><?php echo htmlspecialchars($project['contact_name'] ?? 'Not assigned'); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($project['project_phase']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $project['project_state'] === 'Finished' ? 'success' : ($project['project_state'] === 'Started' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo htmlspecialchars($project['project_state']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : 'Not set'; ?></td>
                                            <td>
                                                <a href="project_view.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No projects found for this account.</p>
                                <a href="../projects.php" class="btn btn-primary">Add Project</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Price Offers Tab -->
                    <div class="tab-pane fade" id="offers" role="tabpanel">
                        <?php if (count($priceOffers) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Offer Code</th>
                                            <th>Project</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($priceOffers as $offer): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($offer['offer_code']); ?></td>
                                            <td><?php echo htmlspecialchars($offer['project_name'] ?? 'Not linked'); ?></td>
                                            <td>$<?php echo number_format($offer['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $offer['status'] === 'Accepted' ? 'success' : 
                                                        ($offer['status'] === 'Rejected' ? 'danger' : 
                                                        ($offer['status'] === 'Sent' ? 'info' : 'secondary')); 
                                                ?>">
                                                    <?php echo htmlspecialchars($offer['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($offer['offer_date'])); ?></td>
                                            <td>
                                                <a href="price_offer_view.php?id=<?php echo $offer['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-dollar-sign fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No price offers found for this account.</p>
                                <a href="../price_offers.php" class="btn btn-primary">Add Price Offer</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
