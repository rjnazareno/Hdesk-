<?php
/**
 * Install Composer Dependencies on Production
 * Run this file ONCE via browser: https://resolveit.resourcestaffonline.com/install_dependencies.php
 * DELETE this file after running!
 */

set_time_limit(300); // 5 minutes
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Installing Composer Dependencies</h1>";
echo "<pre>";

// Check if composer is available
$composerPath = '/usr/local/bin/composer'; // Common path
if (!file_exists($composerPath)) {
    $composerPath = 'composer'; // Try system PATH
}

echo "Step 1: Checking Composer...\n";
exec("$composerPath --version 2>&1", $output, $return);
if ($return !== 0) {
    echo "ERROR: Composer not found. Please install Composer on your server.\n";
    echo "Visit: https://getcomposer.org/download/\n";
    exit;
}
echo implode("\n", $output) . "\n\n";

echo "Step 2: Running composer install...\n";
$output = [];
$cwd = __DIR__;
chdir($cwd);

// Run composer install with ignore platform requirements
exec("$composerPath install --no-dev --optimize-autoloader --ignore-platform-reqs 2>&1", $output, $return);
echo implode("\n", $output) . "\n\n";

if ($return === 0) {
    echo "\n✅ SUCCESS! Composer dependencies installed.\n";
    echo "\n🔥 IMPORTANT: DELETE this file (install_dependencies.php) now for security!\n";
} else {
    echo "\n❌ ERROR: Composer install failed.\n";
    echo "Return code: $return\n";
}

echo "</pre>";
?>
