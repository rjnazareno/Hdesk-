<?php
/**
 * Brand New Ticket View - Complete Rewrite
 * This eliminates any potential corruption in the old file
 */

// Error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session and authentication
session_start();

// Simple auth check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: simple_login.php');
    exit;
}

// Get parameters
$ticketId = intval($_GET['id'] ?? 0);
if ($ticketId <= 0) {
    header('Location: dashboard.php');
    exit;
}

// User data
$isITStaff = $_SESSION['user_type'] === 'it_staff';
$userData = $_SESSION['user_data'] ?? [];
$userName = $userData['name'] ?? $userData['username'] ?? 'User';

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Constants
define('APP_NAME', 'IT Support Ticketing System');

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - Ticket #<?= $ticketId ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center">
                        <i class="fas fa-ticket-alt text-blue-600 text-2xl mr-3"></i>
                        <h1 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars(APP_NAME) ?></h1>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-700 text-sm font-medium">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                    </a>
                    <span class="text-sm text-gray-500">
                        <?= $isITStaff ? 'IT Staff' : 'Employee' ?>: 
                        <span class="font-medium text-gray-900"><?= htmlspecialchars($userName) ?></span>
                    </span>
                    <a href="logout.php" class="text-red-600 hover:text-red-700 text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Loading State -->
        <div id="loadingState" class="text-center py-12">
            <i class="fas fa-spinner fa-spin text-blue-600 text-4xl mb-4"></i>
            <p class="text-gray-600">Loading ticket details...</p>
        </div>

        <!-- Error State -->
        <div id="errorState" class="hidden text-center py-12">
            <i class="fas fa-exclamation-triangle text-red-600 text-4xl mb-4"></i>
            <p class="text-gray-600" id="errorMessage">Error loading ticket</p>
            <a href="dashboard.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                Back to Dashboard
            </a>
        </div>

        <!-- Ticket Content (Hidden initially) -->
        <div id="ticketContent" class="hidden">
            
            <!-- Ticket Header -->
            <div class="bg-white shadow-lg rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900" id="ticketSubject">Loading...</h2>
                            <p class="text-gray-600 mt-1">
                                Ticket #<?= $ticketId ?> • 
                                <span id="ticketCreatedBy">Loading...</span> • 
                                <span id="ticketCreatedAt">Loading...</span>
                            </p>
                        </div>
                        <div class="mt-4 lg:mt-0 flex flex-wrap gap-2" id="ticketBadges">
                            <!-- Badges will be loaded here -->
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <!-- Main Content -->
                        <div class="lg:col-span-2">
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Description</h3>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-gray-700 whitespace-pre-wrap" id="ticketDescription">Loading...</p>
                                </div>
                            </div>
                            
                            <!-- Attachments -->
                            <div class="mb-6" id="attachmentsSection" style="display: none;">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Attachments</h3>
                                <div class="space-y-2" id="attachmentsList"></div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="lg:col-span-1">
                            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Ticket Information</h3>
                                <dl class="space-y-3">
                                    <div><dt class="text-sm font-medium text-gray-500">Status</dt><dd id="sidebarStatus">Loading...</dd></div>
                                    <div><dt class="text-sm font-medium text-gray-500">Priority</dt><dd id="sidebarPriority">Loading...</dd></div>
                                    <div><dt class="text-sm font-medium text-gray-500">Category</dt><dd id="sidebarCategory">Loading...</dd></div>
                                    <?php if ($isITStaff): ?>
                                    <div><dt class="text-sm font-medium text-gray-500">Employee</dt><dd id="sidebarEmployee">Loading...</dd></div>
                                    <?php endif; ?>
                                    <div><dt class="text-sm font-medium text-gray-500">Assigned To</dt><dd id="sidebarAssigned">Loading...</dd></div>
                                    <div><dt class="text-sm font-medium text-gray-500">Last Updated</dt><dd id="sidebarUpdated">Loading...</dd></div>
                                </dl>
                            </div>

                            <?php if ($isITStaff): ?>
                            <!-- IT Staff Actions -->
                            <div class="bg-blue-50 p-4 rounded-lg mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">IT Actions</h3>
                                <div class="space-y-2">
                                    <button onclick="alert('Assign feature coming soon')" 
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md text-sm">
                                        <i class="fas fa-user-plus mr-2"></i>Assign Ticket
                                    </button>
                                    <button onclick="alert('Update status feature coming soon')" 
                                            class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-md text-sm">
                                        <i class="fas fa-edit mr-2"></i>Update Status
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conversation -->
            <div class="bg-white shadow-lg rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Conversation</h3>
                </div>
                
                <div class="p-6">
                    <div class="space-y-6" id="responsesList">Loading responses...</div>
                    
                    <!-- Add Response Form -->
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-4">Add Response</h4>
                        <form id="responseForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="ticket_id" value="<?= $ticketId ?>">
                            
                            <?php if ($isITStaff): ?>
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_internal" value="1" class="rounded border-gray-300 text-blue-600">
                                    <span class="ml-2 text-sm text-gray-700">Internal note (not visible to employee)</span>
                                </label>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-4">
                                <textarea name="message" 
                                          required 
                                          rows="4" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Type your response here..."></textarea>
                            </div>
                            
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-md">
                                <i class="fas fa-reply mr-2"></i>Add Response
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Global variables
    const ticketId = <?= $ticketId ?>;
    const isITStaff = <?= $isITStaff ? 'true' : 'false' ?>;
    const csrfToken = '<?= htmlspecialchars($csrfToken) ?>';

    // Utility functions
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    function getStatusBadge(status) {
        const classes = {
            'open': 'bg-yellow-100 text-yellow-800',
            'in_progress': 'bg-blue-100 text-blue-800',
            'resolved': 'bg-green-100 text-green-800',
            'closed': 'bg-gray-100 text-gray-800'
        };
        
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${classes[status] || classes.open}">
            ${status.replace('_', ' ').toUpperCase()}
        </span>`;
    }

    function getPriorityBadge(priority) {
        const classes = {
            'High': 'bg-red-100 text-red-800',
            'Medium': 'bg-yellow-100 text-yellow-800',
            'Low': 'bg-green-100 text-green-800'
        };
        
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${classes[priority] || classes.Low}">
            ${priority}
        </span>`;
    }

    // Load ticket function
    async function loadTicket() {
        try {
            const response = await fetch(`api/clean_view_ticket.php?id=${ticketId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to load ticket');
            }
            
            const ticket = data.ticket;
            
            // Update content
            document.getElementById('ticketSubject').textContent = ticket.subject;
            document.getElementById('ticketCreatedBy').textContent = ticket.employee.name;
            document.getElementById('ticketCreatedAt').textContent = formatDate(ticket.created_at);
            document.getElementById('ticketDescription').textContent = ticket.description;
            
            // Update badges
            document.getElementById('ticketBadges').innerHTML = `
                ${getStatusBadge(ticket.status)}
                ${getPriorityBadge(ticket.priority)}
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    ${ticket.category}
                </span>
            `;
            
            // Update sidebar
            document.getElementById('sidebarStatus').innerHTML = getStatusBadge(ticket.status);
            document.getElementById('sidebarPriority').innerHTML = getPriorityBadge(ticket.priority);
            document.getElementById('sidebarCategory').textContent = ticket.category;
            document.getElementById('sidebarAssigned').textContent = ticket.assigned_staff ? ticket.assigned_staff.name : 'Unassigned';
            document.getElementById('sidebarUpdated').textContent = formatDate(ticket.updated_at);
            
            if (isITStaff) {
                document.getElementById('sidebarEmployee').textContent = ticket.employee.name;
            }
            
            // Update responses
            const responsesList = document.getElementById('responsesList');
            if (ticket.responses && ticket.responses.length > 0) {
                responsesList.innerHTML = ticket.responses.map(response => `
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-medium">${response.responder.name}</span>
                            <span class="text-xs text-gray-500">${formatDate(response.created_at)}</span>
                        </div>
                        <div class="text-gray-700 whitespace-pre-wrap">${response.message}</div>
                    </div>
                `).join('');
            } else {
                responsesList.innerHTML = '<p class="text-gray-500 text-center py-8">No responses yet.</p>';
            }
            
            // Show content, hide loading
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('ticketContent').classList.remove('hidden');
            
        } catch (error) {
            console.error('Load ticket error:', error);
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('errorState').classList.remove('hidden');
            document.getElementById('errorMessage').textContent = error.message;
        }
    }

    // Form submission
    document.getElementById('responseForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        try {
            const formData = new FormData(e.target);
            const response = await fetch('api/add_response.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Response added successfully!');
                location.reload(); // Reload to show new response
            } else {
                alert('Error: ' + (data.message || 'Failed to add response'));
            }
            
        } catch (error) {
            alert('Network error: ' + error.message);
        }
    });

    // Load ticket on page load
    document.addEventListener('DOMContentLoaded', loadTicket);
    </script>
</body>
</html>