/**
 * Admin Dashboard JavaScript
 * Handles charts, interactions, and real-time updates
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize Charts
    initDailyTicketsChart();
    
    // Initialize interactions
    initSidebarToggle();
    initSearchFilter();
    initNotifications();
    
    // Start real-time updates
    startRealtimeUpdates();
    
    console.log('✅ Admin Dashboard Initialized');
});

/**
 * Daily Tickets Bar Chart
 */
function initDailyTicketsChart() {
    const ctx = document.getElementById('dailyTicketsChart');
    if (!ctx) return;
    
    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.8)');
    gradient.addColorStop(1, 'rgba(168, 85, 247, 0.8)');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData?.labels || ['Jul 07', 'Jul 08', 'Jul 09', 'Jul 10', 'Jul 11', 'Jul 12', 'Jul 13', 'Jul 14', 'Jul 15', 'Jul 16'],
            datasets: [{
                label: 'Tickets',
                data: chartData?.values || [12, 15, 8, 22, 18, 38, 25, 20, 15, 10],
                backgroundColor: gradient,
                borderRadius: 8,
                borderSkipped: false,
                barThickness: 24,
                hoverBackgroundColor: 'rgba(59, 130, 246, 1)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#F1F5F9',
                    bodyColor: '#CBD5E1',
                    borderColor: 'rgba(59, 130, 246, 0.5)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'Tickets: ' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(51, 65, 85, 0.3)',
                        drawBorder: false,
                    },
                    ticks: {
                        color: '#94A3B8',
                        font: {
                            size: 11
                        },
                        padding: 8
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false,
                    },
                    ticks: {
                        color: '#94A3B8',
                        font: {
                            size: 11
                        },
                        padding: 8
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            }
        }
    });
}

/**
 * Sidebar Toggle for Mobile
 */
function initSidebarToggle() {
    // Create mobile toggle button if it doesn't exist
    const header = document.querySelector('header');
    if (window.innerWidth <= 768 && header) {
        const toggleBtn = document.createElement('button');
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.className = 'lg:hidden bg-gray-700/50 hover:bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 transition-colors';
        toggleBtn.onclick = function() {
            document.getElementById('sidebar').classList.toggle('open');
        };
        header.querySelector('div').prepend(toggleBtn);
    }
}

/**
 * Search Filter Functionality
 */
function initSearchFilter() {
    const searchInput = document.querySelector('input[placeholder="Search Dashboard"]');
    if (!searchInput) return;
    
    let searchTimeout;
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.toLowerCase();
        
        searchTimeout = setTimeout(() => {
            filterTableRows(query);
        }, 300);
    });
}

/**
 * Filter Table Rows
 */
function filterTableRows(query) {
    const tableRows = document.querySelectorAll('tbody tr');
    let visibleCount = 0;
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
            row.style.display = '';
            visibleCount++;
            // Fade in animation
            row.style.animation = 'fadeIn 0.3s ease';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show "no results" message if needed
    if (visibleCount === 0 && query !== '') {
        showNoResultsMessage();
    } else {
        hideNoResultsMessage();
    }
}

/**
 * Show No Results Message
 */
function showNoResultsMessage() {
    const tbody = document.querySelector('tbody');
    if (!tbody || tbody.querySelector('.no-results')) return;
    
    const tr = document.createElement('tr');
    tr.className = 'no-results';
    tr.innerHTML = `
        <td colspan="5" class="text-center py-8">
            <i class="fas fa-search text-gray-600 text-3xl mb-3"></i>
            <p class="text-gray-500">No results found</p>
        </td>
    `;
    tbody.appendChild(tr);
}

/**
 * Hide No Results Message
 */
function hideNoResultsMessage() {
    const noResults = document.querySelector('.no-results');
    if (noResults) {
        noResults.remove();
    }
}

/**
 * Notifications System
 */
function initNotifications() {
    const notificationBtn = document.querySelector('[data-notifications]') || 
                           document.querySelector('.fa-bell')?.closest('button');
    
    if (!notificationBtn) return;
    
    notificationBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        showNotificationDropdown();
    });
}

/**
 * Show Notification Dropdown
 */
