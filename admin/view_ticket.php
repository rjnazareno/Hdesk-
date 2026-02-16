<?php
require_once __DIR__ . '/../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireITStaff();

$currentUser = $auth->getCurrentUser();
$isITStaff = $currentUser['role'] === 'it_staff' || $currentUser['role'] === 'admin' || $currentUser['role'] === 'internal';

$ticketModel = new Ticket();
$userModel = new User();
$employeeModel = new Employee();
$activityModel = new TicketActivity();
$slaModel = new SLA();

$ticketId = $_GET['id'] ?? 0;
$ticket = $ticketModel->findById($ticketId);
$slaData = $slaModel->getTicketSLA($ticketId);

if (!$ticket) {
    redirect('tickets.php');
}

if (!$isITStaff && $ticket['submitter_id'] != $currentUser['id']) {
    redirect('tickets.php');
}

$activities = $activityModel->getByTicketId($ticketId);
$itStaff = $isITStaff ? $employeeModel->getAdminEmployees() : [];
$replyModel = new TicketReply();
$replies = $replyModel->getByTicketId($ticketId);

// Handle quick status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isITStaff && isset($_POST['quick_status'])) {
    $newStatus = sanitize($_POST['quick_status']);
    $oldStatus = $ticket['status'];
    
    if ($oldStatus === 'pending' && $newStatus !== 'pending') {
        $slaModel->recordFirstResponse($ticketId);
    }
    
    if (($newStatus === 'resolved' || $newStatus === 'closed') && 
        ($oldStatus !== 'resolved' && $oldStatus !== 'closed')) {
        $slaModel->recordResolution($ticketId);
    }
    
    $activityModel->log([
        'ticket_id' => $ticketId,
        'user_id' => $currentUser['id'],
        'action_type' => 'status_change',
        'old_value' => $oldStatus,
        'new_value' => $newStatus,
        'comment' => 'Status changed to ' . $newStatus
    ]);
    
    try {
        $db = Database::getInstance()->getConnection();
        $notificationModel = new Notification($db);
        $statusLabels = ['pending' => 'Pending', 'open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
        $newStatusLabel = $statusLabels[$newStatus] ?? ucfirst($newStatus);
        $notifType = in_array($newStatus, ['resolved', 'closed']) ? 'ticket_resolved' : 'status_changed';
        
        $notificationModel->create([
            'user_id' => $ticket['submitter_type'] === 'employee' ? null : $ticket['submitter_id'],
            'employee_id' => $ticket['submitter_type'] === 'employee' ? $ticket['submitter_id'] : null,
            'type' => $notifType,
            'title' => 'Ticket Status Updated',
            'message' => "Ticket #{$ticket['ticket_number']} status: {$newStatusLabel}",
            'ticket_id' => $ticketId,
            'related_user_id' => $currentUser['id']
        ]);
    } catch (Exception $e) { error_log("Notification error: " . $e->getMessage()); }
    
    $ticketModel->update($ticketId, ['status' => $newStatus]);
    header("Location: view_ticket.php?id=" . $ticketId . "&success=status");
    exit();
}

// Handle quick assign to self (grab ticket)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isITStaff && isset($_POST['assign_to_me'])) {
    // Determine assignee type based on user type
    $assigneeType = ($_SESSION['user_type'] ?? 'user') === 'employee' ? 'employee' : 'user';
    
    // Use grabTicket to properly set grabbed_by, assigned_to, and assignee_type
    $result = $ticketModel->grabTicket($ticketId, $currentUser['id'], $assigneeType);
    
    if ($result) {
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'grabbed',
            'new_value' => $currentUser['full_name'],
            'comment' => 'Ticket grabbed by ' . $currentUser['full_name']
        ]);
        
        header("Location: view_ticket.php?id=" . $ticketId . "&success=assigned");
        exit();
    } else {
        // Fallback - ticket may already be grabbed, just update assigned_to
        $ticketModel->update($ticketId, [
            'assigned_to' => $currentUser['id'],
            'assignee_type' => $assigneeType
        ]);
        
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'assigned',
            'new_value' => $currentUser['full_name'],
            'comment' => 'Assigned to ' . $currentUser['full_name']
        ]);
        
        header("Location: view_ticket.php?id=" . $ticketId . "&success=assigned");
        exit();
    }
}

