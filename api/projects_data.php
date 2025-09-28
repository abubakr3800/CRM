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
    
    // Get DataTables parameters
    $draw = intval($_GET['draw'] ?? 1);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 25);
    $searchValue = $_GET['search']['value'] ?? '';
    $orderColumn = intval($_GET['order'][0]['column'] ?? 0);
    $orderDir = $_GET['order'][0]['dir'] ?? 'asc';
    
    // Define column mapping
    $columns = [
        0 => 'p.id',
        1 => 'p.project_name',
        2 => 'a.account_name',
        3 => 'p.start_date',
        4 => 'p.closing_date',
        5 => 'p.project_phase',
        6 => 'p.project_state',
        7 => 'p.created_at'
    ];
    
    $orderBy = $columns[$orderColumn] ?? 'p.id';
    $orderDirection = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
    
    // Build search condition
    $searchCondition = '';
    $searchParams = [];
    
    if (!empty($searchValue)) {
        $searchCondition = "WHERE (
            p.project_name LIKE :search OR 
            a.account_name LIKE :search OR 
            p.project_phase LIKE :search OR 
            p.project_state LIKE :search
        )";
        $searchParams[':search'] = "%{$searchValue}%";
    }
    
    // Get total records count
    $totalRecordsQuery = "SELECT COUNT(*) as total FROM projects p";
    $stmt = $pdo->prepare($totalRecordsQuery);
    $stmt->execute();
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get filtered records count
    $filteredRecordsQuery = "
        SELECT COUNT(*) as total 
        FROM projects p 
        LEFT JOIN accounts a ON p.account_id = a.id 
        {$searchCondition}
    ";
    $stmt = $pdo->prepare($filteredRecordsQuery);
    $stmt->execute($searchParams);
    $filteredRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get paginated data
    $dataQuery = "
        SELECT p.id, p.project_name, a.account_name, p.start_date, p.closing_date, 
               p.project_phase, p.project_state, p.created_at 
        FROM projects p 
        LEFT JOIN accounts a ON p.account_id = a.id 
        {$searchCondition}
        ORDER BY {$orderBy} {$orderDirection}
        LIMIT {$start}, {$length}
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    $stmt->execute($searchParams);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data for DataTables
    $formattedData = [];
    foreach ($data as $row) {
        $formattedData[] = [
            $row['id'],
            htmlspecialchars($row['project_name']),
            htmlspecialchars($row['account_name'] ?? 'No Account'),
            $row['start_date'] ? date('Y-m-d', strtotime($row['start_date'])) : '-',
            $row['closing_date'] ? date('Y-m-d', strtotime($row['closing_date'])) : '-',
            htmlspecialchars($row['project_phase']),
            htmlspecialchars($row['project_state']),
            date('Y-m-d', strtotime($row['created_at'])),
            '<div class="btn-group" role="group">
                <a href="view/project_view.php?id=' . $row['id'] . '" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye"></i> View
                </a>
                <a href="projects.php?action=edit&id=' . $row['id'] . '" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-edit"></i> Edit
                </a>
            </div>'
        ];
    }
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $formattedData
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred while processing your request.',
        'message' => $e->getMessage()
    ]);
}
?>
