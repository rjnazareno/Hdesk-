<?php
require_once 'config/database.php';
require_once 'includes/security.php';

// Start session and require login
session_start();
requireLogin();

// Debug: Check session data (remove in production)
if (empty($_SESSION['user_id'])) {
    header('Location: login.php?error=session_expired');
    exit;
}

// Helper functions for safe session data access
function getUserName() {
    if (isset($_SESSION['user_data']['name'])) {
        return $_SESSION['user_data']['name'];
    }
    if (isset($_SESSION['username'])) {
        return $_SESSION['username'];
    }
    return 'User';
}

function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

$isITStaff = isITStaff();
$currentUserId = getUserId();

// Handle form submissions for filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'category' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    $pdo = getDB();
    
    // Get dashboard statistics
    if ($isITStaff) {
        $statsQuery = "
            SELECT 
                COUNT(CASE WHEN status = 'open' THEN 1 END) as open_count,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_count,
                COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_count,
                COUNT(CASE WHEN priority = 'urgent' AND status NOT IN ('resolved', 'closed') THEN 1 END) as urgent_count,
                COUNT(CASE WHEN assigned_to = ? AND status NOT IN ('resolved', 'closed') THEN 1 END) as my_assigned
            FROM tickets
        ";
        $statsStmt = $pdo->prepare($statsQuery);
        $statsStmt->execute([$currentUserId]);
    } else {
        $statsQuery = "
            SELECT 
                COUNT(CASE WHEN status = 'open' THEN 1 END) as open_count,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_count,
                COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_count,
                COUNT(*) as total_count
            FROM tickets 
            WHERE employee_id = ?
        ";
        $statsStmt = $pdo->prepare($statsQuery);
        $statsStmt->execute([$currentUserId]);
    }
    $stats = $statsStmt->fetch();
    
    // Build tickets query
    $whereConditions = [];
    $params = [];
    
    if (!$isITStaff) {
        $whereConditions[] = "t.employee_id = ?";
        $params[] = $currentUserId;
    }
    
    if (!empty($filters['status'])) {
        $whereConditions[] = "t.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['priority'])) {
        $whereConditions[] = "t.priority = ?";
        $params[] = $filters['priority'];
    }
    
    if (!empty($filters['category'])) {
        $whereConditions[] = "t.category = ?";
        $params[] = $filters['category'];
    }
    
    if (!empty($filters['search'])) {
        $whereConditions[] = "(t.subject LIKE ? OR t.description LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(*) 
        FROM tickets t 
        $whereClause
    ";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalTickets = $countStmt->fetchColumn();
    
    // Get tickets
    $ticketsQuery = "
        SELECT 
            t.*,
            (SELECT COUNT(*) FROM ticket_responses tr WHERE tr.ticket_id = t.ticket_id) as response_count
        FROM tickets t 
        $whereClause
        ORDER BY 
            CASE WHEN t.priority = 'urgent' THEN 1 
                 WHEN t.priority = 'high' THEN 2 
                 WHEN t.priority = 'medium' THEN 3 
                 ELSE 4 END,
            t.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $ticketsStmt = $pdo->prepare($ticketsQuery);
    $ticketsStmt->execute(array_merge($params, [$perPage, $offset]));
    $tickets = $ticketsStmt->fetchAll();
    
    // Calculate pagination
    $totalPages = ceil($totalTickets / $perPage);
    $hasNext = $page < $totalPages;
    $hasPrev = $page > 1;
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $tickets = [];
    $stats = [];
}

// Helper functions
function getStatusColor($status) {
    switch ($status) {
        case 'open': return 'yellow';
        case 'in_progress': return 'blue';
        case 'resolved': return 'green';
        case 'closed': return 'gray';
        default: return 'gray';
    }
}

function getPriorityColor($priority) {
    switch ($priority) {
        case 'urgent': return 'red';
        case 'high': return 'orange';
        case 'medium': return 'yellow';
        case 'low': return 'green';
        default: return 'gray';
    }
}

