<?php
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/Database.php';

$db = new Database();

// Get project ID from URL
$projectId = $_GET['id'] ?? null;

if (!$projectId) {
    header('Location: ../projects.php');
    exit;
}

try {
    // Get project details
    $project = $db->fetchOne("
        SELECT p.*, a.account_name, c.contact_name 
        FROM projects p 
        LEFT JOIN accounts a ON p.account_id = a.id 
        LEFT JOIN contacts c ON p.contact_id = c.id 
        WHERE p.id = ?
    ", [$projectId]);
    
    if (!$project) {
        header('Location: ../projects.php');
        exit;
    }
    
    // Get related tasks
    $tasks = $db->fetchAll("
        SELECT t.*, u.full_name as assigned_to_name 
        FROM tasks t 
        LEFT JOIN users u ON t.assigned_to = u.id 
        WHERE t.project_id = ? 
        ORDER BY t.created_at DESC
    ", [$projectId]);
    
    // Get related price offers
    $priceOffers = $db->fetchAll("
        SELECT po.* 
        FROM price_offers po 
        WHERE po.project_id = ? 
        ORDER BY po.created_at DESC
    ", [$projectId]);
    
    // Get attached contacts
    $attachedContacts = $db->fetchAll("
        SELECT pc.*, c.contact_name, c.phone_number, c.email, c.job_title
        FROM project_contacts pc
        LEFT JOIN contacts c ON pc.contact_id = c.id
        WHERE pc.project_id = ?
        ORDER BY pc.is_primary DESC, c.contact_name ASC
    ", [$projectId]);
    
    // Get all available contacts for selection
    $availableContacts = $db->fetchAll("
        SELECT c.id, c.contact_name, c.phone_number, c.email, c.job_title
        FROM contacts c
        WHERE c.id NOT IN (
            SELECT contact_id FROM project_contacts WHERE project_id = ?
        )
        ORDER BY c.contact_name ASC
    ", [$projectId]);
    
} catch (Exception $e) {
    error_log("Error loading project view: " . $e->getMessage());
    header('Location: ../projects.php');
    exit;
}
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-project-diagram"></i> Project Details</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="../projects.php">Projects</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($project['project_name']); ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="../projects.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>
                <a href="../projects.php?action=edit&id=<?php echo $projectId; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Project
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Project Information -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle"></i> Project Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Project Name:</strong></td>
                                <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Account:</strong></td>
                                <td>
                                    <?php if ($project['account_name']): ?>
                                        <a href="account_view.php?id=<?php echo $project['account_id']; ?>">
                                            <?php echo htmlspecialchars($project['account_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        Not linked to any account
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Contact:</strong></td>
                                <td>
                                    <?php if ($project['contact_name']): ?>
                                        <a href="contact_view.php?id=<?php echo $project['contact_id']; ?>">
                                            <?php echo htmlspecialchars($project['contact_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        Not assigned
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Project Phase:</strong></td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($project['project_phase']); ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Project State:</strong></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $project['project_state'] === 'Finished' ? 'success' : 
                                            ($project['project_state'] === 'Started' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo htmlspecialchars($project['project_state']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Start Date:</strong></td>
                                <td><?php echo $project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : 'Not set'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Closing Date:</strong></td>
                                <td><?php echo $project['closing_date'] ? date('M j, Y', strtotime($project['closing_date'])) : 'Not set'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Visit Required:</strong></td>
                                <td>
                                    <?php if ($project['need_visit']): ?>
                                        <span class="badge bg-warning">Yes</span>
                                        <?php if ($project['visit_date']): ?>
                                            <br><small>Visit Date: <?php echo date('M j, Y', strtotime($project['visit_date'])); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-success">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if ($project['address']): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <strong>Project Address:</strong>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($project['address'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($project['feedback']): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <strong>Feedback:</strong>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($project['feedback'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($project['visit_reason']): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <strong>Visit Reason:</strong>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($project['visit_reason'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <small class="text-muted">
                            <strong>Created:</strong> <?php echo date('M j, Y H:i:s', strtotime($project['created_at'])); ?> | 
                            <strong>Last Updated:</strong> <?php echo date('M j, Y H:i:s', strtotime($project['updated_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Related Data Tabs -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="projectTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab">
                            <i class="fas fa-tasks"></i> Tasks (<?php echo count($tasks); ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab">
                            <i class="fas fa-users"></i> Contacts (<?php echo count($attachedContacts); ?>)
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
                <div class="tab-content" id="projectTabsContent">
                    <!-- Tasks Tab -->
                    <div class="tab-pane fade show active" id="tasks" role="tabpanel">
                        <?php if (count($tasks) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Task Name</th>
                                            <th>Assigned To</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Due Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tasks as $task): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                                            <td><?php echo htmlspecialchars($task['assigned_to_name'] ?? 'Unassigned'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $task['priority'] === 'High' ? 'danger' : 
                                                        ($task['priority'] === 'Medium' ? 'warning' : 'success'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($task['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $task['status'] === 'Done' ? 'success' : 
                                                        ($task['status'] === 'In Progress' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($task['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'Not set'; ?></td>
                                            <td>
                                                <a href="../tasks.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary">
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
                                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No tasks found for this project.</p>
                                <a href="../tasks.php" class="btn btn-primary">Add Task</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Contacts Tab -->
                    <div class="tab-pane fade" id="contacts" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Attached Contacts</h5>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attachContactModal">
                                <i class="fas fa-plus"></i> Attach Contact
                            </button>
                        </div>
                        
                        <?php if (count($attachedContacts) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Job Title</th>
                                            <th>Phone</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Primary</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attachedContacts as $contact): ?>
                                            <tr>
                                                <td>
                                                    <a href="contact_view.php?id=<?php echo $contact['contact_id']; ?>">
                                                        <?php echo htmlspecialchars($contact['contact_name']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($contact['job_title']); ?></td>
                                                <td><?php echo htmlspecialchars($contact['phone_number']); ?></td>
                                                <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                                <td><?php echo htmlspecialchars($contact['role']); ?></td>
                                                <td>
                                                    <?php if ($contact['is_primary']): ?>
                                                        <span class="badge bg-primary">Primary</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Secondary</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="removeContact(<?php echo $contact['id']; ?>)">
                                                        <i class="fas fa-times"></i> Remove
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No contacts attached to this project yet.</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attachContactModal">
                                    <i class="fas fa-plus"></i> Attach First Contact
                                </button>
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
                                <p class="text-muted">No price offers found for this project.</p>
                                <a href="../price_offers.php" class="btn btn-primary">Add Price Offer</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attach Contact Modal -->
<div class="modal fade" id="attachContactModal" tabindex="-1" aria-labelledby="attachContactModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attachContactModalLabel">Attach Contact to Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="attachContactForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="contactSearch" class="form-label">Search and Select Contact</label>
                        <input type="text" class="form-control" id="contactSearch" name="contact_search" 
                               list="contactsList" placeholder="Type to search contacts..." required>
                        <datalist id="contactsList">
                            <?php foreach ($availableContacts as $contact): ?>
                                <option value="<?php echo htmlspecialchars($contact['contact_name']); ?>" 
                                        data-contact-id="<?php echo $contact['id']; ?>"
                                        data-job-title="<?php echo htmlspecialchars($contact['job_title']); ?>"
                                        data-phone="<?php echo htmlspecialchars($contact['phone_number']); ?>"
                                        data-email="<?php echo htmlspecialchars($contact['email']); ?>">
                                    <?php echo htmlspecialchars($contact['contact_name']); ?> - <?php echo htmlspecialchars($contact['job_title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                        <input type="hidden" id="selectedContactId" name="contact_id" required>
                        <div class="form-text">Type the contact name to search. You can only select from existing contacts.</div>
                        <div id="contactValidation" class="invalid-feedback" style="display: none;">
                            Please select a valid contact from the dropdown list.
                        </div>
                        <div id="contactDetails" class="mt-2" style="display: none;">
                            <small class="text-muted">
                                <i class="fas fa-user"></i> <span id="selectedContactName"></span><br>
                                <i class="fas fa-briefcase"></i> <span id="selectedContactJob"></span><br>
                                <i class="fas fa-phone"></i> <span id="selectedContactPhone"></span>
                            </small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="contactRole" class="form-label">Role</label>
                        <input type="text" class="form-control" id="contactRole" name="role" value="Contact" placeholder="e.g., Project Manager, Contact Person">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="isPrimary" name="is_primary" value="1">
                            <label class="form-check-label" for="isPrimary">
                                Set as Primary Contact
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Attach Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle datalist selection
document.getElementById('contactSearch').addEventListener('input', function() {
    const input = this.value;
    const datalist = document.getElementById('contactsList');
    const hiddenInput = document.getElementById('selectedContactId');
    const validationDiv = document.getElementById('contactValidation');
    
    // Find matching option
    const options = datalist.querySelectorAll('option');
    let foundMatch = false;
    
    options.forEach(option => {
        if (option.value === input) {
            hiddenInput.value = option.getAttribute('data-contact-id');
            foundMatch = true;
            
            // Show contact details
            document.getElementById('selectedContactName').textContent = option.getAttribute('data-contact-id') ? option.value : '';
            document.getElementById('selectedContactJob').textContent = option.getAttribute('data-job-title') || '';
            document.getElementById('selectedContactPhone').textContent = option.getAttribute('data-phone') || '';
            document.getElementById('contactDetails').style.display = 'block';
        }
    });
    
    // Clear hidden input if no exact match
    if (!foundMatch) {
        hiddenInput.value = '';
        document.getElementById('contactDetails').style.display = 'none';
    }
    
    // Show/hide validation message
    if (input.length > 0 && !foundMatch) {
        this.classList.add('is-invalid');
        validationDiv.style.display = 'block';
    } else {
        this.classList.remove('is-invalid');
        validationDiv.style.display = 'none';
    }
});

// Attach contact functionality
document.getElementById('attachContactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const contactId = document.getElementById('selectedContactId').value;
    const contactSearch = document.getElementById('contactSearch').value;
    
    // Validate that a contact was selected
    if (!contactId) {
        alert('Please select a valid contact from the list. You cannot add contacts that are not in the system.');
        return;
    }
    
    const formData = new FormData(this);
    formData.append('action', 'attach_contact');
    formData.append('project_id', <?php echo $projectId; ?>);
    
    fetch('api/project_contacts.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error attaching contact. Please try again.');
    });
});

// Remove contact functionality
function removeContact(projectContactId) {
    if (confirm('Are you sure you want to remove this contact from the project?')) {
        const formData = new FormData();
        formData.append('action', 'remove_contact');
        formData.append('project_contact_id', projectContactId);
        
        fetch('api/project_contacts.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing contact. Please try again.');
        });
    }
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
