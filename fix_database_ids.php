<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Fix Employee IDs Directly</title></head><body>";
echo "<h1>🔧 Fix Employee ID Issues - Database Direct</h1>";

// Handle POST actions
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        $db = Database::getInstance()->getConnection();
        
        switch ($action) {
            case 'reassign_zero_ids':
                echo "<h2>🔄 Reassigning IDs for employees with id=0</h2>";
                
                // Get all employees with id=0
                $sql = "SELECT id, username, fname, lname, employee_id FROM employees WHERE id = 0 ORDER BY username";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $zeroIdEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($zeroIdEmployees)) {
                    echo "<p style='color: green;'>✅ No employees found with id=0. Database already clean!</p>";
                    break;
                }
                
                echo "<p>Found " . count($zeroIdEmployees) . " employees with id=0. Reassigning proper IDs...</p>";
                
                // Get the current maximum ID to avoid conflicts
                $sql = "SELECT COALESCE(MAX(id), 0) as max_id FROM employees WHERE id > 0";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $maxId = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'];
                
                echo "<p>Current maximum ID: {$maxId}</p>";
                
                // Start transaction
                $db->beginTransaction();
                
                $successCount = 0;
                $nextId = $maxId + 1;
                
                foreach ($zeroIdEmployees as $employee) {
                    // Update employee with new ID
                    $sql = "UPDATE employees SET id = :new_id WHERE id = 0 AND username = :username LIMIT 1";
                    $stmt = $db->prepare($sql);
                    $result = $stmt->execute([
                        ':new_id' => $nextId,
                        ':username' => $employee['username']
                    ]);
                    
                    if ($result && $stmt->rowCount() > 0) {
                        echo "<p>✅ {$employee['username']} ({$employee['fname']} {$employee['lname']}) → ID: {$nextId}</p>";
                        $successCount++;
                        $nextId++;
                    } else {
                        echo "<p style='color: red;'>❌ Failed to update {$employee['username']}</p>";
                    }
                }
                
                if ($successCount > 0) {
                    $db->commit();
                    echo "<div style='background-color: #e6ffe6; padding: 15px; border: 1px solid green; margin: 20px 0;'>";
                    echo "<h3>✅ SUCCESS!</h3>";
                    echo "<p>Successfully reassigned IDs for {$successCount} employees.</p>";
                    echo "<p>Database is now clean - each employee has a unique ID.</p>";
                    echo "</div>";
                } else {
                    $db->rollback();
                    echo "<p style='color: red;'>No updates were successful. Rolling back changes.</p>";
                }
                break;
                
            case 'reset_auto_increment':
                echo "<h2>🔄 Resetting AUTO_INCREMENT</h2>";
                
                // Get current max ID
                $sql = "SELECT COALESCE(MAX(id), 0) as max_id FROM employees";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $maxId = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'];
                
                $newAutoIncrement = $maxId + 1;
                
                // Reset AUTO_INCREMENT
                $sql = "ALTER TABLE employees AUTO_INCREMENT = {$newAutoIncrement}";
                $stmt = $db->prepare($sql);
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<p style='color: green;'>✅ AUTO_INCREMENT reset to {$newAutoIncrement}</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to reset AUTO_INCREMENT</p>";
                }
                break;
                
            case 'cleanup_duplicates':
                echo "<h2>🧹 Cleanup Duplicate Employees</h2>";
                
                // Find duplicates by username
                $sql = "SELECT username, COUNT(*) as count 
                        FROM employees 
                        GROUP BY username 
                        HAVING COUNT(*) > 1 
                        ORDER BY count DESC";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($duplicates)) {
                    echo "<p style='color: green;'>✅ No duplicate usernames found</p>";
                    break;
                }
                
                echo "<p>Found duplicates for " . count($duplicates) . " usernames:</p>";
                
                $db->beginTransaction();
                $totalRemoved = 0;
                
                foreach ($duplicates as $dup) {
                    $username = $dup['username'];
                    echo "<h4>Processing: {$username} ({$dup['count']} records)</h4>";
                    
                    // Get all records for this username
                    $sql = "SELECT id, username, fname, lname, employee_id, created_at 
                            FROM employees 
                            WHERE username = :username 
                            ORDER BY id DESC"; // Keep the one with highest ID (most recent)
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':username' => $username]);
                    $userRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Keep the first one (highest ID), remove the rest
                    $keepRecord = array_shift($userRecords);
                    echo "<p>✅ Keeping: ID {$keepRecord['id']} - {$keepRecord['fname']} {$keepRecord['lname']}</p>";
                    
                    foreach ($userRecords as $removeRecord) {
                        $sql = "DELETE FROM employees WHERE id = :id";
                        $stmt = $db->prepare($sql);
                        $result = $stmt->execute([':id' => $removeRecord['id']]);
                        
                        if ($result) {
                            echo "<p>🗑️ Removed: ID {$removeRecord['id']} (duplicate)</p>";
                            $totalRemoved++;
                        }
                    }
                }
                
                if ($totalRemoved > 0) {
                    $db->commit();
                    echo "<div style='background-color: #e6ffe6; padding: 15px; border: 1px solid green; margin: 20px 0;'>";
                    echo "<h3>✅ Cleanup Complete!</h3>";
                    echo "<p>Removed {$totalRemoved} duplicate employee records.</p>";
                    echo "</div>";
                } else {
                    $db->rollback();
                    echo "<p>No duplicates were removed.</p>";
                }
                break;
        }
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollback();
        }
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Show current database state
try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>📊 Current Database State</h2>";
    
    // Count employees by ID status
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN id = 0 THEN 1 ELSE 0 END) as zero_ids,
                SUM(CASE WHEN id > 0 THEN 1 ELSE 0 END) as proper_ids,
                MIN(id) as min_id,
                MAX(id) as max_id
            FROM employees";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Metric</th><th>Value</th></tr>";
    echo "<tr><td><strong>Total Employees</strong></td><td>{$stats['total']}</td></tr>";
    echo "<tr style='background-color: " . ($stats['zero_ids'] > 0 ? '#ffe6e6' : '#e6ffe6') . ";'><td><strong>Employees with ID = 0</strong></td><td>{$stats['zero_ids']}</td></tr>";
    echo "<tr style='background-color: #e6ffe6;'><td><strong>Employees with proper IDs</strong></td><td>{$stats['proper_ids']}</td></tr>";
    echo "<tr><td><strong>ID Range</strong></td><td>{$stats['min_id']} - {$stats['max_id']}</td></tr>";
    echo "</table>";
    
    // Check for duplicates
    echo "<h3>🔍 Duplicate Check</h3>";
    $sql = "SELECT username, COUNT(*) as count 
            FROM employees 
            GROUP BY username 
            HAVING COUNT(*) > 1 
            ORDER BY count DESC 
            LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($duplicates)) {
        echo "<p style='color: red;'>❌ Found duplicate usernames:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Username</th><th>Count</th></tr>";
        foreach ($duplicates as $dup) {
            echo "<tr><td>{$dup['username']}</td><td>{$dup['count']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✅ No duplicate usernames found</p>";
    }
    
    // Show sample of employees with id=0
    if ($stats['zero_ids'] > 0) {
        echo "<h3>👥 Sample Employees with ID = 0</h3>";
        $sql = "SELECT id, username, fname, lname, employee_id FROM employees WHERE id = 0 LIMIT 10";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $zeroEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Employee_ID</th></tr>";
        foreach ($zeroEmployees as $emp) {
            echo "<tr style='background-color: #ffe6e6;'>";
            echo "<td>{$emp['id']}</td>";
            echo "<td>{$emp['username']}</td>";
            echo "<td>{$emp['fname']} {$emp['lname']}</td>";
            echo "<td>{$emp['employee_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Get current AUTO_INCREMENT value
    $sql = "SELECT AUTO_INCREMENT 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'employees'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $autoInc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Current AUTO_INCREMENT:</strong> " . ($autoInc['AUTO_INCREMENT'] ?? 'Unknown') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking database: " . $e->getMessage() . "</p>";
}

// Action buttons
echo "<h2>🛠️ Database Fix Actions</h2>";

echo "<div style='background-color: #f8f9fa; padding: 20px; border: 1px solid #ddd; margin: 20px 0;'>";

if (isset($stats) && $stats['zero_ids'] > 0) {
    echo "<h3>🚨 Critical: Fix ID = 0 Issue</h3>";
    echo "<p>Found {$stats['zero_ids']} employees with ID = 0. This causes profile and admin rights bugs.</p>";
    echo "<form method='POST' onsubmit='return confirm(\"This will reassign proper IDs to all employees with id=0. Continue?\")'>";
    echo "<input type='hidden' name='action' value='reassign_zero_ids'>";
    echo "<button type='submit' style='background: #dc3545; color: white; border: none; padding: 15px 30px; font-size: 16px; cursor: pointer; margin: 10px;'>🔧 Fix ID = 0 Issues</button>";
    echo "</form>";
}

if (!empty($duplicates)) {
    echo "<h3>🧹 Cleanup Duplicates</h3>";
    echo "<p>Remove duplicate employee records (keeps the most recent one for each username).</p>";
    echo "<form method='POST' onsubmit='return confirm(\"This will remove duplicate employee records. Continue?\")'>";
    echo "<input type='hidden' name='action' value='cleanup_duplicates'>";
    echo "<button type='submit' style='background: #ffc107; color: black; border: none; padding: 15px 30px; font-size: 16px; cursor: pointer; margin: 10px;'>🧹 Remove Duplicates</button>";
    echo "</form>";
}

echo "<h3>🔄 Reset AUTO_INCREMENT</h3>";
echo "<p>Reset the AUTO_INCREMENT counter to start after the highest current ID.</p>";
echo "<form method='POST' onsubmit='return confirm(\"Reset AUTO_INCREMENT counter?\")'>";
echo "<input type='hidden' name='action' value='reset_auto_increment'>";
echo "<button type='submit' style='background: #17a2b8; color: white; border: none; padding: 15px 30px; font-size: 16px; cursor: pointer; margin: 10px;'>🔄 Reset AUTO_INCREMENT</button>";
echo "</form>";

echo "</div>";

echo "<p><a href='debug_admin_rights.php'>← Back to Debug Report</a> | <a href='debug_profile_bug.php'>Profile Bug Debug</a></p>";
echo "</body></html>";
?>