// Handle ticket update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isITStaff && isset($_POST['update_ticket'])) {
    $updateData = [];
    $oldTicket = $ticket;
    
    if (isset($_POST['status']) && $_POST['status'] !== $ticket['status']) {
        $updateData['status'] = sanitize($_POST['status']);
        
        if ($oldTicket['status'] === 'pending' && $updateData['status'] !== 'pending') {
            $slaModel->recordFirstResponse($ticketId);
        }
        
        if (($updateData['status'] === 'resolved' || $updateData['status'] === 'closed') && 
            ($oldTicket['status'] !== 'resolved' && $oldTicket['status'] !== 'closed')) {
            $slaModel->recordResolution($ticketId);
        }
        
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'status_change',
            'old_value' => $oldTicket['status'],
            'new_value' => $updateData['status'],
            'comment' => 'Status changed to ' . $updateData['status']
        ]);
        
        try {
            $db = Database::getInstance()->getConnection();
            $notificationModel = new Notification($db);
            $statusLabels = ['pending' => 'Pending', 'open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
            $newStatusLabel = $statusLabels[$updateData['status']] ?? ucfirst($updateData['status']);
            $notifType = in_array($updateData['status'], ['resolved', 'closed']) ? 'ticket_resolved' : 'status_changed';
            
            $notificationModel->create([
                'user_id' => $ticket['submitter_type'] === 'employee' ? null : $ticket['submitter_id'],
                'employee_id' => $ticket['submitter_type'] === 'employee' ? $ticket['submitter_id'] : null,
                'type' => $notifType,
                'title' => 'Ticket Status Updated',
                'message' => "Ticket #{$ticket['ticket_number']} status: {$newStatusLabel}",
                'ticket_id' => $ticketId,
                'related_user_id' => $currentUser['id']
            ]);
        } catch (Exception $e) { error_log("Notification error: " . $e->getMessage()); }
    }
    
    if (isset($_POST['assigned_to']) && $_POST['assigned_to'] != $ticket['assigned_to']) {
        $updateData['assigned_to'] = $_POST['assigned_to'] ? (int)$_POST['assigned_to'] : null;
        
        if ($updateData['assigned_to']) {
            $assignee = $employeeModel->findById($updateData['assigned_to']);
            $assigneeName = $assignee ? ($assignee['fname'] . ' ' . $assignee['lname']) : 'Unknown';
            
            $activityModel->log([
                'ticket_id' => $ticketId,
                'user_id' => $currentUser['id'],
                'action_type' => 'assigned',
                'new_value' => $assigneeName,
                'comment' => 'Assigned to ' . $assigneeName
            ]);
        }
    }
    
    if (isset($_POST['priority']) && $_POST['priority'] !== $ticket['priority']) {
        $updateData['priority'] = sanitize($_POST['priority']);
        // Set admin_priority flag when admin changes priority
        if (isset($_POST['admin_priority'])) {
            $updateData['admin_priority'] = 1;
        }
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'priority_change',
            'old_value' => $ticket['priority'],
            'new_value' => $updateData['priority'],
            'comment' => 'Priority set by admin to ' . ucfirst($updateData['priority']) . ' (locked)'
        ]);
    }
    
    if (isset($_POST['resolution']) && !empty($_POST['resolution']) && $_POST['resolution'] !== $ticket['resolution']) {
        $updateData['resolution'] = sanitize($_POST['resolution']);
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
        header("Location: view_ticket.php?id=" . $ticketId . "&success=1");
        exit();
    }
}

// Handle comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && !empty($_POST['comment'])) {
    $comment = sanitize($_POST['comment']);
    
    if ($isITStaff && $ticket['status'] === 'pending') {
        $slaModel->recordFirstResponse($ticketId);
    }
    
    $activityModel->log([
        'ticket_id' => $ticketId,
        'user_id' => $currentUser['id'],
        'action_type' => 'comment',
        'comment' => $comment
    ]);
    
    header("Location: view_ticket.php?id=" . $ticketId . "&success=comment");
    exit();
}

