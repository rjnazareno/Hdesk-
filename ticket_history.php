<?php
/**
 * Ticket History Page - Shows complete activity timeline
 */
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/activity_logger.php';

session_start();
requireLogin();

$ticketId = intval($_GET['id'] ?? 0);
$userType = $_SESSION['user_type'];
$userId = $_SESSION['user_id'] ?? 0;

if ($ticketId <= 0) {
    header('Location: dashboard.php');
    exit;
}

try {
    $pdo = getDB();
    $logger = new ActivityLogger($pdo);
    
    // Get ticket details
    $stmt = $pdo->prepare("
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
    
    if (!$ticket) {
        header('Location: dashboard.php?error=ticket_not_found');
        exit;
    }
    
    // Security check
    if ($userType === 'employee' && $ticket['employee_id'] != $userId) {
        header('Location: dashboard.php?error=access_denied');
        exit;
    }
    
    // Pagination for history
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    
    // Get total activity count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM activity_log 
        WHERE entity_type = 'ticket' AND entity_id = ?
    ");
    $stmt->execute([$ticketId]);
    $totalActivities = $stmt->fetchColumn();
    
    // Get activities with pagination
    $stmt = $pdo->prepare("
        SELECT 
            al.*,
            CASE 
                WHEN al.user_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                WHEN al.user_type = 'it_staff' THEN its.name
                ELSE 'System'
            END as user_name,
            CASE 
                WHEN al.user_type = 'employee' THEN e.username
                WHEN al.user_type = 'it_staff' THEN its.username
                ELSE NULL
            END as username,
            CASE 
                WHEN al.user_type = 'employee' THEN 'Employee'
                WHEN al.user_type = 'it_staff' THEN 'IT Staff'
                ELSE 'System'
            END as user_role
        FROM activity_log al
        LEFT JOIN employees e ON al.user_type = 'employee' AND al.user_id = e.id
        LEFT JOIN it_staff its ON al.user_type = 'it_staff' AND al.user_id = its.staff_id
        WHERE al.entity_type = 'ticket' AND al.entity_id = ?
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$ticketId, $perPage, $offset]);
    $activities = $stmt->fetchAll();
    
    // Calculate pagination
    $totalPages = ceil($totalActivities / $perPage);
    
    // Get ticket responses for reference
    $stmt = $pdo->prepare("
        SELECT 
            tr.*,
            CASE 
                WHEN tr.user_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                WHEN tr.user_type = 'it_staff' THEN its.name
                ELSE 'Unknown User'
            END as user_name,
            CASE 
                WHEN tr.user_type = 'employee' THEN e.username
                WHEN tr.user_type = 'it_staff' THEN its.username
                ELSE NULL
            END as username
        FROM ticket_responses tr
        LEFT JOIN employees e ON tr.user_type = 'employee' AND tr.user_id = e.id
        LEFT JOIN it_staff its ON tr.user_type = 'it_staff' AND tr.user_id = its.staff_id
        WHERE tr.ticket_id = ?
        ORDER BY tr.created_at ASC
    ");
    $stmt->execute([$ticketId]);
    $responses = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Ticket history error: " . $e->getMessage());
    header('Location: dashboard.php?error=system_error');
    exit;
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time / 60) . ' minute' . (floor($time / 60) != 1 ? 's' : '') . ' ago';
    if ($time < 86400) return floor($time / 3600) . ' hour' . (floor($time / 3600) != 1 ? 's' : '') . ' ago';
    if ($time < 2592000) return floor($time / 86400) . ' day' . (floor($time / 86400) != 1 ? 's' : '') . ' ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket History - #<?php echo $ticket['ticket_id']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="view_ticket.php?id=<?php echo $ticketId; ?>" class="text-blue-600 hover:text-blue-700">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Ticket
                    </a>
                    <div class="text-gray-300">|</div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        Ticket History - #<?php echo $ticket['ticket_id']; ?>
                    </h1>
                </div>
                <div class="text-sm text-gray-600">
                    <?php echo number_format($totalActivities); ?> activities total
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Ticket Summary Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        <?php echo escape($ticket['subject']); ?>
                    </h2>
                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            <?php 
                            $statusColors = [
                                'open' => 'bg-red-100 text-red-800',
                                'in_progress' => 'bg-yellow-100 text-yellow-800',
                                'resolved' => 'bg-green-100 text-green-800',
                                'closed' => 'bg-gray-100 text-gray-800'
                            ];
                            echo $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                        </span>
                        <span class="capitalize"><?php echo $ticket['priority']; ?> Priority</span>
                        <span><?php echo ucfirst($ticket['category']); ?></span>
                        <span>Created <?php echo timeAgo($ticket['created_at']); ?></span>
                    </div>
                </div>
                <div class="text-right text-sm text-gray-600">
                    <div><strong>Employee:</strong> <?php echo escape($ticket['employee_name']); ?></div>
                    <?php if ($ticket['assigned_staff_name']): ?>
                    <div><strong>Assigned:</strong> <?php echo escape($ticket['assigned_staff_name']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Activity Timeline</h3>
            </div>
            
            <?php if (empty($activities)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-history text-gray-300 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Activity History</h3>
                <p class="text-gray-600">This ticket doesn't have any recorded activities yet.</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($activities as $activity): ?>
                <div class="p-6 hover:bg-gray-50">
                    <div class="flex items-start space-x-4">
                        <!-- Activity Icon -->
                        <div class="flex-shrink-0 mt-1">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                <i class="<?php echo $logger->getActivityIcon($activity['action']); ?> text-sm"></i>
                            </div>
                        </div>
                        
                        <!-- Activity Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium text-gray-900">
                                        <?php echo escape($activity['user_name']); ?>
                                    </span>
                                    <span class="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded-full">
                                        <?php echo $activity['user_role']; ?>
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo timeAgo($activity['created_at']); ?>
                                </div>
                            </div>
                            
                            <div class="text-gray-700">
                                <?php echo $logger->getActivityDescription($activity); ?>
                            </div>
                            
                            <!-- Show response content if it's a response activity -->
                            <?php if ($activity['action'] === 'response_added'): ?>
                                <?php 
                                $details = json_decode($activity['details'], true);
                                $responseId = $details['response_id'] ?? null;
                                if ($responseId) {
                                    foreach ($responses as $response) {
                                        if ($response['response_id'] == $responseId) {
                                ?>
                                <div class="mt-3 bg-gray-50 p-4 rounded-lg border-l-4 border-blue-400">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="text-sm font-medium text-gray-900">
                                            Response Content
                                        </div>
                                        <?php if ($response['is_internal']): ?>
                                        <span class="text-xs px-2 py-1 bg-orange-100 text-orange-600 rounded-full">
                                            Internal Note
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-gray-700 text-sm whitespace-pre-wrap">
                                        <?php echo escape($response['message']); ?>
                                    </div>
                                </div>
                                <?php 
                                            break;
                                        }
                                    }
                                }
                                ?>
                            <?php endif; ?>
                            
                            <!-- Additional activity details -->
                            <div class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-clock mr-1"></i>
                                <?php echo date('M j, Y \a\t g:i A', strtotime($activity['created_at'])); ?>
                                <?php if ($activity['ip_address']): ?>
                                <span class="ml-3">
                                    <i class="fas fa-globe mr-1"></i>
                                    <?php echo escape($activity['ip_address']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $perPage, $totalActivities); ?> 
                        of <?php echo number_format($totalActivities); ?> activities
                    </div>
                    
                    <div class="flex space-x-1">
                        <?php if ($page > 1): ?>
                        <a href="?id=<?php echo $ticketId; ?>&page=<?php echo ($page - 1); ?>" 
                           class="px-3 py-2 text-sm border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-chevron-left mr-1"></i>Previous
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                        <a href="?id=<?php echo $ticketId; ?>&page=<?php echo $i; ?>" 
                           class="px-3 py-2 text-sm border rounded-md 
                           <?php echo $i === $page ? 'bg-blue-50 border-blue-300 text-blue-600' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="?id=<?php echo $ticketId; ?>&page=<?php echo ($page + 1); ?>" 
                           class="px-3 py-2 text-sm border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next<i class="fas fa-chevron-right ml-1"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>