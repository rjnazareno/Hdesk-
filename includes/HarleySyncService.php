<?php
/**
 * Harley HRIS Sync Service
 * Syncs employees from Harley HRIS system to ServiceDesk
 * 
 * Supports:
 * - Direct database connection (recommended for same hosting)
 * - API calls (for cross-domain)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/harley_config.php';
require_once __DIR__ . '/../models/Employee.php';

class HarleySyncService {
    private $harleyDb;
    private $localEmployeeModel;
    private $lastSyncTime;
    private $syncLog = [];
    
    public function __construct() {
        $this->localEmployeeModel = new Employee();
    }
    
    /**
     * Connect to Harley database
     * @return bool
     */
    public function connectToHarley() {
        $this->harleyDb = getHarleyConnection();
        return $this->harleyDb !== null;
    }
    
    /**
     * Fetch all employees from Harley
     * @return array
     */
    public function fetchHarleyEmployees() {
        if (!$this->harleyDb) {
            if (!$this->connectToHarley()) {
                throw new Exception("Cannot connect to Harley database");
            }
        }
        
        try {
            // Query matches exact Harley table structure
            // Harley uses status enum: 'active' or 'inactive'
            // Includes admin_rights_hdesk for admin access control
            $sql = "SELECT 
                        id,
                        fname,
                        lname,
                        email,
                        personal_email,
                        contact,
                        position,
                        company,
                        username,
                        password,
                        status,
                        role,
                        admin_rights_hdesk,
                        official_sched,
                        profile_picture,
                        created_at
                    FROM " . HARLEY_EMPLOYEES_TABLE . "
                    WHERE status = 'active'
                    ORDER BY lname, fname";
            
            $stmt = $this->harleyDb->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Harley Fetch Error: " . $e->getMessage());
            throw new Exception("Error fetching employees from Harley: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch employees updated since last sync
     * Note: Harley doesn't have updated_at column, so we use created_at only
     * For proper incremental sync, we compare all active employees
     * @param string $since DateTime string
     * @return array
     */
    public function fetchUpdatedEmployees($since = null) {
        if (!$this->harleyDb) {
            if (!$this->connectToHarley()) {
                throw new Exception("Cannot connect to Harley database");
            }
        }
        
        try {
            // Harley doesn't have updated_at, use created_at for new employees
            // For updates, we need to do a full comparison
            // Includes admin_rights_hdesk for admin access control
            $sql = "SELECT 
                        id,
                        fname,
                        lname,
                        email,
                        personal_email,
                        contact,
                        position,
                        company,
                        username,
                        password,
                        status,
                        role,
                        admin_rights_hdesk,
                        official_sched,
                        profile_picture,
                        created_at
                    FROM " . HARLEY_EMPLOYEES_TABLE . "
                    WHERE status = 'active'";
            $params = [];
            
            if ($since) {
                // Only get employees created since last sync
                $sql .= " AND created_at >= :since";
                $params[':since'] = $since;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->harleyDb->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Harley Fetch Error: " . $e->getMessage());
            throw new Exception("Error fetching updated employees: " . $e->getMessage());
        }
    }
    
    /**
     * Sync a single employee to local database
     * Syncs all fields including role and admin_rights_hdesk from Harley
     * 
     * @param array $harleyEmployee
     * @return array Result with status
     */
    public function syncEmployee($harleyEmployee) {
        $result = ['status' => 'unknown', 'message' => ''];
        
        try {
            // Check if employee exists locally by employee_id first
            $tempEmployeeId = 'HRLY-' . $harleyEmployee['id'];
            $existingEmployee = $this->localEmployeeModel->findByEmployeeId($tempEmployeeId);
            
            // Also check by username or email if not found
            if (!$existingEmployee && !empty($harleyEmployee['username'])) {
                $existingEmployee = $this->localEmployeeModel->findByUsername($harleyEmployee['username']);
            }
            if (!$existingEmployee && !empty($harleyEmployee['email'])) {
                $existingEmployee = $this->localEmployeeModel->findByEmail($harleyEmployee['email']);
            }
            
            // Map Harley fields to local fields (includes role and admin_rights_hdesk)
            $employeeData = $this->mapHarleyToLocal($harleyEmployee);
            
            if ($existingEmployee) {
                // Update existing employee - include password from Harley
                // If Harley password is empty, keep existing local password
                if (empty($employeeData['password'])) {
                    unset($employeeData['password']);
                }
                
                $updated = $this->localEmployeeModel->update(
                    $existingEmployee['id'],
                    $employeeData
                );
                
                $result['status'] = $updated ? 'updated' : 'unchanged';
                $result['message'] = $updated 
                    ? "Updated: {$employeeData['fname']} {$employeeData['lname']}"
                    : "No changes: {$employeeData['fname']} {$employeeData['lname']}";
            } else {
                // Create new employee - keep Harley password or set default
                if (empty($employeeData['password'])) {
                    $employeeData['password'] = password_hash('Welcome123!', PASSWORD_DEFAULT);
                }
                
                $newId = $this->localEmployeeModel->create($employeeData);
                
                $result['status'] = $newId ? 'created' : 'failed';
                $result['message'] = $newId 
                    ? "Created: {$employeeData['fname']} {$employeeData['lname']}"
                    : "Failed: {$employeeData['fname']} {$employeeData['lname']}";
            }
            
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = "Error syncing {$harleyEmployee['fname']} {$harleyEmployee['lname']}: " . $e->getMessage();
        }
        
        $this->syncLog[] = $result;
        return $result;
    }
    
    /**
     * Map Harley employee fields to local employee fields
     * Harley and ServiceDesk have almost identical structure
     * 
     * Admin Access Conditions:
     * - role = 'internal' AND admin_rights_hdesk IS NOT NULL = Admin access
     * - admin_rights_hdesk values: 'it', 'hr', 'superadmin'
     * 
     * @param array $harleyEmployee
     * @return array
     */
    private function mapHarleyToLocal($harleyEmployee) {
        // Hash the Harley password (Harley stores plain-text passwords)
        $password = null;
        if (!empty($harleyEmployee['password'])) {
            // Check if already hashed (starts with $2y$ for bcrypt)
            if (strpos($harleyEmployee['password'], '$2y$') === 0) {
                $password = $harleyEmployee['password'];
            } else {
                // Hash plain-text password from Harley
                $password = password_hash($harleyEmployee['password'], PASSWORD_DEFAULT);
            }
        }
        
        return [
            'employee_id' => 'HRLY-' . $harleyEmployee['id'], // Prefix to identify Harley employees
            'fname' => $harleyEmployee['fname'] ?? '',
            'lname' => $harleyEmployee['lname'] ?? '',
            'email' => $harleyEmployee['email'] ?? '',
            'personal_email' => $harleyEmployee['personal_email'] ?? '',
            'contact' => $harleyEmployee['contact'] ?? '',
            'company' => $harleyEmployee['company'] ?? '',
            'position' => $harleyEmployee['position'] ?? '',
            'username' => $harleyEmployee['username'] ?? $this->generateUsername($harleyEmployee),
            'password' => $password, // Hashed password from Harley
            'role' => $harleyEmployee['role'] ?? 'employee', // Sync role from Harley
            'admin_rights_hdesk' => $harleyEmployee['admin_rights_hdesk'] ?? null, // Sync admin rights from Harley
            'official_sched' => $harleyEmployee['official_sched'] ?? null,
            'profile_picture' => $harleyEmployee['profile_picture'] ?? null,
            'status' => ($harleyEmployee['status'] == 1 || $harleyEmployee['status'] === 'active') ? 'active' : 'inactive',
        ];
    }
    
    /**
     * Generate username from name if not provided
     */
    private function generateUsername($employee) {
        $fname = $employee['fname'] ?? $employee['first_name'] ?? '';
        $lname = $employee['lname'] ?? $employee['last_name'] ?? '';
        return strtolower($fname . '.' . $lname);
    }
    
    /**
     * Full sync - sync all employees from Harley
     * @return array Sync results
     */
    public function fullSync() {
        $this->syncLog = [];
        $stats = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => 0];
        
        try {
            $employees = $this->fetchHarleyEmployees();
            
            foreach ($employees as $employee) {
                $result = $this->syncEmployee($employee);
                
                if (isset($stats[$result['status']])) {
                    $stats[$result['status']]++;
                } else {
                    $stats['errors']++;
                }
            }
            
            return [
                'success' => true,
                'total' => count($employees),
                'stats' => $stats,
                'log' => $this->syncLog
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $stats,
                'log' => $this->syncLog
            ];
        }
    }
    
    /**
     * Incremental sync - only sync changes since last sync
     * @param string $since
     * @return array
     */
    public function incrementalSync($since = null) {
        $this->syncLog = [];
        $stats = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => 0];
        
        if (!$since) {
            // Default to last 24 hours
            $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
        }
        
        try {
            $employees = $this->fetchUpdatedEmployees($since);
            
            foreach ($employees as $employee) {
                $result = $this->syncEmployee($employee);
                
                if (isset($stats[$result['status']])) {
                    $stats[$result['status']]++;
                }
            }
            
            return [
                'success' => true,
                'since' => $since,
                'total' => count($employees),
                'stats' => $stats,
                'log' => $this->syncLog
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test connection to Harley database
     * @return array
     */
    public function testConnection() {
        try {
            if ($this->connectToHarley()) {
                // Try a simple query
                $stmt = $this->harleyDb->query("SELECT COUNT(*) as count FROM " . HARLEY_EMPLOYEES_TABLE);
                $result = $stmt->fetch();
                
                return [
                    'success' => true,
                    'message' => 'Connected successfully to Harley database',
                    'employee_count' => $result['count']
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to connect to Harley database'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }
}