// Handle reply (conversation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message']) && !empty(trim($_POST['reply_message']))) {
    $replyMsg = sanitize(trim($_POST['reply_message']));
    $userType = ($_SESSION['user_type'] ?? 'user') === 'employee' ? 'employee' : 'user';
    
    $replyModel = new TicketReply();
    $replyModel->create([
        'ticket_id' => $ticketId,
        'user_id' => $currentUser['id'],
        'user_type' => $userType,
        'message' => $replyMsg
    ]);

    // Record first response for SLA if admin is replying to pending ticket
    if ($isITStaff && $ticket['status'] === 'pending') {
        $slaModel->recordFirstResponse($ticketId);
    }

    // Log activity
    $activityModel->log([
        'ticket_id' => $ticketId,
        'user_id' => $currentUser['id'],
        'action_type' => 'reply',
        'comment' => 'Replied to ticket'
    ]);

    // Notify the submitter
    try {
        $db = Database::getInstance()->getConnection();
        $notificationModel = new Notification($db);
        $notificationModel->create([
            'user_id' => $ticket['submitter_type'] === 'employee' ? null : $ticket['submitter_id'],
            'employee_id' => $ticket['submitter_type'] === 'employee' ? $ticket['submitter_id'] : null,
            'type' => 'ticket_reply',
            'title' => 'New Reply on Ticket',
            'message' => "New reply on ticket #{$ticket['ticket_number']}",
            'ticket_id' => $ticketId,
            'related_user_id' => $currentUser['id']
        ]);
    } catch (Exception $e) { error_log("Reply notification error: " . $e->getMessage()); }

    header("Location: view_ticket.php?id=" . $ticketId . "&success=reply");
    exit();
}

// Handle resolve with note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isITStaff && isset($_POST['resolve_ticket'])) {
    $resolution = sanitize($_POST['resolution_note'] ?? '');
    $oldStatus = $ticket['status'];
    
    if ($oldStatus === 'pending') {
        $slaModel->recordFirstResponse($ticketId);
    }
    $slaModel->recordResolution($ticketId);
    
    $updateData = ['status' => 'resolved'];
    if (!empty($resolution)) {
        $updateData['resolution'] = $resolution;
    }
    
    $activityModel->log([
        'ticket_id' => $ticketId,
        'user_id' => $currentUser['id'],
        'action_type' => 'status_change',
        'old_value' => $oldStatus,
        'new_value' => 'resolved',
        'comment' => !empty($resolution) ? "Resolved: " . $resolution : 'Ticket resolved'
    ]);
    
    try {
        $db = Database::getInstance()->getConnection();
        $notificationModel = new Notification($db);
        $notificationModel->create([
            'user_id' => $ticket['submitter_type'] === 'employee' ? null : $ticket['submitter_id'],
            'employee_id' => $ticket['submitter_type'] === 'employee' ? $ticket['submitter_id'] : null,
            'type' => 'ticket_resolved',
            'title' => 'Ticket Resolved',
            'message' => "Ticket #{$ticket['ticket_number']} has been resolved",
            'ticket_id' => $ticketId,
            'related_user_id' => $currentUser['id']
        ]);
    } catch (Exception $e) { error_log("Notification error: " . $e->getMessage()); }
    
    $ticketModel->update($ticketId, $updateData);
    header("Location: view_ticket.php?id=" . $ticketId . "&success=resolved");
    exit();
}

// Config arrays
$statusConfig = [
    'pending' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'icon' => 'fa-clock', 'label' => 'Pending'],
    'open' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'icon' => 'fa-folder-open', 'label' => 'Open'],
    'in_progress' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'border' => 'border-purple-200', 'icon' => 'fa-spinner', 'label' => 'In Progress'],
    'resolved' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'border' => 'border-green-200', 'icon' => 'fa-check-circle', 'label' => 'Resolved'],
    'closed' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'border' => 'border-gray-200', 'icon' => 'fa-lock', 'label' => 'Closed']
];
$priorityConfig = [
    'low' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => 'fa-arrow-down'],
    'medium' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icon' => 'fa-minus'],
    'high' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => 'fa-arrow-up']
];
$s = $statusConfig[$ticket['status']] ?? $statusConfig['pending'];
$p = $priorityConfig[$ticket['priority']] ?? $priorityConfig['medium'];

$pageTitle = $ticket['ticket_number'];
include __DIR__ . '/../views/layouts/header.php';
?>

