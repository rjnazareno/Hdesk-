<?php
/**
 * UI Helper Functions
 * Helper functions for better UX
 */

/**
 * Generate breadcrumb navigation
 * @param array $items Array of ['label' => 'Home', 'url' => '/']
 * @return string HTML breadcrumb
 */
function breadcrumb($items) {
    if (empty($items)) return '';
    
    $html = '<nav class="flex mb-4" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">';
    
    $count = count($items);
    foreach ($items as $index => $item) {
        $isLast = ($index === $count - 1);
        
        $html .= '<li class="inline-flex items-center">';
        
        if ($index > 0) {
            $html .= '<svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>';
        }
        
        if ($isLast) {
            $html .= '<span class="ml-1 text-sm font-medium text-gray-700 dark:text-gray-300">' . 
                     htmlspecialchars($item['label']) . '</span>';
        } else {
            $html .= '<a href="' . htmlspecialchars($item['url']) . '" 
                     class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500">';
            
            if ($index === 0) {
                $html .= '<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                </svg>';
            }
            
            $html .= htmlspecialchars($item['label']) . '</a>';
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ol></nav>';
    
    return $html;
}

/**
 * Get priority badge HTML
 * @param string $priority Priority level
 * @return string HTML badge
 */
function priorityBadge($priority) {
    $classes = [
        'low' => 'bg-gray-100 text-gray-800',
        'medium' => 'bg-blue-100 text-blue-800',
        'high' => 'bg-yellow-100 text-yellow-800',
        'critical' => 'bg-red-100 text-red-800'
    ];
    
    $icons = [
        'low' => 'fa-arrow-down',
        'medium' => 'fa-minus',
        'high' => 'fa-arrow-up',
        'critical' => 'fa-exclamation-triangle'
    ];
    
    $class = $classes[$priority] ?? $classes['medium'];
    $icon = $icons[$priority] ?? $icons['medium'];
    
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $class . '">
        <i class="fas ' . $icon . ' mr-1"></i>' . ucfirst($priority) . '
    </span>';
}

/**
 * Get status badge HTML
 * @param string $status Status
 * @return string HTML badge
 */
function statusBadge($status) {
    $classes = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'open' => 'bg-blue-100 text-blue-800',
        'in_progress' => 'bg-purple-100 text-purple-800',
        'resolved' => 'bg-green-100 text-green-800',
        'closed' => 'bg-gray-100 text-gray-800'
    ];
    
    $icons = [
        'pending' => 'fa-clock',
        'open' => 'fa-folder-open',
        'in_progress' => 'fa-spinner',
        'resolved' => 'fa-check-circle',
        'closed' => 'fa-check-double'
    ];
    
    $class = $classes[$status] ?? $classes['open'];
    $icon = $icons[$status] ?? $icons['open'];
    $label = str_replace('_', ' ', ucfirst($status));
    
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $class . '">
        <i class="fas ' . $icon . ' mr-1"></i>' . $label . '
    </span>';
}

/**
 * Format timestamp for display with time-ago class
 * @param string $timestamp Database timestamp
 * @return string HTML span with time-ago class
 */
function timeAgoElement($timestamp) {
    if (empty($timestamp)) return '-';
    
    $formatted = date('M d, Y \a\t g:i A', strtotime($timestamp));
    
    return '<span class="time-ago" data-timestamp="' . $timestamp . '" title="' . $formatted . '">
        ' . $formatted . '
    </span>';
}

/**
 * Get last login display
 * @param string $timestamp Last login timestamp
 * @return string HTML string
 */
function lastLoginDisplay($timestamp) {
    if (empty($timestamp)) {
        return '<span class="text-xs text-gray-500">Never logged in</span>';
    }
    
    return '<span class="text-xs text-gray-500">
        Last login: <span class="time-ago" data-timestamp="' . $timestamp . '">' . 
        date('M d, Y', strtotime($timestamp)) . '</span>
    </span>';
}

/**
 * Add tooltip attribute
 * @param string $text Tooltip text
 * @return string HTML attribute
 */
function tooltip($text) {
    return 'data-tooltip="' . htmlspecialchars($text) . '"';
}

/**
 * Print button HTML
 * @return string HTML button
 */
function printButton() {
    return '<button onclick="window.print()" 
            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition no-print"
            ' . tooltip('Print this ticket') . '>
        <i class="fas fa-print mr-2"></i>Print
    </button>';
}

/**
 * Include necessary scripts and styles
 */
function includeQuickWinsAssets() {
    echo '
    <!-- Quick Wins CSS -->
    <link rel="stylesheet" href="../assets/css/print.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    
    <!-- Quick Wins JavaScript -->
    <script src="../assets/js/helpers.js"></script>
    ';
}
?>
