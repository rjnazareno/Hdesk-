<?php
/**
 * Index/Home page - redirects to appropriate page
 */

require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
