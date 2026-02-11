<?php
/**
 * Verification Script - Check Reset Status
 * Run this after cleanup to verify everything was reset correctly
 * Access: http://localhost/IThelp/verify_reset.php
 */

require_once 'config/database.php';

// Verify database connection
try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Verification - IT Help Desk</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4">
        <div class="max-w-4xl mx-auto">
            
            <!-- Header -->
            <div class="bg-white border border-gray-200 p-8 mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    🔍 Reset Verification Report
                </h1>
                <p class="text-gray-600">
                    Check if ticket system was successfully reset
                </p>
                <p class="text-sm text-gray-500 mt-2">
                    <?php echo date('F j, Y g:i A'); ?>
                </p>
            </div>

            <?php
            // Check tickets table
            $ticketsCount = 0;
            $ticketActivityCount = 0;
            $notificationsCount = 0;
            $slaTrackingCount = 0;
            $usersCount = 0;
            $categoriesCount = 0;
            $employeesCount = 0;

            try {
                // Count tickets
                $stmt = $db->query("SELECT COUNT(*) as count FROM tickets");
                $ticketsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                // Count ticket activity
                $stmt = $db->query("SELECT COUNT(*) as count FROM ticket_activity");
                $ticketActivityCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                // Count notifications
                $stmt = $db->query("SELECT COUNT(*) as count FROM notifications");
                $notificationsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                // Count SLA tracking
                $result = $db->query("SHOW TABLES LIKE 'sla_tracking'");
                if ($result->rowCount() > 0) {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM sla_tracking");
                    $slaTrackingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                }

                // Count users (should be preserved)
                $stmt = $db->query("SELECT COUNT(*) as count FROM users");
                $usersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                // Count categories (should be preserved)
                $stmt = $db->query("SELECT COUNT(*) as count FROM categories");
                $categoriesCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                // Count employees (if table exists)
                $result = $db->query("SHOW TABLES LIKE 'employees'");
                if ($result->rowCount() > 0) {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM employees");
                    $employeesCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                }

            } catch (PDOException $e) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mb-6">';
                echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }

            // Determine overall status
            $isClean = ($ticketsCount == 0 && $ticketActivityCount == 0 && $notificationsCount == 0 && $slaTrackingCount == 0);
            $hasUsers = ($usersCount > 0 && $categoriesCount > 0);
            $overallStatus = $isClean && $hasUsers;

            ?>

            <!-- Overall Status -->
            <div class="bg-white border border-gray-200 p-8 mb-6">
                <div class="flex items-center mb-4">
                    <div class="text-4xl mr-4">
                        <?php echo $overallStatus ? '✅' : '⚠️'; ?>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">
                            <?php echo $overallStatus ? 'Reset Successful!' : 'Issues Detected'; ?>
                        </h2>
                        <p class="text-gray-600">
                            <?php 
                            if ($overallStatus) {
                                echo 'Your ticket system has been successfully reset and is ready to use.';
                            } else {
                                echo 'Some issues were found. Please review the details below.';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Data Tables Status -->
            <div class="bg-white border border-gray-200 p-8 mb-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">
                    📊 Database Tables Status
                </h3>

                <div class="space-y-3">
                    <!-- Tickets -->
                    <div class="flex items-center justify-between py-3 border-b border-gray-200">
                        <div>
                            <span class="font-medium text-gray-900">Tickets</span>
                            <span class="text-sm text-gray-500 ml-2">(should be 0)</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-lg font-bold mr-3 <?php echo $ticketsCount == 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $ticketsCount; ?>
                            </span>
                            <span class="text-2xl">
                                <?php echo $ticketsCount == 0 ? '✓' : '✗'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Ticket Activity -->
                    <div class="flex items-center justify-between py-3 border-b border-gray-200">
                        <div>
                            <span class="font-medium text-gray-900">Ticket Activity</span>
                            <span class="text-sm text-gray-500 ml-2">(should be 0)</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-lg font-bold mr-3 <?php echo $ticketActivityCount == 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $ticketActivityCount; ?>
                            </span>
                            <span class="text-2xl">
                                <?php echo $ticketActivityCount == 0 ? '✓' : '✗'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="flex items-center justify-between py-3 border-b border-gray-200">
                        <div>
                            <span class="font-medium text-gray-900">Notifications</span>
                            <span class="text-sm text-gray-500 ml-2">(should be 0)</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-lg font-bold mr-3 <?php echo $notificationsCount == 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $notificationsCount; ?>
                            </span>
                            <span class="text-2xl">
                                <?php echo $notificationsCount == 0 ? '✓' : '✗'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- SLA Tracking -->
                    <div class="flex items-center justify-between py-3 border-b border-gray-200">
                        <div>
                            <span class="font-medium text-gray-900">SLA Tracking</span>
                            <span class="text-sm text-gray-500 ml-2">(should be 0)</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-lg font-bold mr-3 <?php echo $slaTrackingCount == 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $slaTrackingCount; ?>
                            </span>
                            <span class="text-2xl">
                                <?php echo $slaTrackingCount == 0 ? '✓' : '✗'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Users (Preserved) -->
                    <div class="flex items-center justify-between py-3 border-b border-gray-200">
                        <div>
                            <span class="font-medium text-gray-900">Users</span>
                            <span class="text-sm text-gray-500 ml-2">(should be > 0, preserved)</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-lg font-bold mr-3 <?php echo $usersCount > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $usersCount; ?>
                            </span>
                            <span class="text-2xl">
                                <?php echo $usersCount > 0 ? '✓' : '✗'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Categories (Preserved) -->
                    <div class="flex items-center justify-between py-3 border-b border-gray-200">
                        <div>
                            <span class="font-medium text-gray-900">Categories</span>
                            <span class="text-sm text-gray-500 ml-2">(should be > 0, preserved)</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-lg font-bold mr-3 <?php echo $categoriesCount > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $categoriesCount; ?>
                            </span>
                            <span class="text-2xl">
                                <?php echo $categoriesCount > 0 ? '✓' : '✗'; ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($employeesCount > 0): ?>
                    <!-- Employees (Preserved) -->
                    <div class="flex items-center justify-between py-3">
                        <div>
                            <span class="font-medium text-gray-900">Employees</span>
                            <span class="text-sm text-gray-500 ml-2">(from Harley, preserved)</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-lg font-bold mr-3 text-blue-600">
                                <?php echo $employeesCount; ?>
                            </span>
                            <span class="text-2xl">ℹ️</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-white border border-gray-200 p-8 mb-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">
                    🎯 Next Steps
                </h3>

                <?php if ($overallStatus): ?>
                <div class="space-y-3">
                    <div class="flex items-start">
                        <span class="text-green-600 mr-3">✓</span>
                        <span class="text-gray-700">Database cleaned successfully</span>
                    </div>
                    <div class="flex items-start">
                        <span class="text-gray-400 mr-3">→</span>
                        <span class="text-gray-700">Clear browser cache and logout/login</span>
                    </div>
                    <div class="flex items-start">
                        <span class="text-gray-400 mr-3">→</span>
                        <span class="text-gray-700">Create a test ticket to verify system works</span>
                    </div>
                    <div class="flex items-start">
                        <span class="text-gray-400 mr-3">→</span>
                        <span class="text-gray-700">Check <code class="bg-gray-100 px-2 py-1 text-sm">uploads/</code> directory is empty</span>
                    </div>
                    <div class="flex items-start">
                        <span class="text-gray-400 mr-3">→</span>
                        <span class="text-gray-700">Consider running <code class="bg-gray-100 px-2 py-1 text-sm">OPTIONAL_SIMPLIFY_SCHEMA.sql</code> to prevent future tracking issues</span>
                    </div>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php if ($ticketsCount > 0): ?>
                    <div class="flex items-start">
                        <span class="text-red-600 mr-3">✗</span>
                        <span class="text-gray-700">Run <code class="bg-gray-100 px-2 py-1 text-sm">database/CLEAN_RESET_TICKETS.sql</code> in phpMyAdmin</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($usersCount == 0): ?>
                    <div class="flex items-start">
                        <span class="text-red-600 mr-3">✗</span>
                        <span class="text-gray-700">No users found! Run <code class="bg-gray-100 px-2 py-1 text-sm">database/schema.sql</code> to recreate default users</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($categoriesCount == 0): ?>
                    <div class="flex items-start">
                        <span class="text-red-600 mr-3">✗</span>
                        <span class="text-gray-700">No categories found! Run <code class="bg-gray-100 px-2 py-1 text-sm">database/schema.sql</code> to recreate default categories</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="flex gap-4">
                <a href="index.php" class="bg-gray-900 text-white px-6 py-3 hover:bg-gray-800">
                    ← Back to Login
                </a>
                <a href="verify_reset.php" class="bg-white border border-gray-300 text-gray-700 px-6 py-3 hover:bg-gray-50">
                    🔄 Refresh Check
                </a>
            </div>

        </div>
    </div>
</body>
</html>
