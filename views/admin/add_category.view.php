<?php 
// Include layout header
$pageTitle = 'Add Category - IT Help Desk';
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gray-50">
    <!-- Top Bar -->
    <div class="bg-white border-b border-gray-200">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-gray-900 flex items-center justify-center text-white">
                    <i class="fas fa-folder-plus text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-gray-900">Add New Category</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Create a new ticket category</p>
                </div>
            </div>
            <div class="hidden lg:flex items-center space-x-2">
                <button id="darkModeToggle" class="p-2 text-gray-500 hover:text-gray-900 transition" title="Toggle dark mode">
                    <i id="dark-mode-icon" class="fas fa-moon text-sm"></i>
                </button>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=fff" 
                     alt="User" 
                     class="w-8 h-8 rounded-full"
                     title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
            </div>
        </div>
    </div>

    <!-- Form Content -->
    <div class="p-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="categories.php" class="ml-1 text-sm font-medium text-gray-600 hover:text-gray-900">Categories</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-700">Add Category</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Form Section -->
            <div class="bg-white border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6 pb-3 border-b border-gray-200">
                    <i class="fas fa-folder mr-2"></i>Category Details
                </h2>
                
                <form action="add_category.php" method="POST" class="space-y-6" id="addCategoryForm">
                    
                    <!-- Category Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-900 mb-2">
                            Category Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                            placeholder="e.g., Hardware Issues, Software Support"
                        >
                        <p class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Choose a clear, descriptive name for the category
                        </p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-900 mb-2">
                            Description
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="4"
                            class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                            placeholder="Describe what types of tickets belong to this category..."
                        ></textarea>
                        <p class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Help users understand when to use this category
                        </p>
                    </div>

                    <!-- Icon Selection -->
                    <div>
                        <label for="icon" class="block text-sm font-medium text-gray-900 mb-2">
                            Icon
                        </label>
                        <div class="flex items-center space-x-3">
                            <select 
                                id="icon" 
                                name="icon"
                                class="flex-1 px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                onchange="updateIconPreview()"
                            >
                                <option value="fa-folder">Default Folder</option>
                                <option value="fa-desktop">Desktop/Hardware</option>
                                <option value="fa-laptop">Laptop</option>
                                <option value="fa-keyboard">Keyboard/Peripherals</option>
                                <option value="fa-print">Printer</option>
                                <option value="fa-mobile-alt">Mobile Device</option>
                                <option value="fa-code">Software/Code</option>
                                <option value="fa-bug">Bug/Error</option>
                                <option value="fa-network-wired">Network</option>
                                <option value="fa-wifi">WiFi/Wireless</option>
                                <option value="fa-shield-alt">Security</option>
                                <option value="fa-database">Database</option>
                                <option value="fa-cloud">Cloud Services</option>
                                <option value="fa-envelope">Email</option>
                                <option value="fa-user-cog">Account/Access</option>
                                <option value="fa-tools">Maintenance</option>
                                <option value="fa-phone">Phone/VoIP</option>
                                <option value="fa-headset">Support</option>
                            </select>
                            <div class="w-12 h-12 border border-gray-300 flex items-center justify-center bg-gray-50" id="iconPreview">
                                <i class="fas fa-folder text-gray-700 text-lg"></i>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Choose an icon that represents this category
                        </p>
                    </div>

                    <!-- Color Selection -->
                    <div>
                        <label for="color" class="block text-sm font-medium text-gray-900 mb-2">
                            Color
                        </label>
                        <div class="grid grid-cols-8 gap-2 mb-3">
                            <?php
                            $colors = [
                                ['name' => 'Red', 'value' => '#ef4444'],
                                ['name' => 'Orange', 'value' => '#f97316'],
                                ['name' => 'Yellow', 'value' => '#eab308'],
                                ['name' => 'Green', 'value' => '#22c55e'],
                                ['name' => 'Teal', 'value' => '#14b8a6'],
                                ['name' => 'Blue', 'value' => '#3b82f6'],
                                ['name' => 'Purple', 'value' => '#8b5cf6'],
                                ['name' => 'Pink', 'value' => '#ec4899'],
                                ['name' => 'Gray', 'value' => '#6b7280'],
                                ['name' => 'Slate', 'value' => '#64748b'],
                                ['name' => 'Zinc', 'value' => '#71717a'],
                                ['name' => 'Stone', 'value' => '#78716c'],
                                ['name' => 'Cyan', 'value' => '#06b6d4'],
                                ['name' => 'Sky', 'value' => '#0ea5e9'],
                                ['name' => 'Indigo', 'value' => '#6366f1'],
                                ['name' => 'Violet', 'value' => '#7c3aed'],
                            ];
                            
                            foreach ($colors as $color):
                            ?>
                            <button 
                                type="button" 
                                class="w-10 h-10 border-2 border-gray-300 hover:border-gray-900 transition color-btn"
                                style="background-color: <?php echo $color['value']; ?>"
                                onclick="selectColor('<?php echo $color['value']; ?>')"
                                title="<?php echo $color['name']; ?>"
                            ></button>
                            <?php endforeach; ?>
                        </div>
                        <input 
                            type="text" 
                            id="color" 
                            name="color"
                            value="#6b7280"
                            class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                            placeholder="#6b7280"
                        >
                        <p class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Click a color above or enter a custom hex code
                        </p>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="categories.php" class="px-6 py-3 border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <button 
                            type="submit"
                            class="px-6 py-3 bg-gray-900 text-white font-semibold hover:bg-gray-800 transition"
                        >
                            <i class="fas fa-folder-plus mr-2"></i>Add Category
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview Section -->
            <div class="bg-white border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6 pb-3 border-b border-gray-200">
                    <i class="fas fa-eye mr-2"></i>Live Preview
                </h2>
                
                <!-- Preview Card -->
                <div class="border border-gray-200 p-4 hover:border-gray-300 transition-colors">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-10 h-10 flex items-center justify-center" id="previewIconContainer">
                            <i class="fas fa-folder text-gray-700 text-xl" id="previewIcon"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900" id="previewName">Category Name</h3>
                            <p class="text-xs text-gray-500 mt-0.5" id="previewDescription">No description yet</p>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 pt-3 mt-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Sample Tickets:</span>
                            <span class="font-semibold text-gray-900">0</span>
                        </div>
                    </div>
                </div>

                <!-- Common Categories Reference -->
                <div class="mt-6 bg-gray-50 border border-gray-200 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">
                        <i class="fas fa-lightbulb mr-1"></i>Common Categories
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-desktop text-gray-500 w-5 mr-2"></i>
                            <span><strong>Hardware Issues</strong> - Physical device problems</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-code text-gray-500 w-5 mr-2"></i>
                            <span><strong>Software Support</strong> - Application errors</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-network-wired text-gray-500 w-5 mr-2"></i>
                            <span><strong>Network Issues</strong> - Connectivity problems</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-user-cog text-gray-500 w-5 mr-2"></i>
                            <span><strong>Account Access</strong> - Login and permissions</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-shield-alt text-gray-500 w-5 mr-2"></i>
                            <span><strong>Security</strong> - Security concerns</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope text-gray-500 w-5 mr-2"></i>
                            <span><strong>Email Support</strong> - Email-related issues</span>
                        </li>
                    </ul>
                </div>

                <!-- Help Section -->
                <div class="mt-6 bg-blue-50 border border-blue-200 p-4">
                    <h3 class="text-sm font-semibold text-blue-900 mb-3">
                        <i class="fas fa-info-circle mr-1"></i>Best Practices
                    </h3>
                    <ul class="space-y-2 text-sm text-blue-800">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                            <span>Use clear, concise names</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                            <span>Avoid overlapping categories</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                            <span>Choose distinctive icons</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                            <span>Write helpful descriptions</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update icon preview
