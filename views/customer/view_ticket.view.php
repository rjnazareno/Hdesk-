<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?> - IT Help Desk</title>
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
        <div class="bg-white shadow-sm border-b">
            <div class="flex items-center justify-between px-8 py-4 pt-20 lg:pt-4">
                <div>
                    <div class="flex items-center space-x-3">
                        <h1 class="text-2xl font-bold text-gray-900">Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?></h1>
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
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $statusClass; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                        </span>
                    </div>
                    <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($ticket['title']); ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="darkModeToggle" class="p-2 text-gray-600 hover:text-gray-900" title="Toggle dark mode">
                        <i id="dark-mode-icon" class="fas fa-moon"></i>
                    </button>
                    <a href="tickets.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Tickets
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
            <nav class="flex mb-6" aria-label="Breadcrumb">
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
                            <a href="tickets.php" class="ml-1 text-sm font-medium text-gray-600 hover:text-blue-600">My Tickets</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-700">#<?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
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
                    if ($_GET['success'] === 'updated') {
                        echo 'Ticket updated successfully!';
                    } elseif ($_GET['success'] === 'commented') {
                        echo 'Comment added successfully!';
                    }
                ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Ticket Details Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Ticket Details</h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Description</label>
                                <div class="mt-2 text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($ticket['description']); ?></div>
                            </div>

                            <?php if ($ticket['attachments']): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Attachment</label>
                                <div class="mt-2">
                                    <a href="../uploads/<?php echo htmlspecialchars($ticket['attachments']); ?>" 
                                       target="_blank"
                                       class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                                        <i class="fas fa-paperclip mr-2"></i>
                                        <?php echo htmlspecialchars($ticket['attachments']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($ticket['resolution']) && $isITStaff): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Resolution</label>
                                <div class="mt-2 p-4 bg-green-50 border border-green-200 rounded-lg text-gray-900">
                                    <?php echo htmlspecialchars($ticket['resolution']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Activity Log -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-history mr-2"></i>Activity Log
                        </h2>
                        
                        <?php if (empty($activities)): ?>
                            <p class="text-gray-500 text-center py-8">No activity yet</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($activities as $activity): ?>
                                <div class="flex items-start space-x-3 pb-4 border-b last:border-0">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-<?php 
                                                echo $activity['action_type'] === 'created' ? 'plus' : 
                                                    ($activity['action_type'] === 'status_change' ? 'exchange-alt' : 
                                                    ($activity['action_type'] === 'assigned' ? 'user-plus' : 
                                                    ($activity['action_type'] === 'commented' ? 'comment' : 'edit'))); 
                                            ?> text-blue-600 text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900">
                                            <strong><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></strong>
                                            <?php echo htmlspecialchars($activity['comment']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php echo date('M d, Y g:i A', strtotime($activity['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- IT Staff Update Form (Only visible to IT Staff) -->
                    <?php if ($isITStaff): ?>
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-edit mr-2"></i>Update Ticket
                        </h2>
                        
                        <form action="view_ticket.php" method="POST" class="space-y-4">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-2">Assign To</label>
                                    <select name="assigned_to" id="assigned_to" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($itStaff as $staff): ?>
                                        <option value="<?php echo $staff['id']; ?>" <?php echo $ticket['assigned_to'] == $staff['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($staff['full_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label for="resolution" class="block text-sm font-medium text-gray-700 mb-2">Resolution / Notes</label>
                                <textarea name="resolution" id="resolution" rows="4" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                          placeholder="Add resolution details or notes..."><?php echo htmlspecialchars($ticket['resolution'] ?? ''); ?></textarea>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-save mr-2"></i>Update Ticket
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Ticket Info Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ticket Information</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Ticket Number</label>
                                <p class="mt-1 font-mono text-blue-600 font-semibold"><?php echo htmlspecialchars($ticket['ticket_number']); ?></p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Priority</label>
                                <div class="mt-1">
                                    <?php
                                    $priorityColors = [
                                        'low' => 'bg-green-100 text-green-800',
                                        'medium' => 'bg-yellow-100 text-yellow-800',
                                        'high' => 'bg-orange-100 text-orange-800',
                                        'urgent' => 'bg-red-600 text-white'
                                    ];
                                    $priorityClass = $priorityColors[$ticket['priority']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $priorityClass; ?>">
                                        <?php echo ucfirst($ticket['priority']); ?>
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Category</label>
                                <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($ticket['category_name']); ?></p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Submitted By</label>
                                <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($ticket['submitter_name']); ?></p>
                            </div>

                            <?php if ($ticket['assigned_name']): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Assigned To</label>
                                <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($ticket['assigned_name']); ?></p>
                            </div>
                            <?php endif; ?>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Created</label>
                                <p class="mt-1 text-gray-900"><?php echo date('M d, Y g:i A', strtotime($ticket['created_at'])); ?></p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Last Updated</label>
                                <p class="mt-1 text-gray-900"><?php echo date('M d, Y g:i A', strtotime($ticket['updated_at'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-blue-900 mb-3">
                            <i class="fas fa-lightbulb mr-2"></i>Need Help?
                        </h3>
                        <ul class="space-y-2 text-sm text-blue-800">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                                <span>Check the activity log for updates</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                                <span>IT staff will respond within 24 hours</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                                <span>You'll receive email notifications</span>
                            </li>
                        </ul>
                    </div>
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
        });
    </script>
</body>
</html>
