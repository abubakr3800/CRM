<?php
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/Database.php';

$db = new Database();

// Get contact ID from URL
$contactId = $_GET['id'] ?? null;

if (!$contactId) {
    header('Location: ../contacts.php');
    exit;
}

try {
    // Get contact details
    $contact = $db->fetchOne("SELECT * FROM contacts WHERE id = ?", [$contactId]);
    
    if (!$contact) {
        header('Location: ../contacts.php');
        exit;
    }
    
    // Get related account
    $account = $db->fetchOne("
        SELECT a.*, ac.relationship_type 
        FROM accounts a 
        LEFT JOIN account_contacts ac ON a.id = ac.account_id 
        WHERE ac.contact_id = ?
    ", [$contactId]);
    
    // Get related projects
    $projects = $db->fetchAll("
        SELECT p.*, a.account_name 
        FROM projects p 
        LEFT JOIN accounts a ON p.account_id = a.id 
        WHERE p.contact_id = ? 
        ORDER BY p.created_at DESC
    ", [$contactId]);
    
} catch (Exception $e) {
    error_log("Error loading contact view: " . $e->getMessage());
    header('Location: ../contacts.php');
    exit;
}
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-user"></i> Contact Details</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="../contacts.php">Contacts</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($contact['contact_name']); ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="../contacts.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Contacts
                </a>
                <a href="../contacts.php?action=edit&id=<?php echo $contactId; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Contact
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Contact Information -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle"></i> Contact Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Contact Name:</strong></td>
                                <td><?php echo htmlspecialchars($contact['contact_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Job Title:</strong></td>
                                <td><?php echo htmlspecialchars($contact['job_title'] ?? 'Not specified'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Department:</strong></td>
                                <td><?php echo htmlspecialchars($contact['department'] ?? 'Not specified'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Phone Number:</strong></td>
                                <td>
                                    <?php if ($contact['phone_number']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($contact['phone_number']); ?>">
                                            <?php echo htmlspecialchars($contact['phone_number']); ?>
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
                                <td><strong>Email:</strong></td>
                                <td>
                                    <?php if ($contact['email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>">
                                            <?php echo htmlspecialchars($contact['email']); ?>
                                        </a>
                                    <?php else: ?>
                                        Not provided
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Related Account:</strong></td>
                                <td>
                                    <?php if ($account): ?>
                                        <a href="account_view.php?id=<?php echo $account['id']; ?>">
                                            <?php echo htmlspecialchars($account['account_name']); ?>
                                        </a>
                                        <span class="badge bg-<?php echo $account['relationship_type'] === 'Primary' ? 'primary' : 'secondary'; ?> ms-2">
                                            <?php echo $account['relationship_type'] ?? 'Related'; ?>
                                        </span>
                                    <?php else: ?>
                                        Not linked to any account
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td><?php echo date('M j, Y H:i:s', strtotime($contact['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Last Updated:</strong></td>
                                <td><?php echo date('M j, Y H:i:s', strtotime($contact['updated_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php if ($contact['address']): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <strong>Address:</strong>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($contact['address'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Related Projects -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-project-diagram"></i> Related Projects (<?php echo count($projects); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($projects) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Account</th>
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
                                    <td>
                                        <?php if ($project['account_name']): ?>
                                            <a href="account_view.php?id=<?php echo $project['account_id']; ?>">
                                                <?php echo htmlspecialchars($project['account_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            Not linked
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($project['project_phase']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $project['project_state'] === 'Finished' ? 'success' : 
                                                ($project['project_state'] === 'Started' ? 'warning' : 'secondary'); 
                                        ?>">
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
                        <p class="text-muted">No projects found for this contact.</p>
                        <a href="../projects.php" class="btn btn-primary">Add Project</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
