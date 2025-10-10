/**
 * Filters System JavaScript
 * Handles filter panel, date range, priority, and status filtering
 */

(function() {
    'use strict';
    
    let filtersOpen = false;
    let currentFilters = {
        dateRange: 'all',
        priority: 'all',
        status: 'all',
        search: ''
    };
    
    /**
     * Initialize filters system
     */
    function initFilters() {
        // Load saved filters from localStorage
        loadSavedFilters();
        
        // Create filter panel
        createFilterPanel();
        
        // Attach event listeners
        attachEventListeners();
        
        // Apply initial filters
        applyFilters();
    }
    
    /**
     * Load saved filters from localStorage
     */
    function loadSavedFilters() {
        const saved = localStorage.getItem('ticketFilters');
        if (saved) {
            try {
                currentFilters = JSON.parse(saved);
            } catch (e) {
                console.error('Error loading saved filters:', e);
            }
        }
    }
    
    /**
     * Save filters to localStorage
     */
    function saveFilters() {
        localStorage.setItem('ticketFilters', JSON.stringify(currentFilters));
    }
    
    /**
     * Create filter dropdown HTML (like notifications)
     */
    function createFilterPanel() {
        const existingPanel = document.getElementById('filter-panel');
        if (existingPanel) return;
        
        // Find the filter button
        let filterButton = document.querySelector('[title="Filters"]');
        if (!filterButton) {
            const slidersIcon = document.querySelector('.fa-sliders');
            if (slidersIcon) {
                filterButton = slidersIcon.closest('button');
            }
        }
        
        if (!filterButton) return;
        
        const panel = document.createElement('div');
        panel.id = 'filter-panel';
        panel.className = 'w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 hidden';
        panel.style.position = 'fixed';
        panel.style.zIndex = '9999';
        panel.style.maxHeight = '500px';
        panel.style.overflowY = 'auto';
        
        panel.innerHTML = `
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-filter mr-2"></i> Quick Filters
                    </h3>
                    <button id="clear-filters-btn" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Clear all filters">
                        <i class="fas fa-times"></i> Clear All
                    </button>
                </div>
            </div>
            
            <div class="p-4 space-y-4">
                <!-- Search -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-search"></i> Search
                    </label>
                    <input 
                        type="text" 
                        id="filter-search" 
                        placeholder="Ticket ID, title, employee..." 
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    />
                </div>
                
                <!-- Date Range -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="far fa-calendar"></i> Date Range
                    </label>
                    <select id="filter-date-range" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="this_week">This Week</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                    </select>
                </div>
                
                <!-- Priority -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-exclamation-circle"></i> Priority
                    </label>
                    <select id="filter-priority" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="all">All Priorities</option>
                        <option value="urgent">🔴 Urgent</option>
                        <option value="high">🟠 High</option>
                        <option value="medium">🟡 Medium</option>
                        <option value="low">🟢 Low</option>
                    </select>
                </div>
                
                <!-- Hidden Status Filter (controlled by stat boxes) -->
                <select id="filter-status" class="hidden">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            
            <!-- Active Filters Display -->
            <div id="active-filters" class="px-4 pb-4 pt-2 border-t border-gray-200 dark:border-gray-700 hidden">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Active Filters:</div>
                <div class="flex flex-wrap gap-2">
                    <!-- Active filter tags will appear here -->
                </div>
            </div>
        `;
        
        // Append to body for fixed positioning
        document.body.appendChild(panel);
    }
    
    /**
     * Attach event listeners
     */
    function attachEventListeners() {
        // Filters button click - try multiple selectors
        let filterButton = document.querySelector('[title="Filters"]');
        
        // If not found, try via sliders icon
        if (!filterButton) {
            const slidersIcon = document.querySelector('.fa-sliders');
            if (slidersIcon) {
                filterButton = slidersIcon.closest('button');
            }
        }
        
        if (filterButton) {
            filterButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleFilters();
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const panel = document.getElementById('filter-panel');
            const filterButton = document.querySelector('[title="Filters"]') || 
                                document.querySelector('.fa-sliders')?.closest('button');
            
            if (filtersOpen && panel && !panel.contains(e.target) && 
                (!filterButton || !filterButton.contains(e.target))) {
                closeFilters();
            }
        });
        
        // Filter change events
        const dateRange = document.getElementById('filter-date-range');
        const priority = document.getElementById('filter-priority');
        const status = document.getElementById('filter-status');
        const search = document.getElementById('filter-search');
        
        if (dateRange) {
            dateRange.value = currentFilters.dateRange;
            dateRange.addEventListener('change', function() {
                currentFilters.dateRange = this.value;
                saveFilters();
                applyFilters();
            });
        }
        
        if (priority) {
            priority.value = currentFilters.priority;
            priority.addEventListener('change', function() {
                currentFilters.priority = this.value;
                saveFilters();
                applyFilters();
            });
        }
        
        if (status) {
            status.value = currentFilters.status;
            status.addEventListener('change', function() {
                currentFilters.status = this.value;
                saveFilters();
                applyFilters();
            });
        }
        
        if (search) {
            search.value = currentFilters.search;
            let searchTimeout;
            search.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentFilters.search = this.value;
                    saveFilters();
                    applyFilters();
                }, 300);
            });
        }
        
        // Clear filters button
        const clearBtn = document.getElementById('clear-filters-btn');
        if (clearBtn) {
            clearBtn.addEventListener('click', clearFilters);
        }
    }
    
    /**
     * Toggle filter dropdown
     */
    function toggleFilters() {
        let panel = document.getElementById('filter-panel');
        
        if (!panel) {
            createFilterPanel();
            panel = document.getElementById('filter-panel');
        }
        
        if (!panel) return;
        
        if (filtersOpen) {
            closeFilters();
        } else {
            openFilters();
        }
    }
    
    /**
     * Open filters dropdown
     */
    function openFilters() {
        const panel = document.getElementById('filter-panel');
        if (!panel) return;
        
        // Position dropdown relative to filter button
        const filterButton = document.querySelector('[title="Filters"]') || 
                            document.querySelector('.fa-sliders')?.closest('button');
        
        if (filterButton) {
            const rect = filterButton.getBoundingClientRect();
            panel.style.position = 'fixed';
            panel.style.top = (rect.bottom + 8) + 'px';
            panel.style.right = (window.innerWidth - rect.right) + 'px';
            panel.style.left = 'auto';
        }
        
        panel.classList.remove('hidden');
        filtersOpen = true;
    }
    
    /**
     * Close filters dropdown
     */
    function closeFilters() {
        const panel = document.getElementById('filter-panel');
        if (panel) {
            panel.classList.add('hidden');
            filtersOpen = false;
        }
    }
    
    /**
     * Apply filters to tickets
     */
    function applyFilters() {
        const tickets = document.querySelectorAll('[data-ticket-row]');
        let visibleCount = 0;
        
        tickets.forEach(ticket => {
            const shouldShow = checkTicket(ticket);
            
            if (shouldShow) {
                ticket.style.display = '';
                visibleCount++;
            } else {
                ticket.style.display = 'none';
            }
        });
        
        // Update active filters display
        updateActiveFiltersDisplay();
        
        // Show "no results" message if needed
        updateNoResultsMessage(visibleCount);
    }
    
    /**
     * Check if ticket should be shown based on filters
     */
    function checkTicket(ticket) {
        // Date range check
        if (currentFilters.dateRange !== 'all') {
            const dateStr = ticket.getAttribute('data-ticket-date');
            if (dateStr && !isInDateRange(dateStr, currentFilters.dateRange)) {
                return false;
            }
        }
        
        // Priority check
        if (currentFilters.priority !== 'all') {
            const priority = ticket.getAttribute('data-ticket-priority');
            if (priority && priority.toLowerCase() !== currentFilters.priority) {
                return false;
            }
        }
        
        // Status check
        if (currentFilters.status !== 'all') {
            const status = ticket.getAttribute('data-ticket-status');
            if (status && status.toLowerCase() !== currentFilters.status) {
                return false;
            }
        }
        
        // Search check
        if (currentFilters.search) {
            const text = ticket.textContent.toLowerCase();
            if (!text.includes(currentFilters.search.toLowerCase())) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if date is in range
     */
    function isInDateRange(dateStr, range) {
        const date = new Date(dateStr);
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        
        switch (range) {
            case 'today':
                return date >= now;
                
            case 'yesterday':
                const yesterday = new Date(now);
                yesterday.setDate(yesterday.getDate() - 1);
                return date >= yesterday && date < now;
                
            case 'this_week':
                const weekStart = new Date(now);
                weekStart.setDate(weekStart.getDate() - weekStart.getDay());
                return date >= weekStart;
                
            case 'last_week':
                const lastWeekStart = new Date(now);
                lastWeekStart.setDate(lastWeekStart.getDate() - lastWeekStart.getDay() - 7);
                const lastWeekEnd = new Date(lastWeekStart);
                lastWeekEnd.setDate(lastWeekEnd.getDate() + 7);
                return date >= lastWeekStart && date < lastWeekEnd;
                
            case 'this_month':
                return date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear();
                
            case 'last_month':
                const lastMonth = new Date(now);
                lastMonth.setMonth(lastMonth.getMonth() - 1);
                return date.getMonth() === lastMonth.getMonth() && date.getFullYear() === lastMonth.getFullYear();
                
            case 'this_year':
                return date.getFullYear() === now.getFullYear();
                
            default:
                return true;
        }
    }
    
    /**
     * Update active filters display
     */
    function updateActiveFiltersDisplay() {
        const container = document.getElementById('active-filters');
        if (!container) return;
        
        const tags = [];
        
        if (currentFilters.dateRange !== 'all') {
            tags.push({
                label: `Date: ${formatDateRangeLabel(currentFilters.dateRange)}`,
                filter: 'dateRange'
            });
        }
        
        if (currentFilters.priority !== 'all') {
            tags.push({
                label: `Priority: ${currentFilters.priority}`,
                filter: 'priority'
            });
        }
        
        if (currentFilters.status !== 'all') {
            tags.push({
                label: `Status: ${currentFilters.status}`,
                filter: 'status'
            });
        }
        
        if (currentFilters.search) {
            tags.push({
                label: `Search: "${currentFilters.search}"`,
                filter: 'search'
            });
        }
        
        if (tags.length > 0) {
            container.classList.remove('hidden');
            container.innerHTML = tags.map(tag => `
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    ${tag.label}
                    <button onclick="window.TicketFilters.removeFilter('${tag.filter}')" class="ml-2 hover:text-blue-600">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
            `).join('');
        } else {
            container.classList.add('hidden');
        }
    }
    
    /**
     * Format date range label
     */
    function formatDateRangeLabel(range) {
        const labels = {
            'today': 'Today',
            'yesterday': 'Yesterday',
            'this_week': 'This Week',
            'last_week': 'Last Week',
            'this_month': 'This Month',
            'last_month': 'Last Month',
            'this_year': 'This Year'
        };
        return labels[range] || range;
    }
    
    /**
     * Update no results message
     */
    function updateNoResultsMessage(visibleCount) {
        let noResults = document.getElementById('no-results-message');
        
        if (visibleCount === 0) {
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.id = 'no-results-message';
                noResults.className = 'text-center py-12 text-gray-500 dark:text-gray-400';
                noResults.innerHTML = `
                    <i class="fas fa-search text-4xl mb-3"></i>
                    <p class="text-lg font-medium">No tickets found</p>
                    <p class="text-sm mt-2">Try adjusting your filters</p>
                `;
                
                const ticketsSection = document.querySelector('.bg-white.rounded-lg.shadow-sm');
                if (ticketsSection) {
                    ticketsSection.appendChild(noResults);
                }
            }
        } else {
            if (noResults) {
                noResults.remove();
            }
        }
    }
    
    /**
     * Clear all filters
     */
    function clearFilters() {
        currentFilters = {
            dateRange: 'all',
            priority: 'all',
            status: 'all',
            search: ''
        };
        
        // Reset form fields
        const dateRange = document.getElementById('filter-date-range');
        const priority = document.getElementById('filter-priority');
        const status = document.getElementById('filter-status');
        const search = document.getElementById('filter-search');
        
        if (dateRange) dateRange.value = 'all';
        if (priority) priority.value = 'all';
        if (status) status.value = 'all';
        if (search) search.value = '';
        
        saveFilters();
        applyFilters();
        
        if (window.showToast) {
            showToast('Filters cleared', 'info');
        }
    }
    
    /**
     * Remove single filter
     */
    function removeFilter(filterName) {
        switch (filterName) {
            case 'dateRange':
                currentFilters.dateRange = 'all';
                document.getElementById('filter-date-range').value = 'all';
                break;
            case 'priority':
                currentFilters.priority = 'all';
                document.getElementById('filter-priority').value = 'all';
                break;
            case 'status':
                currentFilters.status = 'all';
                document.getElementById('filter-status').value = 'all';
                break;
            case 'search':
                currentFilters.search = '';
                document.getElementById('filter-search').value = '';
                break;
        }
        
        saveFilters();
        applyFilters();
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for all elements to be rendered
            setTimeout(initFilters, 300);
        });
    } else {
        // DOM already loaded, wait a bit for dynamic elements
        setTimeout(initFilters, 300);
    }
    
    /**
     * Set status filter programmatically (for stat box clicks)
     */
    function setStatusFilter(status) {
        currentFilters.status = status;
        
        // Update the dropdown if it exists
        const statusSelect = document.getElementById('filter-status');
        if (statusSelect) {
            statusSelect.value = status;
        }
        
        // Save and apply
        saveFilters();
        applyFilters();
        updateActiveFiltersDisplay();
    }
    
    // Export functions for external use
    window.TicketFilters = {
        apply: applyFilters,
        clear: clearFilters,
        removeFilter: removeFilter,
        toggle: toggleFilters,
        setStatus: setStatusFilter,  // Allow external status setting
        init: initFilters  // Allow manual initialization
    };
})();
