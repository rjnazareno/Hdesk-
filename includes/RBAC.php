<?php
/**
 * RBAC (Role-Based Access Control) Helper
 * Centralized permission checking for the entire application
 * 
 * Usage in controllers:
 *   $rbac = new RBAC();
 *   $rbac->requirePermission('tickets.assign');
 *   
 * Usage in views (via global function):
 *   if (can('tickets.assign')) { ... }
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Permission.php';

class RBAC {
    private static $instance = null;
    private $permissions = null;
    private $role = null;
    private $roleModel;
    private $permissionModel;
    private $userDepartments = null;
    
    public function __construct() {
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
    }
    
    /**
     * Get singleton instance
     * 
     * @return RBAC
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load user permissions into session/cache
     * Call this after login
     * 
     * @param int $userId User ID
     * @return void
     */
    public function loadUserPermissions($userId) {
        // Get user's role
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT u.*, r.slug as role_slug, r.hierarchy_level, r.name as role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !$user['role_id']) {
            $this->permissions = [];
            $this->role = null;
            return;
        }
        
        // Store role info
        $this->role = [
            'id' => $user['role_id'],
            'slug' => $user['role_slug'],
            'name' => $user['role_name'],
            'hierarchy_level' => $user['hierarchy_level']
        ];
        
        // Get all permissions for this role
        $this->permissions = $this->roleModel->getPermissionSlugs($user['role_id']);
        
        // Cache in session for performance
        $_SESSION['rbac_role'] = $this->role;
        $_SESSION['rbac_permissions'] = $this->permissions;
        $_SESSION['rbac_loaded'] = true;
    }
    
    /**
     * Load user's assigned departments
     * 
     * @param int $userId User ID
     * @return array Department IDs
     */
    public function loadUserDepartments($userId) {
        if ($this->userDepartments !== null) {
            return $this->userDepartments;
        }
        
        // Check session cache
        if (isset($_SESSION['rbac_departments'])) {
            $this->userDepartments = $_SESSION['rbac_departments'];
            return $this->userDepartments;
        }
        
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT department_id FROM user_departments WHERE user_id = :user_id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $this->userDepartments = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Cache in session
        $_SESSION['rbac_departments'] = $this->userDepartments;
        
        return $this->userDepartments;
    }
    
    /**
     * Get cached permissions (from session or memory)
     * 
     * @return array Permission slugs
     */
    private function getPermissions() {
        if ($this->permissions !== null) {
            return $this->permissions;
        }
        
        if (isset($_SESSION['rbac_permissions'])) {
            $this->permissions = $_SESSION['rbac_permissions'];
            return $this->permissions;
        }
        
        return [];
    }
    
    /**
     * Get cached role
     * 
     * @return array|null Role data
     */
    public function getRole() {
        if ($this->role !== null) {
            return $this->role;
        }
        
        if (isset($_SESSION['rbac_role'])) {
            $this->role = $_SESSION['rbac_role'];
            return $this->role;
        }
        
        return null;
    }
    
    /**
     * Check if current user has a specific permission
     * 
     * @param string $permissionSlug Permission slug (e.g., 'tickets.assign')
     * @return bool True if user has permission
     */
    public function can($permissionSlug) {
        $permissions = $this->getPermissions();
        return in_array($permissionSlug, $permissions);
    }
    
    /**
     * Check if current user has ANY of the given permissions
     * 
     * @param array $permissionSlugs Array of permission slugs
     * @return bool True if user has at least one permission
     */
    public function canAny(array $permissionSlugs) {
        $permissions = $this->getPermissions();
        return count(array_intersect($permissionSlugs, $permissions)) > 0;
    }
    
    /**
     * Check if current user has ALL of the given permissions
     * 
     * @param array $permissionSlugs Array of permission slugs
     * @return bool True if user has all permissions
     */
    public function canAll(array $permissionSlugs) {
        $permissions = $this->getPermissions();
        return count(array_intersect($permissionSlugs, $permissions)) === count($permissionSlugs);
    }
    
    /**
     * Check if current user is Super Admin
     * 
     * @return bool
     */
    public function isSuperAdmin() {
        $role = $this->getRole();
        return $role && $role['slug'] === Role::SUPER_ADMIN;
    }
    
    /**
     * Check if current user is Department Admin
     * 
     * @return bool
     */
    public function isDeptAdmin() {
        $role = $this->getRole();
        return $role && $role['slug'] === Role::DEPT_ADMIN;
    }
    
    /**
     * Check if current user is IT Staff
     * 
     * @return bool
     */
    public function isStaff() {
        $role = $this->getRole();
        return $role && in_array($role['slug'], [Role::IT_STAFF, Role::DEPT_ADMIN, Role::SUPER_ADMIN]);
    }
    
    /**
     * Check if current user is Employee (lowest level)
     * 
     * @return bool
     */
    public function isEmployee() {
        $role = $this->getRole();
        return $role && $role['slug'] === Role::EMPLOYEE;
    }
    
    /**
     * Check if user has access to a specific department
     * 
     * @param int $departmentId Department ID
     * @return bool True if user can access department
     */
    public function canAccessDepartment($departmentId) {
        // Super Admin can access all departments
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return false;
        }
        
        $departments = $this->loadUserDepartments($userId);
        return in_array($departmentId, $departments);
    }
    
    /**
     * Get departments the current user can access
     * 
     * @return array Department IDs (empty array = all for Super Admin)
     */
    public function getAccessibleDepartments() {
        // Super Admin can access all - return empty to indicate "all"
        if ($this->isSuperAdmin()) {
            return []; // Empty means "all" in queries
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return [-1]; // Return impossible ID to return no results
        }
        
        return $this->loadUserDepartments($userId);
    }
    
    /**
     * Check if user can manage another user (based on hierarchy)
     * 
     * @param int $targetUserId User ID to manage
     * @return bool True if current user can manage target user
     */
    public function canManageUser($targetUserId) {
        // Can't manage yourself through this method
        $currentUserId = $_SESSION['user_id'] ?? null;
        if ($currentUserId === $targetUserId) {
            return false;
        }
        
        $currentRole = $this->getRole();
        if (!$currentRole) {
            return false;
        }
        
        // Get target user's role
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT r.hierarchy_level 
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $targetUserId]);
        $targetLevel = $stmt->fetchColumn();
        
        if ($targetLevel === false) {
            return false;
        }
        
        // Can only manage users with lower hierarchy level
        return $currentRole['hierarchy_level'] > $targetLevel;
    }
    
    /**
     * Require a specific permission - redirect if not authorized
     * 
     * @param string $permissionSlug Permission slug
     * @param string $redirectUrl URL to redirect if unauthorized
     * @return void
     */
    public function requirePermission($permissionSlug, $redirectUrl = null) {
        if (!$this->can($permissionSlug)) {
            $this->handleUnauthorized($redirectUrl);
        }
    }
    
    /**
     * Require ANY of the given permissions
     * 
     * @param array $permissionSlugs Permission slugs
     * @param string $redirectUrl URL to redirect if unauthorized
     * @return void
     */
    public function requireAnyPermission(array $permissionSlugs, $redirectUrl = null) {
        if (!$this->canAny($permissionSlugs)) {
            $this->handleUnauthorized($redirectUrl);
        }
    }
    
    /**
     * Require Super Admin role
     * 
     * @param string $redirectUrl URL to redirect if unauthorized
     * @return void
     */
    public function requireSuperAdmin($redirectUrl = null) {
        if (!$this->isSuperAdmin()) {
            $this->handleUnauthorized($redirectUrl);
        }
    }
    
    /**
     * Require Department Admin or higher
     * 
     * @param string $redirectUrl URL to redirect if unauthorized
     * @return void
     */
    public function requireDeptAdminOrHigher($redirectUrl = null) {
        $role = $this->getRole();
        if (!$role || $role['hierarchy_level'] < Role::LEVEL_DEPT_ADMIN) {
            $this->handleUnauthorized($redirectUrl);
        }
    }
    
    /**
     * Require access to a specific department
     * 
     * @param int $departmentId Department ID
     * @param string $redirectUrl URL to redirect if unauthorized
     * @return void
     */
    public function requireDepartmentAccess($departmentId, $redirectUrl = null) {
        if (!$this->canAccessDepartment($departmentId)) {
            $this->handleUnauthorized($redirectUrl);
        }
    }
    
    /**
     * Handle unauthorized access
     * 
     * @param string|null $redirectUrl Custom redirect URL
     * @return void
     */
    private function handleUnauthorized($redirectUrl = null) {
        $_SESSION['error'] = "Access denied. You don't have permission to perform this action.";
        
        if ($redirectUrl) {
            redirect($redirectUrl);
        }
        
        // Default redirect based on role
        $role = $this->getRole();
        if ($role) {
            if ($role['hierarchy_level'] >= Role::LEVEL_DEPT_ADMIN) {
                redirect('admin/dashboard.php');
            } else if ($role['hierarchy_level'] >= Role::LEVEL_IT_STAFF) {
                redirect('admin/it_dashboard.php');
            }
        }
        
        redirect('customer/dashboard.php');
    }
    
    /**
     * Clear cached RBAC data (call on logout)
     * 
     * @return void
     */
    public function clearCache() {
        $this->permissions = null;
        $this->role = null;
        $this->userDepartments = null;
        
        unset($_SESSION['rbac_role']);
        unset($_SESSION['rbac_permissions']);
        unset($_SESSION['rbac_departments']);
        unset($_SESSION['rbac_loaded']);
    }
    
    /**
     * Get role badge HTML for display
     * 
     * @param string|null $roleSlug Role slug (null = current user)
     * @return string HTML badge
     */
    public function getRoleBadge($roleSlug = null) {
        if ($roleSlug === null) {
            $role = $this->getRole();
            $roleSlug = $role ? $role['slug'] : 'employee';
        }
        
        $badges = [
            'super_admin' => '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">Super Admin</span>',
            'dept_admin' => '<span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded">Dept Admin</span>',
            'it_staff' => '<span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded">IT Staff</span>',
            'employee' => '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">Employee</span>',
        ];
        
        return $badges[$roleSlug] ?? $badges['employee'];
    }
}

