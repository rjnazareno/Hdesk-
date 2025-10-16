<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets - IT Help Desk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Quick Wins CSS -->
    <link rel="stylesheet" href="../assets/css/print.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../includes/customer_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-white shadow-sm">
            <div class="flex items-center justify-between px-8 py-4 pt-20 lg:pt-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">My Tickets</h1>
                    <p class="text-gray-600">View and manage your support requests</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="darkModeToggle" class="p-2 text-gray-600 hover:text-gray-900" title="Toggle dark mode">
                        <i id="dark-mode-icon" class="fas fa-moon"></i>
                    </button>
                    <a href="create_ticket.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition" title="Create a new support ticket">
                        <i class="fas fa-plus mr-2"></i>New Ticket
                    </a>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=2563eb&color=fff" 
                         alt="User" 
                         class="w-10 h-10 rounded-full"
                         title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-blue-600">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-700">My Tickets</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

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
                        <a href="tickets.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Clear Filters
                        </a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-search mr-2"></i>Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tickets Table -->
            <div class="bg-white rounded-xl shadow-sm">
                <div class="p-6">
                    <?php if (empty($tickets)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-400 text-5xl mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Tickets Found</h3>
                            <p class="text-gray-600 mb-6">
                                <?php if (!empty(array_filter($filters))): ?>
                                    Try adjusting your filters or <a href="tickets.php" class="text-blue-600 hover:underline">clear all filters</a>
                                <?php else: ?>
                                    Create your first ticket to get started!
                                <?php endif; ?>
                            </p>
                            <?php if (empty(array_filter($filters))): ?>
                            <a href="create_ticket.php" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus mr-2"></i>Create Your First Ticket
                            </a>
                            <?php endif; ?>
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
                                    <?php foreach ($tickets as $ticket): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-4">
                                            <span class="font-mono text-blue-600 font-medium"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                                        </td>
                                        <td class="py-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($ticket['title']); ?></div>
                                        </td>
                                        <td class="py-4">
                                            <span class="text-gray-600"><?php echo htmlspecialchars($ticket['category_name']); ?></span>
                                        </td>
                                        <td class="py-4">
                                            <?php
                                            $priorityColors = [
                                                'low' => 'bg-green-100 text-green-800',
                                                'medium' => 'bg-yellow-100 text-yellow-800',
                                                'high' => 'bg-orange-100 text-orange-800',
                                                'urgent' => 'bg-red-600 text-white'
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
                                                'resolved' => 'bg-green-100 text-green-800',
                                                'closed' => 'bg-gray-100 text-gray-800'
                                            ];
                                            $statusClass = $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 text-gray-600">
                                            <span class="time-ago" data-timestamp="<?php echo $ticket['created_at']; ?>">
                                                <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                            </span>
                                        </td>
                                        <td class="py-4">
                                            <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-800" 
                                               title="View ticket details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-6 flex items-center justify-between text-sm text-gray-600">
                            <div>
                                Showing <strong><?php echo count($tickets); ?></strong> ticket<?php echo count($tickets) !== 1 ? 's' : ''; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Wins JavaScript -->
    <script src="../assets/js/helpers.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initTooltips();
            initDarkMode();
            updateTimeAgo();
            setInterval(updateTimeAgo, 60000);
        });
    </script>
</body>
</html>
