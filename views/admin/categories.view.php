<?php 
// Set page-specific variables
$pageTitle = 'Categories - ' . (defined('APP_NAME') ? APP_NAME : 'ServiceHub');
$baseUrl = '../';

// Include header layout
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-slate-50">
    <?php
    // Set header variables for this page
    $headerTitle = 'Ticket Categories';
    $headerSubtitle = 'Organize and manage ticket categories · ' . count($categories) . ' Parent Categories';
    $showQuickActions = true;
    
    include __DIR__ . '/../../includes/top_header.php';
    ?>

    <!-- Content -->
    <div class="p-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-emerald-600">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-700">Categories</span>
                    </div>
                </li>
            </ol>
        </nav>
        
        <!-- Department Filter -->
        <div class="bg-white border border-slate-200 p-6 mb-6 rounded-lg">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white rounded-lg">
                    <i class="fas fa-filter text-sm"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Filter Categories</h3>
                    <p class="text-sm text-slate-500">Filter by department</p>
                </div>
            </div>
            <form method="GET" action="categories.php" class="flex items-center gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Department
                    </label>
                    <select name="department_id" class="w-full px-4 py-2 border border-gray-300 bg-slate-50 text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition rounded-lg">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $department): ?>
                        <option value="<?php echo $department['id']; ?>" <?php echo ($selectedDepartment == $department['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($department['name']); ?> (<?php echo htmlspecialchars($department['code']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-end gap-3">
                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-emerald-400 to-emerald-600 text-white hover:from-emerald-500 hover:to-emerald-700 transition font-medium rounded-lg shadow-sm">
                        <i class="fas fa-filter mr-2"></i>Apply Filter
                    </button>
                    <?php if ($selectedDepartment): ?>
                    <a href="categories.php" class="inline-flex items-center px-6 py-2 border border-gray-300 text-gray-700 hover:bg-slate-50 transition font-medium rounded-lg">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Category Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($categories as $category): ?>
            <!-- Parent Category Card -->
            <div class="bg-white border border-slate-200 rounded-lg overflow-hidden hover:border-emerald-500/50 transition">
                <div class="p-6 <?php echo !empty($category['children']) ? 'cursor-pointer' : ''; ?>" <?php echo !empty($category['children']) ? 'onclick="toggleCategory(' . $category['id'] . ')"' : ''; ?>>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-10 h-10 flex items-center justify-center rounded-lg" style="background-color: <?php echo $category['color']; ?>20;">
                            <i class="fas fa-<?php echo htmlspecialchars($category['icon'] ?? 'folder'); ?> text-sm" style="color: <?php echo $category['color']; ?>;"></i>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 text-xs font-medium border border-gray-300 text-gray-700 rounded">
                                <?php echo $category['ticket_count']; ?> tickets
                            </span>
                            <?php if (!empty($category['children'])): ?>
                            <i class="fas fa-chevron-down text-slate-400 text-xs transition-transform" id="icon-<?php echo $category['id']; ?>"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <?php if (!empty($category['department_name'])): ?>
                    <p class="text-xs text-slate-500 mb-3"><?php echo htmlspecialchars($category['department_name']); ?></p>
                    <?php endif; ?>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">Open Tickets:</span>
                        <span class="font-semibold text-emerald-600"><?php echo $category['open_tickets']; ?></span>
                    </div>
                </div>
                
                <!-- Sub-categories (Hidden by default) -->
                <?php if (!empty($category['children'])): ?>
                <div id="subcategories-<?php echo $category['id']; ?>" class="hidden border-t border-slate-200 bg-slate-50 p-4 space-y-2">
                    <?php foreach ($category['children'] as $child): ?>
                    <div class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded hover:border-emerald-500/50 transition">
                        <div class="flex items-center gap-2 flex-1">
                            <div class="w-6 h-6 flex items-center justify-center rounded" style="background-color: <?php echo $child['color']; ?>20;">
                                <i class="fas fa-<?php echo htmlspecialchars($child['icon'] ?? 'folder'); ?> text-xs" style="color: <?php echo $child['color']; ?>;"></i>
                            </div>
                            <span class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($child['name']); ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-slate-500"><?php echo $child['ticket_count']; ?></span>
                            <span class="text-emerald-600 font-semibold"><?php echo $child['open_tickets']; ?> open</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Page-specific JavaScript -->
<script>
function toggleCategory(categoryId) {
    const subcategories = document.getElementById('subcategories-' + categoryId);
    const icon = document.getElementById('icon-' + categoryId);
    
    if (subcategories) {
        subcategories.classList.toggle('hidden');
        if (icon) {
            icon.classList.toggle('rotate-180');
        }
    }
}

// Expand/Collapse All
function expandAll() {
    document.querySelectorAll('[id^="subcategories-"]').forEach(el => {
        el.classList.remove('hidden');
    });
    document.querySelectorAll('[id^="icon-"]').forEach(icon => {
        icon.classList.add('rotate-180');
    });
}

function collapseAll() {
    document.querySelectorAll('[id^="subcategories-"]').forEach(el => {
        el.classList.add('hidden');
    });
    document.querySelectorAll('[id^="icon-"]').forEach(icon => {
        icon.classList.remove('rotate-180');
    });
}
</script>

<?php 
// Include footer layout
include __DIR__ . '/../layouts/footer.php'; 
?>


