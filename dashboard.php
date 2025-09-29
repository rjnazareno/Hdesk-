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
    <title>IT Help Desk - Dashboard</title>
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
                                <span class="inline-flex items-center text-white font-medium text-sm">
                                    <i class="fas fa-home w-4 h-4 mr-2"></i>
                                    Dashboard
                                </span>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <i class="fas fa-chevron-right text-blue-200 mx-2 text-xs"></i>
                                    <span class="text-blue-100 text-sm">
                                        <i class="fas fa-tachometer-alt mr-2"></i>
                                        <?= $isITStaff ? 'IT Management' : 'My Tickets' ?>
                                    </span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="hidden sm:flex items-center bg-blue-700 px-3 py-2 rounded-lg">
                        <i class="fas fa-user-circle text-blue-200 mr-2"></i>
                        <div class="text-xs">
                            <div class="text-blue-200"><?= $isITStaff ? 'IT Staff' : 'Employee' ?></div>
                            <div class="text-white font-medium"><?= escape(getUserName()) ?></div>
                        </div>
                    </div>
                    <?php if (!$isITStaff): ?>
                    <a href="create_ticket.php" class="bg-white bg-opacity-20 text-white px-4 py-2 rounded-lg hover:bg-opacity-30 transition-all font-medium">
                        <i class="fas fa-plus mr-2"></i>New Ticket
                    </a>
                    <?php endif; ?>
                    <a href="logout.php" class="bg-red-600 bg-opacity-80 text-white px-4 py-2 rounded-lg hover:bg-opacity-100 transition-all font-medium">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </header>
                


    <div class="max-w-6xl mx-auto px-4 py-8">
        
        <!-- Enhanced Header Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6 card-hover">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
                <div class="flex-1">
                    <div class="flex items-center mb-3">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center mr-4">
                            <i class="fas fa-tachometer-alt text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                IT Support Dashboard
                            </h1>
                            <p class="text-gray-600 text-sm mt-1">
                                Welcome back, <?php echo escape(getUserName()); ?> • <?php echo $isITStaff ? 'IT Staff' : 'Employee'; ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="flex flex-wrap gap-3">
                    <?php if (!$isITStaff): ?>
                    <a href="create_ticket.php" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-lg hover:from-green-600 hover:to-green-700 transition-all font-medium shadow-lg">
                        <i class="fas fa-plus mr-2"></i>New Ticket
                    </a>
                    <?php endif; ?>
                    <a href="?" class="bg-gradient-to-r from-gray-500 to-gray-600 text-white px-6 py-3 rounded-lg hover:from-gray-600 hover:to-gray-700 transition-all font-medium shadow-lg">
                        <i class="fas fa-refresh mr-2"></i>Refresh
                    </a>
                </div>
            </div>
            
            <!-- Enhanced Search Section -->
            <div class="border-t border-gray-200 pt-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <input type="text" 
                               name="search" 
                               value="<?php echo escape($filters['search']); ?>"
                               placeholder="Search tickets..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Status</option>
                            <option value="open" <?php echo $filters['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="closed" <?php echo $filters['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div>
                        <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Priority</option>
                            <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="urgent" <?php echo $filters['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-2 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all font-medium">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Enhanced Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover border-l-4 border-yellow-500">
                <div class="text-3xl font-bold text-yellow-600"><?php echo $stats['open_count'] ?? 0; ?></div>
                <div class="text-sm text-gray-600 mt-1 font-medium">Open Tickets</div>
                <div class="text-xs text-gray-500 mt-1">Needs attention</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover border-l-4 border-blue-500">
                <div class="text-3xl font-bold text-blue-600"><?php echo $stats['in_progress_count'] ?? 0; ?></div>
                <div class="text-sm text-gray-600 mt-1 font-medium">In Progress</div>
                <div class="text-xs text-gray-500 mt-1">Being worked on</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover border-l-4 border-green-500">
                <div class="text-3xl font-bold text-green-600"><?php echo $stats['resolved_count'] ?? 0; ?></div>
                <div class="text-sm text-gray-600 mt-1 font-medium">Resolved</div>
                <div class="text-xs text-gray-500 mt-1">Ready to close</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover border-l-4 border-gray-500">
                <div class="text-3xl font-bold text-gray-600"><?php echo $stats['closed_count'] ?? 0; ?></div>
                <div class="text-sm text-gray-600 mt-1 font-medium">Closed</div>
                <div class="text-xs text-gray-500 mt-1">Completed</div>
            </div>
            <?php if ($isITStaff): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover border-l-4 border-red-500">
                <div class="text-3xl font-bold text-red-600"><?php echo $stats['urgent_count'] ?? 0; ?></div>
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
                        <option value="closed" <?php echo $filters['status'] === 'closed' || $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Closed</option>
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
        <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                <h3 class="text-lg font-bold text-gray-900 flex items-center">
                    <i class="fas fa-ticket-alt text-blue-600 mr-3"></i>
                    Support Tickets
                </h3>
            </div>
            
            <?php if (empty($tickets)): ?>
            <div class="p-12 text-center">
                <div class="bg-gray-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-inbox text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No tickets found</h3>
                <p class="text-gray-600 mb-6">
                    <?php if ($isITStaff): ?>
                        No tickets match your current filters.
                    <?php else: ?>
                        You haven't created any tickets yet.
                    <?php endif; ?>
                </p>
                <?php if (!$isITStaff): ?>
                <a href="create_ticket.php" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all font-medium shadow-lg">
                    <i class="fas fa-plus mr-2"></i>Create Your First Ticket
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($tickets as $ticket): ?>
                <div class="p-6 hover:bg-gradient-to-r hover:from-blue-50 hover:to-transparent transition-all duration-300 border-l-4 border-transparent hover:border-blue-500">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-4 flex-1">
                            <!-- Enhanced Priority Icon -->
                            <div class="flex-shrink-0">
                                <?php
                                $priorityColor = getPriorityColor($ticket['priority']);
                                $priorityIcon = $ticket['priority'] === 'urgent' ? 'exclamation-circle' : 
                                               ($ticket['priority'] === 'high' ? 'arrow-up' : 
                                               ($ticket['priority'] === 'medium' ? 'minus' : 'arrow-down'));
                                ?>
                                <div class="w-10 h-10 bg-<?php echo $priorityColor; ?>-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-<?php echo $priorityIcon; ?> text-<?php echo $priorityColor; ?>-600"></i>
                                </div>
                            </div>
                            
                            <!-- Enhanced Ticket Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-3 mb-3">
                                    <a href="view_ticket.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                       class="text-lg font-bold text-gray-900 hover:text-blue-600 transition-colors">
                                        <?php echo escape($ticket['subject']); ?>
                                    </a>
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-medium">#<?php echo $ticket['ticket_id']; ?></span>
                                </div>
                                
                                <div class="flex flex-wrap items-center gap-3 text-sm">
                                    <!-- Enhanced Status Badge -->
                                    <span class="status-<?php echo $ticket['status']; ?> px-3 py-1 rounded-full text-xs font-semibold border">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                    
                                    <!-- Enhanced Priority Badge -->
                                    <span class="priority-<?php echo $ticket['priority']; ?> px-3 py-1 rounded-full text-xs font-semibold border">
                                        <?php echo ucfirst($ticket['priority']); ?> Priority
                                    </span>
                                    
                                    <!-- Category -->
                                    <span class="flex items-center bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-xs font-medium">
                                        <i class="fas fa-<?php echo getCategoryIcon($ticket['category']); ?> mr-1"></i>
                                        <?php echo ucfirst($ticket['category']); ?>
                                    </span>
                                    
                                    <!-- Employee (for IT staff) -->
                                    <?php if ($isITStaff): ?>
                                    <span class="flex items-center text-gray-600">
                                        <i class="fas fa-user mr-1"></i>
                                        Employee: <?php echo escape($ticket['employee_id']); ?>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <!-- Assigned to -->
                                    <?php if ($ticket['assigned_to']): ?>
                                    <span class="flex items-center text-green-600">
                                        <i class="fas fa-user-check mr-1"></i>
                                        Assigned: <?php echo escape($ticket['assigned_to']); ?>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <!-- Response count -->
                                    <?php if ($ticket['response_count'] > 0): ?>
                                    <span class="flex items-center text-blue-600">
                                        <i class="fas fa-comments mr-1"></i>
                                        <?php echo $ticket['response_count']; ?> responses
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Action Links -->
                                <div class="flex items-center space-x-4 mt-3">
                                    <a href="view_ticket.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
                                        <i class="fas fa-eye mr-1"></i>
                                        View Details
                                    </a>
                                    <a href="ticket_history.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                       class="text-gray-600 hover:text-gray-800 text-sm font-medium transition-colors">
                                        <i class="fas fa-history mr-1"></i>
                                        History
                                    </a>
                                    <?php if ($isITStaff): ?>
                                    <div class="relative group">
                                        <button class="text-gray-500 hover:text-gray-700 text-sm font-medium transition-colors">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <!-- Quick Actions Dropdown would go here -->
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Enhanced Time Display -->
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo timeAgo($ticket['created_at']); ?>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <?php echo date('M j, Y', strtotime($ticket['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Enhanced Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-gray-700 font-medium mb-3 sm:mb-0">
                        Showing <span class="font-bold"><?php echo ($offset + 1); ?>-<?php echo min($offset + $perPage, $totalTickets); ?></span> 
                        of <span class="font-bold"><?php echo number_format($totalTickets); ?></span> tickets
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <?php if ($hasPrev): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                           class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm">
                            <i class="fas fa-chevron-left mr-2"></i>Previous
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="<?php echo $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> px-4 py-2 border rounded-lg font-medium text-sm transition-colors">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($hasNext): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                           class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm">
                            Next<i class="fas fa-chevron-right ml-2"></i>
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