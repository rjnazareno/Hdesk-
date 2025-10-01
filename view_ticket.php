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

// Handle form submissions (only if ticket exists)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ticket) {
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
        // Get responses
        $responsesQuery = "
            SELECT * FROM ticket_responses 
            WHERE ticket_id = ?
        ";
        
        // Filter internal responses for employees
        if ($userType === 'employee') {
            $responsesQuery .= " AND (is_internal = 0 OR is_internal IS NULL)";
        }
        
        $responsesQuery .= " ORDER BY created_at ASC";
        
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
                
                <div class="flex items-center space-x-4">
                    <div class="hidden sm:flex items-center bg-blue-700 px-3 py-2 rounded-lg">
                        <i class="fas fa-user-circle text-blue-200 mr-2"></i>
                        <div class="text-xs">
                            <div class="text-blue-200"><?= $userType === 'it_staff' ? 'IT Staff' : 'Employee' ?></div>
                            <div class="text-white font-medium"><?= htmlspecialchars($_SESSION['user_data']['name'] ?? $_SESSION['username'] ?? 'User') ?></div>
                        </div>
                    </div>
                    <?php if ($userType === 'it_staff'): ?>
                    <a href="ticket_history.php?id=<?= $ticketId ?>" class="bg-white bg-opacity-20 text-white px-4 py-2 rounded-lg hover:bg-opacity-30 transition-all font-medium">
                        <i class="fas fa-history mr-2"></i>History
                    </a>
                    <?php endif; ?>
                    <a href="dashboard.php" class="bg-white bg-opacity-20 text-white px-4 py-2 rounded-lg hover:bg-opacity-30 transition-all font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                    <a href="logout.php" class="bg-red-600 bg-opacity-80 text-white px-4 py-2 rounded-lg hover:bg-opacity-100 transition-all font-medium">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
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
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                    <!-- Employee Card -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl border border-blue-200">
                        <div class="flex items-center mb-4">
                            <div class="bg-blue-500 text-white rounded-lg w-10 h-10 flex items-center justify-center mr-3">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3 class="font-bold text-blue-900">Requester</h3>
                        </div>
                        <div class="space-y-2 text-sm text-blue-800">
                            <div class="flex items-center">
                                <i class="fas fa-id-card w-4 mr-2 text-blue-600"></i>
                                <span class="font-medium"><?= htmlspecialchars($ticket['employee_name'] ?? 'N/A') ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-at w-4 mr-2 text-blue-600"></i>
                                <span><?= htmlspecialchars($ticket['employee_username'] ?? 'N/A') ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope w-4 mr-2 text-blue-600"></i>
                                <span class="text-xs"><?= htmlspecialchars($ticket['employee_email'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category & Priority Card -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl border border-purple-200">
                        <div class="flex items-center mb-4">
                            <div class="bg-purple-500 text-white rounded-lg w-10 h-10 flex items-center justify-center mr-3">
                                <i class="fas fa-tag"></i>
                            </div>
                            <h3 class="font-bold text-purple-900">Category</h3>
                        </div>
                        <div class="space-y-3">
                            <div class="bg-white px-3 py-2 rounded-lg">
                                <div class="text-xs text-purple-600 font-medium">Type</div>
                                <div class="text-sm font-semibold text-purple-900"><?= htmlspecialchars($ticket['category'] ?? 'General') ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Assignment Card -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl border border-green-200">
                        <div class="flex items-center mb-4">
                            <div class="bg-green-500 text-white rounded-lg w-10 h-10 flex items-center justify-center mr-3">
                                <i class="fas fa-user-cog"></i>
                            </div>
                            <h3 class="font-bold text-green-900">Assignment</h3>
                        </div>
                        <div class="space-y-3">
                            <?php if ($ticket['assigned_staff_name']): ?>
                                <div class="bg-white px-3 py-2 rounded-lg">
                                    <div class="text-xs text-green-600 font-medium">Assigned To</div>
                                    <div class="text-sm font-semibold text-green-900 flex items-center">
                                        <i class="fas fa-user-check mr-2 text-green-600"></i>
                                        <?= htmlspecialchars($ticket['assigned_staff_name']) ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="bg-orange-50 px-3 py-2 rounded-lg border border-orange-200">
                                    <div class="text-sm font-semibold text-orange-700 flex items-center">
                                        <i class="fas fa-user-clock mr-2"></i>
                                        Awaiting Assignment
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Activity Card -->
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-6 rounded-xl border border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="bg-gray-500 text-white rounded-lg w-10 h-10 flex items-center justify-center mr-3">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 class="font-bold text-gray-900">Activity</h3>
                        </div>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Responses</span>
                                <span class="font-bold text-gray-900"><?= count($responses) ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Last Update</span>
                                <span class="font-medium text-gray-700 text-xs"><?= date('M j, g:i A', strtotime($ticket['updated_at'])) ?></span>
                            </div>
                            <div class="pt-2">
                                <a href="ticket_history.php?id=<?= $ticketId ?>" class="text-blue-600 hover:text-blue-800 text-xs font-medium flex items-center">
                                    <i class="fas fa-history mr-1"></i>
                                    View Full History
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Description Section -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-file-text text-blue-600 mr-3"></i>
                        Issue Description
                    </h3>
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-6 rounded-xl border border-gray-200">
                        <p class="text-gray-800 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($ticket['description']) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Management Actions (IT Staff Only) -->
            <?php if ($userType === 'it_staff'): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 card-hover">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-tools text-blue-600 mr-3"></i>
                    Management Tools
                </h3>
                
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Status Update -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl border border-blue-200">
                        <h4 class="font-bold text-blue-900 mb-4 flex items-center">
                            <i class="fas fa-edit mr-2"></i>
                            Update Status
                        </h4>
                        <form method="POST" class="space-y-4">
                            <select name="status" class="w-full p-3 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="open" <?= $ticket['status'] == 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="in_progress" <?= $ticket['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="closed" <?= $ticket['status'] == 'closed' || $ticket['status'] == 'resolved' ? 'selected' : '' ?>>Close Ticket</option>
                            </select>
                            <button type="submit" name="update_status" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                <i class="fas fa-save mr-2"></i>Update Status
                            </button>
                        </form>
                    </div>
                    
                    <!-- Assign Ticket -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl border border-green-200">
                        <h4 class="font-bold text-green-900 mb-4 flex items-center">
                            <i class="fas fa-user-plus mr-2"></i>
                            Assign Ticket
                        </h4>
                        <form method="POST" class="space-y-4">
                            <select name="assigned_to" class="w-full p-3 border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">Unassigned</option>
                                <?php foreach ($itStaff as $staff): ?>
                                    <option value="<?= $staff['staff_id'] ?>" <?= $ticket['assigned_to'] == $staff['staff_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($staff['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="assign_ticket" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition-colors font-medium">
                                <i class="fas fa-user-check mr-2"></i>Assign
                            </button>
                        </form>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl border border-purple-200">
                        <h4 class="font-bold text-purple-900 mb-4 flex items-center">
                            <i class="fas fa-bolt mr-2"></i>
                            Quick Actions
                        </h4>
                        <div class="space-y-3">
                            <?php if ($ticket['status'] === 'closed' || $ticket['status'] === 'resolved'): ?>
                                <div class="w-full bg-gray-400 text-white py-3 rounded-lg font-medium text-center cursor-not-allowed">
                                    <i class="fas fa-lock mr-2"></i>Ticket Closed - Chat Disabled
                                </div>
                            <?php else: ?>
                                <button onclick="document.getElementById('responseForm').scrollIntoView({behavior: 'smooth'})" 
                                        class="w-full bg-purple-600 text-white py-3 rounded-lg hover:bg-purple-700 transition-colors font-medium">
                                    <i class="fas fa-reply mr-2"></i>Add Response
                                </button>
                            <?php endif; ?>
                            <a href="ticket_history.php?id=<?= $ticketId ?>" 
                               class="block w-full bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 transition-colors font-medium text-center">
                                <i class="fas fa-history mr-2"></i>View History
                            </a>
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
                                        <div class="flex justify-start mt-2">
                                            <span class="text-xs opacity-75"><?= date('g:i A', strtotime($response['created_at'])) ?></span>
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
                    <form id="messengerForm" method="post" class="flex items-end space-x-3">
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
    </script>
    
    <!-- Firebase Real-Time Chat System -->
    <script src="assets/js/firebase-config.js" type="module"></script>
    <script src="assets/js/firebase-chat.js" type="module"></script>
    <script src="assets/js/firebase-notifications.js" type="module"></script>
    <script src="assets/js/enhanced-chat-system.js" type="module"></script>
    
    <!-- Legacy Notification System (for compatibility) -->
    <script src="assets/js/notifications.js"></script>
    
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
            
            console.log('🔧 Form elements found:', {
                form: !!form,
                textarea: !!textarea,
                sendBtn: !!sendBtn
            });
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    console.log('📝 Form submit event triggered!', {
                        message: textarea?.value,
                        enhancedChatSystem: !!window.enhancedChatSystem,
                        firebaseReady: window.enhancedChatSystem?.firebaseChat?.isInitialized
                    });
                });
            }
            
            if (sendBtn) {
                sendBtn.addEventListener('click', function(e) {
                    console.log('🖱️ Send button clicked!', {
                        message: textarea?.value,
                        enhancedChatSystem: !!window.enhancedChatSystem
                    });
                });
            }
        });
    </script>
    
</body>
</html>
