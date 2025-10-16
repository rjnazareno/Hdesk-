<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Ticket - IT Help Desk</title>
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
                    <h1 class="text-2xl font-bold text-gray-900">Create New Ticket</h1>
                    <p class="text-gray-600">Submit a new support request</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="darkModeToggle" class="p-2 text-gray-600 hover:text-gray-900" title="Toggle dark mode">
                        <i id="dark-mode-icon" class="fas fa-moon"></i>
                    </button>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=2563eb&color=fff" 
                         alt="User" 
                         class="w-10 h-10 rounded-full"
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
                            <span class="ml-1 text-sm font-medium text-gray-700">Create Ticket</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form Section -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-8">
                <form action="create_ticket.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Ticket Title <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Brief description of your issue"
                        >
                    </div>

                    <!-- Category and Priority -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="category_id" 
                                name="category_id" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                            <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                                Priority <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="priority" 
                                name="priority" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="6" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Provide detailed information about your issue..."
                        ></textarea>
                        <p class="text-sm text-gray-500 mt-2">
                            Please include as much detail as possible to help us resolve your issue quickly.
                        </p>
                    </div>

                    <!-- File Attachment -->
                    <div>
                        <label for="attachment" class="block text-sm font-medium text-gray-700 mb-2">
                            Attachment (Optional)
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer hover:bg-gray-50">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-2"></i>
                                    <p class="mb-2 text-sm text-gray-500">
                                        <span class="font-semibold">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500">PNG, JPG, PDF, DOC (MAX. 5MB)</p>
                                </div>
                                <input id="attachment" name="attachment" type="file" class="hidden" />
                            </label>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                        <a href="tickets.php" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button 
                            type="submit"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition"
                        >
                            <i class="fas fa-paper-plane mr-2"></i>Submit Ticket
                        </button>
                    </div>
                </form>
                </div>

                <!-- Sidebar Section -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Your Ticket Stats -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">
                            <i class="fas fa-chart-bar mr-2"></i>Your Ticket Stats
                        </h3>
                        <?php
                        $ticketModel = new Ticket();
                        $userStats = $ticketModel->getStats($currentUser['id'], 'employee');
                        ?>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Tickets</span>
                                <span class="text-lg font-bold text-blue-600"><?php echo $userStats['total'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Open</span>
                                <span class="text-lg font-bold text-green-600"><?php echo $userStats['open'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Pending</span>
                                <span class="text-lg font-bold text-yellow-600"><?php echo $userStats['pending'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">Closed</span>
                                <span class="text-lg font-bold text-gray-600"><?php echo $userStats['closed'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Priority Guide -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Priority Guide
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-start space-x-2">
                                <div class="w-3 h-3 bg-red-600 rounded-full mt-1 flex-shrink-0"></div>
                                <div>
                                    <div class="font-semibold text-gray-900">Urgent</div>
                                    <div class="text-xs text-gray-600">System down, can't work</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-2">
                                <div class="w-3 h-3 bg-orange-600 rounded-full mt-1 flex-shrink-0"></div>
                                <div>
                                    <div class="font-semibold text-gray-900">High</div>
                                    <div class="text-xs text-gray-600">Major issue, needs quick fix</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-2">
                                <div class="w-3 h-3 bg-yellow-600 rounded-full mt-1 flex-shrink-0"></div>
                                <div>
                                    <div class="font-semibold text-gray-900">Medium</div>
                                    <div class="text-xs text-gray-600">Normal issue, has workaround</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-2">
                                <div class="w-3 h-3 bg-green-600 rounded-full mt-1 flex-shrink-0"></div>
                                <div>
                                    <div class="font-semibold text-gray-900">Low</div>
                                    <div class="text-xs text-gray-600">Minor issue, no rush</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Response Time Info -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                        <h3 class="text-sm font-semibold text-blue-900 mb-3">
                            <i class="fas fa-clock mr-2"></i>Expected Response Time
                        </h3>
                        <div class="space-y-2 text-sm text-blue-800">
                            <div class="flex justify-between">
                                <span>Urgent:</span>
                                <strong>1-2 hours</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>High:</span>
                                <strong>4-8 hours</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Medium:</span>
                                <strong>1 business day</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Low:</span>
                                <strong>2-3 business days</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Tips Section -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-blue-900 mb-3">
                            <i class="fas fa-lightbulb mr-2"></i>Quick Tips
                        </h3>
                        <ul class="space-y-2 text-xs text-blue-800">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0"></i>
                                <span>Be specific in your title</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0"></i>
                                <span>Include error messages</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0"></i>
                                <span>Add screenshots if helpful</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0"></i>
                                <span>List steps to reproduce</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0"></i>
                                <span>Choose correct priority</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Common Categories -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">
                            <i class="fas fa-tags mr-2"></i>Common Issues
                        </h3>
                        <div class="space-y-2 text-xs text-gray-600">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-desktop text-gray-400"></i>
                                <span>Hardware problems</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-code text-gray-400"></i>
                                <span>Software installation</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-wifi text-gray-400"></i>
                                <span>Network connectivity</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-lock text-gray-400"></i>
                                <span>Password reset</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-envelope text-gray-400"></i>
                                <span>Email issues</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // File upload preview
        const fileInput = document.getElementById('attachment');
        const label = fileInput.closest('label');
        
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                label.querySelector('p.mb-2').innerHTML = `<span class="font-semibold text-blue-600">${fileName}</span>`;
            }
        });
    </script>
    
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
