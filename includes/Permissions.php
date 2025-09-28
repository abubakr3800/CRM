<?php
/**
 * Enhanced Permissions System for CRM
 * Provides granular role-based access control
 */

class Permissions {
    private $db;
    private $auth;
    
    // Define all available permissions
    const PERMISSIONS = [
        // User Management
        'users.view' => 'View Users',
        'users.create' => 'Create Users',
        'users.edit' => 'Edit Users',
        'users.delete' => 'Delete Users',
        
        // Account Management
        'accounts.view' => 'View Accounts',
        'accounts.create' => 'Create Accounts',
        'accounts.edit' => 'Edit Accounts',
        'accounts.delete' => 'Delete Accounts',
        
        // Contact Management
        'contacts.view' => 'View Contacts',
        'contacts.create' => 'Create Contacts',
        'contacts.edit' => 'Edit Contacts',
        'contacts.delete' => 'Delete Contacts',
        
        // Project Management
        'projects.view' => 'View Projects',
        'projects.create' => 'Create Projects',
        'projects.edit' => 'Edit Projects',
        'projects.delete' => 'Delete Projects',
        
        // Task Management
        'tasks.view' => 'View Tasks',
        'tasks.create' => 'Create Tasks',
        'tasks.edit' => 'Edit Tasks',
        'tasks.delete' => 'Delete Tasks',
        'tasks.assign' => 'Assign Tasks',
        
        // Price Offers
        'offers.view' => 'View Price Offers',
        'offers.create' => 'Create Price Offers',
        'offers.edit' => 'Edit Price Offers',
        'offers.delete' => 'Delete Price Offers',
        'offers.approve' => 'Approve Price Offers',
        
        // Reports
        'reports.view' => 'View Reports',
        'reports.export' => 'Export Reports',
        
        // System Administration
        'admin.backup' => 'Manage Backups',
        'admin.settings' => 'System Settings',
        'admin.logs' => 'View Activity Logs',
        'admin.users' => 'User Management',
        
        // Messages
        'messages.view' => 'View Messages',
        'messages.send' => 'Send Messages',
        'messages.global' => 'Send Global Messages',
        
        // Profile
        'profile.view' => 'View Own Profile',
        'profile.edit' => 'Edit Own Profile',
        'profile.password' => 'Change Own Password'
    ];
    
    // Define role permissions
    const ROLE_PERMISSIONS = [
        'admin' => [
            // Admins have all permissions
            'all'
        ],
        'manager' => [
            'users.view',
            'accounts.view', 'accounts.create', 'accounts.edit',
            'contacts.view', 'contacts.create', 'contacts.edit',
            'projects.view', 'projects.create', 'projects.edit',
            'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.assign',
            'offers.view', 'offers.create', 'offers.edit', 'offers.approve',
            'reports.view', 'reports.export',
            'messages.view', 'messages.send', 'messages.global',
            'profile.view', 'profile.edit', 'profile.password'
        ],
        'worker' => [
            'accounts.view',
            'contacts.view', 'contacts.create', 'contacts.edit',
            'projects.view', 'projects.create', 'projects.edit',
            'tasks.view', 'tasks.create', 'tasks.edit',
            'offers.view', 'offers.create', 'offers.edit',
            'reports.view',
            'messages.view', 'messages.send',
            'profile.view', 'profile.edit', 'profile.password'
        ],
        'viewer' => [
            'accounts.view',
            'contacts.view',
            'projects.view',
            'tasks.view',
            'offers.view',
            'reports.view',
            'messages.view',
            'profile.view', 'profile.edit', 'profile.password'
        ]
    ];
    
    public function __construct($database, $auth) {
        $this->db = $database;
        $this->auth = $auth;
    }
    
