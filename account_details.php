<?php
require_once 'includes/header.php';
require_once 'includes/Database.php';
require_once 'includes/Logger.php';

$db = new Database();
$logger = new Logger();

$accountId = $_GET['id'] ?? null;

if (!$accountId) {
    header('Location: accounts.php');
    exit;
}

// Get account details
$account = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$accountId]);

if (!$account) {
    header('Location: accounts.php');
    exit;
}

// Get related contacts
$relatedContacts = $db->fetchAll("
    SELECT c.*, ac.relationship_type 
    FROM contacts c
    JOIN account_contacts ac ON c.id = ac.contact_id
    WHERE ac.account_id = ?
    ORDER BY ac.relationship_type, c.contact_name
", [$accountId]);

// Get related projects
$relatedProjects = $db->fetchAll("
    SELECT p.*, c.contact_name 
    FROM projects p
    LEFT JOIN contacts c ON p.contact_id = c.id
    WHERE p.account_id = ?
    ORDER BY p.created_at DESC
", [$accountId]);

// Get all contacts for adding to account
$allContacts = $db->fetchAll("SELECT * FROM contacts ORDER BY contact_name");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add_contact') {
            $contactId = $_POST['contact_id'];
            $relationshipType = $_POST['relationship_type'];
            
            // Check if relationship already exists
            $existing = $db->fetchOne(
                "SELECT id FROM account_contacts WHERE account_id = ? AND contact_id = ?",
                [$accountId, $contactId]
            );
            
            if (!$existing) {
                $db->insert('account_contacts', [
                    'account_id' => $accountId,
                    'contact_id' => $contactId,
                    'relationship_type' => $relationshipType
                ]);
                
                $logger->activity('Contact added to account', 'account_contacts', null, null, [
                    'account_id' => $accountId,
                    'contact_id' => $contactId,
                    'relationship_type' => $relationshipType
                ]);
                
                $success = 'Contact added successfully!';
            } else {
                $error = 'This contact is already associated with this account.';
            }
            
        } elseif ($action === 'remove_contact') {
            $contactId = $_POST['contact_id'];
            
            $db->delete('account_contacts', 'account_id = ? AND contact_id = ?', [$accountId, $contactId]);
            
            $logger->activity('Contact removed from account', 'account_contacts', null, [
                'account_id' => $accountId,
                'contact_id' => $contactId
            ], null);
            
            $success = 'Contact removed successfully!';
            
        } elseif ($action === 'create_project') {
            $projectData = [
                'project_name' => $_POST['project_name'],
                'account_id' => $accountId,
                'contact_id' => $_POST['contact_id'] ?? null,
                'address' => $_POST['address'] ?? null,
                'start_date' => $_POST['start_date'] ?? null,
                'closing_date' => $_POST['closing_date'] ?? null,
                'feedback' => $_POST['feedback'] ?? null,
                'need_visit' => isset($_POST['need_visit']) ? 1 : 0,
                'visit_date' => $_POST['visit_date'] ?? null,
                'visit_reason' => $_POST['visit_reason'] ?? null,
                'project_phase' => $_POST['project_phase'] ?? 'Planning',
                'project_state' => $_POST['project_state'] ?? 'Pre-started'
            ];
            
            $projectId = $db->insert('projects', $projectData);
            
            $logger->activity('Project created', 'projects', $projectId, null, $projectData);
            $success = 'Project created successfully!';
        }
        
        // Refresh data
        $relatedContacts = $db->fetchAll("
            SELECT c.*, ac.relationship_type 
            FROM contacts c
            JOIN account_contacts ac ON c.id = ac.contact_id
            WHERE ac.account_id = ?
            ORDER BY ac.relationship_type, c.contact_name
        ", [$accountId]);
        
        $relatedProjects = $db->fetchAll("
            SELECT p.*, c.contact_name 
            FROM projects p
            LEFT JOIN contacts c ON p.contact_id = c.id
            WHERE p.account_id = ?
            ORDER BY p.created_at DESC
        ", [$accountId]);
        
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
        $logger->error('Account details operation failed', ['error' => $e->getMessage()]);
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1><i class="fas fa-building"></i> <?php echo htmlspecialchars($account['account_name']); ?></h1>
                <p class="text-muted mb-0">
                    <span class="badge bg-primary"><?php echo htmlspecialchars($account['code']); ?></span>
                    Created on <?php echo date('M j, Y', strtotime($account['created_at'])); ?>
                </p>
            </div>
            <div>
                <a href="accounts.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Accounts
                </a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                    <i class="fas fa-plus"></i> Create Project
                </button>
            </div>
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

<!-- Account Information -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Account Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($account['phone'] ?? 'Not provided'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($account['email'] ?? 'Not provided'); ?></p>
                        <p><strong>Region:</strong> <?php echo htmlspecialchars($account['region'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>City:</strong> <?php echo htmlspecialchars($account['city'] ?? 'Not provided'); ?></p>
                        <p><strong>Country:</strong> <?php echo htmlspecialchars($account['country'] ?? 'Not provided'); ?></p>
                    </div>
                </div>
                <?php if ($account['address']): ?>
                    <p><strong>Address:</strong> 
                        <?php echo htmlspecialchars($account['address']); ?>
                        <a href="#" onclick="openGoogleMaps('<?php echo htmlspecialchars($account['address']); ?>')" 
                           class="btn btn-sm btn-outline-primary ms-2">
                            <i class="fas fa-map-marker-alt"></i> View on Map
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Statistics</h6>
            </div>
            <div class="card-body text-center">
                <div class="row">
                    <div class="col-6">
                        <h4 class="text-primary"><?php echo count($relatedProjects); ?></h4>
                        <small class="text-muted">Projects</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success"><?php echo count($relatedContacts); ?></h4>
                        <small class="text-muted">Contacts</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Related Contacts -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-users"></i> Related Contacts</h6>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
                    <i class="fas fa-plus"></i> Add Contact
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($relatedContacts)): ?>
                    <p class="text-muted">No contacts associated with this account.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped data-table">
                            <thead>
                                <tr>
                                    <th>Contact Name</th>
                                    <th>Phone Number</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Job Title</th>
                                    <th>Relationship</th>
                                    <th>Address</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatedContacts as $contact): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($contact['contact_name']); ?></td>
                                        <td><?php echo htmlspecialchars($contact['phone_number'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($contact['email'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($contact['department'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($contact['job_title'] ?? '-'); ?></td>
                                        <td><span class="badge bg-info"><?php echo htmlspecialchars($contact['relationship_type']); ?></span></td>
                                        <td>
                                            <?php if ($contact['address']): ?>
                                                <a href="#" onclick="openGoogleMaps('<?php echo htmlspecialchars($contact['address']); ?>')" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-map-marker-alt"></i> View
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="remove_contact">
                                                <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('Are you sure you want to remove this contact?')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                <h6 class="mb-0"><i class="fas fa-project-diagram"></i> Related Projects</h6>
            </div>
            <div class="card-body">
                <?php if (empty($relatedProjects)): ?>
                    <p class="text-muted">No projects associated with this account.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped data-table">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Contact Person</th>
                                    <th>Start Date</th>
                                    <th>Closing Date</th>
                                    <th>Phase</th>
                                    <th>State</th>
                                    <th>Need Visit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatedProjects as $project): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                        <td><?php echo htmlspecialchars($project['contact_name'] ?? '-'); ?></td>
                                        <td><?php echo $project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : '-'; ?></td>
                                        <td><?php echo $project['closing_date'] ? date('M j, Y', strtotime($project['closing_date'])) : '-'; ?></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($project['project_phase']); ?></span></td>
                                        <td><span class="badge bg-info"><?php echo htmlspecialchars($project['project_state']); ?></span></td>
                                        <td>
                                            <?php if ($project['need_visit']): ?>
                                                <span class="badge bg-warning">Yes</span>
                                                <?php if ($project['visit_date']): ?>
                                                    <br><small><?php echo date('M j, Y', strtotime($project['visit_date'])); ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-success">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="project_details.php?id=<?php echo $project['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Contact Modal -->
<div class="modal fade" id="addContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Add Contact to Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_contact">
                    
                    <div class="mb-3">
                        <label for="contact_id" class="form-label">Contact *</label>
                        <select class="form-select" id="contact_id" name="contact_id" required>
                            <option value="">Select Contact</option>
                            <?php foreach ($allContacts as $contact): ?>
                                <option value="<?php echo $contact['id']; ?>">
                                    <?php echo htmlspecialchars($contact['contact_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="relationship_type" class="form-label">Relationship Type</label>
                        <select class="form-select" id="relationship_type" name="relationship_type">
                            <option value="Primary">Primary</option>
                            <option value="Secondary">Secondary</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Project Modal -->
<div class="modal fade" id="createProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Create New Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_project">
                    
                    <div class="mb-3">
                        <label for="project_name" class="form-label">Project Name *</label>
                        <input type="text" class="form-control" id="project_name" name="project_name" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="contact_id" class="form-label">Contact Person</label>
                            <select class="form-select" id="contact_id" name="contact_id">
                                <option value="">Select Contact</option>
                                <?php foreach ($relatedContacts as $contact): ?>
                                    <option value="<?php echo $contact['id']; ?>">
                                        <?php echo htmlspecialchars($contact['contact_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="address" class="form-label">Project Address</label>
                            <input type="text" class="form-control" id="address" name="address">
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
                    <button type="submit" class="btn btn-primary">Create Project</button>
                </div>
            </form>
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
</script>

<?php require_once 'includes/footer.php'; ?>
