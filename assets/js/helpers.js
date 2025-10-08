/**
 * ResolveIT Helper Functions
 * Utility functions for better UX
 */

// ============================================
// 1. TIME AGO FORMATTER
// ============================================

/**
 * Convert timestamp to human-readable format
 * Example: "2 hours ago", "3 days ago", "just now"
 */
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 10) return 'just now';
    if (seconds < 60) return `${seconds} seconds ago`;
    
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return minutes === 1 ? '1 minute ago' : `${minutes} minutes ago`;
    
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return hours === 1 ? '1 hour ago' : `${hours} hours ago`;
    
    const days = Math.floor(hours / 24);
    if (days < 7) return days === 1 ? '1 day ago' : `${days} days ago`;
    
    const weeks = Math.floor(days / 7);
    if (weeks < 4) return weeks === 1 ? '1 week ago' : `${weeks} weeks ago`;
    
    const months = Math.floor(days / 30);
    if (months < 12) return months === 1 ? '1 month ago' : `${months} months ago`;
    
    const years = Math.floor(days / 365);
    return years === 1 ? '1 year ago' : `${years} years ago`;
}

/**
 * Update all elements with class 'time-ago' with relative time
 */
function updateTimeAgo() {
    document.querySelectorAll('.time-ago').forEach(element => {
        const timestamp = element.getAttribute('data-timestamp');
        if (timestamp) {
            element.textContent = timeAgo(timestamp);
            element.title = new Date(timestamp).toLocaleString(); // Show full date on hover
        }
    });
}

// Update time ago on page load and every minute
document.addEventListener('DOMContentLoaded', function() {
    updateTimeAgo();
    setInterval(updateTimeAgo, 60000); // Update every minute
});

// ============================================
// 2. TOAST NOTIFICATIONS
// ============================================

/**
 * Show toast notification
 * @param {string} message - The message to display
 * @param {string} type - Type: 'success', 'error', 'warning', 'info'
 * @param {number} duration - How long to show (milliseconds)
 */
function showToast(message, type = 'success', duration = 3000) {
    // Create toast container if it doesn't exist
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast-notification transform translate-x-full transition-transform duration-300 ease-out ${getToastClass(type)}`;
    
    // Toast content
    toast.innerHTML = `
        <div class="flex items-center space-x-3 p-4 rounded-lg shadow-lg max-w-md">
            <span class="text-2xl">${getToastIcon(type)}</span>
            <span class="flex-1 text-sm font-medium">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Slide in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
        toast.classList.add('translate-x-0');
    }, 100);
    
    // Auto remove
    if (duration > 0) {
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
}

function getToastClass(type) {
    const classes = {
        success: 'bg-green-50 text-green-800 border border-green-200',
        error: 'bg-red-50 text-red-800 border border-red-200',
        warning: 'bg-yellow-50 text-yellow-800 border border-yellow-200',
        info: 'bg-blue-50 text-blue-800 border border-blue-200'
    };
    return classes[type] || classes.info;
}

function getToastIcon(type) {
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    return icons[type] || icons.info;
}

// Show toast from URL parameters (for redirects with messages)
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');
    
    if (success) {
        const messages = {
            'created': 'Ticket created successfully!',
            'updated': 'Ticket updated successfully!',
            'commented': 'Comment added successfully!',
            'deleted': 'Deleted successfully!',
            'assigned': 'Ticket assigned successfully!',
            'logout': 'Logged out successfully!'
        };
        showToast(messages[success] || 'Success!', 'success');
    }
    
    if (error) {
        const messages = {
            'invalid': 'Invalid username or password',
            'empty': 'Please fill in all fields',
            'permission': 'You do not have permission',
            'not_found': 'Item not found',
            'session': 'Session error. Please login again.'
        };
        showToast(messages[error] || 'An error occurred', 'error');
    }
});

// ============================================
// 3. LOADING SPINNER
// ============================================

/**
 * Show loading spinner overlay
 */
