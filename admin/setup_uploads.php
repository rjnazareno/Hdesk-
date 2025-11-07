<?php
/**
 * Setup Upload Directories
 * Creates necessary upload directories with proper permissions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Check if we're logged in as admin (session already started in config)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    die("⚠️ Must be logged in as admin/IT staff to run setup");
}

echo "<h1>📁 Upload Directory Setup</h1>";
echo "<style>
    body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f5f5f5; }
    .setup-section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .setup-section h2 { color: #2563eb; margin-top: 0; }
    .success { color: #16a34a; font-weight: bold; }
    .error { color: #dc2626; font-weight: bold; }
    .info { color: #0891b2; }
    pre { background: #f8fafc; padding: 10px; border-left: 3px solid #2563eb; }
</style>";

// Define directories to create
$directories = [
    'uploads/profiles' => 'Employee/User profile pictures',
    'uploads/tickets' => 'Ticket attachments',
    'uploads/temp' => 'Temporary file uploads'
];

echo "<div class='setup-section'>";
echo "<h2>📋 Creating Upload Directories</h2>";

$basePath = __DIR__ . '/../';
$results = [];

foreach ($directories as $dir => $description) {
    $fullPath = $basePath . $dir;
    
    echo "<h3>📁 {$dir}</h3>";
    echo "<p class='info'>Purpose: {$description}</p>";
    
    if (is_dir($fullPath)) {
        echo "<p class='success'>✅ Directory already exists</p>";
        $results[$dir] = 'exists';
    } else {
        if (mkdir($fullPath, 0755, true)) {
            echo "<p class='success'>✅ Directory created successfully</p>";
            $results[$dir] = 'created';
        } else {
            echo "<p class='error'>❌ Failed to create directory</p>";
            $results[$dir] = 'failed';
        }
    }
    
    // Check permissions
    if (is_dir($fullPath)) {
        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
        echo "<p class='info'>Permissions: {$perms}</p>";
        echo "<p class='info'>Path: " . realpath($fullPath) . "</p>";
        
        // Check if writable
        if (is_writable($fullPath)) {
            echo "<p class='success'>✅ Directory is writable</p>";
        } else {
            echo "<p class='error'>❌ Directory is NOT writable - Please check permissions</p>";
        }
    }
    
    echo "<hr>";
}

echo "</div>";

// Create .htaccess to protect uploads (except images)
echo "<div class='setup-section'>";
echo "<h2>🔒 Security Configuration</h2>";

$htaccessContent = "# Protect upload directories
Options -Indexes
<FilesMatch \"\.(jpg|jpeg|png|gif)$\">
    Allow from all
</FilesMatch>
";

foreach (['uploads/profiles', 'uploads/tickets'] as $dir) {
    $fullPath = $basePath . $dir;
    $htaccessFile = $fullPath . '/.htaccess';
    
    if (is_dir($fullPath)) {
        if (file_exists($htaccessFile)) {
            echo "<p class='success'>✅ .htaccess exists in {$dir}</p>";
        } else {
            if (file_put_contents($htaccessFile, $htaccessContent)) {
                echo "<p class='success'>✅ Created .htaccess in {$dir}</p>";
            } else {
                echo "<p class='error'>❌ Failed to create .htaccess in {$dir}</p>";
            }
        }
    }
}

echo "</div>";

// Summary
echo "<div class='setup-section'>";
echo "<h2>📊 Setup Summary</h2>";

$created = count(array_filter($results, fn($r) => $r === 'created'));
$exists = count(array_filter($results, fn($r) => $r === 'exists'));
$failed = count(array_filter($results, fn($r) => $r === 'failed'));

echo "<p><strong>Total Directories:</strong> " . count($directories) . "</p>";
echo "<p class='success'><strong>Created:</strong> {$created}</p>";
echo "<p class='info'><strong>Already Existed:</strong> {$exists}</p>";
echo "<p class='error'><strong>Failed:</strong> {$failed}</p>";

if ($failed === 0) {
    echo "<p class='success'>🎉 All upload directories are ready!</p>";
} else {
    echo "<p class='error'>⚠️ Some directories could not be created. Please check server permissions.</p>";
}

echo "<p><a href='add_employee.php' style='color: #2563eb;'>← Back to Add Employee</a> | ";
echo "<a href='test_add_employee.php' style='color: #2563eb;'>Run Tests →</a></p>";

echo "</div>";
?>
