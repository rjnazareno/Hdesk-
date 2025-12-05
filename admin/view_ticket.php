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
$slaModel = new SLA();

$ticketId = $_GET['id'] ?? 0;
$ticket = $ticketModel->findById($ticketId);

// Get SLA data for this ticket
$slaData = $slaModel->getTicketSLA($ticketId);

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
        
        // SLA Tracking: Record first response if status changes from pending
        if ($oldTicket['status'] === 'pending' && $updateData['status'] !== 'pending') {
            $slaModel->recordFirstResponse($ticketId);
        }
        
        // SLA Tracking: Record resolution if status changes to resolved/closed
        if (($updateData['status'] === 'resolved' || $updateData['status'] === 'closed') && 
            ($oldTicket['status'] !== 'resolved' && $oldTicket['status'] !== 'closed')) {
            $slaModel->recordResolution($ticketId);
        }
        
        // Log activity
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'status_change',
            'old_value' => $oldTicket['status'],
            'new_value' => $updateData['status'],
            'comment' => 'Status changed from ' . $oldTicket['status'] . ' to ' . $updateData['status']
        ]);
        
        // Create notification for ticket submitter
        try {
            $db = Database::getInstance()->getConnection();
            $notificationModel = new Notification($db);
            
            // Determine notification type based on new status
            $notifType = 'status_changed';
            if ($updateData['status'] === 'resolved' || $updateData['status'] === 'closed') {
                $notifType = 'ticket_resolved';
            }
            
            $statusLabels = [
                'pending' => 'Pending',
                'open' => 'Open',
                'in_progress' => 'In Progress',
                'resolved' => 'Resolved',
                'closed' => 'Closed'
            ];
            
            $newStatusLabel = $statusLabels[$updateData['status']] ?? ucfirst($updateData['status']);
            
            // Notify based on submitter type
            if ($ticket['submitter_type'] === 'employee') {
                // Employee submitted - use employee_id
                $notificationModel->create([
                    'user_id' => null,
                    'employee_id' => $ticket['submitter_id'],
                    'type' => $notifType,
                    'title' => 'Ticket Status Updated',
                    'message' => "Your ticket #{$ticket['ticket_number']} status changed to: {$newStatusLabel}",
                    'ticket_id' => $ticketId,
                    'related_user_id' => $currentUser['id']
                ]);
            } else {
                // User submitted - use user_id
                $notificationModel->create([
                    'user_id' => $ticket['submitter_id'],
                    'employee_id' => null,
                    'type' => $notifType,
                    'title' => 'Ticket Status Updated',
                    'message' => "Ticket #{$ticket['ticket_number']} status changed to: {$newStatusLabel}",
                    'ticket_id' => $ticketId,
                    'related_user_id' => $currentUser['id']
                ]);
            }
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
        }
        
        // Send notification
        try {
            $mailer = new Mailer();
            $submitter = $userModel->findById($ticket['submitter_id']);
            $updatedTicket = array_merge($ticket, $updateData);
            $mailer->sendTicketStatusUpdate($updatedTicket, $submitter, $oldTicket['status'], $updateData['status']);
        } catch (Exception $e) {
            error_log("Failed to send email: " . $e->getMessage());
        }
        
        // Send FCM push notification to submitter
        try {
            require_once __DIR__ . '/../includes/FCMNotification.php';
            $fcm = new FCMNotification();
            
            $fcm->notifyTicketStatusChanged(
                $ticketId,
                $ticket['ticket_number'],
                $updateData['status'],
                $ticket['submitter_id'],
                $ticket['submitter_type']
            );
        } catch (Exception $e) {
            error_log("Failed to send FCM notification: " . $e->getMessage());
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
            
            // Create notification for assigned IT staff
            try {
                $db = Database::getInstance()->getConnection();
                $notificationModel = new Notification($db);
                
                $notificationModel->create([
                    'user_id' => $assignee['id'],
                    'employee_id' => null,
                    'type' => 'ticket_assigned',
                    'title' => 'Ticket Assigned to You',
                    'message' => "You have been assigned to ticket #{$ticket['ticket_number']}: {$ticket['title']}",
                    'ticket_id' => $ticketId,
                    'related_user_id' => $currentUser['id']
                ]);
            } catch (Exception $e) {
                error_log("Failed to create notification: " . $e->getMessage());
            }
            
            // Send notification
            try {
                $mailer = new Mailer();
                $updatedTicket = array_merge($ticket, $updateData);
                $mailer->sendTicketAssigned($updatedTicket, $assignee);
            } catch (Exception $e) {
                error_log("Failed to send email: " . $e->getMessage());
            }
            
            // Send FCM push notification to assigned user
            try {
                require_once __DIR__ . '/../includes/FCMNotification.php';
                $fcm = new FCMNotification();
                
                $fcm->notifyTicketAssigned(
                    $ticketId,
                    $ticket['ticket_number'],
                    $assignee['id'],
                    $currentUser['full_name']
                );
            } catch (Exception $e) {
                error_log("Failed to send FCM notification: " . $e->getMessage());
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
        header("Location: view_ticket.php?id=" . $ticketId . "&success=updated");
        exit();
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = sanitize($_POST['comment']);
    
    if (!empty($comment)) {
        // SLA Tracking: Record first response if IT staff comments on pending ticket
        if ($isITStaff && $ticket['status'] === 'pending') {
            $slaModel->recordFirstResponse($ticketId);
        }
        
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'comment',
            'comment' => $comment
        ]);
        
        // Create notification for ticket submitter (if IT staff commented)
        if ($isITStaff && $ticket['submitter_id'] != $currentUser['id']) {
            try {
                $db = Database::getInstance()->getConnection();
                $notificationModel = new Notification($db);
                
                // Notify based on submitter type
                if ($ticket['submitter_type'] === 'employee') {
                    // Employee submitted - use employee_id
                    $notificationModel->create([
                        'user_id' => null,
                        'employee_id' => $ticket['submitter_id'],
                        'type' => 'comment_added',
                        'title' => 'New Comment on Your Ticket',
                        'message' => "IT staff added a comment on ticket #{$ticket['ticket_number']}",
                        'ticket_id' => $ticketId,
                        'related_user_id' => $currentUser['id']
                    ]);
                } else {
                    // User submitted - use user_id
                    $notificationModel->create([
                        'user_id' => $ticket['submitter_id'],
                        'employee_id' => null,
                        'type' => 'comment_added',
                        'title' => 'New Comment on Ticket',
                        'message' => "A comment was added to ticket #{$ticket['ticket_number']}",
                        'ticket_id' => $ticketId,
                        'related_user_id' => $currentUser['id']
                    ]);
                }
            } catch (Exception $e) {
                error_log("Failed to create notification: " . $e->getMessage());
            }
        }
        
        header("Location: view_ticket.php?id=" . $ticketId . "&success=commented");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket - IT Help Desk</title>
    
    <!-- Tailwind CSS: Uses CDN in development, local file in production -->
    <?php echo getTailwindCSS(); ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Firebase SDK for Cloud Messaging -->
    <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-analytics-compat.js"></script>
    
    <!-- Dark mode CSS (load before print) -->
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    
    <!-- Print styles - MUST BE LAST for proper cascade -->
    <link rel="stylesheet" href="../assets/css/print.css" media="print">
    
    <style>
        /* Dark theme select styling */
        select, textarea {
            color-scheme: dark;
        }
        select option {
            background-color: #1e293b;
            color: #ffffff;
        }
        select option:checked {
            background: linear-gradient(#06b6d4, #06b6d4);
            background-color: #06b6d4 !important;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-white border-b border-gray-200">
            <div class="flex items-center justify-between px-8 py-4 pt-20 lg:pt-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Ticket Details</h1>
                    <p class="text-gray-600"><?php echo htmlspecialchars($ticket['ticket_number']); ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="window.print()" class="px-4 py-2 bg-gray-900 text-white hover:bg-teal-600 transition no-print" title="Print this ticket">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <a href="tickets.php" class="px-4 py-2 border border-gray-300 bg-gray-50 text-gray-700 hover:bg-gray-100 transition" title="Back to tickets list">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Tickets
                    </a>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=06b6d4&color=fff" 
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
                        <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-teal-600">
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
                            <a href="tickets.php" class="ml-1 text-sm font-medium text-gray-600 hover:text-teal-600">Tickets</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-700">View Ticket</span>
                        </div>
                    </li>
                </ol>
            </nav>
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

            <!-- Print-Only Header Information -->
            <div class="print-only-header" style="display: none;">
                <div style="margin-bottom: 20px; padding: 15px; border: 2px solid #000;">
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td style="width: 50%; border: none; vertical-align: top;">
                                <h3 style="margin: 0 0 10px 0; font-size: 14pt;">Ticket Information</h3>
                                <p style="margin: 5px 0;"><strong>Ticket #:</strong> <?php echo htmlspecialchars($ticket['ticket_number']); ?></p>
                                <p style="margin: 5px 0;"><strong>Status:</strong> <?php echo str_replace('_', ' ', strtoupper($ticket['status'])); ?></p>
                                <p style="margin: 5px 0;"><strong>Priority:</strong> <?php echo strtoupper($ticket['priority']); ?></p>
                                <p style="margin: 5px 0;"><strong>Category:</strong> <?php echo htmlspecialchars($ticket['category_name']); ?></p>
                                <p style="margin: 5px 0;"><strong>Created:</strong> <?php echo formatDate($ticket['created_at'], 'M d, Y g:i A'); ?></p>
                            </td>
                            <td style="width: 50%; border: none; vertical-align: top;">
                                <h3 style="margin: 0 0 10px 0; font-size: 14pt;">People Involved</h3>
                                <p style="margin: 5px 0;"><strong>Submitted By:</strong><br><?php echo htmlspecialchars($ticket['submitter_name']); ?>
                                <?php if ($ticket['submitter_type'] === 'employee'): ?>
                                    <br><span style="font-size: 9pt; color: #666;">(Employee)</span>
                                <?php else: ?>
                                    <br><span style="font-size: 9pt; color: #666;">(IT Staff)</span>
                                <?php endif; ?>
                                </p>
                                <?php if ($ticket['assigned_to']): ?>
                                <p style="margin: 5px 0;"><strong>Assigned To:</strong><br><?php echo htmlspecialchars($ticket['assigned_name']); ?></p>
                                <?php endif; ?>
                                <?php if ($ticket['status'] === 'resolved' || $ticket['status'] === 'closed'): 
                                    // Find who resolved it from activities
                                    $resolver = null;
                                    foreach ($activities as $activity) {
                                        if ($activity['action_type'] === 'resolution_added' || 
                                            ($activity['action_type'] === 'status_change' && 
                                             ($activity['new_value'] === 'resolved' || $activity['new_value'] === 'closed'))) {
                                            $resolver = $activity['user_name'];
                                            break;
                                        }
                                    }
                                    if ($resolver):
                                ?>
                                <p style="margin: 5px 0;"><strong>Resolved By:</strong><br><?php echo htmlspecialchars($resolver); ?></p>
                                <?php endif; endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Ticket Info -->
                    <div class="bg-white border border-gray-200 p-6 shadow-sm">
                        <div class="flex items-start justify-between mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($ticket['title']); ?></h2>
                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <span><i class="fas fa-calendar mr-2"></i>
                                        <span class="time-ago" data-timestamp="<?php echo $ticket['created_at']; ?>">
                                            <?php echo formatDate($ticket['created_at']); ?>
                                        </span>
                                    </span>
                                    <span><i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($ticket['submitter_name']); ?></span>
                                </div>
                            </div>
                            <?php
                            $statusColors = [
                                'pending' => 'bg-yellow-600 text-gray-900',
                                'open' => 'bg-blue-600 text-gray-900',
                                'in_progress' => 'bg-purple-600 text-gray-900',
                                'resolved' => 'bg-green-600 text-gray-900',
                                'closed' => 'bg-gray-100 text-gray-900'
                            ];
                            ?>
                            <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $statusColors[$ticket['status']]; ?>">
                                <?php echo str_replace('_', ' ', strtoupper($ticket['status'])); ?>
                            </span>
                        </div>

                        <div class="prose max-w-none">
                            <h3 class="text-lg font-semibold mb-3 text-gray-900">Description</h3>
                            <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                        </div>

                        <?php if ($ticket['attachments']): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-semibold mb-3 text-gray-900">Attachments</h3>
                            <a href="uploads/<?php echo htmlspecialchars($ticket['attachments']); ?>" 
                               class="inline-flex items-center px-4 py-2 bg-gray-50 hover:bg-gray-100 text-teal-600 border border-gray-200 transition"
                               download>
                                <i class="fas fa-paperclip mr-2"></i>
                                <?php echo htmlspecialchars($ticket['attachments']); ?>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($ticket['resolution']): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-semibold mb-3 text-green-600">
                                <i class="fas fa-check-circle mr-2"></i>Resolution
                            </h3>
                            <p class="text-gray-700 bg-green-50 p-4 border border-green-200"><?php echo nl2br(htmlspecialchars($ticket['resolution'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Activity Timeline -->
                    <div class="bg-white border border-gray-200 p-6 shadow-sm">
                        <h3 class="text-lg font-semibold mb-6 text-gray-900">Activity Timeline</h3>
                        <div class="space-y-6">
                            <?php foreach ($activities as $activity): ?>
                            <div class="flex space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-<?php echo $activity['action_type'] === 'created' ? 'plus' : ($activity['action_type'] === 'comment' ? 'comment' : 'history'); ?> text-teal-600"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-medium text-gray-900"><?php echo htmlspecialchars($activity['user_name']); ?></span>
                                        <span class="text-sm text-gray-600"><?php echo formatDate($activity['created_at']); ?></span>
                                    </div>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($activity['comment'] ?? $activity['action_type']); ?></p>
                                    <?php if ($activity['new_value']): ?>
                                    <p class="text-sm text-gray-600 mt-1"><strong>Value:</strong> <?php echo htmlspecialchars($activity['new_value']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Add Comment -->
                    <div class="bg-white border border-gray-200 p-6 shadow-sm">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900">Add Comment</h3>
                        <form method="POST" action="">
                            <textarea 
                                name="comment" 
                                rows="4" 
                                class="w-full px-4 py-3 border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="Write your comment here..."
                                required
                            ></textarea>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-teal-500 to-emerald-600 text-gray-900 hover:from-teal-600 hover:to-emerald-700 transition">
                                    <i class="fas fa-comment mr-2"></i>Post Comment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Ticket Details Card -->
                    <div class="bg-white border border-gray-200 p-6 shadow-sm">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900">Ticket Information</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm text-gray-600 font-medium">Ticket Number</label>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($ticket['ticket_number']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-600 font-medium">Category</label>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($ticket['category_name']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-600 font-medium">Priority</label>
                                <?php
                                $priorityColors = [
                                    'low' => 'bg-green-600 text-gray-900',
                                    'medium' => 'bg-yellow-600 text-gray-900',
                                    'high' => 'bg-orange-600 text-gray-900',
                                    'urgent' => 'bg-red-600 text-gray-900'
                                ];
                                ?>
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-medium mt-1 <?php echo $priorityColors[$ticket['priority']]; ?>">
                                    <?php echo strtoupper($ticket['priority']); ?>
                                </span>
                            </div>
                            <div>
                                <label class="text-sm text-gray-600 font-medium">Submitter</label>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($ticket['submitter_name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($ticket['submitter_email']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-600 font-medium">Assigned To</label>
                                <p class="font-medium text-gray-900"><?php echo $ticket['assigned_name'] ? htmlspecialchars($ticket['assigned_name']) : 'Unassigned'; ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if ($isITStaff && $slaData): ?>
                    <!-- SLA Status Card -->
                    <div class="bg-white border border-gray-200 border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">SLA Status</h3>
                            <?php if ($slaData['is_paused']): ?>
                            <span class="px-2 py-1 bg-gray-100 text-gray-900 text-xs font-semibold">
                                <i class="fas fa-pause mr-1"></i>PAUSED
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="space-y-4">
                            <!-- Response SLA -->
                            <div class="p-4 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border border-gray-200">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">First Response</span>
                                    <?php
                                    $responseStatus = $slaData['response_sla_status'];
                                    $responseStatusConfig = [
                                        'met' => ['bg' => 'bg-green-600', 'icon' => 'fa-check', 'text' => 'Met'],
                                        'breached' => ['bg' => 'bg-red-600', 'icon' => 'fa-exclamation-triangle', 'text' => 'Breached'],
                                        'pending' => ['bg' => 'bg-yellow-600', 'icon' => 'fa-clock', 'text' => 'Pending']
                                    ];
                                    $rsConfig = $responseStatusConfig[$responseStatus] ?? $responseStatusConfig['pending'];
                                    ?>
                                    <span class="px-2 py-1 <?php echo $rsConfig['bg']; ?> text-gray-900 text-xs font-semibold">
                                        <i class="fas <?php echo $rsConfig['icon']; ?> mr-1"></i><?php echo $rsConfig['text']; ?>
                                    </span>
                                </div>
                                <div class="text-xs text-gray-600 space-y-1">
                                    <div class="flex justify-between">
                                        <span>Due:</span>
                                        <span class="font-medium"><?php echo formatDate($slaData['response_due_at'], 'M d, Y h:i A'); ?></span>
                                    </div>
                                    <?php if ($slaData['first_response_at']): ?>
                                    <div class="flex justify-between">
                                        <span>Responded:</span>
                                        <span class="font-medium text-green-600"><?php echo formatDate($slaData['first_response_at'], 'M d, Y h:i A'); ?></span>
                                    </div>
                                    <?php else: ?>
                                    <div class="flex justify-between">
                                        <span>Remaining:</span>
                                        <span class="font-medium <?php echo $slaData['response_remaining']['is_overdue'] ? 'text-red-600' : 'text-gray-900'; ?>">
                                            <?php echo $slaData['response_remaining']['formatted']; ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Resolution SLA -->
                            <div class="p-4 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border border-gray-200">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Resolution</span>
                                    <?php
                                    $resolutionStatus = $slaData['resolution_sla_status'];
                                    $resolutionStatusConfig = [
                                        'met' => ['bg' => 'bg-green-600', 'icon' => 'fa-check-circle', 'text' => 'Met'],
                                        'breached' => ['bg' => 'bg-red-600', 'icon' => 'fa-exclamation-triangle', 'text' => 'Breached'],
                                        'pending' => ['bg' => 'bg-gradient-to-r from-teal-500 to-emerald-600', 'icon' => 'fa-hourglass-half', 'text' => 'In Progress']
                                    ];
                                    $resConfig = $resolutionStatusConfig[$resolutionStatus] ?? $resolutionStatusConfig['pending'];
                                    ?>
                                    <span class="px-2 py-1 <?php echo $resConfig['bg']; ?> text-gray-900 text-xs font-semibold">
                                        <i class="fas <?php echo $resConfig['icon']; ?> mr-1"></i><?php echo $resConfig['text']; ?>
                                    </span>
                                </div>
                                <div class="text-xs text-gray-600 space-y-1">
                                    <div class="flex justify-between">
                                        <span>Due:</span>
                                        <span class="font-medium"><?php echo formatDate($slaData['resolution_due_at'], 'M d, Y h:i A'); ?></span>
                                    </div>
                                    <?php if ($slaData['resolved_at']): ?>
                                    <div class="flex justify-between">
                                        <span>Resolved:</span>
                                        <span class="font-medium text-green-600"><?php echo formatDate($slaData['resolved_at'], 'M d, Y h:i A'); ?></span>
                                    </div>
                                    <?php else: ?>
                                    <div class="flex justify-between">
                                        <span>Remaining:</span>
                                        <span class="font-medium <?php echo $slaData['resolution_remaining']['is_overdue'] ? 'text-red-600' : 'text-gray-900'; ?>">
                                            <?php echo $slaData['resolution_remaining']['formatted']; ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Progress Bar -->
                                <?php if (!$slaData['resolved_at'] && !$slaData['is_paused']): 
                                    $percentage = min(100, max(0, (1 - $slaData['resolution_remaining']['minutes'] / ($slaData['target_resolution'])) * 100));
                                    $barColor = $percentage < 50 ? 'bg-green-600' : ($percentage < 80 ? 'bg-yellow-600' : 'bg-red-600');
                                ?>
                                <div class="mt-3">
                                    <div class="w-full bg-gray-200 h-2">
                                        <div class="<?php echo $barColor; ?> h-2 transition-all duration-300" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Admin Controls -->
                            <?php if ($currentUser['role'] === 'admin' && $ticket['status'] !== 'closed' && $ticket['status'] !== 'resolved'): ?>
                            <div class="pt-4 border-t border-gray-200">
                                <?php if ($slaData['is_paused']): ?>
                                <form method="POST" action="sla_actions.php">
                                    <input type="hidden" name="action" value="resume">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticketId; ?>">
                                    <button type="submit" class="w-full px-4 py-2 bg-green-600 text-gray-900 hover:bg-green-700 transition text-sm font-medium">
                                        <i class="fas fa-play mr-2"></i>Resume SLA Timer
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST" action="sla_actions.php">
                                    <input type="hidden" name="action" value="pause">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticketId; ?>">
                                    <input type="text" name="reason" placeholder="Reason for pause..." class="w-full px-3 py-2 border border-gray-300 bg-white text-gray-900 placeholder-gray-400 text-sm mb-2 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent" required>
                                    <button type="submit" class="w-full px-4 py-2 bg-yellow-600 text-gray-900 hover:bg-yellow-700 transition text-sm font-medium rounded-lg">
                                        <i class="fas fa-pause mr-2"></i>Pause SLA Timer
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($isITStaff): ?>
                    <!-- Update Ticket -->
                    <div class="bg-white border border-gray-200 shadow-sm  p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900">Update Ticket</h3>
                        <form method="POST" action="" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                    <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assign To</label>
                                <select name="assigned_to" class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
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
                                    class="w-full px-4 py-2 border border-gray-300 bg-white text-gray-900 placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="Describe how this issue was resolved..."
                                ><?php echo htmlspecialchars($ticket['resolution'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-teal-500 to-emerald-600 text-gray-900 rounded-lg hover:from-teal-600 hover:to-emerald-700 transition" title="Save ticket changes">
                                <i class="fas fa-save mr-2"></i>Update Ticket
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Wins JavaScript -->
    <script src="../assets/js/helpers.js"></script>
    
    <!-- Firebase Initialization -->
    <script src="../assets/js/firebase-init.js"></script>
    <script>
        // Set logged in status for Firebase
        document.body.dataset.userLoggedIn = 'true';
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initTooltips();
            initDarkMode();
            updateTimeAgo();
            setInterval(updateTimeAgo, 60000);
            
            // Add print date to body for print styling
            const now = new Date();
            const printDate = now.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.body.setAttribute('data-print-date', printDate);
        });
        
        // Optimize for printing
        window.addEventListener('beforeprint', function() {
            document.body.classList.add('printing');
        });
        
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
        });
    </script>
</body>
</html>
