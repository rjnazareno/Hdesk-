<?php
require_once __DIR__ . '/../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireITStaff();

$currentUser = $auth->getCurrentUser();
$isITStaff = $currentUser['role'] === 'it_staff' || $currentUser['role'] === 'admin';

$ticketModel = new Ticket();
$userModel = new User();
$activityModel = new TicketActivity();

$ticketId = $_GET['id'] ?? 0;
$ticket = $ticketModel->findById($ticketId);

if (!$ticket) {
    redirect('tickets.php');
}

// Check permission - employees can only view their own tickets
if (!$isITStaff && $ticket['submitter_id'] != $currentUser['id']) {
    redirect('tickets.php');
}

// Get activity log
$activities = $activityModel->getByTicketId($ticketId);

// Get IT staff for assignment (if IT staff viewing)
$itStaff = [];
if ($isITStaff) {
    $itStaff = $userModel->getITStaff();
}

// Handle ticket update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isITStaff) {
    $updateData = [];
    $oldTicket = $ticket;
    
    if (isset($_POST['status']) && $_POST['status'] !== $ticket['status']) {
        $updateData['status'] = sanitize($_POST['status']);
        
        // Log activity
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'status_change',
            'old_value' => $oldTicket['status'],
            'new_value' => $updateData['status'],
            'comment' => 'Status changed from ' . $oldTicket['status'] . ' to ' . $updateData['status']
        ]);
        
        // Send notification
        try {
            $mailer = new Mailer();
            $submitter = $userModel->findById($ticket['submitter_id']);
            $updatedTicket = array_merge($ticket, $updateData);
            $mailer->sendTicketStatusUpdate($updatedTicket, $submitter, $oldTicket['status'], $updateData['status']);
        } catch (Exception $e) {
            error_log("Failed to send email: " . $e->getMessage());
        }
    }
    
    if (isset($_POST['assigned_to']) && $_POST['assigned_to'] !== $ticket['assigned_to']) {
        $updateData['assigned_to'] = $_POST['assigned_to'] ? (int)$_POST['assigned_to'] : null;
        
        if ($updateData['assigned_to']) {
            $assignee = $userModel->findById($updateData['assigned_to']);
            
            // Log activity
            $activityModel->log([
                'ticket_id' => $ticketId,
                'user_id' => $currentUser['id'],
                'action_type' => 'assigned',
                'new_value' => $assignee['full_name'],
                'comment' => 'Ticket assigned to ' . $assignee['full_name']
            ]);
            
            // Send notification
            try {
                $mailer = new Mailer();
                $updatedTicket = array_merge($ticket, $updateData);
                $mailer->sendTicketAssigned($updatedTicket, $assignee);
            } catch (Exception $e) {
                error_log("Failed to send email: " . $e->getMessage());
            }
        }
    }
    
    if (isset($_POST['resolution']) && !empty($_POST['resolution'])) {
        $updateData['resolution'] = sanitize($_POST['resolution']);
        
        // Log activity
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'resolution_added',
            'new_value' => $updateData['resolution'],
            'comment' => 'Resolution added'
        ]);
    }
    
    if (!empty($updateData)) {
        $ticketModel->update($ticketId, $updateData);
        redirect('view_ticket.php?id=' . $ticketId . '&success=updated');
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = sanitize($_POST['comment']);
    
    if (!empty($comment)) {
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'comment',
            'comment' => $comment
        ]);
        
        redirect('view_ticket.php?id=' . $ticketId . '&success=commented');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket - IT Help Desk</title>
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
                    <h1 class="text-2xl font-bold text-gray-900">Ticket Details</h1>
                    <p class="text-gray-600"><?php echo htmlspecialchars($ticket['ticket_number']); ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="tickets.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Tickets
                    </a>
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
                    <!-- Ticket Info -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-start justify-between mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($ticket['title']); ?></h2>
                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <span><i class="fas fa-calendar mr-2"></i><?php echo formatDate($ticket['created_at']); ?></span>
                                    <span><i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($ticket['submitter_name']); ?></span>
                                </div>
                            </div>
                            <?php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'open' => 'bg-blue-100 text-blue-800',
                                'in_progress' => 'bg-purple-100 text-purple-800',
                                'resolved' => 'bg-green-100 text-green-800',
                                'closed' => 'bg-gray-100 text-gray-800'
                            ];
                            ?>
                            <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $statusColors[$ticket['status']]; ?>">
                                <?php echo str_replace('_', ' ', strtoupper($ticket['status'])); ?>
                            </span>
                        </div>

                        <div class="prose max-w-none">
                            <h3 class="text-lg font-semibold mb-3">Description</h3>
                            <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                        </div>

                        <?php if ($ticket['attachments']): ?>
                        <div class="mt-6 pt-6 border-t">
                            <h3 class="text-lg font-semibold mb-3">Attachments</h3>
                            <a href="uploads/<?php echo htmlspecialchars($ticket['attachments']); ?>" 
                               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                               download>
                                <i class="fas fa-paperclip mr-2"></i>
                                <?php echo htmlspecialchars($ticket['attachments']); ?>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($ticket['resolution']): ?>
                        <div class="mt-6 pt-6 border-t">
                            <h3 class="text-lg font-semibold mb-3 text-green-700">
                                <i class="fas fa-check-circle mr-2"></i>Resolution
                            </h3>
                            <p class="text-gray-700 bg-green-50 p-4 rounded-lg"><?php echo nl2br(htmlspecialchars($ticket['resolution'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Activity Timeline -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-6">Activity Timeline</h3>
                        <div class="space-y-6">
                            <?php foreach ($activities as $activity): ?>
                            <div class="flex space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-<?php echo $activity['action_type'] === 'created' ? 'plus' : ($activity['action_type'] === 'comment' ? 'comment' : 'history'); ?> text-blue-600"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-medium text-gray-900"><?php echo htmlspecialchars($activity['user_name']); ?></span>
                                        <span class="text-sm text-gray-500"><?php echo formatDate($activity['created_at']); ?></span>
                                    </div>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($activity['comment'] ?? $activity['action_type']); ?></p>
                                    <?php if ($activity['new_value']): ?>
                                    <p class="text-sm text-gray-500 mt-1"><strong>Value:</strong> <?php echo htmlspecialchars($activity['new_value']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Add Comment -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">Add Comment</h3>
                        <form method="POST" action="">
                            <textarea 
                                name="comment" 
                                rows="4" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Write your comment here..."
                                required
                            ></textarea>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-comment mr-2"></i>Post Comment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Ticket Details Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">Ticket Information</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm text-gray-600">Ticket Number</label>
                                <p class="font-medium"><?php echo htmlspecialchars($ticket['ticket_number']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-600">Category</label>
                                <p class="font-medium"><?php echo htmlspecialchars($ticket['category_name']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-600">Priority</label>
                                <?php
                                $priorityColors = [
                                    'low' => 'bg-green-100 text-green-800',
                                    'medium' => 'bg-yellow-100 text-yellow-800',
                                    'high' => 'bg-orange-100 text-orange-800',
                                    'urgent' => 'bg-red-100 text-red-800'
                                ];
                                ?>
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-medium mt-1 <?php echo $priorityColors[$ticket['priority']]; ?>">
                                    <?php echo strtoupper($ticket['priority']); ?>
                                </span>
                            </div>
                            <div>
                                <label class="text-sm text-gray-600">Submitter</label>
                                <p class="font-medium"><?php echo htmlspecialchars($ticket['submitter_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($ticket['submitter_email']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-600">Assigned To</label>
                                <p class="font-medium"><?php echo $ticket['assigned_name'] ? htmlspecialchars($ticket['assigned_name']) : 'Unassigned'; ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if ($isITStaff): ?>
                    <!-- Update Ticket -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">Update Ticket</h3>
                        <form method="POST" action="" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assign To</label>
                                <select name="assigned_to" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($itStaff as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>" <?php echo $ticket['assigned_to'] == $staff['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($staff['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Resolution</label>
                                <textarea 
                                    name="resolution" 
                                    rows="4" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Describe how this issue was resolved..."
                                ><?php echo htmlspecialchars($ticket['resolution'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-save mr-2"></i>Update Ticket
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
