<?php
/**
 * Debug Script - Check Auth Class Loading
 * Upload this to production to diagnose the issue
 */

echo "<h1>Auth Class Debug</h1>";

// Check if file exists
$authPath = __DIR__ . '/includes/Auth.php';
echo "<p><strong>Auth.php path:</strong> $authPath</p>";
echo "<p><strong>File exists:</strong> " . (file_exists($authPath) ? 'YES' : 'NO') . "</p>";

// Check directory contents
echo "<h2>includes/ directory contents:</h2>";
$files = scandir(__DIR__ . '/includes/');
echo "<pre>";
print_r($files);
echo "</pre>";

// Try to include config
echo "<h2>Loading config.php...</h2>";
require_once __DIR__ . '/config/config.php';
echo "<p>✓ Config loaded</p>";

// Try to load Auth manually
echo "<h2>Loading Auth.php manually...</h2>";
if (file_exists($authPath)) {
    require_once $authPath;
    echo "<p>✓ Auth.php included</p>";
} else {
    echo "<p>✗ Auth.php NOT FOUND</p>";
}

// Try to instantiate Auth
echo "<h2>Instantiating Auth class...</h2>";
try {
    $auth = new Auth();
    echo "<p>✓ Auth class instantiated successfully!</p>";
    echo "<pre>";
    print_r(get_class_methods($auth));
    echo "</pre>";
} catch (Error $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Autoloader Check</h2>";
echo "<p>Checking if autoloader can find Auth...</p>";

class_exists('Auth', true); // Force autoload

if (class_exists('Auth', false)) {
    echo "<p>✓ Auth class is loaded</p>";
} else {
    echo "<p>✗ Auth class NOT loaded by autoloader</p>";
}

// Check all loaded classes
echo "<h2>All Declared Classes (filtered):</h2>";
$classes = get_declared_classes();
$filtered = array_filter($classes, function($class) {
    return stripos($class, 'Auth') !== false || 
           stripos($class, 'User') !== false || 
           stripos($class, 'Login') !== false;
});
echo "<pre>";
print_r($filtered);
echo "</pre>";

echo "<h2>PHP Info</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>OS: " . PHP_OS . "</p>";
echo "<p>Case sensitive: " . (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'NO (Windows)' : 'YES (Linux/Unix)') . "</p>";
