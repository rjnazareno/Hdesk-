<?php
/**
 * Employee Sync API
 * Fetches employees from Harley database (u816220874_calendartype)
 * and compares with local Hdesk employees to find new ones
 */

header('Content-Type: application/json');

// Load configuration and dependencies
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Employee.php';
require_once __DIR__ . '/../includes/Auth.php';

// Verify user is authenticated and has IT staff access
$auth = new Auth();
if (!$auth->checkSession()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$user = $auth->getCurrentUser();
$role = $user['role'] ?? '';
$adminRights = $user['admin_rights_hdesk'] ?? '';

// Check if user has appropriate access (IT staff or admin)
$hasAccess = in_array($role, ['internal', 'admin', 'superadmin']) || 
             in_array($adminRights, ['it', 'superadmin']);

if (!$hasAccess) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. IT staff or admin access required.']);
    exit;
}

// Harley database configuration (separate user for Harley database)
define('HARLEY_DB_HOST', DB_HOST);
define('HARLEY_DB_USER', 'u816220874_calendartype');
define('HARLEY_DB_PASS', 'Gr33n$$wRf');
define('HARLEY_DB_NAME', 'u816220874_calendartype');

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'preview';

try {
    switch ($action) {
        case 'preview':
            // Get list of new employees from Harley not yet in Hdesk
            $result = previewNewEmployees();
            echo json_encode($result);
            break;
            
        case 'import':
            // Import selected employees
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'POST method required for import']);
                exit;
            }
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!isset($data['employee_ids']) || !is_array($data['employee_ids'])) {
                http_response_code(400);
                echo json_encode(['error' => 'employee_ids array required']);
                exit;
            }
            
            // Fix AUTO_INCREMENT before import to ensure proper ID sequence
            try {
                $hdeskDb = Database::getInstance()->getConnection();
                
                // Get current max ID from employees table
                $stmt = $hdeskDb->query("SELECT COALESCE(MAX(id), 1063) + 1 AS next_id FROM employees");
                $nextId = $stmt->fetch(PDO::FETCH_ASSOC)['next_id'];
                
                // Ensure next ID is at least 1064
                if ($nextId < 1064) {
                    $nextId = 1064;
                }
                
                // Set AUTO_INCREMENT to the next proper ID
                $hdeskDb->exec("ALTER TABLE employees AUTO_INCREMENT = $nextId");
                
                // Disable NO_AUTO_VALUE_ON_ZERO mode to prevent id=0 inserts
                $hdeskDb->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'NO_AUTO_VALUE_ON_ZERO',''))");
                
            } catch (Exception $e) {
                // Log but don't fail - AUTO_INCREMENT will still work
                error_log("AUTO_INCREMENT reset warning: " . $e->getMessage());
            }
            
            $result = importEmployees($data['employee_ids']);
            echo json_encode($result);
            break;
        
        case 'sync_one':
            // Sync a single employee's data from Harley
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'POST method required']);
                exit;
            }
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            $employeeId = $data['employee_id'] ?? $_GET['employee_id'] ?? null;
            if (!$employeeId) {
                http_response_code(400);
                echo json_encode(['error' => 'employee_id required']);
                exit;
            }
            
            $result = syncSingleEmployee($employeeId);
            echo json_encode($result);
            break;
        
        case 'preview_updates':
            // Preview employees that have changes in Harley
            $result = previewExistingUpdates();
            echo json_encode($result);
            break;
        
        case 'bulk_update':
            // Bulk update existing employees from Harley
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'POST method required']);
                exit;
            }
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            $employeeIds = $data['employee_ids'] ?? [];
            $result = bulkUpdateEmployees($employeeIds);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action. Use "preview", "import", "sync_one", "preview_updates", or "bulk_update"']);
    }
}catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage(),
        'trace' => IS_PRODUCTION ? null : $e->getTraceAsString()
    ]);
}

/**
 * Connect to Harley database
 */
function getHarleyConnection() {
    static $harleyDb = null;
    
    if ($harleyDb === null) {
        $dsn = "mysql:host=" . HARLEY_DB_HOST . ";dbname=" . HARLEY_DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        
        $harleyDb = new PDO($dsn, HARLEY_DB_USER, HARLEY_DB_PASS, $options);
    }
    
    return $harleyDb;
}

/**
 * Preview new employees from Harley that don't exist in Hdesk
 */
