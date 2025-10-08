<?php
/**
 * Session Debug - Check current session values
 */

session_start();

echo "<h2>Current Session Information</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<h3>Actions:</h3>";
echo "<p><a href='logout.php'>Logout and Login Again</a></p>";
echo "<p><a href='admin/dashboard.php'>Back to Dashboard</a></p>";
?>