// =====================================================
// GLOBAL HELPER FUNCTIONS FOR VIEWS
// =====================================================

/**
 * Check if current user has a permission (shorthand)
 * 
 * @param string $permission Permission slug
 * @return bool
 */
function can($permission) {
    return RBAC::getInstance()->can($permission);
}

/**
 * Check if current user has any of the permissions
 * 
 * @param array $permissions Permission slugs
 * @return bool
 */
function canAny(array $permissions) {
    return RBAC::getInstance()->canAny($permissions);
}

/**
 * Check if current user is Super Admin
 * 
 * @return bool
 */
function isSuperAdmin() {
    return RBAC::getInstance()->isSuperAdmin();
}

/**
 * Check if current user is Department Admin
 * 
 * @return bool
 */
function isDeptAdmin() {
    return RBAC::getInstance()->isDeptAdmin();
}

/**
 * Check if current user can access department
 * 
 * @param int $departmentId Department ID
 * @return bool
 */
function canAccessDepartment($departmentId) {
    return RBAC::getInstance()->canAccessDepartment($departmentId);
}

/**
 * Get role badge HTML
 * 
 * @param string|null $roleSlug Role slug
 * @return string HTML badge
 */
function roleBadge($roleSlug = null) {
    return RBAC::getInstance()->getRoleBadge($roleSlug);
}
