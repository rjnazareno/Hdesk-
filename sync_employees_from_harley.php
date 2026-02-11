<?php
/**
 * Sync Employees from Harley Calendar Database
 * Fetches active employees from Harley's emp_calendar table 
 * and syncs to IT Help Desk employees table
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/harley_config.php';
require_once __DIR__ . '/models/Employee.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Sync from Harley</title>
    <meta charset="utf-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 20px;
            background: #f5f5f5;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a73e8;
            margin-bottom: 10px;
        }
        .stats {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #1a73e8;
        }
        .stats h2 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }
        .stats p {
            margin: 5px 0;
        }
        .results {
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .status-created {
            background-color: #d4edda;
            color: #155724;
        }
        .status-updated {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-skipped {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        .badge-warning { background: #ffc107; color: #212529; }
        .badge-danger { background: #dc3545; color: white; }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .summary-card {
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .summary-card h3 {
            margin: 0 0 5px 0;
            font-size: 32px;
        }
        .summary-card p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔄 Employee Synchronization from Harley Calendar</h1>
    <p style="color: #666;">Syncing active employees from Harley HRIS to IT Help Desk</p>

<?php

try {
    // Connect to Harley database
    echo '<div class="stats">';
    echo '<h2>Step 1: Connecting to Harley Calendar Database</h2>';
    $harleyDb = getHarleyConnection();
    
    if (!$harleyDb) {
        throw new Exception("Cannot connect to Harley calendar database");
    }
    echo '<p>✅ Connected to Harley Calendar database (<code>u816220874_calendartype</code>)</p>';
    echo '</div>';
    
    // Get current counts
    echo '<div class="stats">';
    echo '<h2>Step 2: Current Employee Counts</h2>';
    $localDb = Database::getInstance()->getConnection();
    
    // Count Harley employees
    $stmt = $harleyDb->query("SELECT COUNT(*) as count FROM emp_calendar WHERE emp_status = 'ACTIVE'");
    $harleyCount = $stmt->fetch()['count'];
    echo "<p><strong>Harley Calendar (Active):</strong> $harleyCount employees</p>";
    
    // Count local employees
    $stmt = $localDb->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'");
    $localCount = $stmt->fetch()['count'];
    echo "<p><strong>IT Help Desk (Active):</strong> $localCount employees</p>";
    
    $difference = $harleyCount - $localCount;
    if ($difference > 0) {
        echo "<p style='color: #856404;'>⚠️ <strong>$difference</strong> new employee(s) to be synced!</p>";
    } elseif ($difference < 0) {
        echo "<p style='color: #666;'>ℹ️ IT Help Desk has " . abs($difference) . " more employees (may include inactive/terminated)</p>";
    } else {
        echo "<p style='color: #155724;'>✅ Counts match - will check for updates</p>";
    }
    echo '</div>';
    
    // Fetch employees from Harley emp_calendar
    echo '<div class="stats">';
    echo '<h2>Step 3: Fetching Employees from Harley</h2>';
    $sql = "SELECT 
                emp_id,
                emp_firstname,
                emp_lastname,
                emp_email,
                emp_mobilenum,
                emp_position_id,
                emp_company_code,
                emp_status
            FROM emp_calendar 
            WHERE emp_status = 'ACTIVE'
            ORDER BY emp_lastname, emp_firstname";
    
    $stmt = $harleyDb->prepare($sql);
    $stmt->execute();
    $harleyEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Fetched <strong>" . count($harleyEmployees) . "</strong> active employees from Harley</p>";
    echo '</div>';
    
    // Sync employees
    echo '<div class="results">';
    echo '<h2>Step 4: Synchronization Results</h2>';
    
    $created = 0;
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    
    $employeeModel = new Employee();
    
    echo '<table>';
    echo '<thead><tr><th>Harley ID</th><th>Name</th><th>Email</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($harleyEmployees as $harleyEmp) {
        $fullName = trim($harleyEmp['emp_firstname'] . ' ' . $harleyEmp['emp_lastname']);
        
        try {
            // Check if employee already exists in local database by employee_id (external Harley ID)
            $localEmp = $employeeModel->findByEmployeeId($harleyEmp['emp_id']);
            
            if (!$localEmp) {
                // Create new employee - use standard create() method
                // The 'employee_id' field stores the external Harley emp_id for reference
                $empData = [
                    'employee_id' => $harleyEmp['emp_id'], // Harley's emp_id stored in employee_id field
                    'fname' => $harleyEmp['emp_firstname'],
                    'lname' => $harleyEmp['emp_lastname'],
                    'email' => $harleyEmp['emp_email'],
                    'username' => null, // Will be set later if they need login access
                    'password' => password_hash('Welcome123!', PASSWORD_DEFAULT), // Default password
                    'company' => $harleyEmp['emp_company_code'] ?? 'RSO',
                    'position' => $harleyEmp['emp_position_id'] ?? null,
                    'contact' => $harleyEmp['emp_mobilenum'] ?? null,
                    'role' => 'employee',
                    'status' => 'active'
                ];
                
                $newId = $employeeModel->create($empData);
                if ($newId) {
                    $created++;
                    echo "<tr class='status-created'>
                            <td>{$harleyEmp['emp_id']}</td>
                            <td>$fullName</td>
                            <td>{$harleyEmp['emp_email']}</td>
                            <td><span class='badge badge-success'>CREATED</span> (New ID: $newId)</td>
                          </tr>";
                } else {
                    $errors++;
                    echo "<tr class='status-error'>
                            <td>{$harleyEmp['emp_id']}</td>
                            <td>$fullName</td>
                            <td>{$harleyEmp['emp_email']}</td>
                            <td><span class='badge badge-danger'>ERROR</span> create() returned 0</td>
                          </tr>";
                }
            } else {
                // Employee exists - check if update needed
                $needsUpdate = false;
                $changes = [];
                
                if ($localEmp['fname'] !== $harleyEmp['emp_firstname']) {
                    $needsUpdate = true;
                    $changes[] = 'first name';
                }
                if ($localEmp['lname'] !== $harleyEmp['emp_lastname']) {
                    $needsUpdate = true;
                    $changes[] = 'last name';
                }
                if ($localEmp['email'] !== $harleyEmp['emp_email']) {
                    $needsUpdate = true;
                    $changes[] = 'email';
                }
                if ($localEmp['contact'] !== $harleyEmp['emp_mobilenum']) {
                    $needsUpdate = true;
                    $changes[] = 'contact';
                }
                
                if ($needsUpdate) {
                    // Update existing employee data from Harley
                    $empData = [
                        'fname' => $harleyEmp['emp_firstname'],
                        'lname' => $harleyEmp['emp_lastname'],
                        'email' => $harleyEmp['emp_email'],
                        'company' => $harleyEmp['emp_company_code'] ?? 'RSO',
                        'position' => $harleyEmp['emp_position_id'] ?? null,
                        'contact' => $harleyEmp['emp_mobilenum'] ?? null,
                        'status' => 'active'
                    ];
                    
                    $isUpdated = $employeeModel->updateByEmployeeId($harleyEmp['emp_id'], $empData);
                    if ($isUpdated) {
                        $updated++;
                        $changesStr = implode(', ', $changes);
                        echo "<tr class='status-updated'>
                                <td>{$harleyEmp['emp_id']}</td>
                                <td>$fullName</td>
                                <td>{$harleyEmp['emp_email']}</td>
                                <td><span class='badge badge-info'>UPDATED</span> ($changesStr)</td>
                              </tr>";
                    } else {
                        $errors++;
                        echo "<tr class='status-error'>
                                <td>{$harleyEmp['emp_id']}</td>
                                <td>$fullName</td>
                                <td>{$harleyEmp['emp_email']}</td>
                                <td><span class='badge badge-danger'>ERROR</span> update failed</td>
                              </tr>";
                    }
                } else {
                    // No changes needed
                    $skipped++;
                    echo "<tr class='status-skipped'>
                            <td>{$harleyEmp['emp_id']}</td>
                            <td>$fullName</td>
                            <td>{$harleyEmp['emp_email']}</td>
                            <td><span class='badge badge-warning'>SKIPPED</span> No changes needed</td>
                          </tr>";
                }
            }
        } catch (Exception $e) {
            $errors++;
            echo "<tr class='status-error'>
                    <td>{$harleyEmp['emp_id']}</td>
                    <td>$fullName</td>
                    <td>{$harleyEmp['emp_email']}</td>
                    <td><span class='badge badge-danger'>ERROR</span> " . htmlspecialchars($e->getMessage()) . "</td>
                  </tr>";
        }
    }
    
    echo '</tbody></table>';
    echo '</div>';
    
    // Summary
    echo '<div class="summary">';
    echo "<div class='summary-card' style='background: #d4edda;'><h3>$created</h3><p>Created</p></div>";
    echo "<div class='summary-card' style='background: #d1ecf1;'><h3>$updated</h3><p>Updated</p></div>";
    echo "<div class='summary-card' style='background: #fff3cd;'><h3>$skipped</h3><p>Skipped</p></div>";
    echo "<div class='summary-card' style='background: #f8d7da;'><h3>$errors</h3><p>Errors</p></div>";
    echo '</div>';
    
    echo '<div class="stats">';
    echo '<h2>✅ Synchronization Complete</h2>';
    echo "<p><strong>Total processed:</strong> " . count($harleyEmployees) . " employees</p>";
    echo "<p><strong>Success rate:</strong> " . (count($harleyEmployees) > 0 ? round((($created + $updated + $skipped) / count($harleyEmployees)) * 100, 1) : 0) . "%</p>";
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div style="background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545; color: #721c24;">';
    echo '<h2>❌ Error</h2>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . $e->getFile() . ' (Line ' . $e->getLine() . ')</p>';
    echo '<pre style="background: white; padding: 10px; border-radius: 3px; overflow-x: auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}

?>

</div>
</body>
</html>
