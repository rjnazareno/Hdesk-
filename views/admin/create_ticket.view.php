<?php 
// Include layout header
$pageTitle = 'Create Ticket - IT Help Desk';
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Top Bar -->
    <div class="bg-slate-800/50 border-b border-slate-700/50 backdrop-blur-md">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white rounded-lg">
                    <i class="fas fa-ticket-alt text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-white">Create New Ticket</h1>
                    <p class="text-sm text-slate-400 mt-0.5">Create a ticket on behalf of an employee</p>
                </div>
            </div>
            <div class="hidden lg:flex items-center space-x-2">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=fff" 
                     alt="User" 
                     class="w-8 h-8 rounded-full"
                     title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
            </div>
        </div>
    </div>

    <!-- Form Content -->
    <div class="p-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-slate-400 hover:text-cyan-400">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="tickets.php" class="ml-1 text-sm font-medium text-slate-400 hover:text-cyan-400">Tickets</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-slate-300">Create Ticket</span>
                    </div>
                </li>
            </ol>
        </nav>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 mb-6 rounded">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Section -->
            <div class="lg:col-span-2 bg-slate-800/50 border border-slate-700/50 p-6 rounded-lg">
            <div class="mb-6">
                <div class="flex items-center space-x-2 text-sm text-slate-300 bg-blue-500/10 border border-blue-500/30 px-4 py-3 rounded">
                    <i class="fas fa-info-circle text-blue-400"></i>
                    <span>You are creating a ticket on behalf of an employee. They will be notified via email.</span>
                </div>
            </div>

            <form action="create_ticket.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Employee Selection -->
                <div>
                    <label for="submitter_id" class="block text-sm font-medium text-white mb-2">
                        Select Employee <span class="text-red-400">*</span>
                    </label>
                    <select 
                        id="submitter_id" 
                        name="submitter_id" 
                        required
                        class="w-full px-4 py-3 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 focus:ring-2 focus:ring-cyan-500 focus:border-transparent rounded-lg"
                    >
                        <option value="">Choose an employee...</option>
                        <?php foreach ($employees as $employee): ?>
                        <option value="<?php echo $employee['id']; ?>">
                            <?php echo htmlspecialchars(trim($employee['fname'] . ' ' . $employee['lname'])); ?> 
                            - <?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-sm text-slate-400 mt-2">
                        <i class="fas fa-user-circle mr-1"></i>
                        Select the employee who is experiencing the issue
                    </p>
                </div>

                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-white mb-2">
                        Ticket Title <span class="text-red-400">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        required
                        class="w-full px-4 py-3 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 focus:ring-2 focus:ring-cyan-500 focus:border-transparent rounded-lg"
                        placeholder="Brief description of the issue"
                    >
                </div>

                <!-- Category and Priority -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-white mb-2">
                            Category <span class="text-red-400">*</span>
                        </label>
                        <select 
                            id="category_id" 
                            name="category_id" 
                            required
                            class="w-full px-4 py-3 border border-slate-600 bg-slate-700/50 text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent rounded-lg"
                        >
                            <option value="">Select category...</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="priority" class="block text-sm font-medium text-white mb-2">
                            Priority <span class="text-red-400">*</span>
                        </label>
                        <select 
                            id="priority" 
                            name="priority" 
                            required
                            class="w-full px-4 py-3 border border-slate-600 bg-slate-700/50 text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent rounded-lg"
                        >
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <!-- Assign To (Optional) -->
                <div>
                    <label for="assigned_to" class="block text-sm font-medium text-white mb-2">
                        Assign To (Optional)
                    </label>
                    <select 
                        id="assigned_to" 
                        name="assigned_to"
                        class="w-full px-4 py-3 border border-slate-600 bg-slate-700/50 text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent rounded-lg"
                    >
                        <option value="">Unassigned (will be auto-assigned)</option>
                        <?php 
                        // Get IT staff for assignment
                        $userModel = new User();
                        $assignableUsers = $userModel->getITStaff();
                        
                        foreach ($assignableUsers as $user): 
                        ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-sm text-slate-400 mt-2">
                        <i class="fas fa-user-tag mr-1"></i>
                        Leave empty for automatic assignment
                    </p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-white mb-2">
                        Description <span class="text-red-400">*</span>
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        rows="6" 
                        required
                        class="w-full px-4 py-3 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 focus:ring-2 focus:ring-cyan-500 focus:border-transparent rounded-lg"
                        placeholder="Provide detailed information about the issue..."
                    ></textarea>
                    <p class="text-sm text-slate-400 mt-2">
                        Include as much detail as possible: error messages, steps to reproduce, when it started, etc.
                    </p>
                </div>

                <!-- File Attachment -->
                <div>
                    <label for="attachment" class="block text-sm font-medium text-white mb-2">
                        Attachment (Optional)
                    </label>
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-600 border-dashed cursor-pointer hover:bg-slate-700/30 transition rounded-lg">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="fas fa-cloud-upload-alt text-slate-400 text-3xl mb-2"></i>
                                <p class="mb-2 text-sm text-slate-400">
                                    <span class="font-semibold">Click to upload</span> or drag and drop
                                </p>
                                <p class="text-xs text-slate-500">PNG, JPG, PDF, DOC (MAX. 5MB)</p>
                                <p class="text-xs text-slate-500 mt-1" id="file-name"></p>
                            </div>
                            <input id="attachment" name="attachment" type="file" class="hidden" />
                        </label>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-slate-700/50">
                    <a href="tickets.php" class="px-6 py-3 border border-slate-600 text-slate-300 hover:bg-slate-700/50 hover:text-white transition rounded-lg">
                        Cancel
                    </a>
                    <button 
                        type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold hover:from-cyan-600 hover:to-blue-700 transition rounded-lg"
                    >
                        <i class="fas fa-paper-plane mr-2"></i>Create Ticket
                    </button>
                </div>
            </form>
            </div>

            <!-- Sidebar Section -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Ticket Statistics -->
                <div class="bg-slate-800/50 border border-slate-700/50 p-6 rounded-lg overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/10 to-transparent pointer-events-none"></div>
                    <h3 class="text-sm font-semibold text-white mb-4 relative z-10">
                        <i class="fas fa-chart-bar mr-2"></i>Today's Stats
                    </h3>
                    <?php
                    $ticketModel = new Ticket();
                    $todayStats = $ticketModel->getTodayStats();
                    ?>
                    <div class="space-y-3 relative z-10">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-400">New Tickets</span>
                            <span class="text-lg font-bold text-blue-400"><?php echo $todayStats['new'] ?? 0; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-400">In Progress</span>
                            <span class="text-lg font-bold text-purple-400"><?php echo $todayStats['in_progress'] ?? 0; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-400">Closed Today</span>
                            <span class="text-lg font-bold text-emerald-400"><?php echo $todayStats['closed'] ?? 0; ?></span>
                        </div>
                        <div class="pt-3 border-t border-slate-700/50">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-semibold text-white">Open Tickets</span>
                                <span class="text-lg font-bold text-orange-400"><?php echo $todayStats['open'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Priority Guide -->
                <div class="bg-slate-800/50 border border-slate-700/50 p-6 rounded-lg">
                    <h3 class="text-sm font-semibold text-white mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Priority Guide
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-start space-x-2">
                            <div class="w-3 h-3 bg-red-600 mt-1 flex-shrink-0"></div>
                            <div>
                                <div class="font-semibold text-white">Urgent</div>
                                <div class="text-xs text-slate-400">Critical system down, blocking work</div>
                            </div>
                        </div>
                        <div class="flex items-start space-x-2">
                            <div class="w-3 h-3 bg-orange-600 mt-1 flex-shrink-0"></div>
                            <div>
                                <div class="font-semibold text-white">High</div>
                                <div class="text-xs text-slate-400">Major issue affecting multiple users</div>
                            </div>
                        </div>
                        <div class="flex items-start space-x-2">
                            <div class="w-3 h-3 bg-yellow-600 mt-1 flex-shrink-0"></div>
                            <div>
                                <div class="font-semibold text-white">Medium</div>
                                <div class="text-xs text-slate-400">Normal issue, has workaround</div>
                            </div>
                        </div>
                        <div class="flex items-start space-x-2">
                            <div class="w-3 h-3 bg-emerald-600 mt-1 flex-shrink-0"></div>
                            <div>
                                <div class="font-semibold text-white">Low</div>
                                <div class="text-xs text-slate-400">Minor issue, no immediate impact</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Tickets -->
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-lg overflow-hidden">
                    <div class="p-6 pb-4 border-b border-slate-700/50">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-white">
                                <i class="fas fa-clock mr-2"></i>Recent Tickets
                            </h3>
                            <?php if ($totalRecent > 0): ?>
                            <span class="text-xs text-slate-400">
                                <?php echo $totalRecent; ?> total
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="p-6 pt-4">
                        <?php if (empty($recentTickets)): ?>
                        <p class="text-sm text-slate-400 text-center py-4">No recent tickets</p>
                        <?php else: ?>
                        <div class="space-y-3 mb-4">
                            <?php foreach ($recentTickets as $ticket): 
                                $priorityColors = [
                                    'low' => 'bg-emerald-600',
                                    'medium' => 'bg-yellow-600',
                                    'high' => 'bg-orange-600',
                                    'urgent' => 'bg-red-600'
                                ];
                                $statusColors = [
                                    'open' => 'bg-blue-600',
                                    'in_progress' => 'bg-purple-600',
                                    'pending' => 'bg-yellow-600',
                                    'resolved' => 'bg-green-600',
                                    'closed' => 'bg-slate-600'
                                ];
                            ?>
                            <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="block border-b border-slate-700/50 pb-3 last:border-0 hover:bg-slate-700/20 transition -mx-2 px-2 py-2 rounded">
                                <div class="flex items-start justify-between mb-1">
                                    <div class="text-sm font-medium text-white line-clamp-1">
                                        <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                    </div>
                                    <span class="px-2 py-0.5 <?php echo $priorityColors[$ticket['priority']]; ?> text-white text-xs uppercase flex-shrink-0 ml-2">
                                        <?php echo $ticket['priority']; ?>
                                    </span>
                                </div>
                                <div class="text-xs text-slate-400 line-clamp-2 mb-2">
                                    <?php echo htmlspecialchars($ticket['title']); ?>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 <?php echo $statusColors[$ticket['status']]; ?> text-white text-xs">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                    <span class="text-xs text-slate-500">
                                        <?php echo date('M j, g:i A', strtotime($ticket['created_at'])); ?>
                                    </span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination Controls -->
                        <?php if ($totalPages > 1): ?>
                        <div class="pt-4 border-t border-slate-700/50">
                            <div class="flex items-center justify-between">
                                <!-- Previous -->
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" 
                                   class="px-2 py-1 bg-slate-700/50 border border-slate-600 text-slate-300 hover:bg-slate-700 hover:text-white transition rounded text-xs">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <?php else: ?>
                                <span class="px-2 py-1 bg-slate-800/50 border border-slate-700/50 text-slate-500 rounded cursor-not-allowed text-xs">
                                    <i class="fas fa-chevron-left"></i>
                                </span>
                                <?php endif; ?>

                                <!-- Page Info -->
                                <span class="text-xs text-slate-400">
                                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                                </span>

                                <!-- Next -->
                                <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" 
                                   class="px-2 py-1 bg-slate-700/50 border border-slate-600 text-slate-300 hover:bg-slate-700 hover:text-white transition rounded text-xs">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php else: ?>
                                <span class="px-2 py-1 bg-slate-800/50 border border-slate-700/50 text-slate-500 rounded cursor-not-allowed text-xs">
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Best Practices -->
                <div class="bg-slate-700/30 border border-slate-700/50 p-6">
                    <h3 class="text-sm font-semibold text-white mb-3">
                        <i class="fas fa-lightbulb mr-2"></i>Best Practices
                    </h3>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-cyan-500 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Use clear, descriptive titles</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-cyan-500 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Include error messages verbatim</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-cyan-500 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Attach screenshots if helpful</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-cyan-500 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Document steps to reproduce</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-cyan-500 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Note when issue started</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-white mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Select correct priority level</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// File upload preview
const fileInput = document.getElementById('attachment');
const fileNameDisplay = document.getElementById('file-name');

fileInput.addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const fileName = this.files[0].name;
        const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
        fileNameDisplay.textContent = `Selected: ${fileName} (${fileSize} MB)`;
        fileNameDisplay.classList.add('text-white', 'font-medium');
    } else {
        fileNameDisplay.textContent = '';
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const submitterId = document.getElementById('submitter_id').value;
    const title = document.getElementById('title').value;
    const categoryId = document.getElementById('category_id').value;
    const description = document.getElementById('description').value;
    
    if (!submitterId || !title || !categoryId || !description) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
    submitBtn.disabled = true;
});
</script>

<?php 
// Include layout footer
include __DIR__ . '/../layouts/footer.php'; 
?>

