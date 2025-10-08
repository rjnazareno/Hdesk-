<?php
require_once __DIR__ . '/../config/config.php';

$auth = new Auth();
$auth->requireLogin();

// Ensure only employees can access
if ($_SESSION['user_type'] !== 'employee') {
    redirect('admin/dashboard.php');
}

$ticketModel = new Ticket();
$categoryModel = new Category();
$activityModel = new TicketActivity();

$currentUser = $auth->getCurrentUser();

// Get statistics for this employee only
$stats = $ticketModel->getStats($currentUser['id'], 'employee');

// Get recent tickets for this employee
$recentTickets = $ticketModel->getAll([
    'limit' => 10,
    'submitter_id' => $currentUser['id']
]);

// Get recent activity for this employee
$recentActivity = $activityModel->getRecent(5, $currentUser['id'], 'employee');

// Get categories
$categories = $categoryModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IT Help Desk</title>
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
            <a href="dashboard.php" class="flex items-center px-6 py-3 bg-gray-800 text-white">
                <i class="fas fa-th-large w-6"></i>
                <span>Dashboard</span>
            </a>
            <a href="tickets.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-ticket-alt w-6"></i>
                <span>My Tickets</span>
            </a>
            <a href="create_ticket.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-plus-circle w-6"></i>
                <span>Create Ticket</span>
            </a>
            <a href="../article.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-newspaper w-6"></i>
                <span>Knowledge Base</span>
            </a>
            <a href="../logout.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition mt-8">
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
                    <h1 class="text-2xl font-bold text-gray-900">Welcome Back</h1>
                    <p class="text-gray-600">Hello <?php echo htmlspecialchars($currentUser['full_name']); ?>, Good Morning!</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="p-2 text-gray-600 hover:text-gray-900 relative">
                        <i class="far fa-bell"></i>
                        <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    <div class="flex items-center space-x-2">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=2563eb&color=fff" 
                             alt="User" 
                             class="w-10 h-10 rounded-full">
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="p-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Total Tickets</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo $stats['total']; ?></h3>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-ticket-alt text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Open Tickets</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo $stats['open']; ?></h3>
                        </div>
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-folder-open text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Pending</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo $stats['pending']; ?></h3>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-lg">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Closed</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo $stats['closed']; ?></h3>
                        </div>
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="fas fa-check-circle text-gray-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Tickets -->
            <div class="bg-white rounded-xl shadow-sm">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold">My Recent Tickets</h3>
                        <p class="text-gray-600 text-sm">View and manage your support tickets</p>
                    </div>
                    <a href="create_ticket.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>New Ticket
                    </a>
                </div>
                <div class="p-6">
                    <?php if (empty($recentTickets)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-inbox text-gray-400 text-4xl mb-3"></i>
                            <p class="text-gray-600">No tickets found. Create your first ticket to get started!</p>
                            <a href="create_ticket.php" class="inline-block mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                                Create Ticket
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-gray-600 text-sm border-b">
                                        <th class="pb-3">Ticket #</th>
                                        <th class="pb-3">Title</th>
                                        <th class="pb-3">Category</th>
                                        <th class="pb-3">Priority</th>
                                        <th class="pb-3">Status</th>
                                        <th class="pb-3">Created</th>
                                        <th class="pb-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm">
                                    <?php foreach ($recentTickets as $ticket): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-4">
                                            <span class="font-mono text-blue-600"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                                        </td>
                                        <td class="py-4">
                                            <div class="font-medium"><?php echo htmlspecialchars($ticket['title']); ?></div>
                                        </td>
                                        <td class="py-4">
                                            <span class="text-gray-600"><?php echo htmlspecialchars($ticket['category_name']); ?></span>
                                        </td>
                                        <td class="py-4">
                                            <?php
                                            $priorityColors = [
                                                'low' => 'bg-green-100 text-green-800',
                                                'medium' => 'bg-yellow-100 text-yellow-800',
                                                'high' => 'bg-red-100 text-red-800'
                                            ];
                                            $priorityClass = $priorityColors[$ticket['priority']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $priorityClass; ?>">
                                                <?php echo ucfirst($ticket['priority']); ?>
                                            </span>
                                        </td>
                                        <td class="py-4">
                                            <?php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'open' => 'bg-blue-100 text-blue-800',
                                                'in_progress' => 'bg-purple-100 text-purple-800',
                                                'closed' => 'bg-gray-100 text-gray-800'
                                            ];
                                            $statusClass = $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 text-gray-600">
                                            <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                        </td>
                                        <td class="py-4">
                                            <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
