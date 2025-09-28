<?php
header('Content-Type: application/json');

try {
    // Direct database connection
    $host = 'localhost:3309';
    $dbname = 'shortcircuit_crm';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create_report':
                $reportName = $_POST['report_name'] ?? '';
                $description = $_POST['description'] ?? '';
                $reportType = $_POST['report_type'] ?? '';
                $filters = $_POST['filters'] ?? '{}';
                $isPublic = isset($_POST['is_public']) ? 1 : 0;
                $createdBy = 1; // TODO: Get from session/auth
                
                if (empty($reportName) || empty($reportType)) {
                    throw new Exception('Report name and type are required');
                }
                
                // Check if report name already exists
                $stmt = $pdo->prepare("SELECT id FROM custom_reports WHERE report_name = ?");
                $stmt->execute([$reportName]);
                if ($stmt->fetch()) {
                    throw new Exception('A report with this name already exists');
                }
                
                // Insert the new report
                $stmt = $pdo->prepare("
                    INSERT INTO custom_reports (report_name, description, report_type, filters, created_by, is_public) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$reportName, $description, $reportType, $filters, $createdBy, $isPublic]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Report created successfully',
                    'report_id' => $pdo->lastInsertId()
                ]);
                break;
                
            case 'delete_report':
                $reportId = (int)$_POST['report_id'];
                
                $stmt = $pdo->prepare("DELETE FROM custom_reports WHERE id = ?");
                $stmt->execute([$reportId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Report deleted successfully'
                ]);
                break;
                
            case 'get_report_data':
                $reportId = (int)$_GET['report_id'];
                
                // Get report details
                $stmt = $pdo->prepare("SELECT * FROM custom_reports WHERE id = ?");
                $stmt->execute([$reportId]);
                $report = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$report) {
                    throw new Exception('Report not found');
                }
                
                // Get filtered data based on report type and filters
                $filters = json_decode($report['filters'], true);
                $data = getFilteredData($pdo, $report['report_type'], $filters);
                
                echo json_encode([
                    'success' => true,
                    'report' => $report,
                    'data' => $data
                ]);
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action'
                ]);
                break;
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}

function getFilteredData($pdo, $reportType, $filters) {
    $tableMap = [
        'accounts' => 'accounts',
        'contacts' => 'contacts',
        'projects' => 'projects',
        'price_offers' => 'price_offers'
    ];
    
    $table = $tableMap[$reportType] ?? null;
    if (!$table) {
        throw new Exception('Invalid report type');
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
    
    $sql .= " ORDER BY created_at DESC LIMIT 1000"; // Limit to prevent huge results
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