    /**
     * Check if current user has a specific permission
     */
    public function hasPermission($permission) {
        if (!$this->auth->isLoggedIn()) {
            return false;
        }
        
        $userRole = $_SESSION['role'];
        
        // Admin has all permissions
        if ($userRole === 'admin') {
            return true;
        }
        
        // Check if role has the permission
        if (isset(self::ROLE_PERMISSIONS[$userRole])) {
            $rolePermissions = self::ROLE_PERMISSIONS[$userRole];
            
            // Check for 'all' permission
            if (in_array('all', $rolePermissions)) {
                return true;
            }
            
            // Check for specific permission
            return in_array($permission, $rolePermissions);
        }
        
        return false;
    }
    
    /**
     * Require a specific permission (redirect if not authorized)
     */
    public function requirePermission($permission, $redirectUrl = 'index.php?error=access_denied') {
        if (!$this->hasPermission($permission)) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Get all permissions for current user
     */
    public function getUserPermissions() {
        if (!$this->auth->isLoggedIn()) {
            return [];
        }
        
        $userRole = $_SESSION['role'];
        
        if ($userRole === 'admin') {
            return array_keys(self::PERMISSIONS);
        }
        
        if (isset(self::ROLE_PERMISSIONS[$userRole])) {
            $rolePermissions = self::ROLE_PERMISSIONS[$userRole];
            
            if (in_array('all', $rolePermissions)) {
                return array_keys(self::PERMISSIONS);
            }
            
            return $rolePermissions;
        }
        
        return [];
    }
    
    /**
     * Get all available roles
     */
    public function getAvailableRoles() {
        return array_keys(self::ROLE_PERMISSIONS);
    }
    
    /**
     * Get permissions for a specific role
     */
    public function getRolePermissions($role) {
        if (isset(self::ROLE_PERMISSIONS[$role])) {
            return self::ROLE_PERMISSIONS[$role];
        }
        return [];
    }
    
    /**
     * Get all available permissions
     */
    public function getAllPermissions() {
        return self::PERMISSIONS;
    }
    
    /**
     * Check if user can access a specific page
     */
    public function canAccessPage($page) {
        $pagePermissions = [
            'admin.php' => 'admin.settings',
            'users.php' => 'users.view',
            'accounts.php' => 'accounts.view',
            'contacts.php' => 'contacts.view',
            'projects.php' => 'projects.view',
            'tasks.php' => 'tasks.view',
            'price_offers.php' => 'offers.view',
            'reports.php' => 'reports.view'
        ];
        
        if (isset($pagePermissions[$page])) {
            return $this->hasPermission($pagePermissions[$page]);
        }
        
        return true; // Default allow for pages without specific permissions
    }
    
    /**
     * Get user's role display name
     */
    public function getRoleDisplayName($role) {
        $roleNames = [
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'worker' => 'Worker',
            'viewer' => 'Viewer'
        ];
        
        return isset($roleNames[$role]) ? $roleNames[$role] : ucfirst($role);
    }
    
    /**
     * Check if user can edit a specific record
     */
    public function canEditRecord($table, $recordId = null) {
        $editPermissions = [
            'users' => 'users.edit',
            'accounts' => 'accounts.edit',
            'contacts' => 'contacts.edit',
            'projects' => 'projects.edit',
            'tasks' => 'tasks.edit',
            'price_offers' => 'offers.edit'
        ];
        
        if (isset($editPermissions[$table])) {
            return $this->hasPermission($editPermissions[$table]);
        }
        
        return false;
    }
    
    /**
     * Check if user can delete a specific record
     */
    public function canDeleteRecord($table, $recordId = null) {
        $deletePermissions = [
            'users' => 'users.delete',
            'accounts' => 'accounts.delete',
            'contacts' => 'contacts.delete',
            'projects' => 'projects.delete',
            'tasks' => 'tasks.delete',
            'price_offers' => 'offers.delete'
        ];
        
        if (isset($deletePermissions[$table])) {
            return $this->hasPermission($deletePermissions[$table]);
        }
        
        return false;
    }
}
?>
