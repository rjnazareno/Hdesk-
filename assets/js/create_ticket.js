/**
 * Create Ticket JavaScript functionality
 * Handles form submission and file uploads
 */

let selectedFiles = [];

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

// Initialize form
function initializeForm() {
    const form = document.getElementById('createTicketForm');
    const dropZone = document.getElementById('dropZone');
    const attachmentsInput = document.getElementById('attachments');
    
    // Form submission
    form.addEventListener('submit', handleFormSubmit);
    
    // File upload handling
    dropZone.addEventListener('click', () => attachmentsInput.click());
    dropZone.addEventListener('dragover', handleDragOver);
    dropZone.addEventListener('dragleave', handleDragLeave);
    dropZone.addEventListener('drop', handleDrop);
    
    attachmentsInput.addEventListener('change', handleFileSelect);
}

function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.classList.add('border-blue-500', 'bg-blue-50');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.classList.remove('border-blue-500', 'bg-blue-50');
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.classList.remove('border-blue-500', 'bg-blue-50');
    
    const files = Array.from(e.dataTransfer.files);
    addFiles(files);
}

function handleFileSelect(e) {
    const files = Array.from(e.target.files);
    addFiles(files);
}

function addFiles(files) {
    const allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    files.forEach(file => {
        // Check file type
        const extension = file.name.split('.').pop().toLowerCase();
        if (!allowedTypes.includes(extension)) {
            showNotification(`File type not allowed: ${file.name}`, 'error');
            return;
        }
        
        // Check file size
        if (file.size > maxSize) {
            showNotification(`File too large: ${file.name} (max 10MB)`, 'error');
            return;
        }
        
        // Check if file already selected
        if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
            showNotification(`File already selected: ${file.name}`, 'error');
            return;
        }
        
        selectedFiles.push(file);
    });
    
    updateFileList();
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    updateFileList();
}

function updateFileList() {
    const fileList = document.getElementById('fileList');
    
    if (selectedFiles.length === 0) {
        fileList.classList.add('hidden');
        return;
    }
    
    fileList.classList.remove('hidden');
    fileList.innerHTML = `
        <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h4>
        <div class="space-y-2">
            ${selectedFiles.map((file, index) => `
                <div class="flex items-center justify-between bg-gray-100 p-2 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-file text-gray-400 mr-2"></i>
                        <span class="text-sm text-gray-700">${file.name}</span>
                        <span class="text-xs text-gray-500 ml-2">(${formatFileSize(file.size)})</span>
                    </div>
                    <button type="button" 
                            onclick="removeFile(${index})"
                            class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('')}
        </div>
    `;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    try {
        showLoading();
        
        // Get form data
        const formData = new FormData();
        const form = e.target;
        
        // Add form fields
        formData.append('csrf_token', form.csrf_token.value);
        formData.append('subject', form.subject.value.trim());
        formData.append('description', form.description.value.trim());
        formData.append('category', form.category.value);
        formData.append('priority', form.priority.value);
        
        // Validate required fields
        if (!formData.get('subject')) {
            throw new Error('Subject is required');
        }
        if (!formData.get('description')) {
            throw new Error('Description is required');
        }
        if (!formData.get('category')) {
            throw new Error('Category is required');
        }
        if (!formData.get('priority')) {
            throw new Error('Priority is required');
        }
        
        // Add files
        selectedFiles.forEach((file, index) => {
            formData.append('attachments[]', file);
        });
        
        // Submit form
        const response = await fetch('api/create_ticket.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to create ticket');
        }
        
        // Show success modal
        showSuccessModal(data.ticket_id, data.attachments);
        
    } catch (error) {
        console.error('Create ticket error:', error);
        showNotification('Failed to create ticket: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function showSuccessModal(ticketId, attachments) {
    const modal = document.getElementById('successModal');
    const ticketIdSpan = document.getElementById('ticketId');
    const successMessage = document.getElementById('successMessage');
    
    ticketIdSpan.textContent = `#${ticketId}`;
    
    let message = 'Your support ticket has been submitted successfully and our IT team has been notified.';
    if (attachments && attachments.length > 0) {
        message += ` ${attachments.length} file${attachments.length > 1 ? 's' : ''} uploaded.`;
    }
    
    successMessage.textContent = message;
    modal.classList.remove('hidden');
}

function createAnother() {
    document.getElementById('successModal').classList.add('hidden');
    resetForm();
}

function resetForm() {
    const form = document.getElementById('createTicketForm');
    form.reset();
    
    // Reset priority to medium
    document.getElementById('priority').value = 'medium';
    
    // Clear selected files
    selectedFiles = [];
    updateFileList();
    
    // Reset character count
    document.getElementById('descriptionCount').textContent = '0';
    document.getElementById('descriptionCount').classList.remove('text-red-600');
    
    // Focus on subject field
    document.getElementById('subject').focus();
}

// Character counter for description
function updateCharacterCount() {
    const textarea = document.getElementById('description');
    const counter = document.getElementById('descriptionCount');
    const count = textarea.value.length;
    
    counter.textContent = count;
    
    if (count > 4500) {
        counter.classList.add('text-red-600');
    } else {
        counter.classList.remove('text-red-600');
    }
}

// Export functions for use in HTML
window.initializeForm = initializeForm;
window.removeFile = removeFile;
window.createAnother = createAnother;
window.resetForm = resetForm;
window.updateCharacterCount = updateCharacterCount;