function getCategoryIcon($category) {
    switch ($category) {
        case 'hardware': return 'desktop';
        case 'software': return 'code';
        case 'network': return 'wifi';
        case 'security': return 'shield';
        default: return 'ticket';
    }
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time / 60) . 'm ago';
    if ($time < 86400) return floor($time / 3600) . 'h ago';
    if ($time < 2592000) return floor($time / 86400) . 'd ago';
    
    return date('M j', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Support Dashboard</title>
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
                        <a href="#" class="text-gray-900 font-medium border-b-2 border-orange-500 pb-2">Dashboard</a>
                        <a href="create_ticket.php" class="text-gray-600 hover:text-gray-900 pb-2">New Ticket</a>
                        <?php if ($isITStaff): ?>
                        <a href="#" class="text-gray-600 hover:text-gray-900 pb-2">Reports</a>
                        <a href="#" class="text-gray-600 hover:text-gray-900 pb-2">Settings</a>
                        <?php endif; ?>
                    </nav>
                </div>
                
                <!-- Search and User Menu -->
                <div class="flex items-center space-x-3">
                    <!-- Quick Actions Dropdown -->
                    <div class="relative">
                        <button type="button" id="quickActionsBtn" class="github-btn flex items-center space-x-2">
                            <i class="fas fa-bolt"></i>
                            <span class="hidden sm:inline">Quick Actions</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div id="quickActionsMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <div class="py-2">
                                <?php if (!$isITStaff): ?>
                                <a href="create_ticket.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-plus mr-3 text-green-500"></i>
                                    New Ticket
                                </a>
                                <?php endif; ?>
                                <a href="?status=open" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-folder-open mr-3 text-yellow-500"></i>
                                    Open Tickets
                                </a>
                                <a href="?status=in_progress" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-spinner mr-3 text-blue-500"></i>
                                    In Progress
                                </a>
                                <?php if ($isITStaff): ?>
                                <a href="?priority=urgent" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-exclamation-triangle mr-3 text-red-500"></i>
                                    Urgent Only
                                </a>
                                <?php endif; ?>
                                <div class="border-t border-gray-100 my-2"></div>
                                <a href="?" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-refresh mr-3 text-gray-500"></i>
                                    All Tickets
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="relative hidden sm:block">
                        <form method="GET" class="flex">
                            <input type="text" 
                                   name="search" 
                                   value="<?php echo escape($filters['search']); ?>"
                                   placeholder="Search tickets..." 
                                   class="w-80 px-3 py-2 border border-gray-300 rounded-l-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <!-- Preserve other filters -->
                            <?php foreach(['status', 'priority', 'category'] as $key): ?>
                                <?php if (!empty($filters[$key])): ?>
                                    <input type="hidden" name="<?php echo $key; ?>" value="<?php echo escape($filters[$key]); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white border border-blue-600 rounded-r-md hover:bg-blue-700 text-sm">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="flex items-center space-x-3 text-sm">
                        <span class="text-gray-600">
                            <?php echo escape(getUserName()); ?> 
                            <span class="text-gray-400">(<?php echo $isITStaff ? 'IT Staff' : 'Employee'; ?>)</span>
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
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
            <div class="github-card p-4 text-center">
                <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['open_count'] ?? 0; ?></div>
                <div class="text-sm text-gray-600 mt-1">Open</div>
            </div>
            <div class="github-card p-4 text-center">
                <div class="text-2xl font-bold text-blue-600"><?php echo $stats['in_progress_count'] ?? 0; ?></div>
                <div class="text-sm text-gray-600 mt-1">In Progress</div>
            </div>
            <div class="github-card p-4 text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo $stats['resolved_count'] ?? 0; ?></div>
                <div class="text-sm text-gray-600 mt-1">Resolved</div>
            </div>
            <div class="github-card p-4 text-center">
                <div class="text-2xl font-bold text-gray-600"><?php echo $stats['closed_count'] ?? 0; ?></div>
                <div class="text-sm text-gray-600 mt-1">Closed</div>
            </div>
            <?php if ($isITStaff): ?>
            <div class="github-card p-4 text-center">
                <div class="text-2xl font-bold text-red-600"><?php echo $stats['urgent_count'] ?? 0; ?></div>
                <div class="text-sm text-gray-600 mt-1">Urgent</div>
            </div>
            <div class="github-card p-4 text-center">
                <div class="text-2xl font-bold text-purple-600"><?php echo $stats['my_assigned'] ?? 0; ?></div>
                <div class="text-sm text-gray-600 mt-1">My Tasks</div>
            </div>
            <?php else: ?>
            <div class="github-card p-4 text-center col-span-2">
                <div class="text-2xl font-bold text-gray-800"><?php echo $stats['total_count'] ?? 0; ?></div>
                <div class="text-sm text-gray-600 mt-1">Total Tickets</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 space-y-4 sm:space-y-0">
            <div class="flex items-center space-x-3">
                <?php if (!$isITStaff): ?>
                <a href="create_ticket.php" class="github-btn github-btn-primary">
                    <i class="fas fa-plus mr-2"></i>New ticket
                </a>
                <?php endif; ?>
                <form method="GET" class="flex items-center space-x-2">
                    <!-- Status Filter -->
                    <select name="status" class="github-btn text-sm" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="open" <?php echo $filters['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $filters['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                    
                    <!-- Priority Filter -->
                    <select name="priority" class="github-btn text-sm" onchange="this.form.submit()">
                        <option value="">All priorities</option>
                        <option value="urgent" <?php echo $filters['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                    
                    <!-- Category Filter -->
                    <select name="category" class="github-btn text-sm" onchange="this.form.submit()">
                        <option value="">All categories</option>
                        <option value="hardware" <?php echo $filters['category'] === 'hardware' ? 'selected' : ''; ?>>Hardware</option>
                        <option value="software" <?php echo $filters['category'] === 'software' ? 'selected' : ''; ?>>Software</option>
                        <option value="network" <?php echo $filters['category'] === 'network' ? 'selected' : ''; ?>>Network</option>
                        <option value="security" <?php echo $filters['category'] === 'security' ? 'selected' : ''; ?>>Security</option>
                        <option value="other" <?php echo $filters['category'] === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                    
                    <!-- Preserve search -->
                    <?php if (!empty($filters['search'])): ?>
                        <input type="hidden" name="search" value="<?php echo escape($filters['search']); ?>">
                    <?php endif; ?>
                    
                    <!-- Clear filters if any are active -->
                    <?php if (array_filter($filters)): ?>
                    <a href="?" class="github-btn text-sm text-gray-600">
                        <i class="fas fa-times mr-1"></i>Clear
                    </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="text-sm text-gray-600">
                <?php echo number_format($totalTickets); ?> ticket<?php echo $totalTickets !== 1 ? 's' : ''; ?> found
            </div>
        </div>

        <!-- Tickets List -->
        <div class="github-card">
            <?php if (empty($tickets)): ?>
            <div class="text-center py-12">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No tickets found</h3>
                <p class="text-gray-600 mb-4">
                    <?php if ($isITStaff): ?>
                        No tickets match your current filters.
                    <?php else: ?>
                        You haven't created any tickets yet.
                    <?php endif; ?>
                </p>
                <?php if (!$isITStaff): ?>
                <a href="create_ticket.php" class="github-btn github-btn-primary">
                    <i class="fas fa-plus mr-2"></i>Create your first ticket
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($tickets as $ticket): ?>
                <div class="p-4 hover:bg-gray-50 transition-colors duration-150">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-3 flex-1">
                            <!-- Priority Icon -->
                            <div class="flex-shrink-0 mt-1">
                                <?php
                                $priorityColor = getPriorityColor($ticket['priority']);
                                $priorityIcon = $ticket['priority'] === 'urgent' ? 'exclamation-circle' : 
                                               ($ticket['priority'] === 'high' ? 'arrow-up' : 
                                               ($ticket['priority'] === 'medium' ? 'minus' : 'arrow-down'));
                                ?>
                                <i class="fas fa-<?php echo $priorityIcon; ?> text-<?php echo $priorityColor; ?>-500"></i>
                            </div>
                            
                            <!-- Ticket Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2 mb-1">
                                    <a href="view_ticket.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                       class="font-semibold text-gray-900 hover:text-blue-600">
                                        <?php echo escape($ticket['subject']); ?>
                                    </a>
                                    <span class="text-gray-400">#<?php echo $ticket['ticket_id']; ?></span>
                                </div>
                                
                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <!-- Status Badge -->
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?php echo getStatusColor($ticket['status']); ?>-100 text-<?php echo getStatusColor($ticket['status']); ?>-800">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                    
                                    <!-- Category -->
                                    <span class="flex items-center">
                                        <i class="fas fa-<?php echo getCategoryIcon($ticket['category']); ?> mr-1"></i>
                                        <?php echo ucfirst($ticket['category']); ?>
                                    </span>
                                    
                                    <!-- Employee (for IT staff) -->
                                    <?php if ($isITStaff): ?>
                                    <span class="flex items-center">
                                        <i class="fas fa-user mr-1"></i>
                                        Employee ID: <?php echo escape($ticket['employee_id']); ?>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <!-- Assigned to -->
                                    <?php if ($ticket['assigned_to']): ?>
                                    <span class="flex items-center">
                                        <i class="fas fa-user-cog mr-1"></i>
                                        Assigned to: <?php echo escape($ticket['assigned_to']); ?>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <!-- Response count -->
                                    <?php if ($ticket['response_count'] > 0): ?>
                                    <span class="flex items-center">
                                        <i class="fas fa-comments mr-1"></i>
                                        <?php echo $ticket['response_count']; ?>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <!-- History link -->
                                    <a href="ticket_history.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                       class="flex items-center text-blue-600 hover:text-blue-700">
                                        <i class="fas fa-history mr-1"></i>
                                        History
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Time -->
                        <div class="text-sm text-gray-500 ml-4">
                            <?php echo timeAgo($ticket['created_at']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $perPage, $totalTickets); ?> of <?php echo number_format($totalTickets); ?> tickets
                    </div>
                    
                    <div class="flex space-x-1">
                        <?php if ($hasPrev): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                           class="github-btn text-sm">
                            <i class="fas fa-chevron-left mr-1"></i>Previous
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="github-btn text-sm <?php echo $i === $page ? 'bg-blue-50 border-blue-300 text-blue-600' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($hasNext): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                           class="github-btn text-sm">
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
    
    <!-- JavaScript for Quick Actions -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quickActionsBtn = document.getElementById('quickActionsBtn');
            const quickActionsMenu = document.getElementById('quickActionsMenu');
            
            if (quickActionsBtn && quickActionsMenu) {
                quickActionsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    quickActionsMenu.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!quickActionsBtn.contains(e.target) && !quickActionsMenu.contains(e.target)) {
                        quickActionsMenu.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</body>
</html>