<?php
// Get the directory of the CRM root
$crmRoot = dirname(__DIR__);

require_once $crmRoot . '/config/database.php';

class Logger {
    private $logPath;
    private $db;
    
    public function __construct() {
        $this->logPath = LOG_PATH;
        $this->db = new Database();
        
        // Create log directory if it doesn't exist
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    public function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}";
        
        if (!empty($context)) {
            $logEntry .= " | Context: " . json_encode($context);
        }
        
        $logEntry .= PHP_EOL;
        
        // Write to file
        $logFile = $this->logPath . date('Y-m-d') . '.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Write to database if it's an activity log
        if ($level === 'ACTIVITY') {
            $this->logToDatabase($message, $context);
        }
    }
    
    private function logToDatabase($action, $context) {
        try {
            $data = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'action' => $action,
                'table_name' => $context['table'] ?? null,
                'record_id' => $context['record_id'] ?? null,
                'old_values' => isset($context['old_values']) ? json_encode($context['old_values']) : null,
                'new_values' => isset($context['new_values']) ? json_encode($context['new_values']) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];
            
            $this->db->insert('activity_logs', $data);
        } catch (Exception $e) {
            error_log("Failed to log to database: " . $e->getMessage());
        }
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function debug($message, $context = []) {
        if (LOG_LEVEL === 'DEBUG') {
            $this->log('DEBUG', $message, $context);
        }
    }
    
    public function activity($action, $table = null, $recordId = null, $oldValues = null, $newValues = null) {
        $context = [
            'table' => $table,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues
        ];
        
        $this->log('ACTIVITY', $action, $context);
    }
    
    public function getLogs($date = null, $level = null) {
        $date = $date ?: date('Y-m-d');
        $logFile = $this->logPath . $date . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $logs = file($logFile, FILE_IGNORE_NEW_LINES);
        
        if ($level) {
            $logs = array_filter($logs, function($log) use ($level) {
                return strpos($log, "[{$level}]") !== false;
            });
        }
        
        return array_reverse($logs); // Most recent first
    }
}
?>
