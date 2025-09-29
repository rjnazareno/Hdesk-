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
        
                
                $message = 'Response added successfully!';
            }
        }
        
        if (isset($_POST['update_status']) && $userType === 'it_staff') {
            // Update ticket status (IT staff only)
            $new_status = $_POST['status'] ?? '';
            $valid_statuses = ['open', 'in_progress', 'resolved', 'closed'];
            
            if (in_array($new_status, $valid_statuses)) {
                $stmt = $db->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE ticket_id = ?");
                $stmt->execute([$new_status, $ticketId]);
                $message = 'Status updated successfully!';
            }
        }
        
        if (isset($_POST['assign_ticket']) && $userType === 'it_staff') {
            // Assign ticket (IT staff only)
            $assigned_to = intval($_POST['assigned_to'] ?? 0);
            
            if ($assigned_to > 0) {
                $stmt = $db->prepare("UPDATE tickets SET assigned_to = ?, updated_at = NOW() WHERE ticket_id = ?");
                $stmt->execute([$assigned_to, $ticketId]);
                $message = 'Ticket assigned successfully!';
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
    <title>View Ticket #<?= $ticketId ?> - IT Ticketing System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b-4 border-blue-600">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <i class="fas fa-ticket-alt text-blue-600 text-2xl mr-3"></i>
                    <h1 class="text-xl font-semibold text-gray-900">IT Ticketing System</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-blue-600 hover:text-blue-700 font-medium">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                    </a>
                    <span class="text-sm text-gray-500">
                        <?= $userType === 'it_staff' ? 'IT Staff' : 'Employee' ?>: 
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['user_data']['name'] ?? $_SESSION['username'] ?? 'User') ?></span>
                    </span>
                </div>
            </div>
        </div>
    </nav>

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
            
            <!-- Ticket Details -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex justify-between items-start mb-6">
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">
                            Ticket #<?= $ticket['ticket_id'] ?>: <?= htmlspecialchars($ticket['subject']) ?>
                        </h2>
                        
                        <!-- Enhanced Ticket Information Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                            <!-- Employee Information -->
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-blue-900 mb-2">
                                    <i class="fas fa-user mr-2"></i>Employee Details
                                </h4>
                                <div class="space-y-1 text-blue-800">
                                    <div><strong>Name:</strong> <?= htmlspecialchars($ticket['employee_name'] ?? 'N/A') ?></div>
                                    <div><strong>Username:</strong> <?= htmlspecialchars($ticket['employee_username'] ?? 'N/A') ?></div>
                                    <div><strong>Email:</strong> <?= htmlspecialchars($ticket['employee_email'] ?? 'N/A') ?></div>
                                    <div><strong>Employee ID:</strong> #<?= $ticket['employee_id'] ?></div>
                                </div>
                            </div>
                            
                            <!-- Ticket Metadata -->
                            <div class="bg-green-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-green-900 mb-2">
                                    <i class="fas fa-info-circle mr-2"></i>Ticket Information
                                </h4>
                                <div class="space-y-1 text-green-800">
                                    <div><strong>Created:</strong> <?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></div>
                                    <div><strong>Last Updated:</strong> <?= date('M j, Y g:i A', strtotime($ticket['updated_at'])) ?></div>
                                    <div><strong>Category:</strong> <?= htmlspecialchars($ticket['category'] ?? 'General') ?></div>
                                    <div><strong>Priority:</strong> <span class="capitalize font-medium"><?= htmlspecialchars($ticket['priority']) ?></span></div>
                                </div>
                            </div>
                            
                            <!-- Assignment & Status -->
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-purple-900 mb-2">
                                    <i class="fas fa-tasks mr-2"></i>Assignment Status
                                </h4>
                                <div class="space-y-1 text-purple-800">
                                    <div><strong>Status:</strong> 
                                        <span class="inline-block px-2 py-1 rounded text-xs font-medium ml-1
                                            <?php 
                                            $statusColors = [
                                                'open' => 'bg-red-100 text-red-800',
                                                'in_progress' => 'bg-yellow-100 text-yellow-800',
                                                'resolved' => 'bg-green-100 text-green-800',
                                                'closed' => 'bg-gray-100 text-gray-800'
                                            ];
                                            echo $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>">
                                            <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                        </span>
                                    </div>
                                    <div><strong>Assigned To:</strong> 
                                        <?= $ticket['assigned_staff_name'] ? htmlspecialchars($ticket['assigned_staff_name']) : 'Unassigned' ?>
                                    </div>
                                    <div><strong>Responses:</strong> <?= count($responses) ?> total</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Description Section -->
                <div class="border-t pt-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">
                        <i class="fas fa-file-text mr-2"></i>Description
                    </h4>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($ticket['description']) ?></p>
                    </div>
                </div>>
                            <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                        </span>
                        
                        <div class="mt-2">
                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">
                                <?= ucfirst($ticket['priority']) ?>
                            </span>
                            <span class="inline-block px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded ml-1">
                                <?= ucfirst($ticket['category']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Description</h3>
                    <p class="text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($ticket['description']) ?></p>
                </div>
                
                <?php if ($ticket['assigned_to']): ?>
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-blue-900 mb-1">Assigned To</h3>
                        <p class="text-blue-800"><?= htmlspecialchars($ticket['assigned_staff_name']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Management Actions (IT Staff Only) -->
            <?php if ($userType === 'it_staff'): ?>
            <div class="grid md:grid-cols-3 gap-6 mb-6">
                
                <!-- Status Update -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Update Status</h3>
                    <form method="POST">
                        <select name="status" class="w-full p-2 border rounded mb-3">
                            <option value="open" <?= $ticket['status'] == 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="in_progress" <?= $ticket['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="resolved" <?= $ticket['status'] == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="closed" <?= $ticket['status'] == 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                        <button type="submit" name="update_status" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                            Update Status
                        </button>
                    </form>
                </div>
                
                <!-- Assign Ticket -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Assign Ticket</h3>
                    <form method="POST">
                        <select name="assigned_to" class="w-full p-2 border rounded mb-3">
                            <option value="">Unassigned</option>
                            <?php foreach ($itStaff as $staff): ?>
                                <option value="<?= $staff['staff_id'] ?>" <?= $ticket['assigned_to'] == $staff['staff_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($staff['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_ticket" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
                            Assign
                        </button>
                    </form>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Quick Actions</h3>
                    <div class="space-y-2">
                        <button onclick="document.getElementById('responseForm').scrollIntoView()" class="w-full bg-purple-600 text-white py-2 rounded hover:bg-purple-700">
                            Add Response
                        </button>
                        <a href="dashboard.php" class="block w-full bg-gray-600 text-white py-2 rounded hover:bg-gray-700 text-center">
                            Back to Dashboard
                        </a>
                    </div>
                </div>
                
            </div>
            <?php endif; ?>
            
            <!-- Responses -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Responses (<?= count($responses) ?>)
                </h3>
                
                <?php if (empty($responses)): ?>
                    <p class="text-gray-500 italic">No responses yet.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($responses as $response): ?>
                            <div class="border-l-4 border-blue-500 pl-4 py-2">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium">Staff Member</span>
                                        <span class="mx-2">•</span>
                                        <span><?= date('M j, Y g:i A', strtotime($response['created_at'])) ?></span>
                                        <?php if ($response['is_internal']): ?>
                                            <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">Internal Note</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($response['message']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Add Response Form -->
            <div id="responseForm" class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Add Response</h3>
                
                <form method="POST">
                    <div class="mb-4">
                        <textarea name="response_text" rows="4" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                  placeholder="Type your response here..." required></textarea>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <?php if ($userType === 'it_staff'): ?>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_internal" class="mr-2">
                            <span class="text-sm text-gray-700">Internal note (not visible to employee)</span>
                        </label>
                        <?php else: ?>
                        <div></div>
                        <?php endif; ?>
                        
                        <button type="submit" name="add_response" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-reply mr-2"></i>Add Response
                        </button>
                    </div>
                </form>
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
</body>
</html>