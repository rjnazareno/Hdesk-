<?php
require_once __DIR__ . '/../config/config.php';

$auth = new Auth();
$auth->requireLogin();

// Ensure only employees can access
if ($_SESSION['user_type'] !== 'employee') {
    redirect('admin/dashboard.php');
}

$categoryModel = new Category();
$categories = $categoryModel->getAll();

$currentUser = $auth->getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketModel = new Ticket();
    $activityModel = new TicketActivity();
    
    // Generate unique ticket number
    do {
        $ticketNumber = generateTicketNumber();
        $existingTicket = $ticketModel->findByTicketNumber($ticketNumber);
    } while ($existingTicket);
    
    // Prepare ticket data
    $ticketData = [
        'ticket_number' => $ticketNumber,
        'title' => sanitize($_POST['title']),
        'description' => sanitize($_POST['description']),
        'category_id' => (int)$_POST['category_id'],
        'priority' => sanitize($_POST['priority']),
        'status' => 'pending',
        'submitter_id' => $currentUser['id'],
        'submitter_type' => $_SESSION['user_type'] ?? 'employee'
    ];
    
    // Handle file upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = UPLOAD_DIR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['attachment']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filePath)) {
            $ticketData['attachments'] = $fileName;
        }
    }
    
    // Create ticket
    $ticketId = $ticketModel->create($ticketData);
    
    if ($ticketId) {
        // Log activity
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'created',
            'new_value' => 'pending',
            'comment' => 'Ticket created'
        ]);
        
        // Send notification email
        try {
            $mailer = new Mailer();
            $ticket = $ticketModel->findById($ticketId);
            $mailer->sendTicketCreated($ticket, $currentUser);
        } catch (Exception $e) {
            error_log("Failed to send email: " . $e->getMessage());
        }
        
        redirect('tickets.php?success=created');
    } else {
        $error = "Failed to create ticket. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Ticket - IT Help Desk</title>
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
            <a href="tickets.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-ticket-alt w-6"></i>
                <span>Tickets</span>
            </a>
            <a href="create_ticket.php" class="flex items-center px-6 py-3 bg-gray-800 text-white">
                <i class="fas fa-plus-circle w-6"></i>
                <span>Create Ticket</span>
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
                    <h1 class="text-2xl font-bold text-gray-900">Create New Ticket</h1>
                    <p class="text-gray-600">Submit a new support request</p>
                </div>
                <div class="flex items-center space-x-2">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=2563eb&color=fff" 
                         alt="User" 
                         class="w-10 h-10 rounded-full">
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <div class="p-8">
            <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm p-8 max-w-4xl">
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

            <!-- Help Section -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6 max-w-4xl">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">
                    <i class="fas fa-info-circle mr-2"></i>Tips for Creating a Good Ticket
                </h3>
                <ul class="space-y-2 text-sm text-blue-800">
                    <li><i class="fas fa-check-circle mr-2"></i>Use a clear and descriptive title</li>
                    <li><i class="fas fa-check-circle mr-2"></i>Provide step-by-step details of the issue</li>
                    <li><i class="fas fa-check-circle mr-2"></i>Include any error messages you've received</li>
                    <li><i class="fas fa-check-circle mr-2"></i>Attach screenshots or relevant files if applicable</li>
                    <li><i class="fas fa-check-circle mr-2"></i>Select the appropriate priority level</li>
                </ul>
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
</body>
</html>