<div class="lg:ml-64 min-h-screen bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 py-6 pt-20 lg:pt-6">
        
        <!-- Top Navigation Bar -->
        <div class="flex items-center justify-between mb-6">
            <a href="tickets.php" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                <i class="fas fa-arrow-left"></i> 
                <span class="hidden sm:inline">Back to Tickets</span>
            </a>
            
            <div class="flex items-center gap-2">
                <?php if ($isITStaff && !$ticket['assigned_to']): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="assign_to_me" value="1">
                    <button type="submit" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-hand-paper"></i>
                        <span class="hidden sm:inline">Take Ticket</span>
                    </button>
                </form>
                <?php endif; ?>
                <button onclick="window.print()" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors" title="Print">
                    <i class="fas fa-print"></i>
                </button>
            </div>
        </div>
        
        <!-- Success Messages -->
        <?php if (isset($_GET['success'])): ?>
        <div id="success-toast" class="fixed top-4 right-4 z-50 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 animate-slide-in">
            <i class="fas fa-check-circle"></i>
            <span>
                <?php
                $successMsg = match($_GET['success']) {
                    'status' => 'Status updated successfully',
                    'assigned' => 'Ticket assigned to you',
                    'comment' => 'Comment added',
                    'reply' => 'Reply sent',
                    'resolved' => 'Ticket resolved',
                    default => 'Updated successfully'
                };
                echo $successMsg;
                ?>
            </span>
            <button onclick="this.parentElement.remove()" class="ml-2 hover:opacity-70">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content Column -->
            <div class="lg:col-span-2 space-y-2">
                
                <!-- Ticket Header Card -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <!-- Status Bar -->
                    <div class="<?= $s['bg'] ?> <?= $s['border'] ?> border-b px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2.5 <?= $s['text'] ?>">
                            <i class="fas <?= $s['icon'] ?> text-base"></i>
                            <span class="font-semibold text-base"><?= $s['label'] ?></span>
                        </div>
                        <span class="text-sm font-mono <?= $s['text'] ?> font-semibold"><?= $ticket['ticket_number'] ?></span>
                    </div>
                    
                    <!-- Title & Meta -->
                    <div class="p-6">
                        <h1 class="text-xl lg:text-2xl font-semibold text-gray-900 mb-5"><?= htmlspecialchars($ticket['title']) ?></h1>
                        
                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($ticket['submitter_name']) ?>&background=e5e7eb&color=374151&size=24" 
                                     alt="" class="w-6 h-6 rounded-full">
                                <span><?= htmlspecialchars($ticket['submitter_name']) ?></span>
                            </div>
                            <span class="text-gray-300">•</span>
                            <span class="text-gray-500">
                                <i class="fas fa-folder mr-1"></i><?= htmlspecialchars($ticket['category_name']) ?>
                            </span>
                            <span class="text-gray-300">•</span>
                            <span class="text-gray-500" title="<?= formatDate($ticket['created_at'], 'F d, Y g:i A') ?>">
                                <i class="fas fa-clock mr-1"></i><?= formatDate($ticket['created_at'], 'M d, g:i A') ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Description Card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Description</h3>
                    <div class="prose prose-gray max-w-none">
                        <p class="text-gray-700 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($ticket['description']) ?></p>
                    </div>
                
                    <?php if ($ticket['attachments']): ?>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Attachments</h4>
                        <a href="../uploads/<?= htmlspecialchars($ticket['attachments']) ?>" 
                           class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition-colors" 
                           target="_blank">
                            <i class="fas fa-paperclip text-gray-400"></i>
                            <span><?= htmlspecialchars($ticket['attachments']) ?></span>
                            <i class="fas fa-external-link-alt text-gray-400 text-xs"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($ticket['resolution']): ?>
                <!-- Resolution Card -->
                <div class="bg-white rounded-xl border border-green-200 p-6 bg-gradient-to-br from-green-50 to-white">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-green-800 mb-1">Resolution</h3>
                            <p class="text-green-700 text-sm leading-relaxed"><?= nl2br(htmlspecialchars($ticket['resolution'])) ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Conversation / Replies -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900">
                                <i class="fas fa-comments text-gray-400 mr-2"></i>Conversation
                            </h3>
                            <span class="text-xs text-gray-400 font-medium"><?= count($replies) ?> messages</span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <!-- Messages -->
                        <div class="space-y-4 mb-6 max-h-[500px] overflow-y-auto" id="replies-container">
                            <?php if (empty($replies)): ?>
                            <div class="text-center py-8">
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-comments text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500">No messages yet</p>
                                <p class="text-xs text-gray-400 mt-1">Start a conversation with the ticket submitter</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($replies as $reply): 
                                $isMe = ($reply['user_id'] == $currentUser['id'] && 
                                        (($_SESSION['user_type'] === 'employee' && $reply['user_type'] === 'employee') || 
                                         ($_SESSION['user_type'] === 'user' && $reply['user_type'] === 'user')));
                                $isStaff = in_array($reply['sender_role'], ['superadmin', 'it', 'hr', 'admin', 'it_staff']);
                            ?>
                            <div class="flex <?= $isMe ? 'justify-end' : 'justify-start' ?>">
                                <div class="max-w-[75%]">
                                    <div class="flex items-center gap-2 mb-1 <?= $isMe ? 'justify-end' : '' ?>">
                                        <?php if (!$isMe): ?>
                                        <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                                            <?= strtoupper(substr($reply['sender_name'] ?? '?', 0, 1)) ?>
                                        </div>
                                        <?php endif; ?>
                                        <span class="text-xs font-medium <?= $isStaff ? 'text-blue-600' : 'text-gray-600' ?>">
                                            <?= htmlspecialchars($reply['sender_name'] ?? 'Unknown') ?>
                                            <?php if ($isStaff): ?>
                                            <span class="text-[10px] bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded-full ml-1">Staff</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="text-[10px] text-gray-400"><?= date('M d, g:i A', strtotime($reply['created_at'])) ?></span>
                                    </div>
                                    <div class="<?= $isMe ? 'bg-blue-50 text-gray-800 border border-blue-200' : 'bg-gray-100 text-gray-800' ?> rounded-2xl px-4 py-3 text-sm leading-relaxed <?= $isMe ? 'rounded-tr-md' : 'rounded-tl-md' ?>">
                                        <?= nl2br(htmlspecialchars($reply['message'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Reply Form -->
                        <?php if (!in_array($ticket['status'], ['closed'])): ?>
                        <form method="POST" class="border-t border-gray-100 pt-4">
                            <div class="flex gap-3">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($currentUser['full_name']) ?>&background=000000&color=fff&size=32" 
                                     alt="" class="w-8 h-8 rounded-full flex-shrink-0 mt-1">
                                <div class="flex-1">
                                    <textarea name="reply_message" rows="2" 
                                              class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent resize-none"
                                              placeholder="Type your reply..." required></textarea>
                                    <div class="mt-2 flex justify-end">
                                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm hover:bg-gray-800 transition-colors inline-flex items-center gap-2">
                                            <i class="fas fa-paper-plane"></i>
                                            <span>Send Reply</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="border-t border-gray-100 pt-4 text-center">
                            <p class="text-xs text-gray-400"><i class="fas fa-lock mr-1"></i>This ticket is closed. Replies are disabled.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900">Activity</h3>
                            <span class="text-xs text-gray-400 font-medium"><?= count($activities) ?> events</span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <!-- Comment Form at Top -->
                        <form method="POST" class="mb-6">
                            <div class="flex gap-3">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($currentUser['full_name']) ?>&background=000000&color=fff&size=32" 
                                     alt="" class="w-8 h-8 rounded-full flex-shrink-0">
                                <div class="flex-1">
                                    <?php 
                                    $commentPlaceholder = $ticket['status'] === 'closed' 
                                        ? "Add a comment to this closed ticket..." 
                                        : "Add a comment or update...";
                                    ?>
                                    <textarea name="comment" rows="2" 
                                              class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none transition-all"
                                              placeholder="<?= $commentPlaceholder ?>" required></textarea>
                                    <div class="mt-2 flex items-center justify-between">
                                        <?php if ($ticket['status'] === 'closed'): ?>
                                        <span class="text-xs text-amber-600"><i class="fas fa-info-circle mr-1"></i>Comments allowed on closed tickets</span>
                                        <?php else: ?>
                                        <span class="text-xs text-gray-400">Press Enter to submit, Shift+Enter for new line</span>
                                        <?php endif; ?>
                                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm hover:bg-gray-800 transition-colors inline-flex items-center gap-2">
                                            <i class="fas fa-paper-plane"></i>
                                            <span>Comment</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Activity List -->
                        <div class="max-h-96 overflow-y-auto space-y-4 pr-2">
                            <?php if (empty($activities)): ?>
                            <div class="text-center py-8">
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-comment-slash text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500">No activity yet</p>
                                <p class="text-xs text-gray-400 mt-1">Be the first to add a comment</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($activities as $activity): 
                                $activityIcons = [
                                    'created' => ['icon' => 'fa-plus', 'bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
                                    'comment' => ['icon' => 'fa-comment', 'bg' => 'bg-gray-100', 'text' => 'text-gray-600'],
                                    'status_change' => ['icon' => 'fa-exchange-alt', 'bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
                                    'assigned' => ['icon' => 'fa-user-plus', 'bg' => 'bg-green-100', 'text' => 'text-green-600'],
                                    'resolution_added' => ['icon' => 'fa-check', 'bg' => 'bg-green-100', 'text' => 'text-green-600'],
                                    'priority_change' => ['icon' => 'fa-flag', 'bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
                                    'reply' => ['icon' => 'fa-reply', 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-600']
                                ];
                                $aIcon = $activityIcons[$activity['action_type']] ?? ['icon' => 'fa-info', 'bg' => 'bg-gray-100', 'text' => 'text-gray-500'];
                            ?>
                            <div class="flex gap-3 group">
                                <div class="flex flex-col items-center">
                                    <div class="w-8 h-8 rounded-full <?= $aIcon['bg'] ?> flex items-center justify-center flex-shrink-0">
                                        <i class="fas <?= $aIcon['icon'] ?> <?= $aIcon['text'] ?> text-xs"></i>
                                    </div>
                                    <?php if ($activity !== end($activities)): ?>
                                    <div class="w-px h-full bg-gray-200 mt-2"></div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 pb-4">
                                    <div class="flex items-baseline gap-2 flex-wrap">
                                        <span class="font-medium text-gray-900 text-sm"><?= htmlspecialchars($activity['user_name']) ?></span>
                                        <span class="text-xs text-gray-400"><?= formatDate($activity['created_at'], 'M d, g:i A') ?></span>
                                    </div>
                                    <?php if ($activity['action_type'] === 'comment'): ?>
                                    <div class="mt-2 p-3 bg-gray-50 rounded-lg text-sm text-gray-700 leading-relaxed">
                                        <?= nl2br(htmlspecialchars($activity['comment'])) ?>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($activity['comment'] ?? ucfirst(str_replace('_', ' ', $activity['action_type']))) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar Column -->
            <div class="space-y-4">
                
                <?php if ($isITStaff && $slaData): ?>
                <!-- SLA Card -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-base font-semibold text-gray-700">SLA Status</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Response SLA -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-gray-500">First Response</span>
                                <?php
                                $rStatus = $slaData['response_sla_status'];
                                $rBadgeClass = [
                                    'met' => 'bg-green-100 text-green-700',
                                    'breached' => 'bg-red-100 text-red-700',
                                    'pending' => 'bg-amber-100 text-amber-700'
                                ];
                                ?>
                                <span id="response-timer" class="text-xs font-medium px-2 py-0.5 rounded <?= $rBadgeClass[$rStatus] ?? 'bg-gray-100 text-gray-600' ?>"
                                      data-due="<?= $slaData['response_due_at'] ?>"
                                      data-met="<?= $slaData['first_response_at'] ? '1' : '0' ?>"
                                      data-overdue="<?= $slaData['response_remaining']['is_overdue'] ? '1' : '0' ?>"
                                      data-paused="<?= $slaData['is_paused'] ? '1' : '0' ?>">
                                    <?= $slaData['first_response_at'] ? 'Met' : ($slaData['response_remaining']['is_overdue'] ? 'Breached' : $slaData['response_remaining']['formatted']) ?>
                                </span>
                            </div>
                            <?php 
                            $responseTargetMinutes = ($slaData['target_response'] ?? 0);
                            if (!$slaData['first_response_at'] && !$slaData['response_remaining']['is_overdue'] && $responseTargetMinutes > 0): 
                                $responseProgress = min(100, max(0, 100 - ($slaData['response_remaining']['minutes'] / $responseTargetMinutes * 100)));
                            ?>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                <div id="response-progress" class="bg-amber-500 h-1.5 rounded-full transition-all" 
                                     style="width: <?= $responseProgress ?>%"
                                     data-target="<?= $responseTargetMinutes ?>"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Resolution SLA -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-gray-500">Resolution</span>
                                <?php
                                $resStatus = $slaData['resolution_sla_status'];
                                ?>
                                <span id="resolution-timer" class="text-xs font-medium px-2 py-0.5 rounded <?= $rBadgeClass[$resStatus] ?? 'bg-gray-100 text-gray-600' ?>"
                                      data-due="<?= $slaData['resolution_due_at'] ?>"
                                      data-met="<?= $slaData['resolved_at'] ? '1' : '0' ?>"
                                      data-overdue="<?= $slaData['resolution_remaining']['is_overdue'] ? '1' : '0' ?>"
                                      data-paused="<?= $slaData['is_paused'] ? '1' : '0' ?>">
                                    <?= $slaData['resolved_at'] ? 'Met' : ($slaData['resolution_remaining']['is_overdue'] ? 'Breached' : $slaData['resolution_remaining']['formatted']) ?>
                                </span>
                            </div>
                            <?php 
                            $resolutionTargetMinutes = ($slaData['target_resolution'] ?? 0);
                            if (!$slaData['resolved_at'] && !$slaData['resolution_remaining']['is_overdue'] && $resolutionTargetMinutes > 0): 
                                $resolutionProgress = min(100, max(0, 100 - ($slaData['resolution_remaining']['minutes'] / $resolutionTargetMinutes * 100)));
                            ?>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                <div id="resolution-progress" class="bg-blue-500 h-1.5 rounded-full transition-all" 
                                     style="width: <?= $resolutionProgress ?>%"
                                     data-target="<?= $resolutionTargetMinutes ?>"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($slaData['is_paused']): ?>
                        <div class="flex items-center gap-2 text-xs text-gray-500 bg-gray-50 rounded-lg p-2">
                            <i class="fas fa-pause-circle"></i>
                            <span>SLA timer paused</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Ticket Details Card -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-base font-semibold text-gray-700">Ticket Details</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Status -->
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase tracking-wide block mb-2">Status</label>
                            <span class="inline-flex items-center gap-1.5 <?= $s['bg'] ?> <?= $s['text'] ?> px-3 py-1.5 rounded-lg text-sm font-medium">
                                <i class="fas <?= $s['icon'] ?> text-xs"></i>
                                <?= $s['label'] ?>
                            </span>
                        </div>
                        
                        <!-- Priority -->
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase tracking-wide block mb-2">Priority</label>
                            <span class="inline-flex items-center gap-1.5 <?= $p['bg'] ?> <?= $p['text'] ?> px-3 py-1.5 rounded-lg text-sm font-medium">
                                <i class="fas <?= $p['icon'] ?> text-xs"></i>
                                <?= ucfirst($ticket['priority']) ?>
                            </span>
                        </div>
                        
                        <!-- Assigned To -->
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase tracking-wide block mb-2">Assigned To</label>
                            <?php if ($ticket['assigned_name']): ?>
                            <div class="flex items-center gap-2">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($ticket['assigned_name']) ?>&background=3b82f6&color=fff&size=24" 
                                     alt="" class="w-6 h-6 rounded-full">
                                <span class="text-sm text-gray-900"><?= htmlspecialchars($ticket['assigned_name']) ?></span>
                            </div>
                            <?php else: ?>
                            <span class="text-sm text-gray-400 italic">Unassigned</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Category -->
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase tracking-wide block mb-2">Category</label>
                            <span class="text-sm text-gray-900"><?= htmlspecialchars($ticket['category_name']) ?></span>
                        </div>
                        
                        <!-- Submitter -->
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase tracking-wide block mb-2">Submitter</label>
                            <div class="text-sm">
                                <div class="text-gray-900"><?= htmlspecialchars($ticket['submitter_name']) ?></div>
                                <div class="text-gray-500 text-xs"><?= htmlspecialchars($ticket['submitter_email']) ?></div>
                            </div>
                        </div>
                        
                        <!-- Dates -->
                        <div class="pt-4 border-t border-gray-100">
                            <div class="grid grid-cols-2 gap-4 text-xs">
                                <div>
                                    <label class="text-gray-400 block">Created</label>
                                    <span class="text-gray-600"><?= formatDate($ticket['created_at'], 'M d, Y') ?></span>
                                </div>
                                <div>
                                    <label class="text-gray-400 block">Updated</label>
                                    <span class="text-gray-600"><?= formatDate($ticket['updated_at'] ?? $ticket['created_at'], 'M d, Y') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($isITStaff): ?>
                <!-- Edit Ticket (Collapsible) -->
                <details class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <summary class="px-6 py-4 cursor-pointer hover:bg-gray-50 transition-colors flex items-center justify-between">
                        <span class="text-base font-semibold text-gray-700">
                            <i class="fas fa-cog mr-2 text-gray-400"></i>Advanced Options
                        </span>
                        <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform"></i>
                    </summary>
                    <form method="POST" class="px-6 pb-6 pt-4 space-y-5 border-t border-gray-100">
                        <input type="hidden" name="update_ticket" value="1">
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="pending" <?= $ticket['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Priority (Admin Controlled)</label>
                            <select name="priority" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="low" <?= $ticket['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= $ticket['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= $ticket['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                            </select>
                            <input type="hidden" name="admin_priority" value="1">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Assign To (Admin)</label>
                            <select name="assigned_to" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Unassigned - Return to Pool</option>
                                <?php foreach ($itStaff as $staff): ?>
                                <option value="<?= $staff['id'] ?>" <?= $ticket['assigned_to'] == $staff['id'] ? 'selected' : '' ?>><?= htmlspecialchars($staff['full_name'] ?? ($staff['fname'] . ' ' . $staff['lname'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Resolution Notes</label>
                            <textarea name="resolution" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none" placeholder="How was this issue resolved?"><?= htmlspecialchars($ticket['resolution'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="w-full px-4 py-2.5 bg-gray-900 text-white rounded-lg text-sm hover:bg-gray-800 transition-colors">
                            <i class="fas fa-save mr-2"></i>Save Changes
                        </button>
                    </form>
                </details>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Resolve Modal -->
<?php if ($isITStaff && $ticket['status'] !== 'resolved' && $ticket['status'] !== 'closed'): ?>
<div id="resolveModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" onclick="document.getElementById('resolveModal').classList.add('hidden')"></div>
        
        <div class="relative bg-white rounded-xl overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="resolve_ticket" value="1">
                
                <div class="p-6">
                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Resolve Ticket</h3>
                    <p class="text-sm text-gray-500 mb-4">Add a resolution note to let the submitter know how their issue was resolved.</p>
                    
                    <textarea name="resolution_note" rows="4" 
                              class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"
                              placeholder="Describe the resolution (optional)..."><?= htmlspecialchars($ticket['resolution'] ?? '') ?></textarea>
                </div>
                
                <div class="bg-gray-50 px-6 py-4 flex gap-3 justify-end">
                    <button type="button" onclick="document.getElementById('resolveModal').classList.add('hidden')" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-check mr-1"></i>Resolve Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
.animate-slide-in { animation: slideIn 0.3s ease-out; }

details[open] summary i.fa-chevron-down { transform: rotate(180deg); }

@media print {
    .lg\\:ml-64 { margin-left: 0 !important; }
    button, form, details summary { display: none !important; }
    .bg-gray-50 { background: white !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss success toast
    const toast = document.getElementById('success-toast');
    if (toast) {
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Comment form - Enter to submit
    const commentBox = document.querySelector('textarea[name="comment"]');
    if (commentBox) {
        commentBox.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim()) {
                    this.form.submit();
                }
            }
        });
    }
    
    // Escape key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('resolveModal');
            if (modal) modal.classList.add('hidden');
        }
    });
    
    // Live SLA Countdown Timers
    function updateSLATimer(timerId, progressId) {
        const timerEl = document.getElementById(timerId);
        const progressEl = document.getElementById(progressId);
        
        if (!timerEl) return null;
        
        const dueDate = new Date(timerEl.dataset.due);
        const isMet = timerEl.dataset.met === '1';
        const isPaused = timerEl.dataset.paused === '1';
        
        if (isMet || isPaused) return null; // Don't update if met or paused
        
        return function() {
            const now = new Date();
            const diff = dueDate - now;
            const isOverdue = diff < 0;
            
            const totalMinutes = Math.floor(Math.abs(diff) / 1000 / 60);
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            
            let displayText = '';
            if (hours > 0) {
                displayText = hours + 'h ' + minutes + 'm';
            } else {
                displayText = minutes + 'm';
            }
            
            if (isOverdue) {
                displayText = 'BREACHED ' + displayText + ' ago';
                timerEl.classList.remove('bg-amber-100', 'text-amber-700', 'bg-gray-100', 'text-gray-600');
                timerEl.classList.add('bg-red-100', 'text-red-700');
            } else {
                displayText = displayText + ' remaining';
            }
            
            timerEl.textContent = displayText;
            
            // Update progress bar if exists
            if (progressEl && !isOverdue) {
                const targetMinutes = parseFloat(progressEl.dataset.target);
                if (targetMinutes > 0) {
                    const progress = Math.max(0, Math.min(100, 100 - (totalMinutes / targetMinutes * 100)));
                    progressEl.style.width = progress + '%';
                }
            }
        };
    }
    
    // Initialize timers
    const responseUpdater = updateSLATimer('response-timer', 'response-progress');
    const resolutionUpdater = updateSLATimer('resolution-timer', 'resolution-progress');
    
    // Update immediately
    if (responseUpdater) responseUpdater();
    if (resolutionUpdater) resolutionUpdater();
    
    // Update every minute
    setInterval(function() {
        if (responseUpdater) responseUpdater();
        if (resolutionUpdater) resolutionUpdater();
    }, 60000); // 60 seconds
});
</script>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
