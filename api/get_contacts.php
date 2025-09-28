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
    
    // Get contacts related to the account
    $contacts = $db->fetchAll("
        SELECT c.* 
        FROM contacts c
        JOIN account_contacts ac ON c.id = ac.contact_id
        WHERE ac.account_id = ?
        ORDER BY c.contact_name
    ", [$accountId]);
    
    echo json_encode($contacts);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
