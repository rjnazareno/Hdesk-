<?php
/**
 * Webhook Endpoint for Employee Sync
 * Receives employee data from external system (Harley website)
 * and adds/updates them in the local employees table
 */

header('Content-Type: application/json');

// Security: API Key authentication
define('WEBHOOK_SECRET_KEY', '333e582f2e6eccbf4d12274296fa6c53779a0d15c135da1863721c1e2509dece'); // Change this!

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Verify API key
// Try multiple methods to get the header (getallheaders() doesn't work on all servers)
$apiKey = '';

// Method 1: getallheaders() - Apache
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $apiKey = $headers['X-API-Key'] ?? $headers['X-Api-Key'] ?? $headers['x-api-key'] ?? '';
}

// Method 2: $_SERVER - Works on most servers including Nginx
if (empty($apiKey)) {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
}

// Method 3: apache_request_headers() - Alternative for Apache
if (empty($apiKey) && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $apiKey = $headers['X-API-Key'] ?? $headers['X-Api-Key'] ?? $headers['x-api-key'] ?? '';
}

if ($apiKey !== WEBHOOK_SECRET_KEY) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Unauthorized. Invalid API key.',
        'debug' => [
            'received_key_length' => strlen($apiKey),
            'expected_key_length' => strlen(WEBHOOK_SECRET_KEY),
            'received_key_first_10' => substr($apiKey, 0, 10),
            'expected_key_first_10' => substr(WEBHOOK_SECRET_KEY, 0, 10),
        ]
    ]);
    exit;
}

// Get JSON payload
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload.']);
    exit;
}

// Load database configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/Employee.php';

$employeeModel = new Employee();

// Expected data structure:
// {
//   "sync_mode": "full",  // or "partial" (default)
//   "employees": [
//     {
//       "employee_id": "EMP001",
//       "fname": "John",
//       "lname": "Doe",
//       "email": "john.doe@company.com",
//       "phone": "1234567890",
//       "department": "IT",
//       "position": "Developer",
//       "username": "john.doe"
//     }
//   ]
// }

