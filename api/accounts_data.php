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
        0 => 'id',
        1 => 'code',
        2 => 'account_name',
        3 => 'phone',
        4 => 'email',
        5 => 'city',
        6 => 'country',
        7 => 'created_at'
    ];
    
    $orderBy = $columns[$orderColumn] ?? 'id';
    $orderDirection = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
    
    // Build search condition
    $searchCondition = '';
    $searchParams = [];
    
    if (!empty($searchValue)) {
        $searchCondition = "WHERE (
            code LIKE :search OR 
            account_name LIKE :search OR 
            phone LIKE :search OR 
            email LIKE :search OR 
            city LIKE :search OR 
            country LIKE :search
        )";
        $searchParams[':search'] = "%{$searchValue}%";
    }
    
    // Get total records count
    $totalRecordsQuery = "SELECT COUNT(*) as total FROM accounts";
    $stmt = $pdo->prepare($totalRecordsQuery);
    $stmt->execute();
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get filtered records count
    $filteredRecordsQuery = "SELECT COUNT(*) as total FROM accounts {$searchCondition}";
    $stmt = $pdo->prepare($filteredRecordsQuery);
    $stmt->execute($searchParams);
    $filteredRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get paginated data
    $dataQuery = "
        SELECT id, code, account_name, phone, email, city, country, created_at 
        FROM accounts 
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
            $row['code'],
            htmlspecialchars($row['account_name']),
            htmlspecialchars($row['phone']),
            htmlspecialchars($row['email']),
            htmlspecialchars($row['city']),
            htmlspecialchars($row['country']),
            date('Y-m-d', strtotime($row['created_at'])),
            '<div class="btn-group" role="group">
                <a href="view/account_view.php?id=' . $row['id'] . '" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye"></i> View
                </a>
                <a href="accounts.php?action=edit&id=' . $row['id'] . '" class="btn btn-sm btn-outline-secondary">
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
