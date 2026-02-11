<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?> - <?php echo defined('APP_NAME') ? APP_NAME : 'ServiceHub'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../includes/customer_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-white border-b border-gray-100">
            <div class="flex items-center justify-between px-6 py-4 pt-20 lg:pt-4">
                <div class="flex items-center space-x-4">
                    <a href="tickets.php" class="text-gray-400 hover:text-gray-600 transition">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <div class="flex items-center space-x-3">
                            <h1 class="text-xl font-semibold text-gray-800">
                                Ticket <span class="font-mono text-emerald-600">#<?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                            </h1>
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
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5"><?php echo htmlspecialchars($ticket['title']); ?></p>
                    </div>
                </div>
                <a href="tickets.php" class="inline-flex items-center px-4 py-2 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition text-sm font-medium">
                    <i class="fas fa-list mr-2"></i>All Tickets
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 flex items-center">
                <i class="fas fa-check-circle mr-3 text-emerald-500"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 flex items-center">
                <i class="fas fa-check-circle mr-3 text-emerald-500"></i>
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
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
                            <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-file-alt text-emerald-500 text-xs"></i>
                            </div>
                            Ticket Details
                        </h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Description</label>
                                <div class="mt-2 text-gray-700 text-sm whitespace-pre-wrap bg-gray-50 p-4 rounded-lg"><?php echo htmlspecialchars($ticket['description']); ?></div>
                            </div>

                            <?php if ($ticket['attachments']): ?>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Attachment</label>
                                <div class="mt-2">
                                    <a href="../uploads/<?php echo htmlspecialchars($ticket['attachments']); ?>" 
                                       target="_blank"
                                       class="inline-flex items-center px-4 py-2 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 transition text-sm border border-gray-200">
                                        <i class="fas fa-paperclip mr-2 text-emerald-500"></i>
                                        <?php echo htmlspecialchars($ticket['attachments']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($ticket['resolution'])): ?>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Resolution</label>
                                <div class="mt-2 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-800">
                                    <?php echo htmlspecialchars($ticket['resolution']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Conversation / Replies -->
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
                            <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-comments text-indigo-500 text-xs"></i>
                            </div>
                            Conversation
                            <span class="ml-auto text-xs text-gray-400 font-normal"><?= count($replies ?? []) ?> messages</span>
                        </h2>
                        
                        <!-- Messages -->
                        <div class="space-y-4 mb-6 max-h-[500px] overflow-y-auto" id="replies-container">
                            <?php if (empty($replies)): ?>
                            <div class="text-center py-8">
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-comments text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500">No messages yet</p>
                                <p class="text-xs text-gray-400 mt-1">Send a message to communicate with IT staff</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($replies as $reply): 
                                $isMe = ($reply['user_id'] == $currentUser['id'] && 
                                        (($_SESSION['user_type'] === 'employee' && $reply['user_type'] === 'employee') || 
                                         ($_SESSION['user_type'] === 'user' && $reply['user_type'] === 'user')));
                                $isStaff = in_array($reply['sender_role'] ?? '', ['superadmin', 'it', 'hr', 'admin', 'it_staff']);
                            ?>
                            <div class="flex <?= $isMe ? 'justify-end' : 'justify-start' ?>">
                                <div class="max-w-[80%]">
                                    <div class="flex items-center gap-2 mb-1 <?= $isMe ? 'justify-end' : '' ?>">
                                        <?php if (!$isMe): ?>
                                        <div class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-[10px] font-semibold text-gray-600">
                                            <?= strtoupper(substr($reply['sender_name'] ?? '?', 0, 1)) ?>
                                        </div>
                                        <?php endif; ?>
                                        <span class="text-xs font-medium <?= $isStaff ? 'text-blue-600' : 'text-gray-600' ?>">
                                            <?= htmlspecialchars($reply['sender_name'] ?? 'Unknown') ?>
                                            <?php if ($isStaff): ?>
                                            <span class="text-[10px] bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded-full ml-1">IT Staff</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="text-[10px] text-gray-400"><?= date('M d, g:i A', strtotime($reply['created_at'])) ?></span>
                                    </div>
                                    <div class="<?= $isMe ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-800' ?> rounded-2xl px-4 py-3 text-sm leading-relaxed <?= $isMe ? 'rounded-tr-md' : 'rounded-tl-md' ?>">
                                        <?= nl2br(htmlspecialchars($reply['message'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Reply Form -->
                        <?php if (!in_array($ticket['status'], ['closed'])): ?>
                        <form method="POST" action="view_ticket.php?id=<?= $ticket['id'] ?>" class="border-t border-gray-100 pt-4">
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 mt-1">
                                    <span class="text-xs font-semibold text-emerald-600"><?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?></span>
                                </div>
                                <div class="flex-1">
                                    <textarea name="reply_message" rows="2" 
                                              class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none"
                                              placeholder="Type your reply..." required></textarea>
                                    <div class="mt-2 flex justify-end">
                                        <button type="submit" class="px-4 py-2 bg-emerald-500 text-white rounded-lg text-sm hover:bg-emerald-600 transition-colors inline-flex items-center gap-2">
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

                    <!-- Activity Log -->
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
                            <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-history text-blue-500 text-xs"></i>
                            </div>
                            Activity Log
                        </h2>
                        
                        <?php if (empty($activities)): ?>
                            <div class="text-center py-8">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-clock text-gray-400"></i>
                                </div>
                                <p class="text-gray-500 text-sm">No activity yet</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($activities as $activity): ?>
                                <div class="flex items-start space-x-3 pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                                    <div class="flex-shrink-0">
                                        <?php
                                        $iconConfig = [
                                            'created' => ['icon' => 'plus', 'bg' => 'bg-emerald-100', 'color' => 'text-emerald-600'],
                                            'status_change' => ['icon' => 'exchange-alt', 'bg' => 'bg-blue-100', 'color' => 'text-blue-600'],
                                            'assigned' => ['icon' => 'user-plus', 'bg' => 'bg-purple-100', 'color' => 'text-purple-600'],
                                            'commented' => ['icon' => 'comment', 'bg' => 'bg-amber-100', 'color' => 'text-amber-600'],
                                            'reply' => ['icon' => 'reply', 'bg' => 'bg-indigo-100', 'color' => 'text-indigo-600']
                                        ];
                                        $actionIcon = $iconConfig[$activity['action_type']] ?? ['icon' => 'edit', 'bg' => 'bg-gray-100', 'color' => 'text-gray-600'];
                                        ?>
                                        <div class="w-8 h-8 rounded-full <?php echo $actionIcon['bg']; ?> flex items-center justify-center">
                                            <i class="fas fa-<?php echo $actionIcon['icon']; ?> <?php echo $actionIcon['color']; ?> text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-700">
                                            <strong class="font-medium text-gray-900"><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></strong>
                                            <?php echo htmlspecialchars($activity['comment']); ?>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
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
                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
                            <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-edit text-amber-500 text-xs"></i>
                            </div>
                            Update Ticket
                        </h2>
                        
                        <form action="view_ticket.php" method="POST" class="space-y-4">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="status" class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Status</label>
                                    <select name="status" id="status" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white">
                                        <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="assigned_to" class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Assign To</label>
                                    <select name="assigned_to" id="assigned_to" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white">
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
                                <label for="resolution" class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Resolution / Notes</label>
                                <textarea name="resolution" id="resolution" rows="4" 
                                          class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                                          placeholder="Add resolution details or notes..."><?php echo htmlspecialchars($ticket['resolution'] ?? ''); ?></textarea>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-5 py-2.5 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 transition font-medium text-sm">
                                    <i class="fas fa-save mr-2"></i>Update Ticket
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-5">
                    <!-- Ticket Info Card -->
                    <div class="bg-white rounded-xl border border-gray-100 p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">Ticket Information</h3>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-xs text-gray-500">Ticket Number</span>
                                <span class="font-mono text-emerald-600 font-semibold text-sm"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                            </div>

                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-xs text-gray-500">Priority</span>
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
                            </div>

                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-xs text-gray-500">Category</span>
                                <span class="text-sm text-gray-800"><?php echo htmlspecialchars($ticket['category_name']); ?></span>
                            </div>

                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-xs text-gray-500">Submitted By</span>
                                <span class="text-sm text-gray-800"><?php echo htmlspecialchars($ticket['submitter_name']); ?></span>
                            </div>

                            <?php if ($ticket['assigned_name']): ?>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-xs text-gray-500">Assigned To</span>
                                <span class="text-sm text-gray-800"><?php echo htmlspecialchars($ticket['assigned_name']); ?></span>
                            </div>
                            <?php endif; ?>

                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-xs text-gray-500">Created</span>
                                <span class="text-sm text-gray-800"><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></span>
                            </div>

                            <div class="flex justify-between items-center py-2">
                                <span class="text-xs text-gray-500">Last Updated</span>
                                <span class="text-sm text-gray-800"><?php echo date('M d, Y', strtotime($ticket['updated_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Help Card -->
                    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-5 border border-emerald-100">
                        <h3 class="text-sm font-semibold text-emerald-800 mb-3 flex items-center">
                            <i class="fas fa-lightbulb mr-2 text-emerald-600"></i>
                            Need Help?
                        </h3>
                        <ul class="space-y-2 text-xs text-emerald-700">
                            <li class="flex items-center">
                                <i class="fas fa-check text-emerald-500 mr-2"></i>
                                <span>Check activity log for updates</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-emerald-500 mr-2"></i>
                                <span>IT staff responds within 24 hours</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-emerald-500 mr-2"></i>
                                <span>Email notifications enabled</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/helpers.js"></script>
</body>
</html>
