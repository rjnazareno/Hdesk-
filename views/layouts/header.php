<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'IT Help Desk'; ?></title>
    
    <!-- Tailwind CSS: Uses CDN in development, local file in production -->
    <?php echo getTailwindCSS(); ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if (isset($includeChartJs) && $includeChartJs): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <?php endif; ?>
    
    <!-- Quick Wins CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl ?? '../'; ?>assets/css/print.css">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? '../'; ?>assets/css/dark-mode.css">
    
    <?php if (isset($customStyles)): ?>
    <style><?php echo $customStyles; ?></style>
    <?php endif; ?>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../includes/admin_nav.php'; ?>