function previewNewEmployees() {
    $harleyDb = getHarleyConnection();
    $hdeskDb = Database::getInstance()->getConnection();
    
    // Get all employee_ids currently in Hdesk
    $stmt = $hdeskDb->query("SELECT employee_id FROM employees WHERE employee_id IS NOT NULL AND employee_id != ''");
    $existingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all active employees from Harley
    $harleyQuery = "SELECT 
            id,
            fname,
            lname,
            email,
            personal_email,
            contact,
            position,
            status,
            company,
            username,
            password,
            profile_picture,
            created_at
        FROM employees 
        WHERE status = 'active'
        ORDER BY fname, lname";
    
    $stmt = $harleyDb->query($harleyQuery);
    $harleyEmployees = $stmt->fetchAll();
    
    // Filter to only new employees (Harley id not in Hdesk employee_id)
    $newEmployees = [];
    $existingCount = 0;
    
    foreach ($harleyEmployees as $emp) {
        // Convert Harley's numeric id to string for comparison
        $harleyId = (string)$emp['id'];
        
        if (in_array($harleyId, $existingIds)) {
            $existingCount++;
        } else {
            // Check if employee exists by email or username (additional safety check)
            $emailCheck = $hdeskDb->prepare("SELECT id FROM employees WHERE email = :email OR username = :username LIMIT 1");
            $emailCheck->execute([
                ':email' => $emp['email'],
                ':username' => $emp['username']
            ]);
            
            if ($emailCheck->fetch()) {
                $existingCount++;
            } else {
                $newEmployees[] = [
                    'harley_id' => $emp['id'],
                    'fname' => $emp['fname'],
                    'lname' => $emp['lname'],
                    'full_name' => trim($emp['fname'] . ' ' . $emp['lname']),
                    'email' => $emp['email'],
                    'personal_email' => $emp['personal_email'],
                    'contact' => $emp['contact'],
                    'position' => $emp['position'],
                    'company' => $emp['company'],
                    'username' => $emp['username'],
                    'status' => $emp['status'],
                    'created_at' => $emp['created_at']
                ];
            }
        }
    }
    
    return [
        'success' => true,
        'harley_total' => count($harleyEmployees),
        'already_synced' => $existingCount,
        'new_count' => count($newEmployees),
        'new_employees' => $newEmployees,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Import selected employees from Harley to Hdesk
 */
function importEmployees($harleyIds) {
    if (empty($harleyIds)) {
        return ['success' => false, 'error' => 'No employees selected'];
    }
    
    $harleyDb = getHarleyConnection();
    $employeeModel = new Employee();
    
    // Sanitize IDs - ensure they're integers
    $harleyIds = array_map('intval', $harleyIds);
    $placeholders = implode(',', array_fill(0, count($harleyIds), '?'));
    
    // Fetch selected employees from Harley
    $query = "SELECT 
            id,
            fname,
            lname,
            email,
            personal_email,
            contact,
            position,
            status,
            company,
            username,
            password,
            profile_picture,
            official_sched,
            created_at
        FROM employees 
        WHERE id IN ($placeholders)";
    
    $stmt = $harleyDb->prepare($query);
    $stmt->execute($harleyIds);
    $employees = $stmt->fetchAll();
    
    $results = [
        'success' => true,
        'imported' => [],
        'failed' => [],
        'skipped' => []
    ];
    
    foreach ($employees as $emp) {
        try {
            // Check if already exists
            $existing = $employeeModel->findByEmployeeId((string)$emp['id']);
            if ($existing) {
                $results['skipped'][] = [
                    'harley_id' => $emp['id'],
                    'name' => $emp['fname'] . ' ' . $emp['lname'],
                    'reason' => 'Already exists (employee_id match)'
                ];
                continue;
            }
            
            // Check by email
            $existing = $employeeModel->findByEmail($emp['email']);
            if ($existing) {
                $results['skipped'][] = [
                    'harley_id' => $emp['id'],
                    'name' => $emp['fname'] . ' ' . $emp['lname'],
                    'reason' => 'Email already exists'
                ];
                continue;
            }
            
            // Also check by username
            $hdeskDb = Database::getInstance()->getConnection();
            $usernameCheck = $hdeskDb->prepare("SELECT id FROM employees WHERE username = :username LIMIT 1");
            $usernameCheck->execute([':username' => $emp['username']]);
            if ($usernameCheck->fetch()) {
                $results['skipped'][] = [
                    'harley_id' => $emp['id'],
                    'name' => $emp['fname'] . ' ' . $emp['lname'],
                    'reason' => 'Username already exists'
                ];
                continue;
            }
            
            // Prepare data for Hdesk
            // KEY: Store Harley's id as employee_id in Hdesk
            $employeeData = [
                'employee_id' => (string)$emp['id'], // Harley's id becomes employee_id
                'fname' => $emp['fname'],
                'lname' => $emp['lname'],
                'email' => $emp['email'] ?: $emp['username'] . '@noemail.local',
                'personal_email' => $emp['personal_email'],
                'contact' => $emp['contact'],
                'position' => $emp['position'],
                'company' => $emp['company'],
                'username' => $emp['username'],
                'password' => $emp['password'] ?: 'Welcome123!',
                'official_sched' => $emp['official_sched'],
                'role' => 'employee', // Default role
                'admin_rights_hdesk' => null, // No admin rights by default
                'status' => $emp['status'] === 'active' ? 'active' : 'inactive',
                'profile_picture' => null // Don't copy profile pictures
            ];
            
            // Create the employee in Hdesk with better error handling
            try {
                $newId = $employeeModel->create($employeeData);
                
                if ($newId) {
                    $results['imported'][] = [
                        'hdesk_id' => $newId,
                        'harley_id' => $emp['id'],
                        'employee_id' => (string)$emp['id'],
                        'name' => $emp['fname'] . ' ' . $emp['lname'],
                        'email' => $employeeData['email']
                    ];
                } else {
                    throw new Exception('Insert failed - no ID returned');
                }
            } catch (PDOException $pdoEx) {
                throw new Exception('Database error: ' . $pdoEx->getMessage());
            }
            
        } catch (Exception $e) {
            $results['failed'][] = [
                'harley_id' => $emp['id'],
                'name' => $emp['fname'] . ' ' . $emp['lname'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    $results['summary'] = [
        'total_requested' => count($harleyIds),
        'imported' => count($results['imported']),
        'failed' => count($results['failed']),
        'skipped' => count($results['skipped'])
    ];
    
    return $results;
}

/**
 * Sync a single employee's data from Harley to Hdesk
 * Used for updating existing employee when they click "Sync" button
 */
function syncSingleEmployee($employeeId) {
    $harleyDb = getHarleyConnection();
    $employeeModel = new Employee();
    
    // Find the employee in Hdesk by employee_id
    $hdeskEmployee = $employeeModel->findByEmployeeId((string)$employeeId);
    
    if (!$hdeskEmployee) {
        return [
            'success' => false,
            'error' => 'Employee not found in Hdesk. Use Import to add new employees.'
        ];
    }
    
    // Fetch employee from Harley
    $query = "SELECT 
            id,
            fname,
            lname,
            email,
            personal_email,
            contact,
            position,
            status,
            company,
            username,
            password,
            official_sched
        FROM employees 
        WHERE id = ?";
    
    $stmt = $harleyDb->prepare($query);
    $stmt->execute([$employeeId]);
    $harleyEmployee = $stmt->fetch();
    
    if (!$harleyEmployee) {
        return [
            'success' => false,
            'error' => 'Employee not found in Harley system'
        ];
    }
    
    // Prepare update data - sync all fields from Harley
    // NOTE: We sync password, position, contact, etc. but NOT role or admin_rights_hdesk
    $updateData = [
        'fname' => $harleyEmployee['fname'],
        'lname' => $harleyEmployee['lname'],
        'email' => $harleyEmployee['email'] ?: $hdeskEmployee['email'],
        'personal_email' => $harleyEmployee['personal_email'],
        'contact' => $harleyEmployee['contact'],
        'position' => $harleyEmployee['position'],
        'company' => $harleyEmployee['company'],
        'username' => $harleyEmployee['username'],
        'password' => $harleyEmployee['password'] ?: $hdeskEmployee['password'],
        'official_sched' => $harleyEmployee['official_sched'],
        'status' => $harleyEmployee['status'] === 'active' ? 'active' : 'inactive'
    ];
    
    // Track what changed
    $changes = [];
    foreach ($updateData as $field => $newValue) {
        $oldValue = $hdeskEmployee[$field] ?? null;
        if ($oldValue !== $newValue) {
            // Don't show password in plain text
            if ($field === 'password') {
                $changes[] = ['field' => $field, 'old' => '***', 'new' => '***'];
            } else {
                $changes[] = ['field' => $field, 'old' => $oldValue, 'new' => $newValue];
            }
        }
    }
    
    // Update the employee
    try {
        $employeeModel->update($hdeskEmployee['id'], $updateData);
        
        return [
            'success' => true,
            'employee_id' => $employeeId,
            'hdesk_id' => $hdeskEmployee['id'],
            'name' => $harleyEmployee['fname'] . ' ' . $harleyEmployee['lname'],
            'changes_count' => count($changes),
            'changes' => $changes,
            'message' => count($changes) > 0 
                ? 'Employee updated with ' . count($changes) . ' change(s)'
                : 'Employee is already up to date'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Update failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Preview existing employees that have changes in Harley
 */
function previewExistingUpdates() {
    $harleyDb = getHarleyConnection();
    $hdeskDb = Database::getInstance()->getConnection();
    
    // Get all Hdesk employees that have employee_id (synced from Harley)
    $stmt = $hdeskDb->query("SELECT * FROM employees WHERE employee_id IS NOT NULL AND employee_id != ''");
    $hdeskEmployees = $stmt->fetchAll();
    
    if (empty($hdeskEmployees)) {
        return [
            'success' => true,
            'existing_count' => 0,
            'with_changes' => 0,
            'employees' => []
        ];
    }
    
    // Get corresponding employees from Harley
    $employeeIds = array_map(function($e) { return (int)$e['employee_id']; }, $hdeskEmployees);
    $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
    
    $query = "SELECT 
            id, fname, lname, email, personal_email, contact, position, status, company, username, password, official_sched
        FROM employees 
        WHERE id IN ($placeholders)";
    
    $stmt = $harleyDb->prepare($query);
    $stmt->execute($employeeIds);
    $harleyEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Index Harley employees by id
    $harleyIndex = [];
    foreach ($harleyEmployees as $emp) {
        $harleyIndex[$emp['id']] = $emp;
    }
    
    // Compare and find changes
    $employeesWithChanges = [];
    $fieldsToCompare = ['fname', 'lname', 'email', 'personal_email', 'contact', 'position', 'company', 'username', 'password', 'official_sched', 'status'];
    
    foreach ($hdeskEmployees as $hdesk) {
        $harleyId = (int)$hdesk['employee_id'];
        
        if (!isset($harleyIndex[$harleyId])) {
            continue; // Employee deleted from Harley or not found
        }
        
        $harley = $harleyIndex[$harleyId];
        $changes = [];
        
        foreach ($fieldsToCompare as $field) {
            $hdeskVal = $hdesk[$field] ?? '';
            $harleyVal = $harley[$field] ?? '';
            
            // Normalize status comparison
            if ($field === 'status') {
                $harleyVal = ($harleyVal === 'active') ? 'active' : 'inactive';
            }
            
            if ($hdeskVal !== $harleyVal) {
                $changes[] = [
                    'field' => $field,
                    'hdesk' => ($field === 'password') ? '***' : $hdeskVal,
                    'harley' => ($field === 'password') ? '***' : $harleyVal
                ];
            }
        }
        
        if (!empty($changes)) {
            $employeesWithChanges[] = [
                'hdesk_id' => $hdesk['id'],
                'employee_id' => $hdesk['employee_id'],
                'full_name' => trim($hdesk['fname'] . ' ' . $hdesk['lname']),
                'email' => $hdesk['email'],
                'changes_count' => count($changes),
                'changes' => $changes
            ];
        }
    }
    
    return [
        'success' => true,
        'existing_count' => count($hdeskEmployees),
        'with_changes' => count($employeesWithChanges),
        'employees' => $employeesWithChanges,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Bulk update existing employees from Harley
 */
function bulkUpdateEmployees($employeeIds = []) {
    $results = [
        'success' => true,
        'updated' => [],
        'failed' => [],
        'skipped' => []
    ];
    
    // If no specific IDs provided, update all synced employees
    if (empty($employeeIds)) {
        $hdeskDb = Database::getInstance()->getConnection();
        $stmt = $hdeskDb->query("SELECT employee_id FROM employees WHERE employee_id IS NOT NULL AND employee_id != ''");
        $employeeIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    foreach ($employeeIds as $empId) {
        $result = syncSingleEmployee($empId);
        
        if ($result['success']) {
            if ($result['changes_count'] > 0) {
                $results['updated'][] = [
                    'employee_id' => $empId,
                    'name' => $result['name'],
                    'changes_count' => $result['changes_count']
                ];
            } else {
                $results['skipped'][] = [
                    'employee_id' => $empId,
                    'name' => $result['name'],
                    'reason' => 'No changes detected'
                ];
            }
        } else {
            $results['failed'][] = [
                'employee_id' => $empId,
                'error' => $result['error']
            ];
        }
    }
    
    $results['summary'] = [
        'total' => count($employeeIds),
        'updated' => count($results['updated']),
        'skipped' => count($results['skipped']),
        'failed' => count($results['failed'])
    ];
    
    return $results;
}
