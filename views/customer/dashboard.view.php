<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IT Help Desk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Firebase SDK for Cloud Messaging -->
    <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-analytics-compat.js"></script>
    
    <!-- Quick Wins CSS -->
    <link rel="stylesheet" href="../assets/css/print.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../includes/customer_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-gradient-to-r from-white to-blue-50 shadow-sm border-b border-blue-100">
            <div class="flex items-center justify-between px-4 lg:px-8 py-6 pt-20 lg:pt-6">
                <div class="flex items-center space-x-4">
                    <div class="hidden lg:block">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg ring-4 ring-blue-100">
                            <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2 mb-1">
                            <h1 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-gray-900 to-blue-600 bg-clip-text text-transparent">
                                <span id="greetingText">Good Morning</span>, <?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?>! 👋
                            </h1>
                        </div>
                        <div class="flex items-center space-x-4 text-sm text-gray-600">
                            <span class="flex items-center">
                                <i class="far fa-clock mr-1.5 text-blue-500"></i>
                                <span id="lastLoginDisplay">Last login: Loading...</span>
                            </span>
                            <span class="hidden md:flex items-center">
                                <i class="fas fa-user mr-1.5 text-blue-500"></i>
                                Employee
                            </span>
                            <span class="hidden md:flex items-center">
                                <i class="far fa-calendar mr-1.5 text-blue-500"></i>
                                <span id="currentDate"></span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="darkModeToggle" class="p-2 text-gray-600 hover:text-gray-900" title="Toggle dark mode">
                        <i id="dark-mode-icon" class="fas fa-moon"></i>
                    </button>
                    <button class="p-2 text-gray-600 hover:text-gray-900 relative" title="Notifications">
                        <i class="far fa-bell"></i>
                        <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    <div class="flex items-center space-x-2">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=2563eb&color=fff" 
                             alt="User"
                             title="<?php echo htmlspecialchars($currentUser['full_name']); ?>" 
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
                                                'high' => 'bg-red-100 text-red-800',
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
                                            <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="text-blue-600 hover:text-blue-800" title="View ticket details">
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
    
    <!-- Quick Wins JavaScript -->
    <script src="../assets/js/helpers.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script>
        // Dynamic greeting based on time
        function updateGreeting() {
            const hour = new Date().getHours();
            const greetingText = document.getElementById('greetingText');
            
            if (hour >= 5 && hour < 12) {
                greetingText.textContent = 'Good Morning';
            } else if (hour >= 12 && hour < 17) {
                greetingText.textContent = 'Good Afternoon';
            } else if (hour >= 17 && hour < 22) {
                greetingText.textContent = 'Good Evening';
            } else {
                greetingText.textContent = 'Welcome Back';
            }
        }
        
        // Update current date display
        function updateCurrentDate() {
            const dateElement = document.getElementById('currentDate');
            if (dateElement) {
                const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
                dateElement.textContent = new Date().toLocaleDateString('en-US', options);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            initTooltips();
            initDarkMode();
            
            // Update greeting and date
            updateGreeting();
            updateCurrentDate();
            
            updateLastLogin('<?php echo date('Y-m-d H:i:s'); ?>');
            updateTimeAgo();
            setInterval(updateTimeAgo, 60000);
            
            // Update greeting every minute
            setInterval(updateGreeting, 60000);
        });
    </script>
    
    <!-- Firebase Initialization -->
    <script src="../assets/js/firebase-init.js"></script>
    <script>
        // Set logged in status for Firebase
        document.body.dataset.userLoggedIn = 'true';
    </script>
</body>
</html>
