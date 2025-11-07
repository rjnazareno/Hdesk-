<?php
/**
 * Test Script for Add Employee Functionality
 * Run this after adding a test employee to verify database state
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Check if we're logged in as admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    die("⚠️ Must be logged in as admin/IT staff to run tests");
}

echo "<h1>🧪 Add Employee Test Results</h1>";
echo "<style>
    body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f5f5f5; }
    .test-section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .test-section h2 { color: #2563eb; margin-top: 0; }
    .pass { color: #16a34a; font-weight: bold; }
    .fail { color: #dc2626; font-weight: bold; }
    .info { color: #0891b2; }
    pre { background: #f8fafc; padding: 10px; border-left: 3px solid #2563eb; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    th { background: #f1f5f9; font-weight: 600; }
</style>";

$db = Database::getInstance()->getConnection();

// TEST 1: Recent Employees
echo "<div class='test-section'>";
echo "<h2>📋 Test 1: Recently Added Employees</h2>";
$sql = "SELECT id, employee_id, username, email, fname, lname, position, role, status, profile_picture, created_at 
        FROM employees 
        ORDER BY created_at DESC 
        LIMIT 5";
$stmt = $db->query($sql);
$recentEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($recentEmployees) > 0) {
    echo "<p class='pass'>✅ Found " . count($recentEmployees) . " recent employees</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th><th>Position</th><th>Role</th><th>Profile Pic</th><th>Created</th></tr>";
    foreach ($recentEmployees as $emp) {
        $profileIcon = $emp['profile_picture'] ? "✅ Yes" : "❌ No";
        echo "<tr>";
        echo "<td>{$emp['id']}</td>";
        echo "<td>{$emp['username']}</td>";
        echo "<td>{$emp['email']}</td>";
        echo "<td>{$emp['fname']} {$emp['lname']}</td>";
        echo "<td>{$emp['position']}</td>";
        echo "<td>{$emp['role']}</td>";
        echo "<td>{$profileIcon}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($emp['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='fail'>❌ No employees found in database</p>";
}
echo "</div>";

// TEST 2: Check for Duplicate Usernames
echo "<div class='test-section'>";
echo "<h2>🔍 Test 2: Duplicate Username Detection</h2>";
$sql = "SELECT username, COUNT(*) as count 
        FROM employees 
        GROUP BY username 
        HAVING count > 1";
$stmt = $db->query($sql);
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($duplicates) === 0) {
    echo "<p class='pass'>✅ No duplicate usernames found</p>";
} else {
    echo "<p class='fail'>❌ Found duplicate usernames:</p>";
    echo "<pre>" . print_r($duplicates, true) . "</pre>";
}
echo "</div>";

// TEST 3: Check for Duplicate Emails
echo "<div class='test-section'>";
echo "<h2>📧 Test 3: Duplicate Email Detection</h2>";
$sql = "SELECT email, COUNT(*) as count 
        FROM employees 
        WHERE email IS NOT NULL AND email != ''
        GROUP BY email 
        HAVING count > 1";
$stmt = $db->query($sql);
$duplicateEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($duplicateEmails) === 0) {
    echo "<p class='pass'>✅ No duplicate emails found</p>";
} else {
    echo "<p class='fail'>❌ Found duplicate emails:</p>";
    echo "<pre>" . print_r($duplicateEmails, true) . "</pre>";
}
echo "</div>";

// TEST 4: Password Hashing Verification
echo "<div class='test-section'>";
echo "<h2>🔐 Test 4: Password Hashing Verification</h2>";
$sql = "SELECT id, username, password FROM employees ORDER BY created_at DESC LIMIT 5";
$stmt = $db->query($sql);
$passwordCheck = $stmt->fetchAll(PDO::FETCH_ASSOC);

$allHashed = true;
foreach ($passwordCheck as $emp) {
    $info = password_get_info($emp['password']);
    if ($info['algo'] === null) {
        echo "<p class='fail'>❌ User {$emp['username']} has unhashed password!</p>";
        $allHashed = false;
    }
}

if ($allHashed) {
    echo "<p class='pass'>✅ All passwords are properly hashed (bcrypt)</p>";
    echo "<p class='info'>Sample hash format: " . substr($passwordCheck[0]['password'], 0, 20) . "...</p>";
}
echo "</div>";

// TEST 5: Profile Picture Upload Check
echo "<div class='test-section'>";
echo "<h2>🖼️ Test 5: Profile Picture Upload</h2>";
$uploadDir = __DIR__ . '/../uploads/profiles/';
$filesInDir = is_dir($uploadDir) ? scandir($uploadDir) : [];
$imageFiles = array_filter($filesInDir, function($file) {
    return !in_array($file, ['.', '..']);
});

echo "<p class='info'>Upload directory: " . realpath($uploadDir) . "</p>";
echo "<p class='info'>Directory exists: " . (is_dir($uploadDir) ? "✅ Yes" : "❌ No") . "</p>";
echo "<p class='info'>Files found: " . count($imageFiles) . "</p>";

if (count($imageFiles) > 0) {
    echo "<p class='pass'>✅ Profile pictures uploaded:</p>";
    echo "<ul>";
    foreach ($imageFiles as $file) {
        $size = filesize($uploadDir . $file);
        $sizeKB = round($size / 1024, 2);
        echo "<li>{$file} ({$sizeKB} KB)</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='info'>ℹ️ No profile pictures uploaded yet</p>";
}
echo "</div>";

// TEST 6: Employee Stats
echo "<div class='test-section'>";
echo "<h2>📊 Test 6: Employee Statistics</h2>";

// Count by role
$sql = "SELECT role, COUNT(*) as count FROM employees GROUP BY role";
$stmt = $db->query($sql);
$roleStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>By Role:</h3>";
echo "<table>";
echo "<tr><th>Role</th><th>Count</th></tr>";
foreach ($roleStats as $stat) {
    echo "<tr><td>" . ucfirst($stat['role']) . "</td><td>{$stat['count']}</td></tr>";
}
echo "</table>";

// Count by status
$sql = "SELECT status, COUNT(*) as count FROM employees GROUP BY status";
$stmt = $db->query($sql);
$statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>By Status:</h3>";
echo "<table>";
echo "<tr><th>Status</th><th>Count</th></tr>";
foreach ($statusStats as $stat) {
    echo "<tr><td>" . ucfirst($stat['status']) . "</td><td>{$stat['count']}</td></tr>";
}
echo "</table>";
echo "</div>";

// TEST 7: Auto-generated Username Pattern Check
echo "<div class='test-section'>";
echo "<h2>🔤 Test 7: Username Generation Pattern</h2>";
$sql = "SELECT fname, lname, username FROM employees ORDER BY created_at DESC LIMIT 10";
$stmt = $db->query($sql);
$nameCheck = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p class='info'>Checking if usernames follow firstname.lastname pattern:</p>";
echo "<table>";
echo "<tr><th>First Name</th><th>Last Name</th><th>Username</th><th>Match?</th></tr>";
foreach ($nameCheck as $emp) {
    $expected = strtolower($emp['fname']) . '.' . strtolower($emp['lname']);
    $actual = $emp['username'];
    $match = ($expected === $actual) ? "✅ Yes" : "⚠️ Custom";
    echo "<tr><td>{$emp['fname']}</td><td>{$emp['lname']}</td><td>{$actual}</td><td>{$match}</td></tr>";
}
echo "</table>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>✅ Testing Complete!</h2>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='add_employee.php' style='color: #2563eb;'>← Back to Add Employee</a> | ";
echo "<a href='customers.php' style='color: #2563eb;'>View All Employees →</a></p>";
echo "</div>";
?>
