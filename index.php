<?php
/**
 * Main Index Page - Redirects to Dashboard or Login
 */
session_start();

// Redirect based on authentication status
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>