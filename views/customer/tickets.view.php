<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets - <?php echo defined('APP_NAME') ? APP_NAME : 'ServiceHub'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../includes/customer_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <?php 
        // Set page variables for header
        $pageTitle = 'My Tickets';
        $pageSubtitle = 'View and manage your support requests';
        $showSearch = true;
        $basePath = '';
        include __DIR__ . '/../../includes/customer_header.php'; 
        ?>
        
        <script>
        // Ticket search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('customerQuickSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const query = e.target.value.toLowerCase();
                    document.querySelectorAll('.ticket-row').forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(query) ? '' : 'none';
                    });
                });
            }
        });
        </script>

        <!-- Content -->
        <div class="p-6">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border-2 border-emerald-400 rounded-2xl mb-6 shadow-lg overflow-hidden">
                <div class="flex items-start p-6">
                    <div class="flex-shrink-0">
                        <div class="w-14 h-14 bg-emerald-500 rounded-full flex items-center justify-center animate-bounce">
                            <i class="fas fa-check text-white text-2xl"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-xl font-bold text-emerald-900 mb-1">Success!</h3>
                        <p class="text-emerald-700 text-base font-medium"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                        <p class="text-emerald-600 text-sm mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            You'll receive email updates on your ticket status.
                        </p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 text-emerald-600 hover:text-emerald-800 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="h-2 bg-emerald-500 relative overflow-hidden">
                    <div class="h-full bg-emerald-600 animate-pulse"></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 flex items-center">
                <i class="fas fa-check-circle mr-3 text-emerald-500"></i>
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
            <div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
                <form method="GET" action="tickets.php" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Search</label>
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($filters['search']); ?>"
                            placeholder="Ticket number, title..."
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="open" <?php echo $filters['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $filters['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Priority</label>
                        <select name="priority" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white">
                            <option value="">All Priority</option>
                            <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Category</label>
                        <select name="category_id" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $filters['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Department</label>
                        <select name="department_id" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['id']; ?>" <?php echo $filters['department_id'] == $department['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($department['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition text-sm font-medium">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                        <a href="tickets.php" class="px-4 py-2.5 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition text-sm">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tickets Table -->
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <?php if (empty($tickets)): ?>
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-inbox text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">No Tickets Found</h3>
                        <p class="text-gray-500 text-sm mb-4">
                            <?php if (!empty(array_filter($filters))): ?>
                                Try adjusting your filters or <a href="tickets.php" class="text-emerald-600 hover:underline">clear all filters</a>
                            <?php else: ?>
                                Create your first ticket to get started!
                            <?php endif; ?>
                        </p>
                        <?php if (empty(array_filter($filters))): ?>
                        <a href="create_ticket.php" class="inline-flex items-center px-5 py-2.5 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition font-medium text-sm">
                            <i class="fas fa-plus mr-2"></i>Create Your First Ticket
                        </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ticket #</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($tickets as $ticket): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-4">
                                        <span class="font-mono text-emerald-600 font-medium text-sm"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($ticket['title']); ?></span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="text-gray-600 text-sm"><?php echo htmlspecialchars($ticket['category_name']); ?></span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <?php
                                        $priorityConfig = [
                                            'low' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
                                            'medium' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
                                            'high' => ['bg' => 'bg-red-100', 'text' => 'text-red-700']
                                        ];
                                        $priority = $priorityConfig[$ticket['priority']] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700'];
                                        ?>
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium <?php echo $priority['bg']; ?> <?php echo $priority['text']; ?>">
                                            <?php echo ucfirst($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <?php
                                        $statusConfig = [
                                            'pending' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'Pending'],
                                            'open' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Open'],
                                            'in_progress' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'label' => 'In Progress'],
                                            'resolved' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => 'Resolved'],
                                            'closed' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => 'Closed']
                                        ];
                                        $status = $statusConfig[$ticket['status']] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => ucfirst($ticket['status'])];
                                        ?>
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium <?php echo $status['bg']; ?> <?php echo $status['text']; ?>">
                                            <?php echo $status['label']; ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="text-gray-500 text-sm">
                                            <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center space-x-2">
                                            <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-emerald-600 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition">
                                                View Details
                                            </a>
                                            <?php 
                                            // Only show Cancel button if ticket is still pending and hasn't been assigned or worked on
                                            $canCancel = $ticket['status'] === 'pending' && 
                                                         empty($ticket['assigned_to']) && 
                                                         empty($ticket['grabbed_by']);
                                            if ($canCancel): 
                                            ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this ticket request?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-50 text-gray-600 hover:bg-gray-100 rounded-lg transition text-sm font-medium">
                                                    <i class="fas fa-times-circle mr-2"></i>
                                                    Cancel Request
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-4 border-t border-gray-100 bg-gray-50">
                        <span class="text-sm text-gray-500">
                            Showing <strong><?php echo count($tickets); ?></strong> ticket<?php echo count($tickets) !== 1 ? 's' : ''; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/helpers.js"></script>
</body>
</html>
