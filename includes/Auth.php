<?php
// Get the directory of the CRM root
$crmRoot = dirname(__DIR__);

require_once $crmRoot . '/includes/Database.php';
require_once $crmRoot . '/includes/Logger.php';

class Auth {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = new Database();
        $this->logger = new Logger();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function getDatabase() {
        return $this->db;
    }
    
    public function login($username, $password) {
        try {
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE username = ? OR email = ?",
                [$username, $username]
            );
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                $this->logger->info("User logged in", ['user_id' => $user['id'], 'username' => $username]);
                
                return true;
            }
            
            $this->logger->warning("Failed login attempt", ['username' => $username]);
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("Login error", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logger->info("User logged out", ['user_id' => $_SESSION['user_id']]);
        }
        
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public function requireRole($requiredRole) {
        $this->requireLogin();
        
        if ($_SESSION['role'] !== $requiredRole && $_SESSION['role'] !== 'admin') {
            header('Location: index.php?error=access_denied');
            exit;
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role']
        ];
    }
    
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['role'] === 'admin';
    }
    
    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            $user = $this->db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
            
            if (!$user || !password_verify($oldPassword, $user['password'])) {
                return false;
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
            
            $this->logger->info("Password changed", ['user_id' => $userId]);
            return true;
            
        } catch (Exception $e) {
            $this->logger->error("Password change error", ['error' => $e->getMessage()]);
            return false;
        }
    }
}
?>
