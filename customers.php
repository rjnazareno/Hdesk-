<?php
require_once __DIR__ . '/config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireITStaff();

$userModel = new User();
$currentUser = $auth->getCurrentUser();

// Get all customers (employees)
$customers = $userModel->getAll('employee');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - IT Help Desk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-gray-900 text-white">
        <div class="flex items-center justify-center h-16 bg-gray-800">
            <i class="fas fa-layer-group text-xl mr-2"></i>
            <span class="text-xl font-bold">Simply Web</span>
        </div>
        
        <nav class="mt-6">
            <a href="dashboard.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-th-large w-6"></i>
                <span>Dashboard</span>
            </a>
            <a href="tickets.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-ticket-alt w-6"></i>
                <span>Tickets</span>
            </a>
            <a href="customers.php" class="flex items-center px-6 py-3 bg-gray-800 text-white">
                <i class="fas fa-users w-6"></i>
                <span>Employees</span>
            </a>
            <a href="categories.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-folder w-6"></i>
                <span>Categories</span>
            </a>
            <a href="logout.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition mt-8">
                <i class="fas fa-sign-out-alt w-6"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-white shadow-sm">
            <div class="flex items-center justify-between px-8 py-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
                    <p class="text-gray-600">Manage registered customers</p>
                </div>
                <div class="flex items-center space-x-2">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=2563eb&color=fff" 
                         alt="User" 
                         class="w-10 h-10 rounded-full">
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($customers as $customer): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($customer['full_name']); ?>&background=random" 
                                             alt="<?php echo htmlspecialchars($customer['full_name']); ?>" 
                                             class="w-10 h-10 rounded-full mr-3">
                                        <div>
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($customer['full_name']); ?></div>
                                            <div class="text-sm text-gray-500">@<?php echo htmlspecialchars($customer['username']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($customer['department'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($customer['is_active']): ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    <?php else: ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo formatDate($customer['created_at'], 'M d, Y'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
