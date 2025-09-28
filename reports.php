<?php
require_once 'includes/header.php';
require_once 'includes/Database.php';

$db = new Database();

try {
    // Get all custom reports
    $reports = $db->fetchAll("
        SELECT cr.*, u.full_name as created_by_name
        FROM custom_reports cr
        LEFT JOIN users u ON cr.created_by = u.id
        ORDER BY cr.created_at DESC
    ");
    
    // Get report counts
    $reportCounts = [
        'total' => count($reports),
        'accounts' => count(array_filter($reports, function($r) { return $r['report_type'] === 'accounts'; })),
        'contacts' => count(array_filter($reports, function($r) { return $r['report_type'] === 'contacts'; })),
        'projects' => count(array_filter($reports, function($r) { return $r['report_type'] === 'projects'; })),
        'price_offers' => count(array_filter($reports, function($r) { return $r['report_type'] === 'price_offers'; }))
    ];
    
} catch (Exception $e) {
    error_log("Error loading reports: " . $e->getMessage());
    $reports = [];
    $reportCounts = ['total' => 0, 'accounts' => 0, 'contacts' => 0, 'projects' => 0, 'price_offers' => 0];
}
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-chart-bar"></i> Custom Reports</h2>
                <p class="text-muted">Create and manage custom filtered data views</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createReportModal">
                    <i class="fas fa-plus"></i> Create New Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $reportCounts['total']; ?></h4>
                        <p class="mb-0">Total Reports</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-bar fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $reportCounts['accounts']; ?></h4>
                        <p class="mb-0">Account Reports</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $reportCounts['projects']; ?></h4>
                        <p class="mb-0">Project Reports</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-project-diagram fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $reportCounts['contacts']; ?></h4>
                        <p class="mb-0">Contact Reports</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reports List -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Saved Reports</h5>
            </div>
            <div class="card-body">
                <?php if (count($reports) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped" id="reportsTable">
                            <thead>
                                <tr>
                                    <th>Report Name</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Created By</th>
                                    <th>Created</th>
                                    <th>Visibility</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($report['report_name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $report['report_type'] === 'accounts' ? 'info' : 
                                                    ($report['report_type'] === 'projects' ? 'success' : 
                                                    ($report['report_type'] === 'contacts' ? 'warning' : 'primary')); 
                                            ?>">
                                                <?php echo ucfirst($report['report_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($report['description']); ?></td>
                                        <td><?php echo htmlspecialchars($report['created_by_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($report['created_at'])); ?></td>
                                        <td>
                                            <?php if ($report['is_public']): ?>
                                                <span class="badge bg-success">Public</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Private</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewReport(<?php echo $report['id']; ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                        onclick="editReport(<?php echo $report['id']; ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteReport(<?php echo $report['id']; ?>)">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No Custom Reports Yet</h5>
                        <p class="text-muted">Create your first custom report to get started.</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createReportModal">
                            <i class="fas fa-plus"></i> Create First Report
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Report Modal -->
<div class="modal fade" id="createReportModal" tabindex="-1" aria-labelledby="createReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createReportModalLabel">Create Custom Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createReportForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reportName" class="form-label">Report Name</label>
                                <input type="text" class="form-control" id="reportName" name="report_name" required 
                                       placeholder="e.g., Active Projects in Cairo">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reportType" class="form-label">Report Type</label>
                                <select class="form-select" id="reportType" name="report_type" required>
                                    <option value="">Select data type...</option>
                                    <option value="accounts">Accounts</option>
                                    <option value="contacts">Contacts</option>
                                    <option value="projects">Projects</option>
                                    <option value="price_offers">Price Offers</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reportDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="reportDescription" name="description" rows="3" 
                                  placeholder="Describe what this report shows..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Filters</label>
                        <div id="filtersContainer">
                            <p class="text-muted">Select a report type first to configure filters.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addFilterBtn" style="display: none;">
                            <i class="fas fa-plus"></i> Add Filter
                        </button>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="isPublic" name="is_public" value="1">
                            <label class="form-check-label" for="isPublic">
                                Make this report public (visible to all users)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Report type change handler
document.getElementById('reportType').addEventListener('change', function() {
    const reportType = this.value;
    const filtersContainer = document.getElementById('filtersContainer');
    const addFilterBtn = document.getElementById('addFilterBtn');
    
    if (reportType) {
        addFilterBtn.style.display = 'inline-block';
        loadFilterOptions(reportType);
    } else {
        addFilterBtn.style.display = 'none';
        filtersContainer.innerHTML = '<p class="text-muted">Select a report type first to configure filters.</p>';
    }
});

// Load filter options based on report type
function loadFilterOptions(reportType) {
    const filtersContainer = document.getElementById('filtersContainer');
    
    // Define available filters for each report type
    const filterOptions = {
        'accounts': [
            { field: 'account_name', label: 'Account Name', type: 'text' },
            { field: 'city', label: 'City', type: 'text' },
            { field: 'country', label: 'Country', type: 'text' },
            { field: 'created_at', label: 'Created Date', type: 'date' }
        ],
        'contacts': [
            { field: 'contact_name', label: 'Contact Name', type: 'text' },
            { field: 'job_title', label: 'Job Title', type: 'text' },
            { field: 'department', label: 'Department', type: 'text' },
            { field: 'created_at', label: 'Created Date', type: 'date' }
        ],
        'projects': [
            { field: 'project_name', label: 'Project Name', type: 'text' },
            { field: 'project_phase', label: 'Project Phase', type: 'select', options: ['Planning', 'Execution', 'Monitoring', 'Closure'] },
            { field: 'project_state', label: 'Project State', type: 'select', options: ['Pre-started', 'Started', 'Finished'] },
            { field: 'created_at', label: 'Created Date', type: 'date' }
        ],
        'price_offers': [
            { field: 'offer_code', label: 'Offer Code', type: 'text' },
            { field: 'status', label: 'Status', type: 'select', options: ['Draft', 'Sent', 'Accepted', 'Rejected'] },
            { field: 'total_amount', label: 'Total Amount', type: 'number' },
            { field: 'created_at', label: 'Created Date', type: 'date' }
        ]
    };
    
    const options = filterOptions[reportType] || [];
    filtersContainer.innerHTML = '<p class="text-muted">No filters added yet. Click "Add Filter" to start.</p>';
    
    // Store filter options for later use
    window.currentFilterOptions = options;
}

// Add filter functionality
document.getElementById('addFilterBtn').addEventListener('click', function() {
    const filtersContainer = document.getElementById('filtersContainer');
    const filterCount = filtersContainer.querySelectorAll('.filter-row').length;
    
    if (filterCount >= 5) {
        alert('Maximum 5 filters allowed per report.');
        return;
    }
    
    const filterRow = document.createElement('div');
    filterRow.className = 'filter-row border p-3 mb-2 rounded';
    filterRow.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <select class="form-select filter-field" name="filter_field[]">
                    <option value="">Select field...</option>
                    ${window.currentFilterOptions.map(opt => 
                        `<option value="${opt.field}">${opt.label}</option>`
                    ).join('')}
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select filter-operator" name="filter_operator[]">
                    <option value="equals">Equals</option>
                    <option value="contains">Contains</option>
                    <option value="starts_with">Starts with</option>
                    <option value="greater_than">Greater than</option>
                    <option value="less_than">Less than</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control filter-value" name="filter_value[]" placeholder="Filter value">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFilter(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    if (filtersContainer.innerHTML.includes('No filters added yet')) {
        filtersContainer.innerHTML = '';
    }
    
    filtersContainer.appendChild(filterRow);
});

// Remove filter functionality
function removeFilter(button) {
    button.closest('.filter-row').remove();
}

// Create report form submission
document.getElementById('createReportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'create_report');
    
    // Collect filters
    const filters = {};
    const filterRows = document.querySelectorAll('.filter-row');
    filterRows.forEach((row, index) => {
        const field = row.querySelector('.filter-field').value;
        const operator = row.querySelector('.filter-operator').value;
        const value = row.querySelector('.filter-value').value;
        
        if (field && value) {
            filters[`filter_${index}`] = {
                field: field,
                operator: operator,
                value: value
            };
        }
    });
    
    formData.append('filters', JSON.stringify(filters));
    
    fetch('api/reports.php', {
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
        alert('Error creating report. Please try again.');
    });
});

// View report functionality
function viewReport(reportId) {
    window.open(`view/report_view.php?id=${reportId}`, '_blank');
}

// Edit report functionality
function editReport(reportId) {
    // TODO: Implement edit functionality
    alert('Edit functionality coming soon!');
}

// Delete report functionality
function deleteReport(reportId) {
    if (confirm('Are you sure you want to delete this report?')) {
        const formData = new FormData();
        formData.append('action', 'delete_report');
        formData.append('report_id', reportId);
        
        fetch('api/reports.php', {
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
            alert('Error deleting report. Please try again.');
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
