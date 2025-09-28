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
            case 'attach_contact':
                $projectId = (int)$_POST['project_id'];
                $contactId = (int)$_POST['contact_id'];
                $role = $_POST['role'] ?? 'Contact';
                $isPrimary = isset($_POST['is_primary']) ? 1 : 0;
                
                // If setting as primary, remove primary status from other contacts
                if ($isPrimary) {
                    $stmt = $pdo->prepare("UPDATE project_contacts SET is_primary = 0 WHERE project_id = ?");
                    $stmt->execute([$projectId]);
                }
                
                // Insert the new contact attachment
                $stmt = $pdo->prepare("
                    INSERT INTO project_contacts (project_id, contact_id, role, is_primary) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$projectId, $contactId, $role, $isPrimary]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Contact attached successfully'
                ]);
                break;
                
            case 'remove_contact':
                $projectContactId = (int)$_POST['project_contact_id'];
                
                $stmt = $pdo->prepare("DELETE FROM project_contacts WHERE id = ?");
                $stmt->execute([$projectContactId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Contact removed successfully'
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
?>
