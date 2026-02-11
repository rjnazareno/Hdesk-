<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? (defined('APP_NAME') ? APP_NAME : 'ServiceHub'); ?></title>
    
    <!-- Tailwind CSS: Uses CDN in development, local file in production -->
    <?php echo getTailwindCSS(); ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if (isset($includeChartJs) && $includeChartJs): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <?php endif; ?>
    
    <!-- Firebase SDK for Cloud Messaging -->
    <?php if (isset($includeFirebase) && $includeFirebase): ?>
    <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-analytics-compat.js"></script>
    <?php endif; ?>
    
    <!-- Quick Wins CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl ?? '../'; ?>assets/css/print.css">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? '../'; ?>assets/css/dark-mode.css">
    
    <?php if (isset($customStyles)): ?>
    <style><?php echo $customStyles; ?></style>
    <?php endif; ?>
</head>
<body class="bg-slate-50">
    <?php 
    // Load appropriate navigation based on user type and role
    $userType = $_SESSION['user_type'] ?? 'employee';
    $userRole = $_SESSION['role'] ?? '';
    
    // Show admin navigation for:
    // 1. Users table (IT staff/admin)
    // 2. Employees with internal role
    if ($userType === 'user' || $userRole === 'internal' || $userRole === 'it_staff' || $userRole === 'admin') {
        include __DIR__ . '/../../includes/admin_nav.php';
    } else {
        include __DIR__ . '/../../includes/customer_nav.php';
    }
    ?>
