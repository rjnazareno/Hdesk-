/**
 * Dashboard JavaScript functionality
 * Handles AJAX operations for ticket management
 */

// Utility functions
function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 max-w-sm ${
        type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
        type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
        'bg-blue-100 text-blue-800 border border-blue-200'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

function getPriorityBadge(priority) {
    const classes = {
        'high': 'bg-red-100 text-red-800',
        'medium': 'bg-yellow-100 text-yellow-800',
        'low': 'bg-green-100 text-green-800'
    };
    
    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${classes[priority] || classes.low}">
        ${priority.charAt(0).toUpperCase() + priority.slice(1)}
    </span>`;
}

function getStatusBadge(status) {
    const classes = {
        'open': 'bg-yellow-100 text-yellow-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'resolved': 'bg-green-100 text-green-800',
        'closed': 'bg-gray-100 text-gray-800'
    };
    
    const labels = {
        'open': 'Open',
        'in_progress': 'In Progress',
        'resolved': 'Resolved',
        'closed': 'Closed'
    };
    
    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${classes[status] || classes.open}">
        ${labels[status] || status}
    </span>`;
}

function getCategoryIcon(category) {
    const icons = {
        'Hardware': 'fas fa-desktop',
        'Software': 'fas fa-code',
        'Network': 'fas fa-network-wired',
        'Account': 'fas fa-user',
        'Email': 'fas fa-envelope',
        'Phone': 'fas fa-phone',
        'Security': 'fas fa-shield-alt',
        'General': 'fas fa-question-circle'
    };
    
    return icons[category] || icons.General;
}

// Load tickets function
async function loadTickets(page = 1) {
    try {
        showLoading();
        
        // Build query parameters
        const params = new URLSearchParams({
            page: page,
            ...currentFilters
        });
        
        const response = await fetch(`api/get_tickets.php?${params}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load tickets');
        }
        
        displayTickets(data.tickets);
        displayPagination(data.pagination);
        
    } catch (error) {
        console.error('Load tickets error:', error);
        showNotification('Failed to load tickets: ' + error.message, 'error');
        
        // Show empty state
        document.getElementById('ticketsTableBody').innerHTML = `
            <tr>
                <td colspan="${isITStaff ? '8' : '6'}" class="px-6 py-4 text-center text-red-500">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Error loading tickets. Please refresh the page.
                </td>
            </tr>
        `;
    } finally {
        hideLoading();
    }
}

function displayTickets(tickets) {
    const tbody = document.getElementById('ticketsTableBody');
    
    if (tickets.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${isITStaff ? '8' : '6'}" class="px-6 py-4 text-center text-gray-500">
                    <i class="fas fa-inbox mr-2"></i>
                    No tickets found
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = tickets.map(ticket => {
        const truncatedSubject = ticket.subject.length > 50 ? 
            ticket.subject.substring(0, 50) + '...' : ticket.subject;
        
        return `
            <tr class="hover:bg-gray-50 cursor-pointer" onclick="viewTicket(${ticket.id})">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="${getCategoryIcon(ticket.category)} text-gray-400 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">#${ticket.id}</div>
                            <div class="text-sm text-gray-500" title="${ticket.subject}">
                                ${truncatedSubject}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${getStatusBadge(ticket.status)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${getPriorityBadge(ticket.priority)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${ticket.category}
                </td>
                ${isITStaff ? `
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${ticket.employee.name}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${ticket.assigned_staff ? ticket.assigned_staff.name : 'Unassigned'}
                    </td>
                ` : ''}
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${formatDate(ticket.created_at)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                        <button onclick="event.stopPropagation(); viewTicket(${ticket.id})" 
                                class="text-blue-600 hover:text-blue-900" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${ticket.response_count > 0 ? `
                            <span class="text-gray-400" title="Responses">
                                <i class="fas fa-comments"></i> ${ticket.response_count}
                            </span>
                        ` : ''}
                        ${ticket.attachment_count > 0 ? `
                            <span class="text-gray-400" title="Attachments">
                                <i class="fas fa-paperclip"></i> ${ticket.attachment_count}
                            </span>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function displayPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    
    if (pagination.total_pages <= 1) {
        container.innerHTML = `
            <div class="text-sm text-gray-700">
                Showing ${pagination.total_count} ticket${pagination.total_count !== 1 ? 's' : ''}
            </div>
        `;
        return;
    }
    
    let paginationHTML = `
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Showing page ${pagination.current_page} of ${pagination.total_pages} 
                (${pagination.total_count} total ticket${pagination.total_count !== 1 ? 's' : ''})
            </div>
            <div class="flex space-x-2">
    `;
    
    // Previous button
    if (pagination.has_prev) {
        paginationHTML += `
            <button onclick="loadTickets(${pagination.current_page - 1})" 
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Previous
            </button>
        `;
    }
    
    // Page numbers
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <button onclick="loadTickets(${i})" 
                    class="px-3 py-2 text-sm font-medium ${i === pagination.current_page ? 
                        'text-blue-600 bg-blue-50 border border-blue-300' : 
                        'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'
                    } rounded-md">
                ${i}
            </button>
        `;
    }
    
    // Next button
    if (pagination.has_next) {
        paginationHTML += `
            <button onclick="loadTickets(${pagination.current_page + 1})" 
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Next
            </button>
        `;
    }
    
    paginationHTML += `
            </div>
        </div>
    `;
    
    container.innerHTML = paginationHTML;
}

// Filter functions
function applyFilters() {
    currentFilters = {
        status: document.getElementById('statusFilter').value,
        priority: document.getElementById('priorityFilter').value,
        category: document.getElementById('categoryFilter').value,
        search: document.getElementById('searchFilter').value
    };
    
    currentPage = 1;
    loadTickets(currentPage);
}

function refreshTickets() {
    loadTickets(currentPage);
}

function viewTicket(ticketId) {
    window.location.href = `ticket_view.php?id=${ticketId}`;
}

// Initialize dashboard
function initializeDashboard() {
    console.log('Dashboard initialized');
}

// Export functions for use in HTML
window.loadTickets = loadTickets;
window.applyFilters = applyFilters;
window.refreshTickets = refreshTickets;
window.viewTicket = viewTicket;
window.initializeDashboard = initializeDashboard;