if (!isset($data['employees']) || !is_array($data['employees'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid "employees" array.']);
    exit;
}

// Determine sync mode
$syncMode = $data['sync_mode'] ?? 'partial';
$trackSyncedIds = [];  // Track which employee_ids we've synced

$results = [
    'success' => [],
    'failed' => [],
    'updated' => [],
];

foreach ($data['employees'] as $emp) {
    try {
        // Validate required fields (only ID and name are truly required)
        $requiredFields = ['employee_id', 'fname', 'lname'];
        foreach ($requiredFields as $field) {
            if (empty($emp[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Generate email if missing (use employee_id@noemail.local as placeholder)
        $email = !empty($emp['email']) ? trim($emp['email']) : $emp['employee_id'] . '@noemail.local';
        
        // Validate email format
        $validatedEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
        if (!$validatedEmail) {
            // If invalid, create a safe placeholder
            $validatedEmail = 'emp' . $emp['employee_id'] . '@noemail.local';
        }

        // Sanitize data
        // NOTE: 'role' and 'admin_rights_hdesk' are intentionally NOT synced
        // These fields are managed locally for admin access control
        $employeeData = [
            'employee_id' => sanitize($emp['employee_id']),
            'fname' => sanitize($emp['fname']),
            'lname' => sanitize($emp['lname']),
            'email' => $validatedEmail,
            'phone' => isset($emp['phone']) ? sanitize($emp['phone']) : null,
            'contact' => isset($emp['phone']) ? sanitize($emp['phone']) : null,
            'company' => isset($emp['department']) ? sanitize($emp['department']) : (isset($emp['company']) ? sanitize($emp['company']) : null),
            'position' => isset($emp['position']) ? sanitize($emp['position']) : null,
            'username' => isset($emp['username']) ? sanitize($emp['username']) : strtolower($emp['fname'] . '.' . $emp['lname']),
        ];

        // Check if employee already exists by employee_id or email
        $existingEmployee = $employeeModel->findByEmployeeId($employeeData['employee_id']);
        
        if (!$existingEmployee) {
            // Check by email as well
            $existingEmployee = $employeeModel->findByEmail($employeeData['email']);
        }

        if ($existingEmployee) {
            // Update existing employee (DO NOT update password)
            $employeeData['id'] = $existingEmployee['id'];
            
            // Remove password from update data to preserve existing password
            unset($employeeData['password']);
            
            if ($employeeModel->update($employeeData['id'], $employeeData)) {
                $results['updated'][] = [
                    'employee_id' => $employeeData['employee_id'],
                    'email' => $employeeData['email'],
                    'name' => $employeeData['fname'] . ' ' . $employeeData['lname'],
                ];
                $trackSyncedIds[] = $employeeData['employee_id'];
            } else {
                throw new Exception("Failed to update employee");
            }
        } else {
            // Generate a default password (they should reset it)
            $defaultPassword = 'Welcome123!';
            $employeeData['password'] = password_hash($defaultPassword, PASSWORD_DEFAULT);
            
            // Create new employee
            $newId = $employeeModel->create($employeeData);
            
            if ($newId) {
                // Send welcome email to new employee
                try {
                    $mailer = new Mailer();
                    $emailSent = $mailer->sendWelcomeEmail(
                        $employeeData['email'],
                        $employeeData['fname'] . ' ' . $employeeData['lname'],
                        $employeeData['username'],
                        $defaultPassword
                    );
                } catch (Exception $mailError) {
                    // Log email error but don't fail the sync
                    error_log("Failed to send welcome email to {$employeeData['email']}: " . $mailError->getMessage());
                }
                
                $results['success'][] = [
                    'employee_id' => $employeeData['employee_id'],
                    'email' => $employeeData['email'],
                    'name' => $employeeData['fname'] . ' ' . $employeeData['lname'],
                    'id' => $newId,
                    'email_sent' => $emailSent ?? false,
                ];
                $trackSyncedIds[] = $employeeData['employee_id'];
            } else {
                throw new Exception("Failed to create employee");
            }
        }
    } catch (Exception $e) {
        $results['failed'][] = [
            'employee_id' => $emp['employee_id'] ?? 'unknown',
            'email' => $emp['email'] ?? 'unknown',
            'error' => $e->getMessage(),
        ];
    }
}

// If full sync mode, check for employees that don't exist in Harley system anymore
// (Optional: you can disable this if you don't want to deactivate missing employees)
if ($syncMode === 'full' && !empty($trackSyncedIds)) {
    try {
        // Get all employees from local database that have employee_id set
        $allLocalEmployees = $employeeModel->getAll();
        
        foreach ($allLocalEmployees as $localEmp) {
            // Skip if employee doesn't have employee_id (manually created)
            if (empty($localEmp['employee_id'])) {
                continue;
            }
            
            // If this employee wasn't in the sync payload, mark it
            if (!in_array($localEmp['employee_id'], $trackSyncedIds)) {
                $results['not_in_source'][] = [
                    'employee_id' => $localEmp['employee_id'],
                    'email' => $localEmp['email'],
                    'name' => $localEmp['fname'] . ' ' . $localEmp['lname'],
                    'note' => 'Employee exists locally but not in Harley system',
                ];
            }
        }
    } catch (Exception $e) {
        $results['sync_check_error'] = $e->getMessage();
    }
}

// Return results
http_response_code(200);
echo json_encode([
    'status' => 'completed',
    'sync_mode' => $syncMode,
    'timestamp' => date('Y-m-d H:i:s'),
    'summary' => [
        'total' => count($data['employees']),
        'created' => count($results['success']),
        'updated' => count($results['updated']),
        'failed' => count($results['failed']),
        'not_in_source' => isset($results['not_in_source']) ? count($results['not_in_source']) : 0,
    ],
    'details' => $results,
]);
