<?php
/**
 * Enhanced Ticket View with Activity Logging
 */
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/activity_logger.php';
require_once 'includes/MessageTracker.php';

session_start();
requireLogin();

// Helper functions
function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

function getUserName() {
    if (isset($_SESSION['user_data']['name'])) {
        return $_SESSION['user_data']['name'];
    }
    return $_SESSION['username'] ?? 'User';
}

$ticketId = intval($_GET['id'] ?? 0);
$userType = $_SESSION['user_type'];
$userId = getUserId();

if ($ticketId <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Initialize variables
$message = '';
$error = '';
$ticket = null;

// Handle success messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'response_added':
            $message = 'Response added successfully.';
            break;
        case 'status_updated':
            $message = 'Status updated successfully.';
            break;
        case 'assigned':
            $message = 'Ticket assignment updated successfully.';
            break;
        case 'priority_updated':
            $message = 'Priority updated successfully.';
            break;
    }
}

// Load ticket data first
try {
    require_once 'config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get ticket with employee info
    $stmt = $db->prepare("
        SELECT 
            t.*,
            e.username as employee_username,
            CONCAT(e.fname, ' ', e.lname) as employee_name,
            e.email as employee_email,
            its.name as assigned_staff_name
        FROM tickets t
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN it_staff its ON t.assigned_to = its.staff_id
        WHERE t.ticket_id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    // Security check: Employees can only view their own tickets
    if (!$ticket) {
        $error = 'Ticket not found.';
    } elseif ($userType === 'employee' && $ticket['employee_id'] != $userId) {
        $error = 'You can only view your own tickets.';
        $ticket = null;
    } else {
        // ✅ MARK MESSAGES AS READ - User is viewing this ticket
        try {
            $messageTracker = new MessageTracker();
            $messageTracker->markTicketAsRead($ticketId, $userId, $userType);
        } catch (Exception $e) {
            error_log("Error marking ticket as read: " . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Handle form submissions (only if ticket exists and not AJAX)
// Skip processing if this is an AJAX request (should go to api/add_response.php instead)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ticket && !isset($_POST['ajax_request'])) {
    try {
        $logger = new ActivityLogger($db);
        
        if (isset($_POST['add_response'])) {
            // Add response
            $response_text = trim($_POST['response_text'] ?? '');
            $is_internal = isset($_POST['is_internal']) ? 1 : 0;
            
            // Security checks
            if ($userType === 'employee') {
                // Employees can only add responses to their own tickets
                if ($ticket['employee_id'] != $userId) {
                    $error = 'You can only add responses to your own tickets.';
                } else {
                    // Employees cannot add internal responses
                    $is_internal = 0;
                }
            }
            
            if ($response_text && !$error) {
                $stmt = $db->prepare("
                    INSERT INTO ticket_responses (ticket_id, user_id, user_type, message, is_internal, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$ticketId, $userId, $userType, $response_text, $is_internal]);
                $responseId = $db->lastInsertId();
                
                // Update ticket updated_at
                $stmt = $db->prepare("UPDATE tickets SET updated_at = NOW() WHERE ticket_id = ?");
                $stmt->execute([$ticketId]);
                
                // Log the activity
                $logger->logResponseAdded($userId, $userType, $ticketId, $responseId, $is_internal);
                
                // Clear notification session to allow immediate detection by other users
                unset($_SESSION["last_check_ticket_{$ticketId}"]);
                
                $message = 'Response added successfully.';
                
                // Add flag to trigger immediate notification check for other users
                $_SESSION["response_added_ticket_{$ticketId}"] = time();
                
                // Refresh page to show new response
                header("Location: view_ticket.php?id=$ticketId&success=response_added");
                exit;
            } else if (!$response_text) {
                $error = 'Please enter a response message.';
            }
        }
        
        if (isset($_POST['update_status']) && $userType === 'it_staff') {
            $new_status = $_POST['status'] ?? '';
            $old_status = $ticket['status'];
            
            if ($new_status && $new_status !== $old_status) {
                $stmt = $db->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE ticket_id = ?");
                $stmt->execute([$new_status, $ticketId]);
                
                // Log the activity
                $logger->logStatusChange($userId, $userType, $ticketId, $old_status, $new_status);
                
                // Send Firebase notification for status change
                try {
                    require_once 'includes/firebase_notifications.php';
                    $firebaseNotifier = new FirebaseNotificationSender();
                    $firebaseNotifier->sendStatusChangeNotification($ticketId, $new_status, $userId);
                } catch (Exception $e) {
                    error_log("Firebase notification error for status change: " . $e->getMessage());
                }
                
                $message = 'Status updated successfully.';
                header("Location: view_ticket.php?id=$ticketId&success=status_updated");
                exit;
            }
        }
        
        if (isset($_POST['assign_ticket']) && $userType === 'it_staff') {
            $assigned_to = intval($_POST['assigned_to'] ?? 0);
            $old_assigned = $ticket['assigned_to'];
            
            if ($assigned_to !== $old_assigned) {
                // Get assigned staff name
                $stmt = $db->prepare("SELECT name FROM it_staff WHERE staff_id = ?");
                $stmt->execute([$assigned_to]);
                $assignedStaff = $stmt->fetch();
                $assignedName = $assignedStaff ? $assignedStaff['name'] : 'Unknown';
                
                $stmt = $db->prepare("UPDATE tickets SET assigned_to = ?, updated_at = NOW() WHERE ticket_id = ?");
                $stmt->execute([$assigned_to, $ticketId]);
                
                // Log the activity
                $logger->logAssignment($userId, $userType, $ticketId, $assigned_to, $assignedName);
                
                $message = 'Ticket assignment updated successfully.';
                header("Location: view_ticket.php?id=$ticketId&success=assigned");
                exit;
            }
        }
        
        if (isset($_POST['update_priority']) && $userType === 'it_staff') {
            $new_priority = $_POST['priority'] ?? '';
            $old_priority = $ticket['priority'];
            
            if ($new_priority && $new_priority !== $old_priority) {
                $stmt = $db->prepare("UPDATE tickets SET priority = ?, updated_at = NOW() WHERE ticket_id = ?");
                $stmt->execute([$new_priority, $ticketId]);
                
                // Log the activity
                $logger->logPriorityChange($userId, $userType, $ticketId, $old_priority, $new_priority);
                
                $message = 'Priority updated successfully.';
                header("Location: view_ticket.php?id=$ticketId&success=priority_updated");
                exit;
            }
        }
        
    } catch (Exception $e) {
        $error = 'Error processing request: ' . $e->getMessage();
    }
}

// Load additional data if ticket exists
if ($ticket) {
    try {
        // Get responses with seen status using subquery to avoid duplicates
        $responsesQuery = "
            SELECT r.*, 
                   (SELECT COUNT(*) FROM message_seen ms 
                    WHERE ms.response_id = r.response_id 
                    AND ((r.user_type = 'employee' AND ms.seen_by_user_type = 'it_staff') 
                         OR (r.user_type = 'it_staff' AND ms.seen_by_user_type = 'employee'))) > 0 as is_seen,
                   (SELECT MAX(ms2.seen_at) FROM message_seen ms2 
                    WHERE ms2.response_id = r.response_id 
                    AND ((r.user_type = 'employee' AND ms2.seen_by_user_type = 'it_staff') 
                         OR (r.user_type = 'it_staff' AND ms2.seen_by_user_type = 'employee'))) as seen_at
            FROM ticket_responses r
            WHERE r.ticket_id = ?
        ";
        
        // Filter internal responses for employees
        if ($userType === 'employee') {
            $responsesQuery .= " AND (r.is_internal = 0 OR r.is_internal IS NULL)";
        }
        
        $responsesQuery .= " ORDER BY r.created_at ASC";
        
        $stmt = $db->prepare($responsesQuery);
        $stmt->execute([$ticketId]);
        $responses = $stmt->fetchAll();
        
        // Get IT staff for assignment dropdown
        $stmt = $db->query("SELECT staff_id, name FROM it_staff WHERE is_active = 1 ORDER BY name");
        $itStaff = $stmt->fetchAll();
        
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
} else {
    $responses = [];
    $itStaff = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $ticket ? "Ticket #{$ticketId}: " . htmlspecialchars($ticket['subject']) : "View Ticket #$ticketId" ?> - IT Help Desk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .priority-low { @apply bg-green-100 text-green-800 border-green-200; }
        .priority-medium { @apply bg-yellow-100 text-yellow-800 border-yellow-200; }
        .priority-high { @apply bg-orange-100 text-orange-800 border-orange-200; }
        .priority-critical { @apply bg-red-100 text-red-800 border-red-200; }
        .status-open { @apply bg-red-100 text-red-800 border-red-200; }
        .status-in_progress { @apply bg-blue-100 text-blue-800 border-blue-200; }
        .status-resolved { @apply bg-green-100 text-green-800 border-green-200; }
        .status-closed { @apply bg-gray-100 text-gray-800 border-gray-200; }
        .card-hover { @apply transition-all duration-300 hover:shadow-xl hover:-translate-y-1; }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Fix notification dropdown overlapping */
        #notificationDropdown {
            z-index: 99999 !important;
            position: fixed !important;
            top: auto !important;
            right: 1rem !important;
            margin-top: 0.5rem !important;
        }
        
        /* Ensure notification container creates proper stacking context */
        .notification-container {
            position: relative;
            z-index: 9999;
        }
    </style>
     <style>
        .typing-dots {
            display: inline-block;
        }
        
        .typing-dots span {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #3B82F6;
            animation: typing 1.4s infinite ease-in-out both;
        }
        
        .typing-dots span:nth-child(1) {
            animation-delay: -0.32s;
        }
        
        .typing-dots span:nth-child(2) {
            animation-delay: -0.16s;
        }
        
        @keyframes typing {
            0%, 80%, 100% {
                transform: scale(0);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Scrollable chat container styles */
        #chatContainer {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
        }
        
        #chatContainer::-webkit-scrollbar {
            width: 8px;
        }
        
        #chatContainer::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 4px;
        }
        
        #chatContainer::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 4px;
        }
        
        #chatContainer::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
        
        /* Messenger-style chat bubble animations and effects */
        .chat-bubble {
            transition: all 0.2s ease-in-out;
            animation: messageSlideIn 0.3s ease-out;
        }
        
        .chat-bubble p {
            text-align: left !important;
            margin: 0;
        }
        
        .chat-bubble {
            text-align: left !important;
        }
        
        .chat-bubble:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Message bubble tail effects */
        .bubble-sent::before {
            content: '';
            position: absolute;
            bottom: 0;
            right: -6px;
            width: 0;
            height: 0;
            border-left: 6px solid #3b82f6;
            border-bottom: 6px solid transparent;
        }
        
        .bubble-received::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: -6px;
            width: 0;
            height: 0;
            border-right: 6px solid #f0f9ff;
            border-bottom: 6px solid transparent;
        }
        
        .bubble-staff::before {
            border-right-color: #dcfce7 !important;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    
    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-600 to-blue-800 shadow-xl">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Top Navigation Bar -->
            <div class="flex justify-between items-start py-4">
                <div class="flex flex-col">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-headset text-white text-2xl mr-3"></i>
                        <h1 class="text-xl font-bold text-white">IT Help Desk</h1>
                    </div>
                    
                    <!-- Breadcrumb Navigation -->
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="dashboard.php" class="inline-flex items-center text-blue-100 hover:text-white transition-colors text-sm">
                                    <i class="fas fa-home w-4 h-4 mr-2"></i>
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <i class="fas fa-chevron-right text-blue-200 mx-2 text-xs"></i>
                                    <span class="text-white font-medium text-sm">
                                        <i class="fas fa-ticket-alt mr-2"></i>
                                        <?= $ticket ? "Ticket #{$ticketId}" : "View Ticket" ?>
                                    </span>
                                </div>
                            </li>
                            <?php if ($ticket): ?>
                            <li>
                                <div class="flex items-center">
                                    <i class="fas fa-chevron-right text-blue-200 mx-2 text-xs"></i>
                                    <span class="text-blue-100 text-sm truncate max-w-xs">
                                        <?= htmlspecialchars($ticket['subject']) ?>
                                    </span>
                                </div>
                            </li>
                            <?php endif; ?>
                        </ol>
                    </nav>
                </div>
                
                <div class="flex items-center space-x-3">
                    <!-- Notification Bell -->
                    <div class="relative notification-container">
                        <button id="notificationBell" class="relative bg-white bg-opacity-10 text-white p-2.5 rounded-lg hover:bg-opacity-20 transition-all border border-white border-opacity-20">
                            <i class="fas fa-bell text-base"></i>
                            <span id="notificationBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center font-bold hidden">0</span>
                        </button>
                        
                        <!-- Notification Dropdown -->
                        <div id="notificationDropdown" class="absolute right-0 top-full mt-2 w-80 sm:w-96 md:w-80 bg-white rounded-xl shadow-2xl border border-gray-200 z-[9999] hidden max-w-[calc(100vw-2rem)] sm:max-w-none">
                            <!-- Header -->
                            <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100 rounded-t-xl">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-bold text-gray-900">Notifications</h3>
                                    <div class="flex items-center space-x-2">
                                        <button id="markAllRead" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Mark all read</button>
                                        <button id="clearAll" class="text-xs text-red-600 hover:text-red-800 font-medium">Clear all</button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Notifications List -->
                            <div id="notificationsList" class="max-h-96 overflow-y-auto">
                                <!-- Loading -->
                                <div id="notificationsLoading" class="text-center py-8">
                                    <i class="fas fa-spinner fa-spin text-gray-400 text-2xl mb-2"></i>
                                    <p class="text-sm text-gray-500">Loading notifications...</p>
                                </div>
                                
                                <!-- Empty state -->
                                <div id="notificationsEmpty" class="text-center py-8 hidden">
                                    <i class="fas fa-bell-slash text-gray-400 text-2xl mb-2"></i>
                                    <p class="text-sm text-gray-500">No notifications yet</p>
                                </div>
                            </div>
                            
                            <!-- Footer -->
                            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                                <a href="dashboard.php" class="block text-center text-sm text-blue-600 hover:text-blue-800 font-medium">View all notifications</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Profile Dropdown -->
                    <div class="relative">
                        <button id="userProfileBtn" class="flex items-center space-x-2 bg-white bg-opacity-10 text-white px-3 py-2 rounded-lg hover:bg-opacity-20 transition-all border border-white border-opacity-20">
                            <i class="fas fa-user-circle text-lg"></i>
                            <div class="text-left hidden sm:block">
                                <div class="text-xs text-blue-200"><?= $userType === 'it_staff' ? 'IT Staff' : 'Employee' ?></div>
                                <div class="text-sm font-medium"><?= htmlspecialchars($_SESSION['user_data']['name'] ?? $_SESSION['username'] ?? 'User') ?></div>
                            </div>
                            <i class="fas fa-chevron-down text-xs ml-1"></i>
                        </button>
                        
                        <!-- User Dropdown Menu -->
                        <div id="userDropdown" class="absolute right-0 top-full mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50 hidden">
                            <?php if ($userType === 'it_staff'): ?>
                            <a href="ticket_history.php?id=<?= $ticketId ?>" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors border-b border-gray-100">
                                <i class="fas fa-history text-gray-400 mr-3 w-4"></i>
                                Ticket History
                            </a>
                            <?php endif; ?>
                            <a href="dashboard.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors border-b border-gray-100">
                                <i class="fas fa-tachometer-alt text-gray-400 mr-3 w-4"></i>
                                Dashboard
                            </a>
                            <a href="logout.php" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors rounded-b-lg">
                                <i class="fas fa-sign-out-alt text-red-500 mr-3 w-4"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 py-8">
        
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php if (!$ticket): ?>
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Ticket Not Accessible</h3>
                    <p class="text-gray-600 mb-4">The ticket you're trying to access either doesn't exist or you don't have permission to view it.</p>
                    <a href="dashboard.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($ticket): ?>
            
            <!-- Admin Controls (IT Staff Only) -->
            <?php if ($userType === 'it_staff'): ?>
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-tools text-blue-600 mr-2"></i>
                        Administrative Controls
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Manage ticket status and assignment</p>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Status Control -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Ticket Status</label>
                            <form method="POST">
                                <select name="status" onchange="this.form.submit()" 
                                        class="w-full bg-white border border-gray-300 text-gray-900 px-4 py-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm cursor-pointer">
                                    <option value="open" <?= $ticket['status'] == 'open' ? 'selected' : '' ?>>🔵 Open</option>
                                    <option value="in_progress" <?= $ticket['status'] == 'in_progress' ? 'selected' : '' ?>>🟡 In Progress</option>
                                    <option value="resolved" <?= $ticket['status'] == 'resolved' ? 'selected' : '' ?>>🟢 Resolved</option>
                                    <option value="closed" <?= $ticket['status'] == 'closed' ? 'selected' : '' ?>>🔴 Closed</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </div>
                        
                        <!-- Assignment Control -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Assigned To</label>
                            <form method="POST">
                                <select name="assigned_to" onchange="this.form.submit()" 
                                        class="w-full bg-white border border-gray-300 text-gray-900 px-4 py-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm cursor-pointer">
                                    <option value="">👤 Unassigned</option>
                                    <?php foreach ($itStaff as $staff): ?>
                                        <option value="<?= $staff['staff_id'] ?>" <?= $ticket['assigned_to'] == $staff['staff_id'] ? 'selected' : '' ?>>
                                            <?= $ticket['assigned_to'] == $staff['staff_id'] ? '✅ ' : '👤 ' ?><?= htmlspecialchars($staff['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="assign_ticket" value="1">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Employee Ticket Status (Employees Only) -->
            <?php if ($userType === 'employee'): ?>
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Ticket Information
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Current status and assignment details</p>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Status Display -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Current Status</label>
                            <div class="bg-gray-50 border border-gray-200 px-4 py-2.5 rounded-lg flex items-center">
                                <?php
                                switch($ticket['status']) {
                                    case 'open': echo '<div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div><span class="text-sm font-medium text-gray-900">Open</span>'; break;
                                    case 'in_progress': echo '<div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div><span class="text-sm font-medium text-gray-900">In Progress</span>'; break;
                                    case 'resolved': echo '<div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div><span class="text-sm font-medium text-gray-900">Resolved</span>'; break;
                                    case 'closed': echo '<div class="w-3 h-3 bg-red-500 rounded-full mr-3"></div><span class="text-sm font-medium text-gray-900">Closed</span>'; break;
                                    default: echo '<div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div><span class="text-sm font-medium text-gray-900">' . ucfirst($ticket['status']) . '</span>'; break;
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Assignment Display -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Assigned To</label>
                            <div class="bg-gray-50 border border-gray-200 px-4 py-2.5 rounded-lg flex items-center">
                                <?php if ($ticket['assigned_to']): ?>
                                    <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                    <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($ticket['assigned_staff_name'] ?? 'IT Staff') ?></span>
                                <?php else: ?>
                                    <div class="w-3 h-3 bg-gray-400 rounded-full mr-3"></div>
                                    <span class="text-sm font-medium text-gray-500">Unassigned</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Main Content Layout -->
            <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
                <!-- Main Content Area -->
                <div class="xl:col-span-3 space-y-6">
                    
                    <!-- Ticket Header -->
                    <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
                    <div class="flex-1">
                        <div class="flex items-center mb-3">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center mr-4">
                                <i class="fas fa-ticket-alt text-xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">
                                    Ticket #<?= $ticket['ticket_id'] ?>
                                </h1>
                                <p class="text-gray-600 text-sm mt-1">
                                    Created <?= date('M j, Y \a\t g:i A', strtotime($ticket['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                        
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">
                            <?= htmlspecialchars($ticket['subject']) ?>
                        </h2>
                    </div>
                    
                    <!-- Status and Priority Badges -->
                    <div class="flex flex-wrap gap-3 lg:flex-col lg:items-end">
                        <div class="flex items-center">
                            <span class="status-<?= $ticket['status'] ?> px-4 py-2 rounded-full text-sm font-semibold border">
                                <i class="fas fa-circle mr-2 text-xs"></i>
                                <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                            </span>
                        </div>
                        <div class="flex items-center">
                            <span class="priority-<?= $ticket['priority'] ?> px-4 py-2 rounded-full text-sm font-semibold border">
                                <i class="fas fa-flag mr-2"></i>
                                <?= ucfirst($ticket['priority']) ?> Priority
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Enhanced Information Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
                    <!-- Requester Card -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-5 rounded-xl border border-blue-200 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl w-12 h-12 flex items-center justify-center mr-3 shadow-sm">
                                <i class="fas fa-user text-lg"></i>
                            </div>
                            <h3 class="font-bold text-blue-900 text-lg">Requester</h3>
                        </div>
                        <div class="space-y-3 text-sm text-blue-800">
                            <div class="flex items-center">
                                <i class="fas fa-id-card w-4 mr-3 text-blue-600"></i>
                                <span class="font-semibold truncate"><?= htmlspecialchars($ticket['employee_name'] ?? 'N/A') ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-at w-4 mr-3 text-blue-600"></i>
                                <span class="truncate"><?= htmlspecialchars($ticket['employee_username'] ?? 'N/A') ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope w-4 mr-3 text-blue-600"></i>
                                <span class="text-xs truncate"><?= htmlspecialchars($ticket['employee_email'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category & Priority Card -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-5 rounded-xl border border-purple-200 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl w-12 h-12 flex items-center justify-center mr-3 shadow-sm">
                                <i class="fas fa-tag text-lg"></i>
                            </div>
                            <h3 class="font-bold text-purple-900 text-lg">Category</h3>
                        </div>
                        <div class="space-y-3">
                            <div class="bg-white bg-opacity-70 backdrop-blur-sm px-4 py-3 rounded-lg shadow-sm border border-purple-100">
                                <div class="text-xs text-purple-600 font-medium uppercase tracking-wide mb-1">Type</div>
                                <div class="text-sm font-bold text-purple-900"><?= htmlspecialchars($ticket['category'] ?? 'General') ?></div>
                            </div>
                            <div class="bg-white bg-opacity-70 backdrop-blur-sm px-4 py-3 rounded-lg shadow-sm border border-purple-100">
                                <div class="text-xs text-purple-600 font-medium uppercase tracking-wide mb-1">Priority</div>
                                <div class="text-sm font-bold text-purple-900 capitalize"><?= htmlspecialchars($ticket['priority'] ?? 'Medium') ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Assignment Card -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 p-5 rounded-xl border border-green-200 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl w-12 h-12 flex items-center justify-center mr-3 shadow-sm">
                                <i class="fas fa-user-cog text-lg"></i>
                            </div>
                            <h3 class="font-bold text-green-900 text-lg">Assignment</h3>
                        </div>
                        <div class="space-y-3">
                            <?php if ($ticket['assigned_staff_name']): ?>
                                <div class="bg-white bg-opacity-70 backdrop-blur-sm px-4 py-3 rounded-lg shadow-sm border border-green-100">
                                    <div class="text-xs text-green-600 font-medium uppercase tracking-wide mb-1">Assigned To</div>
                                    <div class="text-sm font-bold text-green-900 flex items-center">
                                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                                        <?= htmlspecialchars($ticket['assigned_staff_name']) ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="bg-gradient-to-r from-orange-50 to-orange-100 px-4 py-3 rounded-lg border border-orange-200 shadow-sm">
                                    <div class="text-sm font-bold text-orange-700 flex items-center">
                                        <div class="w-2 h-2 bg-orange-500 rounded-full mr-2 animate-pulse"></div>
                                        Awaiting Assignment
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Activity Card -->
                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 p-5 rounded-xl border border-slate-200 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="bg-gradient-to-br from-slate-500 to-slate-600 text-white rounded-xl w-12 h-12 flex items-center justify-center mr-3 shadow-sm">
                                <i class="fas fa-chart-line text-lg"></i>
                            </div>
                            <h3 class="font-bold text-slate-900 text-lg">Activity</h3>
                        </div>
                        <div class="space-y-4 text-sm">
                            <div class="bg-white bg-opacity-70 backdrop-blur-sm px-4 py-3 rounded-lg shadow-sm border border-slate-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600 font-medium">Responses</span>
                                    <div class="flex items-center">
                                        <div class="bg-blue-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?= count($responses) ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white bg-opacity-70 backdrop-blur-sm px-4 py-3 rounded-lg shadow-sm border border-slate-100">
                                <div class="text-xs text-slate-600 font-medium uppercase tracking-wide mb-1">Last Update</div>
                                <div class="font-bold text-slate-700 text-xs"><?= date('M j, g:i A', strtotime($ticket['updated_at'])) ?></div>
                            </div>
                            <div class="pt-1">
                                <a href="ticket_history.php?id=<?= $ticketId ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-xs font-semibold hover:bg-blue-50 px-3 py-2 rounded-lg transition-colors">
                                    <i class="fas fa-history mr-2"></i>
                                    View History
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Description Section -->
                <div class="border-t border-gray-200 pt-8">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg w-8 h-8 flex items-center justify-center mr-3">
                                <i class="fas fa-file-text text-sm"></i>
                            </div>
                            Issue Description
                        </h3>
                        <p class="text-gray-600 text-sm">Detailed information about the reported issue</p>
                    </div>
                    <div class="bg-gradient-to-br from-gray-50 to-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="prose max-w-none">
                            <p class="text-gray-800 whitespace-pre-wrap leading-relaxed text-base"><?= htmlspecialchars($ticket['description']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Management Actions (IT Staff Only) -->
            <?php if ($userType === 'it_staff'): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 card-hover border border-gray-100">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg w-8 h-8 flex items-center justify-center mr-3">
                            <i class="fas fa-tools text-sm"></i>
                        </div>
                        Management Tools
                    </h3>
                    <p class="text-gray-600 text-sm">Update ticket status, assignment, and perform quick actions</p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Status Update -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl border border-blue-200 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center mb-4">
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg w-10 h-10 flex items-center justify-center mr-3 shadow-sm">
                                <i class="fas fa-edit text-sm"></i>
                            </div>
                            <h4 class="font-bold text-blue-900 text-lg">Status</h4>
                        </div>
                        <form method="POST" class="space-y-4">
                            <div class="relative">
                                <select name="status" class="w-full p-3 pr-10 border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-800 font-medium">
                                    <option value="open" <?= $ticket['status'] == 'open' ? 'selected' : '' ?>>🔴 Open</option>
                                    <option value="in_progress" <?= $ticket['status'] == 'in_progress' ? 'selected' : '' ?>>🔵 In Progress</option>
                                    <option value="resolved" <?= $ticket['status'] == 'resolved' ? 'selected' : '' ?>>🟢 Resolved</option>
                                    <option value="closed" <?= $ticket['status'] == 'closed' ? 'selected' : '' ?>>⚫ Closed</option>
                                </select>
                                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-blue-400"></i>
                                </div>
                            </div>
                            <button type="submit" name="update_status" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all font-semibold shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                                <i class="fas fa-save mr-2"></i>Update Status
                            </button>
                        </form>
                    </div>
                    
                    <!-- Assign Ticket -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl border border-green-200 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center mb-4">
                            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg w-10 h-10 flex items-center justify-center mr-3 shadow-sm">
                                <i class="fas fa-user-plus text-sm"></i>
                            </div>
                            <h4 class="font-bold text-green-900 text-lg">Assignment</h4>
                        </div>
                        <form method="POST" class="space-y-4">
                            <div class="relative">
                                <select name="assigned_to" class="w-full p-3 pr-10 border-2 border-green-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-800 font-medium">
                                    <option value="">👤 Unassigned</option>
                                    <?php foreach ($itStaff as $staff): ?>
                                        <option value="<?= $staff['staff_id'] ?>" <?= $ticket['assigned_to'] == $staff['staff_id'] ? 'selected' : '' ?>>
                                                👨‍💻 <?= htmlspecialchars($staff['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-green-400"></i>
                                </div>
                            </div>
                            <button type="submit" name="assign_ticket" class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-3 rounded-lg hover:from-green-700 hover:to-green-800 transition-all font-semibold shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                                <i class="fas fa-user-check mr-2"></i>Assign Ticket
                            </button>
                        </form>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl border border-purple-200 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center mb-4">
                            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg w-10 h-10 flex items-center justify-center mr-3 shadow-sm">
                                <i class="fas fa-bolt text-sm"></i>
                            </div>
                            <h4 class="font-bold text-purple-900 text-lg">Quick Actions</h4>
                        </div>
                        <div class="space-y-3">
                            <?php if ($ticket['status'] === 'closed' || $ticket['status'] === 'resolved'): ?>
                                <div class="w-full bg-gradient-to-r from-gray-400 to-gray-500 text-white py-3 px-4 rounded-lg font-semibold text-center cursor-not-allowed shadow-sm">
                                    <i class="fas fa-lock mr-2"></i>Ticket Closed
                                </div>
                            <?php else: ?>
                                <button onclick="document.getElementById('messengerForm').scrollIntoView({behavior: 'smooth'}); document.getElementById('response_text').focus();" 
                                        class="w-full bg-gradient-to-r from-purple-600 to-purple-700 text-white py-3 px-4 rounded-lg hover:from-purple-700 hover:to-purple-800 transition-all font-semibold shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                                    <i class="fas fa-reply mr-2"></i>Reply Now
                                </button>
                            <?php endif; ?>
                            <a href="ticket_history.php?id=<?= $ticketId ?>" 
                               class="block w-full bg-gradient-to-r from-indigo-600 to-indigo-700 text-white py-3 px-4 rounded-lg hover:from-indigo-700 hover:to-indigo-800 transition-all font-semibold text-center shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                                <i class="fas fa-history mr-2"></i>View History
                            </a>
                            <button onclick="window.print()" 
                                   class="w-full bg-gradient-to-r from-gray-600 to-gray-700 text-white py-3 px-4 rounded-lg hover:from-gray-700 hover:to-gray-800 transition-all font-semibold shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                                <i class="fas fa-print mr-2"></i>Print Ticket
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Integrated Chat Interface -->
            <div class="bg-white rounded-xl shadow-lg card-hover overflow-hidden">
                <!-- Chat Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-comments mr-3"></i>
                        Conversation
                        <span class="ml-3 bg-blue-500 bg-opacity-50 px-3 py-1 rounded-full text-sm font-medium"><?= count($responses) ?></span>
                        <div class="ml-auto flex items-center space-x-2">
                            <span class="text-blue-200 text-sm">Ticket #<?= $ticketId ?></span>
                        </div>
                    </h3>
                </div>
                
                <!-- Chat Messages Area -->
                <?php if (empty($responses)): ?>
                    <div class="text-center py-16 px-6">
                        <div class="bg-gray-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-comment-slash text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-gray-500 text-lg font-medium">No messages yet</p>
                        <p class="text-gray-400 text-sm mt-1">Start the conversation by sending a message below</p>
                    </div>
                <?php else: ?>
                    <!-- Messenger-Style Chat Container -->
                    <div id="chatContainer" class="h-96 overflow-y-auto p-4 bg-gray-50 space-y-3">
                        <?php foreach ($responses as $index => $response): ?>
                            <?php 
                            $isStaff = $response['user_type'] === 'it_staff';
                            $currentUserIsStaff = $_SESSION['user_type'] === 'it_staff';
                            
                            // Simple logic: 
                            // If current user is staff and message is from staff = RIGHT (blue)
                            // If current user is staff and message is from employee = LEFT (green)
                            // If current user is employee and message is from employee = RIGHT (blue)  
                            // If current user is employee and message is from staff = LEFT (green)
                            
                            $isMyMessage = ($currentUserIsStaff && $isStaff) || (!$currentUserIsStaff && !$isStaff);
                            $alignRight = $isMyMessage;
                            
                            // Determine colors: My messages = blue (right), Other messages = green (left)
                            if ($alignRight) {
                                $bubbleClasses = 'bg-blue-500 text-white rounded-l-2xl rounded-tr-2xl bubble-sent';
                            } else {
                                $bubbleClasses = 'bg-green-100 border border-green-200 rounded-r-2xl rounded-tl-2xl text-gray-800 bubble-staff';
                            }
                            ?>
                            
                            <!-- Chat Bubble -->
                            <div class="flex <?= $alignRight ? 'justify-end' : 'justify-start' ?> mb-4">
                                <div class="max-w-xs">
                                    <div class="chat-bubble relative <?= $bubbleClasses ?> px-4 py-3 shadow-sm">
                                        <p class="text-sm leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($response['message']) ?></p>
                                        <div class="flex justify-between items-center mt-2">
                                            <span class="text-xs opacity-75"><?= date('g:i:s A', strtotime($response['created_at'])) ?></span>
                                            <?php if ($alignRight): // Only show for my messages ?>
                                                <div class="flex items-center space-x-1">
                                                    <i class="fas fa-check text-xs opacity-60" title="Sent"></i>
                                                    <?php if ($response['is_seen']): ?>
                                                        <i class="fas fa-check-double text-xs text-blue-400" title="Seen by <?= $isStaff ? 'Employee' : 'IT Staff' ?>"></i>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Show closed indicator at end of chat if ticket is closed -->
                        <?php if ($ticket['status'] === 'closed' || $ticket['status'] === 'resolved'): ?>
                            <div class="text-center py-4 px-6">
                                <div class="bg-red-50 border-2 border-red-200 rounded-xl p-4 mx-4">
                                    <div class="bg-red-100 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                                        <i class="fas fa-lock text-red-500 text-lg"></i>
                                    </div>
                                    <p class="text-red-600 font-bold text-sm">🔒 Conversation Closed</p>
                                    <p class="text-gray-500 text-xs mt-1">This ticket has been closed. No new messages can be added.</p>
                                    <p class="text-gray-400 text-xs mt-1">To reopen, change the ticket status above.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Typing Indicator -->
                <div id="typingIndicator" class="hidden mx-4 mb-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center">
                        <div class="typing-dots mr-3">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <span class="text-sm text-blue-700 font-medium">Someone is typing...</span>
                    </div>
                </div>
                
                <!-- Integrated Message Input -->
                <div class="border-t border-gray-200 bg-white p-4">
                    <form id="messengerForm" class="flex items-end space-x-3">
                        <!-- Removed method="post" and hidden fields - using AJAX only -->
                        <div class="flex-1">
                            <textarea name="response_text" id="response_text" rows="2" 
                                      class="w-full p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none" 
                                      placeholder="Type your message..."
                                      required></textarea>
                        </div>
                        
                        <?php if ($userType === 'it_staff'): ?>
                        <div class="flex items-center">
                            <label class="inline-flex items-center text-sm">
                                <input type="checkbox" name="is_internal" class="mr-2 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="text-gray-700">Internal</span>
                            </label>
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" id="messengerSendBtn" class="bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition-colors font-medium flex items-center">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Send
                        </button>
                    </form>
                    
                    <div class="mt-2 text-xs text-gray-500 text-center">
                        Press Ctrl+Enter to send â€¢ Include details to help resolve the issue
                    </div>
                </div>
            </div>
            
            </div>
            
            <!-- Sidebar -->
            <div class="xl:col-span-1 space-y-4">
                
                <!-- Device Notifications -->
                <div class="bg-white rounded-xl shadow-lg p-4 card-hover">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-bell text-blue-600 mr-2"></i>
                        Notifications
                        <span class="ml-2 bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">Beta</span>
                    </h3>
                    
                    <!-- Browser Notifications -->
                    <div class="mb-4">
                        <h4 class="font-semibold text-gray-900 mb-2 flex items-center text-sm">
                            <i class="fas fa-desktop mr-2 text-blue-600"></i>
                            Browser Alerts
                        </h4>
                        <p class="text-xs text-gray-600 mb-3">Get real-time notifications for updates</p>
                        <button id="enableNotifications" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium w-full">
                            <i class="fas fa-bell mr-1"></i>Enable Notifications
                        </button>
                    </div>
                    
                    <!-- Email Notifications -->
                    <div class="border border-gray-200 rounded-lg p-3">
                        <h4 class="font-semibold text-gray-900 mb-2 flex items-center text-sm">
                            <i class="fas fa-envelope mr-2 text-green-600"></i>
                            Email Updates
                        </h4>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" id="emailNotif" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                                <label for="emailNotif" class="text-xs text-gray-700">Email enabled</label>
                            </div>
                            <span class="text-xs text-green-600 font-medium">Active</span>
                        </div>
                    </div>
                    
                    <!-- Notification Status -->
                    <div id="notificationStatus" class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200 hidden">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                <span class="text-xs text-gray-700 font-medium">Notifications enabled</span>
                            </div>

                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-lg p-4 card-hover">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-bolt text-yellow-600 mr-2"></i>
                        Quick Actions
                    </h3>
                    
                    <?php if ($userType === 'it_staff'): ?>
                        <div class="space-y-2">
                            <button class="w-full text-left px-3 py-2 text-sm bg-green-50 hover:bg-green-100 rounded-lg transition-colors border border-green-200">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                Mark Resolved
                            </button>
                            <button class="w-full text-left px-3 py-2 text-sm bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200">
                                <i class="fas fa-user-plus text-blue-600 mr-2"></i>
                                Assign Ticket
                            </button>
                            <button class="w-full text-left px-3 py-2 text-sm bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors border border-orange-200">
                                <i class="fas fa-flag text-orange-600 mr-2"></i>
                                Change Priority
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle text-blue-600 text-2xl mb-2"></i>
                            <p class="text-sm text-gray-600">Additional options available to IT staff</p>
                        </div>
                    <?php endif; ?>
                </div>
            
            </div>
            
        </div>
            
            <!-- Add Response Form (Hidden - Using Integrated Form Instead) -->
            <?php if ($ticket['status'] === 'closed' || $ticket['status'] === 'resolved'): ?>
                <div id="responseForm" class="hidden bg-gray-100 rounded-xl shadow-lg p-6 card-hover border-2 border-red-200">
                    <div class="text-center py-8">
                        <div class="bg-red-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-lock text-red-500 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-red-600 mb-2">🔒 Ticket Closed</h3>
                        <p class="text-gray-600 text-sm">This conversation has been closed and cannot accept new messages.</p>
                        <p class="text-gray-500 text-xs mt-2">To add a response, first reopen the ticket by changing its status to "Open" or "In Progress".</p>
                    </div>
                </div>
            <?php else: ?>
                <div id="responseForm" class="hidden bg-white rounded-xl shadow-lg p-6 card-hover">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-pen text-blue-600 mr-3"></i>
                        Add Your Response
                    </h3>
                
                <!-- Typing Indicator -->
                <div id="typingIndicator" class="hidden mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center">
                        <div class="typing-dots mr-3">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <span class="text-sm text-blue-700 font-medium">Someone is typing...</span>
                    </div>
                </div>
                
                <form id="ajaxResponseForm" class="space-y-6">
                    <div>
                        <label for="response_text" class="block text-sm font-semibold text-gray-700 mb-2">
                            Your Message
                        </label>
                        <textarea name="response_text" id="response_text" rows="5" 
                                  class="w-full p-4 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                  placeholder="Type your response here... Be detailed and clear to help resolve the issue quickly." 
                                  required></textarea>
                        <p class="text-xs text-gray-500 mt-2">Tip: Include any error messages, steps you've tried, or additional context</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
                        <?php if ($userType === 'it_staff'): ?>
                            <label class="flex items-center bg-orange-50 p-3 rounded-lg border border-orange-200">
                                <input type="checkbox" name="is_internal" id="is_internal" class="mr-3 w-4 h-4 text-orange-600 focus:ring-orange-500 border-orange-300 rounded">
                                <div>
                                    <span class="text-sm font-medium text-orange-900">Internal Note</span>
                                    <div class="text-xs text-orange-700">Only visible to IT staff members</div>
                                </div>
                                <i class="fas fa-lock text-orange-600 ml-2"></i>
                            </label>
                        <?php else: ?>
                            <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                    <span class="text-sm text-blue-800 font-medium">Your response will be visible to IT support staff</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex space-x-3">
                            <button type="button" id="clearBtn" 
                                    class="px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                                <i class="fas fa-undo mr-2"></i>Clear
                            </button>
                            <button type="submit" id="sendBtn" 
                                    class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-3 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-medium shadow-lg">
                                <i class="fas fa-paper-plane mr-2"></i>Send Response
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Status Messages -->
                <div id="responseStatus" class="hidden mt-4"></div>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Ticket Not Found</h2>
                <p class="text-gray-600 mb-4">The requested ticket could not be found.</p>
                <a href="dashboard.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Return to Dashboard
                </a>
            </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Global Variables for Firebase Chat System -->
    <script>
        // Firebase Chat Configuration
        window.TICKET_ID = <?= $ticketId ?>;
        window.INITIAL_RESPONSE_COUNT = <?= count($responses) ?>;
        window.CURRENT_USER_TYPE = '<?= $_SESSION['user_type'] ?>';
        window.CURRENT_USER_NAME = '<?= htmlspecialchars(getUserName(), ENT_QUOTES) ?>';
        window.USER_ID = <?= $_SESSION['user_id'] ?>;
        window.SESSION_RESPONSE_ADDED = <?= isset($_SESSION["response_added_ticket_{$ticketId}"]) ? 'true' : 'false' ?>;
        
        <?php if (isset($_SESSION["response_added_ticket_{$ticketId}"])): ?>
        <?php unset($_SESSION["response_added_ticket_{$ticketId}"]); ?>
        <?php endif; ?>
        
        console.log('🔧 Global variables loaded:', {
            ticketId: window.TICKET_ID,
            userType: window.CURRENT_USER_TYPE,
            userName: window.CURRENT_USER_NAME,
            responseCount: window.INITIAL_RESPONSE_COUNT
        });
        
        // Toolbar helper functions
        function scrollToChat() {
            const responseForm = document.getElementById('response-form');
            if (responseForm) {
                responseForm.scrollIntoView({ behavior: 'smooth' });
                const textarea = responseForm.querySelector('textarea');
                if (textarea) {
                    setTimeout(() => textarea.focus(), 500);
                }
            }
        }
        
        function refreshTicket() {
            // Show loading indicator
            const refreshBtn = event.target.closest('button');
            const originalContent = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span class="hidden sm:inline">Refreshing...</span>';
            refreshBtn.disabled = true;
            
            // Reload the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    </script>
    
    <!-- Firebase Real-Time Chat System -->
    <script src="assets/js/firebase-config.js" type="module"></script>
    <script src="assets/js/firebase-chat.js" type="module"></script>
    <script src="assets/js/firebase-notifications.js" type="module"></script>
    <script src="assets/js/enhanced-chat-system.js" type="module"></script>
    
    <!-- Firebase Initialization -->
    <script type="module">
        console.log('🚀 Initializing Firebase Real-Time Chat...');
        
        // Wait for DOM and Firebase to be ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📱 DOM loaded, initializing chat system...');
            
            setTimeout(() => {
                console.log('🔍 Checking enhanced chat system...', {
                    enhancedChatSystem: !!window.enhancedChatSystem,
                    firebaseChat: window.enhancedChatSystem ? !!window.enhancedChatSystem.firebaseChat : 'N/A',
                    isInitialized: window.enhancedChatSystem?.firebaseChat?.isInitialized || 'N/A'
                });
                
                if (window.enhancedChatSystem) {
                    console.log('✅ Firebase Real-Time Chat is active!');
                    
                    // Show Firebase status
                    const statusDiv = document.createElement('div');
                    statusDiv.innerHTML = '🔥 <span class="text-green-600 text-xs font-medium">Real-time messaging active</span>';
                    statusDiv.className = 'fixed bottom-4 left-4 bg-white border border-green-200 px-3 py-2 rounded-lg shadow-sm z-40';
                    statusDiv.id = 'firebaseStatusIndicator';
                    document.body.appendChild(statusDiv);
                    

                    
                    // Auto-hide status after 5 seconds
                    setTimeout(() => {
                        if (statusDiv.parentNode) {
                            statusDiv.style.opacity = '0';
                            statusDiv.style.transform = 'translateY(10px)';
                            setTimeout(() => statusDiv.remove(), 300);
                        }

                    }, 10000);
                    
                } else {
                    console.warn('⚠️ Enhanced chat system not loaded, using fallback');
                    
                    // Show error status
                    const errorDiv = document.createElement('div');
                    errorDiv.innerHTML = '⚠️ <span class="text-red-600 text-xs font-medium">Chat system not loaded</span>';
                    errorDiv.className = 'fixed bottom-4 left-4 bg-white border border-red-200 px-3 py-2 rounded-lg shadow-sm z-40';
                    document.body.appendChild(errorDiv);
                }
            }, 2000);
        });
        
        // Handle page visibility for connection management
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                console.log('📱 Page hidden - maintaining Firebase connection');
            } else {
                console.log('👁️ Page visible - Firebase connection active');
            }
        });
        

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('messengerForm');
            const textarea = document.getElementById('response_text');
            const sendBtn = document.getElementById('messengerSendBtn');
            const chatContainer = document.getElementById('chatContainer');
            
            console.log('🔧 Form elements found:', {
                form: !!form,
                textarea: !!textarea,
                sendBtn: !!sendBtn,
                chatContainer: !!chatContainer
            });
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Prevent normal form submission immediately
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const message = textarea?.value?.trim();
                    const isInternal = document.querySelector('input[name="is_internal"]:checked') ? '1' : '0';
                    
                    if (!message) {
                        alert('Please enter a message');
                        return;
                    }
                    
                    console.log('📝 Sending message via AJAX:', {
                        message: message,
                        isInternal: isInternal,
                        ticketId: <?= $ticketId ?>
                    });
                    
                    // Disable send button and show loading
                    sendBtn.disabled = true;
                    const originalText = sendBtn.innerHTML;
                    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
                    
                    // Send via AJAX
                    const formData = new FormData();
                    formData.append('ticket_id', '<?= $ticketId ?>');
                    formData.append('message', message);
                    formData.append('is_internal', isInternal);
                    formData.append('ajax_request', '1'); // Prevent PHP form handler from processing
                    
                    fetch('api/add_response.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('✅ Response added:', data);
                        
                        if (data.success) {
                            console.log('📝 Message sent successfully:', data);
                            console.log('🕒 Server timestamp:', data.timestamp);
                            
                            // Clear the textarea
                            textarea.value = '';
                            
                            // Show success message
                            showNotification('Message sent successfully!', 'success');
                            
                            // Refresh chat messages to show the new message with proper server timestamps
                            console.log('🔄 Triggering chat refresh after message send');
                            refreshChatMessages();
                            
                        } else {
                            showNotification('Error: ' + (data.message || 'Failed to send message'), 'error');
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error sending message:', error);
                        showNotification('Error: Failed to send message', 'error');
                    })
                    .finally(() => {
                        // Re-enable send button
                        sendBtn.disabled = false;
                        sendBtn.innerHTML = originalText;
                    });
                });
            }
            
            // Add Ctrl+Enter support for quick sending
            if (textarea) {
                textarea.addEventListener('keydown', function(e) {
                    if (e.ctrlKey && e.key === 'Enter') {
                        e.preventDefault();
                        form.dispatchEvent(new Event('submit'));
                    }
                });
            }
            
            // Function to add message to chat dynamically
            function addMessageToChat(messageText, isInternal, timestamp, responseId = null) {
                if (!chatContainer) return;
                
                // Remove "No messages yet" placeholder if it exists
                const placeholder = chatContainer.querySelector('.text-center');
                if (placeholder && placeholder.textContent.includes('No messages yet')) {
                    placeholder.closest('.text-center').remove();
                }
                
                // Create message element (always align right for current user's messages)
                const messageDiv = document.createElement('div');
                messageDiv.className = 'flex justify-end mb-4';
                
                const messageContent = document.createElement('div');
                messageContent.className = 'max-w-xs';
                
                const bubbleDiv = document.createElement('div');
                bubbleDiv.className = 'chat-bubble relative bg-blue-500 text-white rounded-l-2xl rounded-tr-2xl bubble-sent px-4 py-3 shadow-sm';
                
                const messageP = document.createElement('p');
                messageP.className = 'text-sm leading-relaxed whitespace-pre-wrap';
                messageP.textContent = messageText;
                
                const metaDiv = document.createElement('div');
                metaDiv.className = 'flex justify-between items-center mt-2';
                
                const timeSpan = document.createElement('span');
                timeSpan.className = 'text-xs opacity-75';
                timeSpan.textContent = timestamp.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
                const statusDiv = document.createElement('div');
                statusDiv.className = 'flex items-center space-x-1';
                statusDiv.setAttribute('data-response-id', responseId || Date.now()); // Use response_id for tracking
                
                // Single check (sent) - will be updated to double check when seen
                const sentIcon = document.createElement('i');
                sentIcon.className = 'fas fa-check text-xs opacity-60';
                sentIcon.title = 'Sent';
                
                statusDiv.appendChild(sentIcon);
                
                // Add internal indicator if applicable
                if (isInternal) {
                    const internalSpan = document.createElement('span');
                    internalSpan.className = 'text-xs bg-orange-500 bg-opacity-20 text-orange-200 px-2 py-1 rounded-full ml-2';
                    internalSpan.innerHTML = '<i class="fas fa-lock mr-1"></i>Internal';
                    bubbleDiv.appendChild(internalSpan);
                }
                
                metaDiv.appendChild(timeSpan);
                metaDiv.appendChild(statusDiv);
                
                bubbleDiv.appendChild(messageP);
                bubbleDiv.appendChild(metaDiv);
                
                messageContent.appendChild(bubbleDiv);
                messageDiv.appendChild(messageContent);
                
                // Add to chat container in chronological order
                insertMessageInOrder(messageDiv, timestamp);
                
                // Start polling for seen status for this new message
                setTimeout(() => {
                    pollForSeenStatus();
                }, 2000); // Check after 2 seconds
            }
            
            // Function to insert message in chronological order
            function insertMessageInOrder(messageDiv, messageTimestamp) {
                if (!chatContainer) return;
                
                const existingMessages = chatContainer.querySelectorAll('.flex.justify-start, .flex.justify-end');
                let inserted = false;
                
                // Find the correct position based on timestamp
                for (let i = existingMessages.length - 1; i >= 0; i--) {
                    const existingMessage = existingMessages[i];
                    const timeElement = existingMessage.querySelector('.text-xs.opacity-75');
                    
                    if (timeElement) {
                        const existingTimeText = timeElement.textContent;
                        const existingTime = parseTimeString(existingTimeText);
                        
                        if (messageTimestamp >= existingTime) {
                            // Insert after this message
                            existingMessage.parentNode.insertBefore(messageDiv, existingMessage.nextSibling);
                            inserted = true;
                            break;
                        }
                    }
                }
                
                // If not inserted, append at the end
                if (!inserted) {
                    chatContainer.appendChild(messageDiv);
                }
            }
            
            // Helper function to parse time string back to Date for comparison
            function parseTimeString(timeString) {
                const today = new Date();
                const [time, period] = timeString.split(' ');
                const [hours, minutes] = time.split(':');
                
                let hour24 = parseInt(hours);
                if (period === 'PM' && hour24 !== 12) hour24 += 12;
                if (period === 'AM' && hour24 === 12) hour24 = 0;
                
                today.setHours(hour24, parseInt(minutes), 0, 0);
                return today;
            }
            
            // Function to poll for seen status updates
            function pollForSeenStatus() {
                fetch(`api/get_seen_status.php?ticket_id=<?= $ticketId ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.seen_messages) {
                        updateSeenIndicators(data.seen_messages);
                    }
                })
                .catch(error => {
                    console.log('Seen status check (silent):', error);
                });
            }
            
            // Function to update seen indicators to double check
            function updateSeenIndicators(seenMessages) {
                seenMessages.forEach(seenMessage => {
                    // Find the specific message by response_id
                    const statusDiv = document.querySelector(`[data-response-id="${seenMessage.response_id}"]`);
                    if (statusDiv) {
                        const singleCheck = statusDiv.querySelector('.fa-check:not(.fa-check-double)');
                        if (singleCheck && !statusDiv.querySelector('.fa-check-double')) {
                            // Replace single check with double check
                            singleCheck.className = 'fas fa-check-double text-xs text-blue-400';
                            singleCheck.title = `Seen by ${seenMessage.seen_by_name || 'recipient'}`;
                        }
                    }
                });
            }
            
            // Flag to prevent multiple simultaneous refreshes
            let isRefreshing = false;
            
            // Function to refresh chat messages with proper server timestamps
            function refreshChatMessages() {
                if (isRefreshing) {
                    console.log('⏳ Refresh already in progress, skipping...');
                    return;
                }
                
                isRefreshing = true;
                console.log('🔄 Refreshing chat messages...');
                
                fetch(`api/get_chat_messages.php?ticket_id=<?= $ticketId ?>`)
                .then(response => response.json())
                .then(data => {
                    console.log('📨 Chat refresh response:', data);
                    if (data.success && data.messages) {
                        console.log(`📝 Updating chat with ${data.messages.length} messages`);
                        updateChatContainer(data.messages);
                        
                        // Scroll to bottom to show new message
                        if (chatContainer) {
                            setTimeout(() => {
                                chatContainer.scrollTop = chatContainer.scrollHeight;
                            }, 100);
                        }
                    } else {
                        console.error('❌ Chat refresh failed:', data);
                    }
                })
                .finally(() => {
                    isRefreshing = false;
                    console.log('✅ Chat refresh completed');
                })
                .catch(error => {
                    console.error('❌ Error refreshing chat:', error);
                    showNotification('Error loading messages. Please refresh the page.', 'error');
                });
            }
            
            // Function to update chat container with server-provided messages
            function updateChatContainer(messages) {
                if (!chatContainer) {
                    console.error('❌ Chat container not found');
                    return;
                }
                
                console.log(`🔄 Updating chat container with ${messages.length} messages`);
                
                // Save current scroll position
                const wasAtBottom = chatContainer.scrollTop >= (chatContainer.scrollHeight - chatContainer.offsetHeight - 50);
                
                // Clear ALL existing content (both server-rendered and AJAX messages)
                chatContainer.innerHTML = '';
                console.log(`🗑️ Cleared entire chat container`);
                
                if (messages.length === 0) {
                    // Show no messages placeholder
                    chatContainer.innerHTML = `
                        <div class="text-center py-16 px-6">
                            <div class="bg-gray-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-comment-slash text-gray-400 text-2xl"></i>
                            </div>
                            <p class="text-gray-500 text-lg font-medium">No messages yet</p>
                            <p class="text-gray-400 text-sm mt-1">Start the conversation by sending a message below</p>
                        </div>
                    `;
                    return;
                }
                
                // Add each message with proper server styling
                messages.forEach((msg, index) => {
                    console.log(`📨 Message ${index + 1}:`, {
                        text: msg.message.substring(0, 20) + '...',
                        time: msg.formatted_time,
                        created_at: msg.created_at
                    });
                    const messageHtml = createMessageHtml(msg);
                    chatContainer.innerHTML += messageHtml;
                });
                
                // Restore scroll position
                if (wasAtBottom) {
                    setTimeout(() => {
                        chatContainer.scrollTop = chatContainer.scrollHeight;
                    }, 50);
                }
            }
            
            // Function to create message HTML (matches server-side styling)
            function createMessageHtml(msg) {
                const isMyMessage = (<?= json_encode($userType) ?> === 'it_staff' && msg.user_type === 'it_staff') || 
                                   (<?= json_encode($userType) ?> === 'employee' && msg.user_type === 'employee');
                
                const alignClass = isMyMessage ? 'justify-end' : 'justify-start';
                const bubbleClass = isMyMessage ? 
                    'bg-blue-500 text-white rounded-l-2xl rounded-tr-2xl bubble-sent' :
                    'bg-green-100 border border-green-200 rounded-r-2xl rounded-tl-2xl text-gray-800 bubble-staff';
                
                const seenIcon = msg.is_seen ? 
                    '<i class="fas fa-check-double text-xs text-blue-400" title="Seen"></i>' :
                    '<i class="fas fa-check text-xs opacity-60" title="Sent"></i>';
                
                return `
                    <div class="flex ${alignClass} mb-4">
                        <div class="max-w-xs">
                            <div class="chat-bubble relative ${bubbleClass} px-4 py-3 shadow-sm" data-response-id="${msg.response_id}">
                                <p class="text-sm leading-relaxed whitespace-pre-wrap">${escapeHtml(msg.message)}</p>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-xs opacity-75">${msg.formatted_time}</span>
                                    ${isMyMessage ? `<div class="flex items-center space-x-1">${seenIcon}</div>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Helper function to escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Function to add message immediately for instant feedback
            function addMessageToChatImmediate(messageText, isInternal, timestamp, responseId) {
                if (!chatContainer) return;
                
                console.log('⚡ Adding message immediately:', messageText);
                console.log('📅 Timestamp received:', timestamp);
                
                // Remove "No messages yet" placeholder if it exists
                const placeholder = chatContainer.querySelector('.text-center');
                if (placeholder && placeholder.textContent.includes('No messages yet')) {
                    placeholder.closest('.text-center').remove();
                }
                
                // Create message element for current user (always right-aligned)
                const messageDiv = document.createElement('div');
                messageDiv.className = 'flex justify-end mb-4';
                messageDiv.setAttribute('data-temp-message', 'true'); // Mark as temporary
                
                const messageContent = document.createElement('div');
                messageContent.className = 'max-w-xs';
                
                const bubbleDiv = document.createElement('div');
                bubbleDiv.className = 'chat-bubble relative bg-blue-500 text-white rounded-l-2xl rounded-tr-2xl bubble-sent px-4 py-3 shadow-sm';
                bubbleDiv.setAttribute('data-response-id', responseId);
                
                const messageP = document.createElement('p');
                messageP.className = 'text-sm leading-relaxed whitespace-pre-wrap';
                messageP.textContent = messageText;
                
                const metaDiv = document.createElement('div');
                metaDiv.className = 'flex justify-between items-center mt-2';
                
                const timeSpan = document.createElement('span');
                timeSpan.className = 'text-xs opacity-75';
                // Always show seconds for immediate messages to ensure uniqueness
                const formattedTime = timestamp.toLocaleTimeString([], {
                    hour: '2-digit', 
                    minute: '2-digit',
                    second: '2-digit'
                });
                timeSpan.textContent = formattedTime;
                console.log('📅 Immediate display time:', formattedTime, 'from:', timestamp);
                
                const statusDiv = document.createElement('div');
                statusDiv.className = 'flex items-center space-x-1';
                statusDiv.setAttribute('data-response-id', responseId);
                
                const sentIcon = document.createElement('i');
                sentIcon.className = 'fas fa-check text-xs opacity-60';
                sentIcon.title = 'Sent';
                
                statusDiv.appendChild(sentIcon);
                
                // Add internal indicator if applicable
                if (isInternal) {
                    const internalSpan = document.createElement('span');
                    internalSpan.className = 'text-xs bg-orange-500 bg-opacity-20 text-orange-200 px-2 py-1 rounded-full ml-2';
                    internalSpan.innerHTML = '<i class="fas fa-lock mr-1"></i>Internal';
                    bubbleDiv.appendChild(internalSpan);
                }
                
                metaDiv.appendChild(timeSpan);
                metaDiv.appendChild(statusDiv);
                
                bubbleDiv.appendChild(messageP);
                bubbleDiv.appendChild(metaDiv);
                
                messageContent.appendChild(bubbleDiv);
                messageDiv.appendChild(messageContent);
                
                // Add to chat container
                chatContainer.appendChild(messageDiv);
                
                // Auto-scroll to show new message
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
            
            // Start periodic polling for seen status (every 10 seconds)
            setInterval(pollForSeenStatus, 10000);
            
            // Helper function to show notifications
            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${
                    type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 
                    'bg-red-100 border border-red-400 text-red-700'
                }`;
                notification.textContent = message;
                
                document.body.appendChild(notification);
                
                // Auto-remove after 3 seconds
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }
        });
    </script>
    
    <!-- Unified Notification System with Pictures -->
    <script src="assets/js/notification-system.js"></script>
    
    <!-- Chat Enhancements (Typing & Seen Indicators) -->
    <script src="assets/js/chat-enhancements.js"></script>
    
    <!-- Header Dropdown Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Notification Bell Dropdown
            const notificationBell = document.getElementById('notificationBell');
            const notificationDropdown = document.getElementById('notificationDropdown');
            
            if (notificationBell && notificationDropdown) {
                notificationBell.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Close user dropdown if open
                    const userDropdown = document.getElementById('userDropdown');
                    if (userDropdown) {
                        userDropdown.classList.add('hidden');
                    }
                    
                    // Toggle notification dropdown
                    if (notificationDropdown.classList.contains('hidden')) {
                        // Calculate position based on bell button
                        const bellRect = notificationBell.getBoundingClientRect();
                        const dropdownWidth = 320; // w-80 = 320px
                        
                        // Position dropdown below the bell, aligned to the right
                        notificationDropdown.style.top = (bellRect.bottom + 8) + 'px';
                        notificationDropdown.style.right = (window.innerWidth - bellRect.right) + 'px';
                        
                        // Show dropdown
                        notificationDropdown.classList.remove('hidden');
                    } else {
                        // Hide dropdown
                        notificationDropdown.classList.add('hidden');
                    }
                });
            }
            
            // User Profile Dropdown
            const userProfileBtn = document.getElementById('userProfileBtn');
            const userDropdown = document.getElementById('userDropdown');
            
            if (userProfileBtn && userDropdown) {
                userProfileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Close notification dropdown if open
                    if (notificationDropdown) {
                        notificationDropdown.classList.add('hidden');
                    }
                    
                    // Toggle user dropdown
                    userDropdown.classList.toggle('hidden');
                });
            }
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (notificationDropdown && !notificationBell?.contains(e.target) && !notificationDropdown.contains(e.target)) {
                    notificationDropdown.classList.add('hidden');
                }
                
                if (userDropdown && !userProfileBtn?.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.add('hidden');
                }
            });
        });
    </script>
    
</body>
</html>
