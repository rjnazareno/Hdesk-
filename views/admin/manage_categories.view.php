<?php 
// Include layout header
$pageTitle = 'Manage Categories - IT Help Desk';
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gray-50">
    <!-- Top Bar -->
    <div class="bg-white border-b border-gray-200 ">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-gradient-to-r from-teal-500 to-emerald-600 flex items-center justify-center text-gray-900">
                    <i class="fas fa-edit text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-gray-900">Manage Categories</h1>
                    <p class="text-sm text-gray-600 mt-0.5">Edit or delete existing categories</p>
                </div>
            </div>
            <div class="hidden lg:flex items-center space-x-2">
                <a href="add_category.php" class="px-4 py-2 bg-gradient-to-r from-teal-500 to-emerald-600 text-gray-900 text-sm font-medium hover:from-teal-700 hover:to-emerald-700 transition">
                    <i class="fas fa-plus mr-2"></i>Add New
                </a>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=fff" 
                     alt="User" 
                     class="w-8 h-8 rounded-full"
                     title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
            </div>
        </div>
    </div>

    <!-- Content -->
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
                        <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="categories.php" class="ml-1 text-sm font-medium text-gray-600 hover:text-gray-900">Categories</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-700">Manage</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-900/30 border border-red-700/50 text-red-400 px-4 py-3 mb-6 rounded">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-900/30 border border-green-700/50 text-green-400 px-4 py-3 mb-6 rounded">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <!-- Categories Table -->
        <div class="bg-white border border-gray-200  rounded-lg shadow-2xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">All Categories</h2>
                        <p class="text-sm text-gray-600 mt-0.5"><?php echo count($categories); ?> categories total</p>
                    </div>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="searchCategories"
                            placeholder="Search categories..." 
                            class="pl-8 pr-4 py-2 border border-gray-300 bg-gray-50 text-gray-900 placeholder-slate-400 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-cyan-500/50 text-sm rounded"
                        >
                        <i class="fas fa-search absolute left-3 top-3 text-gray-600 text-xs"></i>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg">
                <table class="w-full">
                    <thead class="bg-white border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Icon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Color</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tickets</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-700/50">
                        <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-600">
                                <i class="fas fa-folder-open text-4xl mb-3 text-gray-500 block"></i>
                                <p class="text-lg">No categories found</p>
                                <a href="add_category.php" class="inline-block mt-4 px-4 py-2 bg-gradient-to-r from-teal-500 to-emerald-600 text-gray-900 text-sm hover:from-teal-700 hover:to-emerald-700 transition rounded">
                                    <i class="fas fa-plus mr-2"></i>Add Your First Category
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                            <tr class="hover:bg-white transition-colors category-row" data-category-name="<?php echo strtolower(htmlspecialchars($category['name'])); ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 flex items-center justify-center" style="background-color: <?php echo htmlspecialchars($category['color']); ?>20; border: 1px solid <?php echo htmlspecialchars($category['color']); ?>40;">
                                            <i class="fas fa-folder text-gray-700"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></div>
                                            <div class="text-xs text-gray-600">ID: <?php echo $category['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600 max-w-xs truncate">
                                        <?php echo htmlspecialchars($category['description'] ?? 'No description'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-folder text-gray-600"></i>
                                        <span class="text-xs text-gray-600">fa-folder</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-6 h-6 border border-gray-300" style="background-color: <?php echo htmlspecialchars($category['color']); ?>"></div>
                                        <span class="text-xs text-gray-600"><?php echo htmlspecialchars($category['color']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <span class="font-semibold text-gray-900"><?php echo $category['ticket_count']; ?></span>
                                        <span class="text-gray-600">total</span>
                                    </div>
                                    <?php if ($category['open_tickets'] > 0): ?>
                                    <div class="text-xs text-orange-600 mt-1">
                                        <?php echo $category['open_tickets']; ?> active
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button 
                                        onclick="openEditModal(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>', '<?php echo htmlspecialchars(addslashes($category['description'] ?? '')); ?>', 'fa-folder', '<?php echo htmlspecialchars($category['color']); ?>')"
                                        class="inline-flex items-center px-3 py-1.5 border border-teal-500/30 text-sm text-teal-600 hover:bg-teal-500/10 rounded transition"
                                        title="Edit category"
                                    >
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </button>
                                    <button 
                                        onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>', <?php echo $category['ticket_count']; ?>)"
                                        class="inline-flex items-center px-3 py-1.5 border border-red-500/30 text-sm text-red-400 hover:bg-red-500/10 rounded transition"
                                        title="Delete category"
                                        <?php echo $category['ticket_count'] > 0 ? 'disabled opacity-50 cursor-not-allowed' : ''; ?>
                                    >
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white border border-gray-200 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-edit mr-2"></i>Edit Category
            </h3>
            <button onclick="closeEditModal()" class="text-gray-600 hover:text-gray-900 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form action="manage_categories.php" method="POST" class="p-6 space-y-6" id="editForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="category_id" id="edit_category_id">
            
            <!-- Name -->
            <div>
                <label for="edit_name" class="block text-sm font-medium text-gray-900 mb-2">
                    Category Name <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="edit_name" 
                    name="name" 
                    required
                    class="w-full px-4 py-3 border border-gray-300 bg-gray-50 text-gray-900 placeholder-slate-400 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-cyan-500/50 rounded"
                >
            </div>

            <!-- Description -->
            <div>
                <label for="edit_description" class="block text-sm font-medium text-gray-900 mb-2">
                    Description
                </label>
                <textarea 
                    id="edit_description" 
                    name="description" 
                    rows="3"
                    class="w-full px-4 py-3 border border-gray-300 bg-gray-50 text-gray-900 placeholder-slate-400 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-cyan-500/50 rounded"
                ></textarea>
            </div>

            <!-- Icon -->
            <div>
                <label for="edit_icon" class="block text-sm font-medium text-gray-900 mb-2">
                    Icon
                </label>
                <select 
                    id="edit_icon" 
                    name="icon"
                    class="w-full px-4 py-3 border border-gray-300 bg-gray-50 text-gray-900 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-cyan-500/50 rounded"
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
                </select>
            </div>

            <!-- Color -->
            <div>
                <label for="edit_color" class="block text-sm font-medium text-gray-900 mb-2">
                    Color
                </label>
                <input 
                    type="text" 
                    id="edit_color" 
                    name="color"
                    class="w-full px-4 py-3 border border-gray-300 bg-gray-50 text-gray-900 placeholder-slate-400 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-cyan-500/50 rounded"
                    placeholder="#6b7280"
                >
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                <button 
                    type="button"
                    onclick="closeEditModal()"
                    class="px-6 py-3 border border-gray-300 text-gray-700 hover:bg-white rounded transition"
                >
                    Cancel
                </button>
                <button 
                    type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-teal-500 to-emerald-600 text-gray-900 font-semibold hover:from-teal-700 hover:to-emerald-700 rounded transition"
                >
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white border border-gray-200 max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>Confirm Delete
            </h3>
        </div>
        
        <form action="manage_categories.php" method="POST" class="p-6">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="category_id" id="delete_category_id">
            
            <p class="text-gray-700 mb-4">
                Are you sure you want to delete the category "<strong id="delete_category_name"></strong>"?
            </p>
            <p class="text-sm text-gray-600 bg-yellow-900/30 border border-yellow-700/50 p-3 rounded">
                <i class="fas fa-info-circle mr-1"></i>
                This action cannot be undone. The category will be permanently removed.
            </p>

            <div class="flex items-center justify-end space-x-4 mt-6">
                <button 
                    type="button"
                    onclick="closeDeleteModal()"
                    class="px-6 py-3 border border-gray-300 text-gray-700 hover:bg-white rounded transition"
                >
                    Cancel
                </button>
                <button 
                    type="submit"
                    class="px-6 py-3 bg-red-600 text-gray-900 font-semibold hover:bg-red-700 rounded transition"
                >
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Search categories
document.getElementById('searchCategories').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.category-row');
    
    rows.forEach(row => {
        const categoryName = row.getAttribute('data-category-name');
        if (categoryName.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Open edit modal
function openEditModal(id, name, description, icon, color) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_icon').value = icon;
    document.getElementById('edit_color').value = color;
    
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
    document.body.style.overflow = '';
}

// Confirm delete
function confirmDelete(id, name, ticketCount) {
    if (ticketCount > 0) {
        alert('Cannot delete category with existing tickets (' + ticketCount + ' tickets). Please reassign or close tickets first.');
        return;
    }
    
    document.getElementById('delete_category_id').value = id;
    document.getElementById('delete_category_name').textContent = name;
    
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

// Close delete modal
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
    document.body.style.overflow = '';
}

// Close modals on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
        closeDeleteModal();
    }
});

// Close modals on outside click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php 
// Include layout footer
include __DIR__ . '/../layouts/footer.php'; 
?>