function showNotificationDropdown() {
    // Check if dropdown already exists
    let dropdown = document.getElementById('notificationDropdown');
    
    if (dropdown) {
        dropdown.remove();
        return;
    }
    
    dropdown = document.createElement('div');
    dropdown.id = 'notificationDropdown';
    dropdown.className = 'absolute right-0 top-full mt-2 w-80 bg-gray-800 border border-gray-700 rounded-lg shadow-2xl z-50';
    dropdown.innerHTML = `
        <div class="p-4 border-b border-gray-700">
            <h3 class="font-semibold text-white">Notifications</h3>
            <p class="text-sm text-gray-400">You have 3 unread notifications</p>
        </div>
        <div class="max-h-96 overflow-y-auto">
            <div class="p-4 hover:bg-gray-700/50 cursor-pointer border-b border-gray-800">
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-300">New ticket created</p>
                        <p class="text-xs text-gray-500 mt-1">2 minutes ago</p>
                    </div>
                </div>
            </div>
            <div class="p-4 hover:bg-gray-700/50 cursor-pointer border-b border-gray-800">
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-300">Ticket resolved</p>
                        <p class="text-xs text-gray-500 mt-1">15 minutes ago</p>
                    </div>
                </div>
            </div>
            <div class="p-4 hover:bg-gray-700/50 cursor-pointer">
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full mt-2"></div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-300">New customer registered</p>
                        <p class="text-xs text-gray-500 mt-1">1 hour ago</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-3 border-t border-gray-700 text-center">
            <a href="#" class="text-sm text-blue-400 hover:text-blue-300">View all notifications</a>
        </div>
    `;
    
    const notificationBtn = document.querySelector('.fa-bell')?.closest('button');
    notificationBtn.style.position = 'relative';
    notificationBtn.appendChild(dropdown);
    
    // Close on outside click
    setTimeout(() => {
        document.addEventListener('click', function closeDropdown(e) {
            if (!dropdown.contains(e.target)) {
                dropdown.remove();
                document.removeEventListener('click', closeDropdown);
            }
        });
    }, 100);
}

/**
 * Real-time Updates
 */
function startRealtimeUpdates() {
    // Update stats every 30 seconds
    setInterval(() => {
        updateDashboardStats();
    }, 30000);
    
    // Animate progress bars on load
    animateProgressBars();
}

/**
 * Update Dashboard Stats via AJAX
 */
function updateDashboardStats() {
    fetch('controllers/DashboardController.php?action=getStats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update stat numbers with animation
                updateStatWithAnimation('active_tickets', data.stats.active_tickets);
                updateStatWithAnimation('total_customers', data.stats.total_customers);
                
                // Update progress bars
                updateProgressBar('pending', data.stats.pending_percentage);
                updateProgressBar('open', data.stats.open_percentage);
                updateProgressBar('closed', data.stats.closed_percentage);
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}

/**
 * Update Stat with Count Up Animation
 */
function updateStatWithAnimation(elementId, newValue) {
    const element = document.getElementById(elementId) || 
                   document.querySelector(`[data-stat="${elementId}"]`);
    
    if (!element) return;
    
    const currentValue = parseInt(element.textContent) || 0;
    const duration = 1000;
    const steps = 30;
    const increment = (newValue - currentValue) / steps;
    let step = 0;
    
    const timer = setInterval(() => {
        step++;
        const value = Math.round(currentValue + (increment * step));
        element.textContent = value;
        
        if (step >= steps) {
            element.textContent = newValue;
            clearInterval(timer);
        }
    }, duration / steps);
}

/**
 * Update Progress Bar
 */
function updateProgressBar(status, percentage) {
    const progressFill = document.querySelector(`.progress-fill.bg-${status}-500`);
    if (progressFill) {
        progressFill.style.width = percentage + '%';
    }
    
    const percentageText = progressFill?.closest('.space-y-4')
        ?.querySelector('.flex.items-center.justify-between .text-white');
    if (percentageText) {
        percentageText.textContent = percentage + '%';
    }
}

/**
 * Animate Progress Bars on Load
 */
function animateProgressBars() {
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach((bar, index) => {
        const targetWidth = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.transition = 'width 1.5s ease-out';
            bar.style.width = targetWidth;
        }, index * 200);
    });
}

/**
 * Show Toast Notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 animate-fadeIn
                      ${type === 'success' ? 'bg-green-500' : 
                        type === 'error' ? 'bg-red-500' : 
                        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'}`;
    toast.innerHTML = `
        <div class="flex items-center space-x-3">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 
                               type === 'error' ? 'exclamation-circle' : 
                               type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
            <span class="text-white font-medium">${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Format Number with Commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Debounce Function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export functions for use in other scripts
window.adminDashboard = {
    showToast,
    updateDashboardStats,
    formatNumber
};
