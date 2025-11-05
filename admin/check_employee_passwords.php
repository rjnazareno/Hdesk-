<?php
/**
 * Production Database Employee Password Diagnostic (Web Version)
 * Access via: https://resolveit.resourcestaffonline.com/check_prod_passwords.php
 * 
 * This checks the production database to see if employee passwords are properly hashed
 */

// Simple security check - only allow from admin dashboard
session_start();
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'user' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo "Access denied. Admin only.";
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/config/database.php';
    
    $db = Database::getInstance()->getConnection();
    
    echo "<h1>🔍 Employee Password Diagnostic Report</h1>\n";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>\n";
    
    // Get all employees with their passwords
    $stmt = $db->prepare("SELECT id, username, email, password, status FROM employees LIMIT 30");
    $stmt->execute();
    $employees = $stmt->fetchAll();
    
    echo "Total employees to check: " . count($employees) . "\n\n";
    echo "=== PASSWORD FORMAT ANALYSIS ===\n\n";
    
    $bcryptCount = 0;
    $md5Count = 0;
    $plainCount = 0;
    $nullCount = 0;
    $details = [];
    
    foreach ($employees as $i => $emp) {
        $status = "unknown";
        
        if (empty($emp['password'])) {
            $status = "EMPTY/NULL ❌";
            $nullCount++;
            $details[] = "ID {$emp['id']}: {$emp['username']} - Empty password!";
        } elseif (strpos($emp['password'], '$2') === 0) {
            $status = "bcrypt ✅";
            $bcryptCount++;
        } elseif (strlen($emp['password']) === 32 && ctype_xdigit($emp['password'])) {
            $status = "MD5 ❌";
            $md5Count++;
            $details[] = "ID {$emp['id']}: {$emp['username']} - MD5 hash, password_verify() will fail!";
        } elseif (strlen($emp['password']) < 20) {
            $status = "PLAIN TEXT ❌";
            $plainCount++;
            $details[] = "ID {$emp['id']}: {$emp['username']} - Plain text password: {$emp['password']}";
        } else {
            $status = "Unknown";
            $details[] = "ID {$emp['id']}: {$emp['username']} - Unknown format";
        }
        
        echo sprintf("%d. %-25s | %-30s | %-8s | %s\n", $i+1, $emp['username'], $emp['email'], $emp['status'], $status);
    }
    
    echo "\n";
    echo "╔════════════════════════════════════════════════════╗\n";
    echo "║                    SUMMARY                         ║\n";
    echo "╠════════════════════════════════════════════════════╣\n";
    echo "║ ✅ Bcrypt hashes (working):      $bcryptCount\n";
    echo "║ ❌ MD5 hashes (won't work):       $md5Count\n";
    echo "║ ❌ Plain text:                    $plainCount\n";
    echo "║ ❌ Empty/NULL:                    $nullCount\n";
    echo "╚════════════════════════════════════════════════════╝\n";
    
    if ($md5Count > 0 || $plainCount > 0 || $nullCount > 0) {
        echo "\n⚠️  ISSUE FOUND!\n";
        echo "Not all employee passwords are bcrypt hashes.\n";
        echo "password_verify() only works with bcrypt hashes.\n\n";
        
        echo "AFFECTED EMPLOYEES:\n";
        foreach ($details as $detail) {
            echo "  - $detail\n";
        }
        
        echo "\n📋 SOLUTION:\n";
        echo "1. Employees with non-bcrypt passwords cannot log in\n";
        echo "2. Admin should: regenerate_passwords.php (run manually)\n";
        echo "3. OR: Ask employees to use 'Forgot Password' feature\n";
    } else {
        echo "\n✅ All passwords are properly hashed as bcrypt!\n";
        echo "No login issues related to password hashing.\n";
    }
    
    echo "</pre>\n";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe0e0; padding: 15px; border-radius: 5px;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

?>
