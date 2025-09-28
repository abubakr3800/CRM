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
    $length = intval($_GET['length'] ?? 10);
    $searchValue = $_GET['search']['value'] ?? '';
    $orderColumn = intval($_GET['order'][0]['column'] ?? 0);
    $orderDir = $_GET['order'][0]['dir'] ?? 'asc';
    
    // Define column mapping
    $columns = [
        0 => 'po.id',
        1 => 'po.offer_code',
        2 => 'a.account_name',
        3 => 'po.total_amount',
        4 => 'po.status',
        5 => 'po.offer_date',
        6 => 'po.created_at'
    ];
    
    $orderBy = $columns[$orderColumn] ?? 'po.id';
    $orderDirection = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
    
    // Build search condition
    $searchCondition = '';
    $searchParams = [];
    
    if (!empty($searchValue)) {
        $searchCondition = "WHERE (
            po.offer_code LIKE :search OR 
            a.account_name LIKE :search OR 
            po.status LIKE :search
        )";
        $searchParams[':search'] = "%{$searchValue}%";
    }
    
    // Get total records count
    $totalRecordsQuery = "SELECT COUNT(*) as total FROM price_offers po";
    $stmt = $pdo->prepare($totalRecordsQuery);
    $stmt->execute();
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get filtered records count
    $filteredRecordsQuery = "
        SELECT COUNT(*) as total 
        FROM price_offers po 
        LEFT JOIN accounts a ON po.account_id = a.id 
        {$searchCondition}
    ";
    $stmt = $pdo->prepare($filteredRecordsQuery);
    $stmt->execute($searchParams);
    $filteredRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get paginated data
    $dataQuery = "
        SELECT po.id, po.offer_code, a.account_name, po.total_amount, 
               po.status, po.offer_date, po.created_at 
        FROM price_offers po 
        LEFT JOIN accounts a ON po.account_id = a.id 
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
            htmlspecialchars($row['offer_code']),
            htmlspecialchars($row['account_name'] ?? 'No Account'),
            '$' . number_format($row['total_amount'], 2),
            '<span class="badge bg-' . getStatusColor($row['status']) . '">' . htmlspecialchars($row['status']) . '</span>',
            $row['offer_date'] ? date('Y-m-d', strtotime($row['offer_date'])) : '-',
            date('Y-m-d', strtotime($row['created_at'])),
            '<div class="btn-group" role="group">
                <a href="view/price_offer_view.php?id=' . $row['id'] . '" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye"></i> View
                </a>
                <a href="price_offers.php?action=edit&id=' . $row['id'] . '" class="btn btn-sm btn-outline-secondary">
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

function getStatusColor($status) {
    switch (strtolower($status)) {
        case 'draft': return 'secondary';
        case 'sent': return 'info';
        case 'accepted': return 'success';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
}
?>
