<?php
require_once '../config/database.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    $auth->requireLogin();
    
    $db = new Database();
    $accountId = $_GET['account_id'] ?? null;
    
    if (!$accountId) {
        throw new Exception('Account ID is required');
    }
    
    // Get projects related to the account
    $projects = $db->fetchAll("
        SELECT p.* 
        FROM projects p
        WHERE p.account_id = ?
        ORDER BY p.project_name
    ", [$accountId]);
    
    echo json_encode($projects);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
