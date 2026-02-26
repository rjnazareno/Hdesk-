<?php
// Quick cache-clear helper - delete this file after use
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully.";
} else {
    echo "OPcache not enabled (no action needed).";
}
echo "<br>PHP version: " . PHP_VERSION;
echo "<br>Done. <a href='admin/dashboard.php'>Go to Dashboard</a>";