function updateIconPreview() {
    const iconSelect = document.getElementById('icon');
    const iconClass = iconSelect.value;
    const previewIcon = document.getElementById('previewIcon');
    const iconPreview = document.getElementById('iconPreview').querySelector('i');
    
    // Update both previews
    previewIcon.className = 'fas ' + iconClass + ' text-xl';
    iconPreview.className = 'fas ' + iconClass + ' text-gray-700 text-lg';
}

// Select color
function selectColor(colorValue) {
    const colorInput = document.getElementById('color');
    const previewIconContainer = document.getElementById('previewIconContainer');
    
    colorInput.value = colorValue;
    
    // Update preview background
    previewIconContainer.style.backgroundColor = colorValue + '20'; // 20% opacity
    previewIconContainer.style.border = '1px solid ' + colorValue + '40';
    
    // Highlight selected color button
    document.querySelectorAll('.color-btn').forEach(btn => {
        btn.classList.remove('ring-2', 'ring-gray-900');
    });
    event.target.classList.add('ring-2', 'ring-gray-900');
}

// Live preview updates
document.getElementById('name').addEventListener('input', function() {
    const previewName = document.getElementById('previewName');
    previewName.textContent = this.value || 'Category Name';
});

document.getElementById('description').addEventListener('input', function() {
    const previewDescription = document.getElementById('previewDescription');
    previewDescription.textContent = this.value || 'No description yet';
});

// Manual color input
document.getElementById('color').addEventListener('input', function() {
    const previewIconContainer = document.getElementById('previewIconContainer');
    const colorValue = this.value;
    
    if (/^#[0-9A-F]{6}$/i.test(colorValue)) {
        previewIconContainer.style.backgroundColor = colorValue + '20';
        previewIconContainer.style.border = '1px solid ' + colorValue + '40';
    }
});

// Form validation
document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    
    if (!name) {
        e.preventDefault();
        alert('Please enter a category name');
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding Category...';
    submitBtn.disabled = true;
});

// Initialize preview with default gray color
window.addEventListener('DOMContentLoaded', function() {
    selectColor('#6b7280');
});
</script>

<?php 
// Include layout footer
include __DIR__ . '/../layouts/footer.php'; 
?>
