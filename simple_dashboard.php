<?php
/**
 * Simple Dashboard - Redirect to New GitHub-Style Dashboard
 */
header('Location: dashboard.php');
exit;
?>

$userType = $_SESSION['user_type'];
$userId = $_SESSION['user_id'];

// Handle filter parameters
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Load dashboard data
try {
    require_once 'config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get dashboard stats
    if ($userType === 'it_staff') {
        $stmt = $db->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tickets,
                COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
                COUNT(CASE WHEN assigned_to = ? THEN 1 END) as assigned_to_me,
                COUNT(CASE WHEN priority = 'high' AND status IN ('open', 'in_progress') THEN 1 END) as high_priority
            FROM tickets
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tickets,
                COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
                0 as assigned_to_me,
                COUNT(CASE WHEN priority = 'high' AND status IN ('open', 'in_progress') THEN 1 END) as high_priority
            FROM tickets
            WHERE employee_id = ?
        ");
        $stmt->execute([$userId]);
    }
    $stats = $stmt->fetch();
    
    // Build tickets query with filters
    $whereClause = '';
    $params = [];
    
    if ($userType === 'employee') {
        $whereClause = 'WHERE t.employee_id = ?';
        $params[] = $userId;
    } else {
        $whereClause = 'WHERE 1=1';
    }
    
    if ($statusFilter) {
        $whereClause .= ' AND t.status = ?';
        $params[] = $statusFilter;
    }
    
    if ($priorityFilter) {
        $whereClause .= ' AND t.priority = ?';
        $params[] = $priorityFilter;
    }
    
    if ($searchTerm) {
        $whereClause .= ' AND (t.subject LIKE ? OR t.description LIKE ?)';
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
    }
    
    // Get tickets
    $sql = "
        SELECT 
            t.*,
            CONCAT(e.fname, ' ', e.lname) as employee_name,
            its.name as assigned_staff_name
        FROM tickets t
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN it_staff its ON t.assigned_to = its.staff_id
        $whereClause
        ORDER BY t.created_at DESC
        LIMIT 50
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
    $stats = ['open_tickets' => 0, 'in_progress_tickets' => 0, 'resolved_tickets' => 0, 'closed_tickets' => 0, 'assigned_to_me' => 0, 'high_priority' => 0];
    $tickets = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IT Ticketing System</title>
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
                    <span class="text-sm text-gray-500">
                        <?= $userType === 'it_staff' ? 'IT Staff' : 'Employee' ?>: 
                        <span class="font-medium text-gray-900">
                            <?= htmlspecialchars($_SESSION['user_data']['name'] ?? 'User') ?>
                        </span>
                    </span>
                    <a href="login.php?logout=1" class="text-red-600 hover:text-red-700 text-sm font-medium">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Welcome Message -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">
                Welcome, <?= htmlspecialchars($_SESSION['user_data']['name'] ?? 'User') ?>!
            </h2>
            <p class="text-gray-600 mt-1">
                <?= $userType === 'it_staff' ? 'Manage and resolve IT support tickets.' : 'Track your IT support requests.' ?>
            </p>
        </div>

        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?= $userType === 'it_staff' ? '6' : '4' ?> gap-6 mb-8">
            
            <!-- Open Tickets -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-red-900"><?= $stats['open_tickets'] ?></h3>
                        <p class="text-red-700 text-sm">Open</p>
                    </div>
                </div>
            </div>
            
            <!-- In Progress -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-yellow-900"><?= $stats['in_progress_tickets'] ?></h3>
                        <p class="text-yellow-700 text-sm">In Progress</p>
                    </div>
                </div>
            </div>
            
            <!-- Resolved -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-green-900"><?= $stats['resolved_tickets'] ?></h3>
                        <p class="text-green-700 text-sm">Resolved</p>
                    </div>
                </div>
            </div>
            
            <!-- Closed -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-gray-100 rounded-lg">
                        <i class="fas fa-archive text-gray-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?= $stats['closed_tickets'] ?></h3>
                        <p class="text-gray-700 text-sm">Closed</p>
                    </div>
                </div>
            </div>
            
            <?php if ($userType === 'it_staff'): ?>
                <!-- Assigned to Me -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-user text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-blue-900"><?= $stats['assigned_to_me'] ?></h3>
                            <p class="text-blue-700 text-sm">Assigned to Me</p>
                        </div>
                    </div>
                </div>
                
                <!-- High Priority -->
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="fas fa-fire text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-purple-900"><?= $stats['high_priority'] ?></h3>
                            <p class="text-purple-700 text-sm">High Priority</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>

        <!-- Quick Actions -->
        <div class="mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex gap-2">
                        <a href="create_ticket.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition duration-200">
                            <i class="fas fa-plus mr-2"></i>New Ticket
                        </a>
                        <a href="dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition duration-200">
                            <i class="fas fa-sync mr-2"></i>Refresh
                        </a>
                    </div>
                    
                    <!-- Filters -->
                    <form method="GET" class="flex gap-2">
                        <select name="status" class="border rounded px-3 py-1">
                            <option value="">All Status</option>
                            <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                        
                        <select name="priority" class="border rounded px-3 py-1">
                            <option value="">All Priority</option>
                            <option value="low" <?= $priorityFilter === 'low' ? 'selected' : '' ?>>Low</option>
                            <option value="medium" <?= $priorityFilter === 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="high" <?= $priorityFilter === 'high' ? 'selected' : '' ?>>High</option>
                        </select>
                        
                        <input type="text" name="search" placeholder="Search tickets..." value="<?= htmlspecialchars($searchTerm) ?>" class="border rounded px-3 py-1">
                        
                        <button type="submit" class="bg-green-600 text-white px-4 py-1 rounded hover:bg-green-700">
                            Filter
                        </button>
                        
                        <a href="dashboard.php" class="bg-gray-500 text-white px-4 py-1 rounded hover:bg-gray-600">
                            Clear
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tickets List -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Tickets</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                            <?php if ($userType === 'it_staff'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned</th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="<?= $userType === 'it_staff' ? '8' : '6' ?>" class="px-6 py-8 text-center text-gray-500">
                                    No tickets found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-blue-600">
                                        #<?= $ticket['ticket_id'] ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs truncate">
                                            <?= htmlspecialchars($ticket['subject']) ?>
                                        </div>
                                    </td>
                                    <?php if ($userType === 'it_staff'): ?>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <?= htmlspecialchars($ticket['employee_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <?= $ticket['assigned_staff_name'] ? htmlspecialchars($ticket['assigned_staff_name']) : '<span class="text-gray-400">Unassigned</span>' ?>
                                        </td>
                                    <?php endif; ?>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 py-1 rounded text-xs font-medium
                                            <?php 
                                            $priorityColors = [
                                                'low' => 'bg-gray-100 text-gray-800',
                                                'medium' => 'bg-yellow-100 text-yellow-800',
                                                'high' => 'bg-red-100 text-red-800'
                                            ];
                                            echo $priorityColors[$ticket['priority']] ?? 'bg-gray-100 text-gray-800';
                                            ?>">
                                            <?= ucfirst($ticket['priority']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 py-1 rounded text-xs font-medium
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
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?= date('M j, Y', strtotime($ticket['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <a href="view_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                           class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition duration-200">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</body>
</html>