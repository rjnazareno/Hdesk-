<?php
/**
 * Production Database Employee Password Diagnostic
 * Connects to Hostinger production database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Production database credentials (from Hostinger control panel)
$dbHost = 'localhost';
$dbUser = 'u816220874_AyrgoResolveIT';
$dbPass = '#2js&v3+P';
$dbName = 'u816220874_resolveIT';

try {
    $db = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connected to production database\n\n";
    
    // Get all employees with their passwords
    $stmt = $db->prepare("SELECT id, username, email, password, status FROM employees LIMIT 25");
    $stmt->execute();
    $employees = $stmt->fetchAll();
    
    echo "=== EMPLOYEE PASSWORD ANALYSIS ===\n";
    echo "Total employees fetched: " . count($employees) . "\n\n";
    
    $bcryptCount = 0;
    $md5Count = 0;
    $plainCount = 0;
    $nullCount = 0;
    
    foreach ($employees as $i => $emp) {
        echo ($i+1) . ". Username: {$emp['username']} | Email: {$emp['email']} | Status: {$emp['status']}\n";
        
        if (empty($emp['password'])) {
            echo "   ❌ PASSWORD IS EMPTY/NULL!\n";
            $nullCount++;
        } elseif (strpos($emp['password'], '$2') === 0) {
            echo "   ✅ bcrypt hash (correct format)\n";
            $bcryptCount++;
        } elseif (strlen($emp['password']) === 32 && ctype_xdigit($emp['password'])) {
            echo "   ⚠️  MD5 hash (32 hex chars) - password_verify() WON'T WORK!\n";
            $md5Count++;
        } elseif (strlen($emp['password']) < 20) {
            echo "   ❌ PLAIN TEXT PASSWORD! - {$emp['password']}\n";
            $plainCount++;
        } else {
            echo "   ❓ Unknown format: " . substr($emp['password'], 0, 30) . "...\n";
        }
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Bcrypt (working): $bcryptCount\n";
    echo "MD5 hashes: $md5Count\n";
    echo "Plain text: $plainCount\n";
    echo "Empty/NULL: $nullCount\n";
    
    if ($md5Count > 0 || $plainCount > 0 || $nullCount > 0) {
        echo "\n⚠️  PROBLEM FOUND!\n";
        echo "Not all employee passwords are bcrypt hashes.\n";
        echo "password_verify() will fail for non-bcrypt passwords.\n";
        echo "\nSOLUTION: Employees need to reset their passwords or admin must regenerate them.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

?>
