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
                
                $message = 'Response added successfully.';
                
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
    <title><?= $ticket ? "Ticket #{$ticketId}: " . htmlspecialchars($ticket['subject']) : "View Ticket #$ticketId" ?> - IT Support</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .github-card {
            background: #ffffff;
            border: 1px solid #d1d9e0;
            border-radius: 12px;
        }
        .github-card:hover {
            border-color: #bbc1c7;
        }
        .github-btn {
            border: 1px solid #d1d9e0;
            border-radius: 6px;
            padding: 6px 16px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.15s ease;
        }
        .github-btn:hover {
            background-color: #f6f8fa;
            border-color: #bbc1c7;
        }
        .github-btn-primary {
            background-color: #238636;
            border-color: #238636;
            color: white;
        }
        .github-btn-primary:hover {
            background-color: #2ea043;
            border-color: #2ea043;
        }
        .priority-low { @apply bg-green-100 text-green-800 border-green-200; }
        .priority-medium { @apply bg-yellow-100 text-yellow-800 border-yellow-200; }
        .priority-high { @apply bg-orange-100 text-orange-800 border-orange-200; }
        .priority-critical { @apply bg-red-100 text-red-800 border-red-200; }
        .status-open { @apply bg-yellow-100 text-yellow-800 border-yellow-200; }
        .status-in_progress { @apply bg-blue-100 text-blue-800 border-blue-200; }
        .status-resolved { @apply bg-green-100 text-green-800 border-green-200; }
        .status-closed { @apply bg-gray-100 text-gray-800 border-gray-200; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- GitHub-style Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo and Navigation -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <i class="fas fa-ticket text-gray-900 text-xl mr-2"></i>
                        <h1 class="text-xl font-semibold text-gray-900">IT Support</h1>
                    </div>
                    <nav class="hidden md:flex space-x-6">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900 pb-2">Dashboard</a>
                        <a href="#" class="text-gray-900 font-medium border-b-2 border-orange-500 pb-2">View Ticket</a>
                        <a href="create_ticket.php" class="text-gray-600 hover:text-gray-900 pb-2">New Ticket</a>
                        <?php if ($userType === 'it_staff'): ?>
                        <a href="ticket_history.php?id=<?= $ticketId ?>" class="text-gray-600 hover:text-gray-900 pb-2">History</a>
                        <?php endif; ?>
                    </nav>
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center space-x-3">
                    <a href="dashboard.php" class="github-btn">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                    <div class="flex items-center space-x-3 text-sm">
                        <span class="text-gray-600">
                            <?= htmlspecialchars($_SESSION['user_data']['name'] ?? $_SESSION['username'] ?? 'User') ?>
                            <span class="text-gray-400">(<?= $userType === 'it_staff' ? 'IT Staff' : 'Employee' ?>)</span>
                        </span>
                        <a href="logout.php" class="text-red-600 hover:text-red-700">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="github-card border-green-200 bg-green-50 p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <span class="text-green-800"><?= htmlspecialchars($message) ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="github-card border-red-200 bg-red-50 p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                    <span class="text-red-800"><?= htmlspecialchars($error) ?></span>
                </div>
            </div>
            <?php if (!$ticket): ?>
                <div class="github-card p-8 text-center">
                    <i class="fas fa-exclamation-triangle text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Ticket Not Accessible</h3>
                    <p class="text-gray-600 mb-6">The ticket you're trying to access either doesn't exist or you don't have permission to view it.</p>
                    <a href="dashboard.php" class="github-btn-primary inline-flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($ticket): ?>
            
            <!-- Ticket Header -->
            <div class="github-card p-6 mb-6">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between mb-6">
                    <div class="flex-1">
                        <div class="flex items-start mb-4">
                            <div class="flex items-center mr-4">
                                <div class="bg-blue-600 text-white rounded-lg w-10 h-10 flex items-center justify-center mr-3">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-semibold text-gray-900">
                                        Ticket #<?= $ticket['ticket_id'] ?>
                                    </h1>
                                    <p class="text-gray-500 text-sm mt-1">
                                        Opened <?= date('M j, Y', strtotime($ticket['created_at'])) ?> by <?= htmlspecialchars($ticket['employee_name'] ?? 'Unknown') ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <h2 class="text-xl font-medium text-gray-800 mb-4">
                            <?= htmlspecialchars($ticket['subject']) ?>
                        </h2>
                    </div>
                    
                    <!-- Status and Priority -->
                    <div class="flex flex-wrap gap-2 lg:flex-col lg:items-end">
                        <span class="status-<?= $ticket['status'] ?> px-3 py-1 rounded-full text-sm font-medium border">
                            <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                        </span>
                        <span class="priority-<?= $ticket['priority'] ?> px-3 py-1 rounded-full text-sm font-medium border">
                            <?= ucfirst($ticket['priority']) ?> priority
                        </span>
                    </div>
                </div>
                
                <!-- GitHub-style Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="github-card p-4 text-center">
                        <div class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($ticket['employee_name'] ?? 'N/A') ?></div>
                        <div class="text-sm text-gray-600 mt-1">Requester</div>
                    </div>
                    <div class="github-card p-4 text-center">
                        <div class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($ticket['category'] ?? 'General') ?></div>
                        <div class="text-sm text-gray-600 mt-1">Category</div>
                    </div>
                    <div class="github-card p-4 text-center">
                        <div class="text-lg font-semibold text-gray-900"><?= $ticket['assigned_staff_name'] ? htmlspecialchars($ticket['assigned_staff_name']) : 'Unassigned' ?></div>
                        <div class="text-sm text-gray-600 mt-1">Assigned To</div>
                    </div>
                    <div class="github-card p-4 text-center">
                        <div class="text-lg font-semibold text-gray-900"><?= count($responses) ?></div>
                        <div class="text-sm text-gray-600 mt-1">Responses</div>
                    </div>
                </div>
                
                <!-- Description Section -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">
                        Description
                    </h3>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-gray-800 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($ticket['description']) ?></p>
                    </div>
                </div>
            </div>
            <!-- Management Actions (IT Staff Only) -->
            <?php if ($userType === 'it_staff'): ?>
            <div class="github-card p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Management Actions
                </h3>
                
                <div class="grid md:grid-cols-3 gap-4">
                    <!-- Status Update -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Update Status</h4>
                        <form method="POST" class="space-y-3">
                            <select name="status" class="w-full p-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="open" <?= $ticket['status'] == 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="in_progress" <?= $ticket['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $ticket['status'] == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="closed" <?= $ticket['status'] == 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                            <button type="submit" name="update_status" class="w-full github-btn-primary">
                                Update Status
                            </button>
                        </form>
                    </div>
                    
                    <!-- Assign Ticket -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Assign Ticket</h4>
                        <form method="POST" class="space-y-3">
                            <select name="assigned_to" class="w-full p-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Unassigned</option>
                                <?php foreach ($itStaff as $staff): ?>
                                    <option value="<?= $staff['staff_id'] ?>" <?= $ticket['assigned_to'] == $staff['staff_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($staff['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="assign_ticket" class="w-full github-btn-primary">
                                Assign
                            </button>
                        </form>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Quick Actions</h4>
                        <div class="space-y-2">
                            <button onclick="document.getElementById('responseForm').scrollIntoView({behavior: 'smooth'})" 
                                    class="w-full github-btn text-left">
                                <i class="fas fa-reply mr-2"></i>Add Response
                            </button>
                            <a href="ticket_history.php?id=<?= $ticketId ?>" 
                               class="block w-full github-btn text-center">
                                <i class="fas fa-history mr-2"></i>View History
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Responses -->
            <div class="github-card p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Conversation
                    <span class="ml-2 bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium"><?= count($responses) ?></span>
                </h3>
                
                <?php if (empty($responses)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-comment-slash text-gray-400 text-3xl mb-3"></i>
                        <p class="text-gray-500 font-medium">No responses yet</p>
                        <p class="text-gray-400 text-sm mt-1">Be the first to respond to this ticket</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($responses as $response): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                            <?= $response['user_type'] === 'it_staff' ? 'IT' : 'U' ?>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-900">
                                                <?= $response['user_type'] === 'it_staff' ? 'IT Support' : 'User' ?>
                                            </span>
                                            <span class="text-gray-500 text-sm ml-2">
                                                <?= date('M j, Y \a\t g:i A', strtotime($response['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($response['is_internal']): ?>
                                        <span class="bg-orange-100 text-orange-800 px-2 py-1 text-xs font-medium rounded border border-orange-200">
                                            Internal
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-gray-800 whitespace-pre-wrap"><?= htmlspecialchars($response['message']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Add Response Form -->
            <div id="responseForm" class="github-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Add Response
                </h3>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <textarea name="response_text" id="response_text" rows="4" 
                                  class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                  placeholder="Leave a comment..." 
                                  required></textarea>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-3 sm:space-y-0">
                        <?php if ($userType === 'it_staff'): ?>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_internal" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700">Internal note (visible to staff only)</span>
                            </label>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>
                        
                        <button type="submit" name="add_response" class="github-btn-primary">
                            <i class="fas fa-paper-plane mr-2"></i>Comment
                        </button>
                    </div>
                </form>
            </div>
            
        <?php else: ?>
            <div class="github-card p-8 text-center">
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Ticket Not Found</h2>
                <p class="text-gray-600 mb-4">The requested ticket could not be found.</p>
                <a href="dashboard.php" class="github-btn-primary">
                    Return to Dashboard
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</body>
</html>