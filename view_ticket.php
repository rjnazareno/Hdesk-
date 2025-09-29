<?php
/**
 * Enhanced Ticket View with Activity Logging
 */
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/activity_logger.php';

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
            
            <!-- Ticket Header -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 card-hover">
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
                                <option value="open" <?= $ticket['status'] == 'open' ? 'selected' : '' ?>>🔴 Open</option>
                                <option value="in_progress" <?= $ticket['status'] == 'in_progress' ? 'selected' : '' ?>>🔵 In Progress</option>
                                <option value="closed" <?= $ticket['status'] == 'closed' || $ticket['status'] == 'resolved' ? 'selected' : '' ?>>✅ Closed (Resolved)</option>
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
                                <option value="">👤 Unassigned</option>
                                <?php foreach ($itStaff as $staff): ?>
                                    <option value="<?= $staff['staff_id'] ?>" <?= $ticket['assigned_to'] == $staff['staff_id'] ? 'selected' : '' ?>>
                                        👨‍💻 <?= htmlspecialchars($staff['name']) ?>
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
                            <button onclick="document.getElementById('responseForm').scrollIntoView({behavior: 'smooth'})" 
                                    class="w-full bg-purple-600 text-white py-3 rounded-lg hover:bg-purple-700 transition-colors font-medium">
                                <i class="fas fa-reply mr-2"></i>Add Response
                            </button>
                            <a href="ticket_history.php?id=<?= $ticketId ?>" 
                               class="block w-full bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 transition-colors font-medium text-center">
                                <i class="fas fa-history mr-2"></i>View History
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Responses -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 card-hover">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-comments text-blue-600 mr-3"></i>
                        Conversation
                        <span id="responseCounter" class="ml-3 bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium"><?= count($responses) ?></span>
                    </h3>
                    
                    <?php if (count($responses) > 10): ?>
                    <button id="loadOlderBtn" class="text-sm text-blue-600 hover:text-blue-800 font-medium hidden">
                        <i class="fas fa-chevron-up mr-1"></i>Load Older Messages
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Scrollable Chat Container -->
                <div id="chatContainer" class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg bg-gray-50">
                    <!-- Load More Button (top) -->
                    <div id="loadMoreTop" class="text-center py-3 hidden">
                        <button id="loadMoreBtn" class="text-sm text-blue-600 hover:text-blue-800 font-medium bg-white px-4 py-2 rounded-full border border-blue-200 hover:bg-blue-50 transition-colors">
                            <i class="fas fa-chevron-up mr-1"></i>Load More Messages
                        </button>
                    </div>
                    
                    <!-- Messages Container -->
                    <div id="messagesContainer" class="p-4">
                        <?php if (empty($responses)): ?>
                            <div id="emptyState" class="text-center py-12">
                                <div class="bg-gray-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-comment-slash text-gray-400 text-2xl"></i>
                                </div>
                                <p class="text-gray-500 text-lg font-medium">No responses yet</p>
                                <p class="text-gray-400 text-sm mt-1">Be the first to respond to this ticket</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-6" id="responsesList">
                        <?php foreach ($responses as $index => $response): ?>
                            <div class="relative">
                                <!-- Timeline connector -->
                                <?php if ($index < count($responses) - 1): ?>
                                    <div class="absolute left-6 top-16 w-0.5 h-full bg-gray-200"></div>
                                <?php endif; ?>
                                
                                <div class="flex items-start space-x-4">
                                    <!-- Avatar -->
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    </div>
                                    
                                    <!-- Response Content -->
                                    <div class="flex-1 bg-gray-50 rounded-xl p-5 border border-gray-200">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center space-x-3">
                                                <span class="font-bold text-gray-900">
                                                    <?= $response['user_type'] === 'it_staff' ? 'IT Support' : 'Employee' ?>
                                                </span>
                                                <span class="text-gray-400">•</span>
                                                <span class="text-sm text-gray-600">
                                                    <?= date('M j, Y \a\t g:i A', strtotime($response['created_at'])) ?>
                                                </span>
                                            </div>
                                            
                                            <div class="flex items-center space-x-2">
                                                <?php if ($response['is_internal']): ?>
                                                    <span class="px-3 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded-full border border-orange-200">
                                                        <i class="fas fa-lock mr-1"></i>Internal
                                                    </span>
                                                <?php endif; ?>
                                                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                                    <?= $response['user_type'] === 'it_staff' ? 'Staff' : 'User' ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="prose prose-sm max-w-none">
                                            <p class="text-gray-800 whitespace-pre-wrap leading-relaxed m-0"><?= htmlspecialchars($response['message']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php 
                            // Load only the last 10 responses initially
                            $recentResponses = array_slice($responses, -10);
                            foreach ($recentResponses as $index => $response): 
                                $actualIndex = count($responses) - 10 + $index;
                                if ($actualIndex < 0) $actualIndex = $index;
                            ?>
                                <div class="relative">
                                    <!-- Timeline connector -->
                                    <?php if ($index < count($recentResponses) - 1): ?>
                                        <div class="absolute left-6 top-16 w-0.5 h-full bg-gray-200"></div>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-start space-x-4">
                                        <!-- Avatar -->
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                        
                                        <!-- Response Content -->
                                        <div class="flex-1 bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center space-x-3">
                                                    <span class="font-bold text-gray-900">
                                                        <?= $response['user_type'] === 'it_staff' ? 'IT Support' : 'Employee' ?>
                                                    </span>
                                                    <span class="text-gray-400">•</span>
                                                    <span class="text-sm text-gray-600">
                                                        <?= date('M j, Y \a\t g:i A', strtotime($response['created_at'])) ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="flex items-center space-x-2">
                                                    <?php if ($response['is_internal']): ?>
                                                        <span class="px-3 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded-full border border-orange-200">
                                                            <i class="fas fa-lock mr-1"></i>Internal
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                                        <?= $response['user_type'] === 'it_staff' ? 'Staff' : 'User' ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="prose prose-sm max-w-none">
                                                <p class="text-gray-800 whitespace-pre-wrap leading-relaxed m-0"><?= htmlspecialchars($response['message']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Device Notifications -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 card-hover">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-bell text-blue-600 mr-3"></i>
                    Device Notifications
                    <span class="ml-3 bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">Beta</span>
                </h3>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Browser Notifications -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-desktop mr-2 text-blue-600"></i>
                            Browser Notifications
                        </h4>
                        <p class="text-sm text-gray-600 mb-4">Get real-time notifications when there are updates to this ticket</p>
                        <button id="enableNotifications" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium w-full">
                            <i class="fas fa-bell mr-2"></i>Enable Browser Notifications
                        </button>
                    </div>
                    
                    <!-- Email Notifications -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-green-300 transition-colors">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-envelope mr-2 text-green-600"></i>
                            Email Notifications
                        </h4>
                        <p class="text-sm text-gray-600 mb-4">Receive email updates for ticket status changes and responses</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" id="emailNotif" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                                <label for="emailNotif" class="text-sm text-gray-700">Email enabled</label>
                            </div>
                            <span class="text-xs text-green-600 font-medium">✓ Active</span>
                        </div>
                    </div>
                </div>
                
                <!-- Notification Status -->
                <div id="notificationStatus" class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200 hidden">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            <span class="text-sm text-gray-700 font-medium">Notifications are enabled for Ticket #<?= $ticketId ?></span>
                        </div>
                        <div class="space-x-2">
                            <button id="testNotificationBtn" class="text-xs bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                                Test Notification
                            </button>
                            <button id="checkNowBtn" class="text-xs bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">
                                Check Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Add Response Form -->
            <div id="responseForm" class="bg-white rounded-xl shadow-lg p-6 card-hover">
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
    
    <!-- Device Notification JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const enableBtn = document.getElementById('enableNotifications');
            const statusDiv = document.getElementById('notificationStatus');
            const ticketId = <?= $ticketId ?>;
            
            // Auto-start background checking for all users
            console.log('Auto-starting notification system for ticket:', ticketId);
            startUpdateChecker();
            
            // Check if a response was just added (from PHP session)
            <?php if (isset($_SESSION["response_added_ticket_{$ticketId}"])): ?>
            console.log('Response was just added, clearing notification cache for other users');
            // Clear the session flag
            <?php unset($_SESSION["response_added_ticket_{$ticketId}"]); ?>
            // Force an immediate check after 3 seconds to allow other users to be notified
            setTimeout(() => {
                console.log('Triggering immediate notification check for other users');
            }, 3000);
            <?php endif; ?>
            
            // Check if browser notifications are manually enabled
            if (localStorage.getItem(`notifications_ticket_${ticketId}`) === 'enabled') {
                updateButtonState(true);
                statusDiv?.classList.remove('hidden');
            }
            
            // Handle manual notification enable/disable
            enableBtn?.addEventListener('click', function() {
                if (Notification.permission === 'denied') {
                    alert('Notifications are blocked. Please enable them in your browser settings and refresh the page.');
                    return;
                }
                
                if (Notification.permission === 'default') {
                    Notification.requestPermission().then(function(permission) {
                        if (permission === 'granted') {
                            enableNotifications();
                        } else {
                            alert('Please allow notifications to use this feature.');
                        }
                    });
                } else if (Notification.permission === 'granted') {
                    if (localStorage.getItem(`notifications_ticket_${ticketId}`) === 'enabled') {
                        disableNotifications();
                    } else {
                        enableNotifications();
                    }
                }
            });
            
            function enableNotifications() {
                localStorage.setItem(`notifications_ticket_${ticketId}`, 'enabled');
                updateButtonState(true);
                statusDiv?.classList.remove('hidden');
                
                // Show confirmation notification
                if (Notification.permission === 'granted') {
                    new Notification('IT Help Desk - Notifications Enabled', {
                        body: `You'll now receive desktop notifications for updates to Ticket #${ticketId}`,
                        icon: '/favicon.ico',
                        tag: `ticket-${ticketId}-enabled`
                    });
                }
                
                console.log('Browser notifications enabled for ticket:', ticketId);
            }
            
            function disableNotifications() {
                localStorage.removeItem(`notifications_ticket_${ticketId}`);
                updateButtonState(false);
                statusDiv?.classList.add('hidden');
                console.log('Browser notifications disabled for ticket:', ticketId);
            }
            
            function updateButtonState(enabled) {
                if (enabled) {
                    enableBtn.innerHTML = '<i class="fas fa-bell-slash mr-2"></i>Disable Notifications';
                    enableBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    enableBtn.classList.add('bg-gray-600', 'hover:bg-gray-700');
                } else {
                    enableBtn.innerHTML = '<i class="fas fa-bell mr-2"></i>Enable Browser Notifications';
                    enableBtn.classList.remove('bg-gray-600', 'hover:bg-gray-700');
                    enableBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                }
            }
            
            // Function to show in-page notification banner
            function showInPageNotification(message) {
                // Create notification banner
                const banner = document.createElement('div');
                banner.className = 'fixed top-4 right-4 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in';
                banner.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-bell mr-3"></i>
                        <span>${message}</span>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                
                document.body.appendChild(banner);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (banner.parentNode) {
                        banner.remove();
                    }
                }, 5000);
            }
            
            function startUpdateChecker() {
                // Check for updates every 30 seconds
                console.log('Starting update checker for ticket:', ticketId);
                window.updateChecker = setInterval(checkForUpdates, 30000);
            }
            
            function stopUpdateChecker() {
                if (window.updateChecker) {
                    clearInterval(window.updateChecker);
                    console.log('Update checker stopped');
                }
            }
            
            function checkForUpdates() {
                // Check if page is visible to avoid unnecessary requests
                if (document.hidden) return;
                
                console.log('Checking for updates on ticket:', ticketId);
                
                fetch(`api/safe_check_updates.php?id=${ticketId}`)
                    .then(response => {
                        console.log('API Response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Update check data:', data);
                        if (data.hasUpdates) {
                            console.log('New updates found, showing notifications');
                            
                            // Always show in-page notification
                            showInPageNotification(data.message);
                            
                            // Show browser notification only if explicitly enabled and permitted
                            const browserNotificationsEnabled = localStorage.getItem(`notifications_ticket_${ticketId}`) === 'enabled';
                            if (browserNotificationsEnabled && Notification.permission === 'granted') {
                                new Notification(`Ticket #${ticketId} - New Update`, {
                                    body: data.message || 'New activity on your ticket',
                                    icon: '/favicon.ico',
                                    tag: `ticket-${ticketId}-update`,
                                    requireInteraction: false
                                });
                                console.log('Desktop notification sent');
                            }
                            
                            // Refresh the responses section to show new content
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            console.log('No updates found');
                        }
                    })
                    .catch(error => {
                        console.error('Update check failed:', error);
                        // If API doesn't exist yet, stop checking to avoid spam
                        if (error.message.includes('404')) {
                            clearInterval(window.updateChecker);
                        }
                    });
            }
            
            // Start update checker if notifications are already enabled
            if (localStorage.getItem(`notifications_ticket_${ticketId}`) === 'enabled') {
                startUpdateChecker();
            }
            
            // Stop update checker when page is unloaded
            window.addEventListener('beforeunload', function() {
                if (window.updateChecker) {
                    clearInterval(window.updateChecker);
                }
            });
            
            // Test buttons functionality
            document.getElementById('testNotificationBtn')?.addEventListener('click', function() {
                if (Notification.permission === 'granted') {
                    new Notification('Test Notification - IT Help Desk', {
                        body: `This is a test notification for Ticket #${ticketId}`,
                        icon: '/favicon.ico',
                        tag: `ticket-${ticketId}-test`
                    });
                    console.log('Test notification sent');
                } else {
                    alert('Notifications not permitted. Permission: ' + Notification.permission);
                }
            });
            
            document.getElementById('checkNowBtn')?.addEventListener('click', function() {
                console.log('Manual update check triggered');
                checkForUpdates();
            });
            
            // Debug info on page load
            console.log('Notification system initialized for ticket:', ticketId);
            console.log('Notification permission:', Notification.permission);
            console.log('Notifications enabled:', localStorage.getItem(`notifications_ticket_${ticketId}`));
            
            // AJAX Chat System
            initializeAjaxChat();
            
            // Initialize scroll functionality
            initializeScrollableChat();
        });
        
        // Global variables for AJAX chat
        let lastResponseCount = <?= count($responses) ?>;
        let isTyping = false;
        let typingTimer;
        let loadedResponseCount = <?= count($responses) ?>; // Track how many we've loaded
        let totalResponseCount = <?= count($responses) ?>;
        
        // AJAX Chat Functions
        function initializeAjaxChat() {
            const form = document.getElementById('ajaxResponseForm');
            const textarea = document.getElementById('response_text');
            const clearBtn = document.getElementById('clearBtn');
            const sendBtn = document.getElementById('sendBtn');
            const statusDiv = document.getElementById('responseStatus');
            const responsesContainer = document.querySelector('.space-y-6');
            
            // Handle form submission via AJAX
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData();
                formData.append('ticket_id', <?= $ticketId ?>);
                formData.append('response_text', textarea.value);
                
                // Add internal checkbox if it exists (IT staff only)
                const internalCheckbox = document.getElementById('is_internal');
                if (internalCheckbox && internalCheckbox.checked) {
                    formData.append('is_internal', '1');
                }
                
                // Disable form while sending
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
                
                fetch('api/add_response_ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.text().then(text => {
                        console.log('Raw response:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                        }
                    });
                })
                .then(data => {
                    console.log('Parsed data:', data);
                    if (data.success) {
                        showStatus('Response sent successfully!', 'success');
                        textarea.value = '';
                        
                        // Add the new response to the display immediately (optimistic update)
                        if (data.response) {
                            addResponseToDisplay(data.response);
                        } else {
                            // Fallback: create immediate display while saving to database
                            const immediateResponse = {
                                id: 'temp_' + Date.now(),
                                user_type: '<?= $_SESSION['user_type'] ?>',
                                display_name: '<?= $_SESSION['user_type'] === 'it_staff' ? 'IT Support' : 'Employee' ?>',
                                message: formData.get('response_text'),
                                is_internal: formData.has('is_internal'),
                                formatted_date: new Date().toLocaleString('en-US', {
                                    month: 'short', 
                                    day: 'numeric', 
                                    year: 'numeric',
                                    hour: 'numeric', 
                                    minute: '2-digit',
                                    hour12: true
                                })
                            };
                            addResponseToDisplay(immediateResponse);
                        }
                        
                        // Update response counter
                        lastResponseCount++;
                        updateResponseCounter();
                        
                        // Clear typing status
                        clearTypingStatus();
                        
                        // Trigger fast polling for immediate updates
                        if (window.triggerFastPolling) {
                            window.triggerFastPolling();
                        }
                        
                    } else {
                        showStatus(data.error || 'Failed to send response', 'error');
                        if (data.debug) {
                            console.error('Server debug info:', data.debug);
                        }
                    }
                })
                .catch(error => {
                    console.error('AJAX error:', error);
                    showStatus('Error: ' + error.message, 'error');
                })
                .finally(() => {
                    // Re-enable form
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send Response';
                });
            });
            
            // Clear button functionality
            clearBtn.addEventListener('click', function() {
                textarea.value = '';
                clearTypingStatus();
            });
            
            // Typing indicator functionality
            textarea.addEventListener('input', function() {
                if (!isTyping) {
                    isTyping = true;
                    sendTypingStatus(true);
                }
                
                // Reset the typing timer
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => {
                    isTyping = false;
                    sendTypingStatus(false);
                }, 2000); // Stop typing after 2 seconds of inactivity
            });
            
            // Stop typing when user leaves textarea
            textarea.addEventListener('blur', function() {
                if (isTyping) {
                    clearTimeout(typingTimer);
                    isTyping = false;
                    sendTypingStatus(false);
                }
            });
            
            // Start checking for new responses and typing indicators
            startRealtimeUpdates();
        }
        
        function showStatus(message, type) {
            const statusDiv = document.getElementById('responseStatus');
            statusDiv.className = `mt-4 p-4 rounded-lg ${type === 'success' ? 'bg-green-100 border border-green-300 text-green-700' : 'bg-red-100 border border-red-300 text-red-700'}`;
            statusDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>${message}`;
            statusDiv.classList.remove('hidden');
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                statusDiv.classList.add('hidden');
            }, 5000);
        }
        
        function addResponseToDisplay(response) {
            const responsesContainer = document.querySelector('.space-y-6');
            const emptyState = document.querySelector('.text-center.py-12');
            
            // Remove empty state if present
            if (emptyState) {
                emptyState.remove();
            }
            
            // Check if this is a temporary message
            const isTemp = response.id && response.id.toString().startsWith('temp_');
            
            // Create new response HTML
            const responseHtml = `
                <div class="relative" ${isTemp ? 'data-temp-message="true"' : ''}>
                    <div class="flex items-start space-x-4">
                        <!-- Avatar -->
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        
                        <!-- Response Content -->
                        <div class="flex-1 bg-gray-50 rounded-xl p-5 border border-gray-200 ${isTemp ? 'opacity-75 border-dashed' : ''}">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <span class="font-bold text-gray-900">${response.display_name}</span>
                                    <span class="text-gray-400">•</span>
                                    <span class="text-sm text-gray-600">${response.formatted_date}</span>
                                    ${isTemp ? '<span class="text-xs text-gray-500 italic">(sending...)</span>' : ''}
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    ${response.is_internal ? '<span class="px-3 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded-full border border-orange-200"><i class="fas fa-lock mr-1"></i>Internal</span>' : ''}
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                        ${response.user_type === 'it_staff' ? 'Staff' : 'User'}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="prose prose-sm max-w-none">
                                <p class="text-gray-800 whitespace-pre-wrap leading-relaxed m-0">${response.message}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add the new response
            if (responsesContainer) {
                responsesContainer.insertAdjacentHTML('beforeend', responseHtml);
                
                // Add timeline connector to previous response if it exists
                const responses = responsesContainer.querySelectorAll('.relative');
                if (responses.length > 1) {
                    const previousResponse = responses[responses.length - 2];
                    if (!previousResponse.querySelector('.absolute.left-6')) {
                        const connector = document.createElement('div');
                        connector.className = 'absolute left-6 top-16 w-0.5 h-full bg-gray-200';
                        previousResponse.appendChild(connector);
                    }
                }
            }
        }
        
        function updateResponseCounter() {
            const counter = document.querySelector('.bg-blue-100.text-blue-800.px-3.py-1.rounded-full');
            if (counter) {
                counter.textContent = lastResponseCount;
            }
            
            const activityCounter = document.querySelector('.font-bold.text-gray-900');
            if (activityCounter && activityCounter.textContent.match(/^\d+$/)) {
                activityCounter.textContent = lastResponseCount;
            }
        }
        
        function sendTypingStatus(isTyping) {
            fetch('api/typing_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ticket_id: <?= $ticketId ?>,
                    is_typing: isTyping
                })
            }).catch(error => {
                console.error('Typing status error:', error);
            });
        }
        
        function clearTypingStatus() {
            sendTypingStatus(false);
        }
        
        function checkForTypingIndicators() {
            fetch(`api/get_typing_status.php?ticket_id=<?= $ticketId ?>`)
                .then(response => response.json())
                .then(data => {
                    const typingIndicator = document.getElementById('typingIndicator');
                    
                    if (data.someone_typing) {
                        typingIndicator.classList.remove('hidden');
                    } else {
                        typingIndicator.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Typing check error:', error);
                });
        }
        
        function startRealtimeUpdates() {
            console.log('Starting real-time updates...');
            
            // Check for typing indicators every 2 seconds
            const typingInterval = setInterval(checkForTypingIndicators, 2000);
            
            let normalInterval = 5000; // Normal checking every 5 seconds when quiet
            let fastInterval = 2000;   // Fast checking every 2 seconds after activity  
            let slowInterval = 10000;  // Slow checking every 10 seconds when very quiet
            let currentInterval = normalInterval;
            let lastActivityTime = Date.now();
            let consecutiveEmptyChecks = 0;
            
            function checkForNewResponses() {
                console.log('Checking for new responses... Current count:', lastResponseCount);
                
                fetch(`api/get_latest_responses.php?ticket_id=<?= $ticketId ?>&after_count=${lastResponseCount}`)
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        
                        if (data.success && data.new_responses && data.new_responses.length > 0) {
                            console.log(`Found ${data.new_responses.length} new responses`);
                            
                            // Remove any temporary messages before adding real ones
                            document.querySelectorAll('[data-temp-message="true"]').forEach(temp => temp.remove());
                            
                            // Check if any of the new messages are from OTHER users (not current user)
                            const currentUserType = '<?= $_SESSION['user_type'] ?>';
                            const currentUserId = <?= $_SESSION['user_id'] ?>;
                            
                            let hasMessagesFromOthers = false;
                            
                            data.new_responses.forEach(response => {
                                addResponseToDisplay(response);
                                
                                // Check if this message is from someone else
                                if (response.user_type !== currentUserType || response.user_id !== currentUserId) {
                                    hasMessagesFromOthers = true;
                                }
                            });
                            
                            lastResponseCount += data.new_responses.length;
                            updateResponseCounter();
                            
                            // Only show notification for messages from OTHER users
                            if (hasMessagesFromOthers) {
                                console.log('New message from another user - showing notification');
                                showNewMessageNotification(data.new_responses.length);
                            } else {
                                console.log('New message from current user - no notification needed');
                            }
                            
                            // Reset activity tracking and switch to fast polling
                            lastActivityTime = Date.now();
                            consecutiveEmptyChecks = 0;
                            
                            if (currentInterval !== fastInterval) {
                                switchToFastPolling();
                            }
                        } else {
                            console.log('No new responses found');
                            consecutiveEmptyChecks++;
                            
                            // Adaptive polling based on inactivity
                            const timeSinceActivity = Date.now() - lastActivityTime;
                            
                            if (timeSinceActivity > 120000 && consecutiveEmptyChecks > 10) { 
                                // Very quiet: 2+ minutes + 10 empty checks = slow polling
                                if (currentInterval !== slowInterval) {
                                    switchToSlowPolling();
                                }
                            } else if (timeSinceActivity > 30000 && currentInterval === fastInterval) {
                                // Quiet: 30+ seconds since activity = normal polling  
                                switchToNormalPolling();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Response check error:', error);
                    });
            }
            
            function switchToFastPolling() {
                console.log('Switching to fast polling (2s) - recent activity detected');
                clearInterval(window.chatIntervals.responses);
                currentInterval = fastInterval;
                consecutiveEmptyChecks = 0; // Reset counter
                window.chatIntervals.responses = setInterval(checkForNewResponses, fastInterval);
            }
            
            function switchToNormalPolling() {
                console.log('Switching to normal polling (5s) - moderate activity');
                clearInterval(window.chatIntervals.responses);
                currentInterval = normalInterval;
                window.chatIntervals.responses = setInterval(checkForNewResponses, normalInterval);
            }
            
            function switchToSlowPolling() {
                console.log('Switching to slow polling (10s) - very quiet');
                clearInterval(window.chatIntervals.responses);
                currentInterval = slowInterval;
                window.chatIntervals.responses = setInterval(checkForNewResponses, slowInterval);
            }
            
            // Start with normal polling
            const responseInterval = setInterval(checkForNewResponses, currentInterval);
            
            // Store intervals for potential cleanup
            window.chatIntervals = {
                typing: typingInterval,
                responses: responseInterval
            };
            
            // Expose functions globally for triggering after message send
            window.triggerFastPolling = switchToFastPolling;
        }
        
        // Notification management with cooldown
        let lastNotificationTime = 0;
        const notificationCooldown = 5000; // 5 seconds between notifications
        
        function showNewMessageNotification(messageCount) {
            const now = Date.now();
            
            // Check cooldown to prevent notification spam
            if (now - lastNotificationTime < notificationCooldown) {
                console.log('Notification cooldown active - skipping notification');
                return;
            }
            
            lastNotificationTime = now;
            
            // Remove any existing notifications first
            document.querySelectorAll('.chat-notification').forEach(notif => notif.remove());
            
            const notification = document.createElement('div');
            notification.className = 'chat-notification fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-comment mr-2"></i>
                    <span>${messageCount > 1 ? `${messageCount} new messages` : 'New message received!'}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-white hover:text-gray-200">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Slide in animation
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, 4000);
        }
    </script>
    
    <!-- CSS for typing dots animation -->
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
    </style>
    
    <script>
        // Scrollable Chat Initialization
        function initializeScrollableChat() {
            const chatContainer = document.getElementById('chatContainer');
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            const loadMoreTop = document.getElementById('loadMoreTop');
            
            if (!chatContainer) return;
            
            // Auto-scroll to bottom on load
            setTimeout(() => {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }, 100);
            
            // Show/hide load more button based on message count
            if (typeof totalResponseCount !== 'undefined' && totalResponseCount > 10) {
                if (loadMoreTop) loadMoreTop.classList.remove('hidden');
            }
            
            // Load more messages functionality
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Loading...';
                    loadMoreBtn.disabled = true;
                    
                    // Load older messages (simulation - you can implement API call here)
                    setTimeout(() => {
                        loadMoreBtn.innerHTML = '<i class="fas fa-chevron-up mr-1"></i>Load More Messages';
                        loadMoreBtn.disabled = false;
                        
                        // If all messages loaded, hide button
                        if (typeof loadedResponseCount !== 'undefined' && typeof totalResponseCount !== 'undefined' && 
                            loadedResponseCount >= totalResponseCount) {
                            if (loadMoreTop) loadMoreTop.classList.add('hidden');
                        }
                    }, 1000);
                });
            }
            
            // Auto-scroll to bottom when new messages arrive
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        // Check if new message was added
                        const addedNode = mutation.addedNodes[0];
                        if (addedNode.nodeType === 1 && addedNode.classList && addedNode.classList.contains('relative')) {
                            // Auto-scroll to bottom for new messages
                            setTimeout(() => {
                                chatContainer.scrollTop = chatContainer.scrollHeight;
                            }, 100);
                        }
                    }
                });
            });
            
            const responsesList = document.getElementById('responsesList');
            if (responsesList) {
                observer.observe(responsesList, { childList: true, subtree: true });
            }
        }
        
        // Initialize scrollable chat when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeScrollableChat();
        });
    </script>
</body>
</html>