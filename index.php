<?php
/**
 * Index/Home page - redirects to appropriate page
 */

require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    // Redirect based on user type
    if (isset($_SESSION['user_type'])) {
        if ($_SESSION['user_type'] === 'employee') {
            redirect('customer/dashboard.php');
        } else {
            redirect('admin/dashboard.php');
        }
    } else {
        // Fallback if user_type not set
        redirect('login.php');
    }
} else {
    redirect('login.php');
}
