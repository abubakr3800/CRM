<?php
require_once '../config/database.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    $auth->requireLogin();
    
    $db = new Database();
    $currentUser = $auth->getCurrentUser();
    
    $notifications = [];
    
    // Get upcoming visits (next 7 days)
    $upcoming_visits = $db->fetchAll("
        SELECT p.*, a.account_name 
        FROM projects p 
        LEFT JOIN accounts a ON p.account_id = a.id 
        WHERE p.need_visit = 1 
        AND p.visit_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY p.visit_date ASC
    ");
    
    foreach ($upcoming_visits as $visit) {
        $notifications[] = [
            'title' => 'Upcoming Visit',
            'message' => "Visit scheduled for {$visit['account_name']} - {$visit['project_name']} on " . date('M j, Y', strtotime($visit['visit_date'])),
            'time' => date('M j, Y', strtotime($visit['visit_date'])),
            'type' => 'info'
        ];
    }
    
    // Get overdue visits
    $overdue_visits = $db->fetchAll("
        SELECT p.*, a.account_name 
        FROM projects p 
        LEFT JOIN accounts a ON p.account_id = a.id 
        WHERE p.need_visit = 1 AND p.visit_date < CURDATE()
        ORDER BY p.visit_date ASC
    ");
    
    foreach ($overdue_visits as $visit) {
        $notifications[] = [
            'title' => 'Overdue Visit',
            'message' => "Visit overdue for {$visit['account_name']} - {$visit['project_name']} (was due " . date('M j, Y', strtotime($visit['visit_date'])) . ")",
            'time' => date('M j, Y', strtotime($visit['visit_date'])),
            'type' => 'warning'
        ];
    }
    
    // Get overdue tasks
    $overdue_tasks = $db->fetchAll("
        SELECT t.*, p.project_name, u.full_name as assigned_to_name
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.due_date < CURDATE() AND t.status != 'Done'
        ORDER BY t.due_date ASC
    ");
    
    foreach ($overdue_tasks as $task) {
        $notifications[] = [
            'title' => 'Overdue Task',
            'message' => "Task '{$task['task_name']}' is overdue (was due " . date('M j, Y', strtotime($task['due_date'])) . ")",
            'time' => date('M j, Y', strtotime($task['due_date'])),
            'type' => 'danger'
        ];
    }
    
    // Get global messages
    $global_messages = $db->fetchAll("
        SELECT m.*, u.full_name as sender_name
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE m.is_global = 1 AND m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY m.created_at DESC
    ");
    
    foreach ($global_messages as $message) {
        $notifications[] = [
            'title' => 'Message from ' . $message['sender_name'],
            'message' => $message['subject'] . ': ' . substr($message['message'], 0, 100) . '...',
            'time' => date('M j, Y H:i', strtotime($message['created_at'])),
            'type' => 'info'
        ];
    }
    
    // Sort notifications by time (most recent first)
    usort($notifications, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    
    // Limit to 10 notifications
    $notifications = array_slice($notifications, 0, 10);
    
    echo json_encode($notifications);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load notifications']);
}
?>
