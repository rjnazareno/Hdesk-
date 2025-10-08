<?php
require_once __DIR__ . '/../config/config.php';

$auth = new Auth();
$auth->requireLogin();

// Ensure only employees can access
if ($_SESSION['user_type'] !== 'employee') {
    redirect('admin/dashboard.php');
}

$currentUser = $auth->getCurrentUser();
$isITStaff = $currentUser['role'] === 'it_staff' || $currentUser['role'] === 'admin';

$ticketModel = new Ticket();
$categoryModel = new Category();

// Get filter parameters
$filters = [
    'status' => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// If employee, only show their tickets
if (!$isITStaff) {
    $filters['submitter_id'] = $currentUser['id'];
}

// Get tickets
$tickets = $ticketModel->getAll($filters);
$categories = $categoryModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets - IT Help Desk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-gray-900 text-white">
        <div class="flex items-center justify-center h-16 bg-gray-800">
            <i class="fas fa-layer-group text-xl mr-2"></i>
            <span class="text-xl font-bold">ResolveIT</span>
        </div>
        
        <nav class="mt-6">
            <a href="dashboard.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-th-large w-6"></i>
                <span>Dashboard</span>
            </a>
            <a href="tickets.php" class="flex items-center px-6 py-3 bg-gray-800 text-white">
                <i class="fas fa-ticket-alt w-6"></i>
                <span>Tickets</span>
            </a>
            <?php if (!$isITStaff): ?>
            <a href="create_ticket.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-plus-circle w-6"></i>
                <span>Create Ticket</span>
            </a>
            <?php endif; ?>
            <?php if ($isITStaff): ?>
            <a href="customers.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-users w-6"></i>
                <span>Customers</span>
            </a>
            <a href="categories.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-folder w-6"></i>
                <span>Categories</span>
            </a>
            <?php endif; ?>
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
                    <h1 class="text-2xl font-bold text-gray-900">Support Tickets</h1>
                    <p class="text-gray-600">Manage and track all support requests</p>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (!$isITStaff): ?>
                    <a href="create_ticket.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>New Ticket
                    </a>
                    <?php endif; ?>
                    <?php if ($isITStaff): ?>
                    <a href="export_tickets.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-file-excel mr-2"></i>Export
                    </a>
                    <?php endif; ?>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=2563eb&color=fff" 
                         alt="User" 
                         class="w-10 h-10 rounded-full">
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php 
                    if ($_GET['success'] === 'created') {
                        echo 'Ticket created successfully!';
                    } elseif ($_GET['success'] === 'updated') {
                        echo 'Ticket updated successfully!';
                    }
                ?>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <form method="GET" action="tickets.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($filters['search']); ?>"
                            placeholder="Ticket number, title..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="open" <?php echo $filters['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $filters['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <select name="priority" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Priority</option>
                            <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="urgent" <?php echo $filters['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $filters['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-4 flex justify-end space-x-2">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                        <a href="tickets.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tickets Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <?php if ($isITStaff): ?>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitter</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                <?php endif; ?>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="<?php echo $isITStaff ? '8' : '6'; ?>" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 text-gray-400"></i>
                                    <p>No tickets found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $ticket): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($ticket['title']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium" style="background-color: <?php echo $ticket['category_color']; ?>20; color: <?php echo $ticket['category_color']; ?>">
                                            <?php echo htmlspecialchars($ticket['category_name']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $priorityColors = [
                                            'low' => 'bg-green-100 text-green-800',
                                            'medium' => 'bg-yellow-100 text-yellow-800',
                                            'high' => 'bg-orange-100 text-orange-800',
                                            'urgent' => 'bg-red-100 text-red-800'
                                        ];
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $priorityColors[$ticket['priority']]; ?>">
                                            <?php echo strtoupper($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'open' => 'bg-blue-100 text-blue-800',
                                            'in_progress' => 'bg-purple-100 text-purple-800',
                                            'resolved' => 'bg-green-100 text-green-800',
                                            'closed' => 'bg-gray-100 text-gray-800'
                                        ];
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusColors[$ticket['status']]; ?>">
                                            <?php echo str_replace('_', ' ', strtoupper($ticket['status'])); ?>
                                        </span>
                                    </td>
                                    <?php if ($isITStaff): ?>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($ticket['submitter_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo $ticket['assigned_name'] ? htmlspecialchars($ticket['assigned_name']) : '<span class="text-gray-400">Unassigned</span>'; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo formatDate($ticket['created_at'], 'M d, Y'); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
