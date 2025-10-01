/**
 * Ticket View JavaScript functionality
 * Handles ticket display, responses, and IT staff actions
 */

// Global variables
let refreshInterval;

// Utility functions
function showLoading(text = 'Loading...') {
    const overlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');
    if (loadingText) loadingText.textContent = text;
    overlay.classList.remove('hidden');
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

// Load ticket data
async function loadTicket() {
    try {
        showLoading('Loading ticket details...');
        
        const response = await fetch(`api/view_ticket.php?id=${ticketId}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load ticket');
        }
        
        currentTicket = data.ticket;
        displayTicket(data.ticket);
        
        // Hide loading and show content
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('ticketContent').classList.remove('hidden');
        
        // Setup auto-refresh for responses
        setupAutoRefresh();
        
    } catch (error) {
        console.error('Load ticket error:', error);
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('errorState').classList.remove('hidden');
        document.getElementById('errorMessage').textContent = error.message;
    } finally {
        hideLoading();
    }
}

function displayTicket(ticket) {
    // Update header
    document.getElementById('ticketSubject').textContent = ticket.subject;
    document.getElementById('ticketCreatedBy').textContent = ticket.employee.name;
    document.getElementById('ticketCreatedAt').textContent = formatDate(ticket.created_at);
    
    // Update badges
    const badgesContainer = document.getElementById('ticketBadges');
    badgesContainer.innerHTML = `
        ${getStatusBadge(ticket.status)}
        ${getPriorityBadge(ticket.priority)}
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            ${ticket.category}
        </span>
    `;
    
    // Update description
    document.getElementById('ticketDescription').textContent = ticket.description;
    
    // Update sidebar info
    document.getElementById('sidebarStatus').innerHTML = getStatusBadge(ticket.status);
    document.getElementById('sidebarPriority').innerHTML = getPriorityBadge(ticket.priority);
    document.getElementById('sidebarCategory').textContent = ticket.category;
    document.getElementById('sidebarUpdated').textContent = formatDate(ticket.updated_at);
    
    if (isITStaff) {
        document.getElementById('sidebarEmployee').textContent = ticket.employee.name;
    }
    
    document.getElementById('sidebarAssigned').textContent = 
        ticket.assigned_staff ? ticket.assigned_staff.name : 'Unassigned';
    
    // Display attachments
    displayAttachments(ticket.attachments);
    
    // Only display responses if we're using AJAX loading (when responsesList container exists)
    // Skip if using PHP-rendered conversation (chatContainer)
    if (document.getElementById('responsesList')) {
        displayResponses(ticket.responses);
    }
    
    // Setup forms
    setupForms();
}

function displayAttachments(attachments) {
    const section = document.getElementById('attachmentsSection');
    const list = document.getElementById('attachmentsList');
    
    if (!attachments || attachments.length === 0) {
        section.style.display = 'none';
        return;
    }
    
    section.style.display = 'block';
    list.innerHTML = attachments.map(attachment => `
        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-md">
            <div class="flex items-center">
                <i class="fas fa-file text-gray-400 mr-3"></i>
                <div>
                    <div class="text-sm font-medium text-gray-900">${attachment.original_name}</div>
                    <div class="text-xs text-gray-500">
                        Uploaded by ${attachment.uploader.name} on ${formatDate(attachment.created_at)}
                    </div>
                </div>
            </div>
            <a href="${attachment.download_url}" 
               class="text-blue-600 hover:text-blue-700"
               title="Download">
                <i class="fas fa-download"></i>
            </a>
        </div>
    `).join('');
}

function displayResponses(responses) {
    const container = document.getElementById('responsesList');
    
    if (!responses || responses.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-comments text-4xl mb-4"></i>
                <p>No responses yet. Be the first to respond!</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = responses.map((response, index) => {
        const isEmployee = response.responder.type === 'employee';
        const isInternal = response.is_internal;
        
        return `
            <div class="flex ${isEmployee ? 'justify-end' : 'justify-start'}">
                <div class="max-w-3xl w-full ${isEmployee ? 'ml-12' : 'mr-12'}">
                    <div class="bg-${isEmployee ? 'blue' : 'gray'}-${isInternal ? '50' : '100'} rounded-lg p-4 ${isInternal ? 'border-l-4 border-orange-500' : ''}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <span class="text-sm font-medium text-gray-900">
                                    ${response.responder.name}
                                </span>
                                <span class="text-xs text-gray-500 ml-2">
                                    ${response.responder.type === 'employee' ? 'Employee' : 'IT Staff'}
                                </span>
                                ${isInternal ? '<span class="text-xs text-orange-600 ml-2 font-medium">Internal</span>' : ''}
                            </div>
                            <span class="text-xs text-gray-500">
                                ${formatDate(response.created_at)}
                            </span>
                        </div>
                        <div class="text-gray-700 whitespace-pre-wrap">
                            ${response.message}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function setupForms() {
    // Response form
    const responseForm = document.getElementById('responseForm');
    if (responseForm) {
        responseForm.addEventListener('submit', handleResponseSubmit);
    }
    
    // Attachment form
    const attachmentForm = document.getElementById('attachmentForm');
    if (attachmentForm) {
        attachmentForm.addEventListener('submit', handleAttachmentSubmit);
    }
    
    // Hide response form for closed tickets
    if (currentTicket.status === 'closed') {
        const responseFormSection = document.getElementById('responseFormSection');
        if (responseFormSection) {
            responseFormSection.style.display = 'none';
        }
    }
}

async function handleResponseSubmit(e) {
    e.preventDefault();
    
    try {
        showLoading('Adding response...');
        
        const formData = new FormData(e.target);
        const message = formData.get('message').trim();
        
        if (!message) {
            throw new Error('Response message cannot be empty');
        }
        
        const response = await fetch('api/add_response.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to add response');
        }
        
        showNotification('Response added successfully', 'success');
        
        // Clear form
        document.getElementById('responseMessage').value = '';
        
        // Reload ticket to show new response
        await loadTicket();
        
    } catch (error) {
        console.error('Add response error:', error);
        showNotification('Failed to add response: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function handleAttachmentSubmit(e) {
    e.preventDefault();
    
    try {
        const fileInput = document.getElementById('attachmentFile');
        if (!fileInput.files || fileInput.files.length === 0) {
            throw new Error('Please select a file to upload');
        }
        
        showLoading('Uploading file...');
        
        const formData = new FormData(e.target);
        
        const response = await fetch('api/upload_attachment.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to upload file');
        }
        
        showNotification('File uploaded successfully', 'success');
        
        // Clear form
        fileInput.value = '';
        
        // Reload ticket to show new attachment
        await loadTicket();
        
    } catch (error) {
        console.error('Upload error:', error);
        showNotification('Failed to upload file: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// IT Staff functions
function showAssignModal() {
    // Create and show assign modal
    const modal = createModal('Assign Ticket', `
        <form id="assignForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="ticket_id" value="${ticketId}">
            <input type="hidden" name="action" value="assign">
            
            <div class="mb-4">
                <label for="assign_to" class="block text-sm font-medium text-gray-700 mb-2">
                    Assign to IT Staff:
                </label>
                <select id="assign_to" name="assign_to" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">Select staff member</option>
                    <option value="0">Unassign</option>
                    <!-- IT staff options would be loaded via AJAX in real implementation -->
                </select>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Assign
                </button>
                <button type="button" onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md">
                    Cancel
                </button>
            </div>
        </form>
    `);
    
    // Handle form submission
    document.getElementById('assignForm').addEventListener('submit', handleITAction);
}

function showStatusModal() {
    const modal = createModal('Update Status', `
        <form id="statusForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="ticket_id" value="${ticketId}">
            <input type="hidden" name="action" value="update_status">
            
            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    New Status:
                </label>
                <select id="status" name="status" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">Select status</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                    Update
                </button>
                <button type="button" onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md">
                    Cancel
                </button>
            </div>
        </form>
    `);
    
    document.getElementById('statusForm').addEventListener('submit', handleITAction);
}

function showResolveModal() {
    const modal = createModal('Resolve Ticket', `
        <form id="resolveForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="ticket_id" value="${ticketId}">
            <input type="hidden" name="action" value="resolve">
            
            <div class="mb-4">
                <label for="resolution" class="block text-sm font-medium text-gray-700 mb-2">
                    Resolution Details:
                </label>
                <textarea id="resolution" name="resolution" required rows="4" 
                          class="w-full border border-gray-300 rounded-md px-3 py-2"
                          placeholder="Describe how the issue was resolved..."></textarea>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md">
                    Mark as Resolved
                </button>
                <button type="button" onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md">
                    Cancel
                </button>
            </div>
        </form>
    `);
    
    document.getElementById('resolveForm').addEventListener('submit', handleITAction);
}

function showCloseModal() {
    const modal = createModal('Close Ticket', `
        <form id="closeForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="ticket_id" value="${ticketId}">
            <input type="hidden" name="action" value="close">
            
            <div class="mb-4">
                <label for="resolution" class="block text-sm font-medium text-gray-700 mb-2">
                    Closing Notes (Optional):
                </label>
                <textarea id="resolution" name="resolution" rows="3" 
                          class="w-full border border-gray-300 rounded-md px-3 py-2"
                          placeholder="Any additional notes..."></textarea>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-md mb-4">
                <p class="text-yellow-800 text-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Closing this ticket will prevent further responses.
                </p>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Close Ticket
                </button>
                <button type="button" onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md">
                    Cancel
                </button>
            </div>
        </form>
    `);
    
    document.getElementById('closeForm').addEventListener('submit', handleITAction);
}

async function handleITAction(e) {
    e.preventDefault();
    
    try {
        showLoading('Processing...');
        
        const formData = new FormData(e.target);
        
        const response = await fetch('api/resolve_ticket.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Action failed');
        }
        
        showNotification(data.message, 'success');
        closeModal();
        
        // Reload ticket to show changes
        await loadTicket();
        
    } catch (error) {
        console.error('IT action error:', error);
        showNotification('Action failed: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function createModal(title, content) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.id = 'activeModal';
    
    modal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">${title}</h3>
            ${content}
        </div>
    `;
    
    document.getElementById('modalContainer').appendChild(modal);
    return modal;
}

function closeModal() {
    const modal = document.getElementById('activeModal');
    if (modal) {
        modal.remove();
    }
}

function setupAutoRefresh() {
    // Refresh responses every 30 seconds
    refreshInterval = setInterval(() => {
        loadTicket();
    }, 30000);
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

// Export functions for use in HTML
window.loadTicket = loadTicket;
window.showAssignModal = showAssignModal;
window.showStatusModal = showStatusModal;
window.showResolveModal = showResolveModal;
window.showCloseModal = showCloseModal;
window.closeModal = closeModal;