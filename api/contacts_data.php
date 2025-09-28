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
        1 => 'contact_name',
        2 => 'phone_number',
        3 => 'email',
        4 => 'department',
        5 => 'job_title',
        6 => 'created_at'
    ];
    
    $orderBy = $columns[$orderColumn] ?? 'id';
    $orderDirection = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
    
    // Build search condition
    $searchCondition = '';
    $searchParams = [];
    
    if (!empty($searchValue)) {
        $searchCondition = "WHERE (
            contact_name LIKE :search OR 
            phone_number LIKE :search OR 
            email LIKE :search OR 
            department LIKE :search OR 
            job_title LIKE :search
        )";
        $searchParams[':search'] = "%{$searchValue}%";
    }
    
    // Get total records count
    $totalRecordsQuery = "SELECT COUNT(*) as total FROM contacts";
    $stmt = $pdo->prepare($totalRecordsQuery);
    $stmt->execute();
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get filtered records count
    $filteredRecordsQuery = "SELECT COUNT(*) as total FROM contacts {$searchCondition}";
    $stmt = $pdo->prepare($filteredRecordsQuery);
    $stmt->execute($searchParams);
    $filteredRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get paginated data
    $dataQuery = "
        SELECT id, contact_name, phone_number, email, department, job_title, created_at 
        FROM contacts 
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
            htmlspecialchars($row['contact_name']),
            htmlspecialchars($row['phone_number']),
            htmlspecialchars($row['email']),
            htmlspecialchars($row['department']),
            htmlspecialchars($row['job_title']),
            date('Y-m-d', strtotime($row['created_at'])),
            '<div class="btn-group" role="group">
                <a href="view/contact_view.php?id=' . $row['id'] . '" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye"></i> View
                </a>
                <a href="contacts.php?action=edit&id=' . $row['id'] . '" class="btn btn-sm btn-outline-secondary">
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