function showLoading(message = 'Loading...') {
    let overlay = document.getElementById('loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
        overlay.innerHTML = `
            <div class="bg-white rounded-lg p-6 text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent mb-3"></div>
                <p class="text-gray-700 font-medium" id="loading-message">${message}</p>
            </div>
        `;
        document.body.appendChild(overlay);
    } else {
        overlay.style.display = 'flex';
        document.getElementById('loading-message').textContent = message;
    }
}

/**
 * Hide loading spinner
 */
function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Show loading on form submit
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form[data-loading]').forEach(form => {
        form.addEventListener('submit', function() {
            const message = this.getAttribute('data-loading') || 'Processing...';
            showLoading(message);
        });
    });
    
    // Show loading on page navigation
    document.querySelectorAll('a[data-loading]').forEach(link => {
        link.addEventListener('click', function(e) {
            const message = this.getAttribute('data-loading') || 'Loading...';
            showLoading(message);
        });
    });
});

// ============================================
// 4. TOOLTIPS
// ============================================

/**
 * Initialize tooltips for elements with data-tooltip attribute
 */
function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const text = this.getAttribute('data-tooltip');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip-popup fixed bg-gray-900 text-white text-xs px-3 py-2 rounded shadow-lg z-50 pointer-events-none';
            tooltip.textContent = text;
            tooltip.id = 'active-tooltip';
            document.body.appendChild(tooltip);
            
            // Position tooltip
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
            
            // Fade in
            setTimeout(() => tooltip.style.opacity = '1', 10);
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltip = document.getElementById('active-tooltip');
            if (tooltip) {
                tooltip.style.opacity = '0';
                setTimeout(() => tooltip.remove(), 200);
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', initTooltips);

// ============================================
// 5. PRINT TICKET
// ============================================

/**
 * Print current ticket
 */
function printTicket() {
    window.print();
}

// ============================================
// 6. DARK MODE TOGGLE
// ============================================

/**
 * Toggle dark mode
 */
function toggleDarkMode() {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark');
    
    if (isDark) {
        html.classList.remove('dark');
        localStorage.setItem('darkMode', 'false');
    } else {
        html.classList.add('dark');
        localStorage.setItem('darkMode', 'true');
    }
    
    // Update toggle button icon
    updateDarkModeIcon();
}

/**
 * Update dark mode toggle icon
 */
function updateDarkModeIcon() {
    const icon = document.getElementById('dark-mode-icon');
    if (icon) {
        const isDark = document.documentElement.classList.contains('dark');
        icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    }
}

/**
 * Initialize dark mode from localStorage
 */
function initDarkMode() {
    const darkMode = localStorage.getItem('darkMode');
    if (darkMode === 'true') {
        document.documentElement.classList.add('dark');
    }
    updateDarkModeIcon();
    
    // Attach click handler to dark mode toggle
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', toggleDarkMode);
    }
}

/**
 * Update last login display
 */
function updateLastLogin(timestamp) {
    const display = document.getElementById('lastLoginDisplay');
    if (!display || !timestamp) return;
    
    const loginDate = new Date(timestamp);
    const now = new Date();
    const isToday = loginDate.toDateString() === now.toDateString();
    
    let displayText = '';
    if (isToday) {
        displayText = `Last login: Today at ${loginDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`;
    } else {
        displayText = `Last login: ${loginDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} at ${loginDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`;
    }
    
    display.textContent = displayText;
    display.className = 'text-xs text-gray-500';
}

document.addEventListener('DOMContentLoaded', initDarkMode);

// ============================================
// 7. CONFIRMATION DIALOGS
// ============================================

/**
 * Show confirmation dialog
 */
function confirmAction(message, onConfirm) {
    if (confirm(message)) {
        onConfirm();
    }
}

// ============================================
// 8. UTILITY FUNCTIONS
// ============================================

/**
 * Format file size
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success', 2000);
    }).catch(() => {
        showToast('Failed to copy', 'error', 2000);
    });
}

// ============================================
// GLOBAL EXPORTS
// ============================================

window.ResolveIT = {
    timeAgo,
    updateTimeAgo,
    updateLastLogin,
    showToast,
    showLoading,
    hideLoading,
    initTooltips,
    printTicket,
    toggleDarkMode,
    initDarkMode,
    confirmAction,
    formatFileSize,
    copyToClipboard
};
