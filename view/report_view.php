<?php
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/Database.php';

$reportId = (int)($_GET['id'] ?? 0);

if (!$reportId) {
    header('Location: ../reports.php');
    exit;
}

try {
    $db = new Database();
    
    // Get report details
    $report = $db->fetchOne("
        SELECT cr.*, u.full_name as created_by_name
        FROM custom_reports cr
        LEFT JOIN users u ON cr.created_by = u.id
        WHERE cr.id = ?
    ", [$reportId]);
    
    if (!$report) {
        header('Location: ../reports.php');
        exit;
    }
    
    // Get filtered data
    $filters = json_decode($report['filters'], true);
    $data = getFilteredData($db, $report['report_type'], $filters);
    
} catch (Exception $e) {
    error_log("Error loading report view: " . $e->getMessage());
    header('Location: ../reports.php');
    exit;
}

function getFilteredData($db, $reportType, $filters) {
    $tableMap = [
        'accounts' => 'accounts',
        'contacts' => 'contacts',
        'projects' => 'projects',
        'price_offers' => 'price_offers'
    ];
    
    $table = $tableMap[$reportType] ?? null;
    if (!$table) {
        return [];
    }
    
    $sql = "SELECT * FROM $table WHERE 1=1";
    $params = [];
    
    // Apply filters
    foreach ($filters as $filter) {
        if (isset($filter['field']) && isset($filter['value']) && !empty($filter['value'])) {
            $field = $filter['field'];
            $operator = $filter['operator'] ?? 'equals';
            $value = $filter['value'];
            
            switch ($operator) {
                case 'equals':
                    $sql .= " AND $field = ?";
                    $params[] = $value;
                    break;
                case 'contains':
                    $sql .= " AND $field LIKE ?";
                    $params[] = "%$value%";
                    break;
                case 'starts_with':
                    $sql .= " AND $field LIKE ?";
                    $params[] = "$value%";
                    break;
                case 'greater_than':
                    $sql .= " AND $field > ?";
                    $params[] = $value;
                    break;
                case 'less_than':
                    $sql .= " AND $field < ?";
                    $params[] = $value;
                    break;
            }
        }
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 1000";
    
    return $db->fetchAll($sql, $params);
}
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-chart-bar"></i> <?php echo htmlspecialchars($report['report_name']); ?></h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="../reports.php">Reports</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($report['report_name']); ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="../reports.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Reports
                </a>
                <button type="button" class="btn btn-primary" onclick="exportReport()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Report Info -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h5>Report Information</h5>
                        <p class="text-muted"><?php echo htmlspecialchars($report['description']); ?></p>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <strong>Type:</strong> <?php echo ucfirst($report['report_type']); ?><br>
                                    <strong>Created by:</strong> <?php echo htmlspecialchars($report['created_by_name']); ?><br>
                                    <strong>Created:</strong> <?php echo date('M j, Y H:i', strtotime($report['created_at'])); ?>
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <strong>Records found:</strong> <?php echo count($data); ?><br>
                                    <strong>Visibility:</strong> 
                                    <?php if ($report['is_public']): ?>
                                        <span class="badge bg-success">Public</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Private</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6>Applied Filters</h6>
                        <?php if (!empty($filters)): ?>
                            <div class="filter-list">
                                <?php foreach ($filters as $filter): ?>
                                    <?php if (isset($filter['field']) && isset($filter['value']) && !empty($filter['value'])): ?>
                                        <span class="badge bg-info me-1 mb-1">
                                            <?php echo htmlspecialchars($filter['field']); ?>: <?php echo htmlspecialchars($filter['value']); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No filters applied</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-table"></i> Report Data</h5>
            </div>
            <div class="card-body">
                <?php if (count($data) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped" id="reportDataTable">
                            <thead>
                                <?php if ($report['report_type'] === 'accounts'): ?>
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
                                <?php elseif ($report['report_type'] === 'contacts'): ?>
                                    <tr>
                                        <th>ID</th>
                                        <th>Contact Name</th>
                                        <th>Job Title</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                <?php elseif ($report['report_type'] === 'projects'): ?>
                                    <tr>
                                        <th>ID</th>
                                        <th>Project Name</th>
                                        <th>Phase</th>
                                        <th>State</th>
                                        <th>Start Date</th>
                                        <th>Closing Date</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                <?php elseif ($report['report_type'] === 'price_offers'): ?>
                                    <tr>
                                        <th>ID</th>
                                        <th>Offer Code</th>
                                        <th>Status</th>
                                        <th>Total Amount</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                <?php endif; ?>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <?php if ($report['report_type'] === 'accounts'): ?>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['code']); ?></td>
                                            <td><?php echo htmlspecialchars($row['account_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['city']); ?></td>
                                            <td><?php echo htmlspecialchars($row['country']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <a href="account_view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        <?php elseif ($report['report_type'] === 'contacts'): ?>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['contact_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['job_title']); ?></td>
                                            <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <a href="contact_view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        <?php elseif ($report['report_type'] === 'projects'): ?>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['project_name']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($row['project_phase']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $row['project_state'] === 'Started' ? 'success' : ($row['project_state'] === 'Finished' ? 'secondary' : 'warning'); ?>">
                                                    <?php echo htmlspecialchars($row['project_state']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $row['start_date'] ? date('Y-m-d', strtotime($row['start_date'])) : '-'; ?></td>
                                            <td><?php echo $row['closing_date'] ? date('Y-m-d', strtotime($row['closing_date'])) : '-'; ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <a href="project_view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        <?php elseif ($report['report_type'] === 'price_offers'): ?>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['offer_code']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $row['status'] === 'Accepted' ? 'success' : 
                                                        ($row['status'] === 'Rejected' ? 'danger' : 
                                                        ($row['status'] === 'Sent' ? 'info' : 'secondary')); 
                                                ?>">
                                                    <?php echo htmlspecialchars($row['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($row['total_amount'], 2); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <a href="price_offer_view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No Data Found</h5>
                        <p class="text-muted">No records match the applied filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize DataTable
$(document).ready(function() {
    $('#reportDataTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});

// Export report functionality
function exportReport() {
    // Trigger DataTable export
    $('#reportDataTable').DataTable().button('.buttons-excel').trigger();
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
