<?php 
// Include layout header
$pageTitle = 'Create Ticket - ' . APP_NAME;
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

<style>
    /* Department card selection */
    .dept-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    .dept-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(5, 150, 105, 0.05) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .dept-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -8px rgba(16, 185, 129, 0.15);
    }
    .dept-card:hover::before {
        opacity: 1;
    }
    .dept-card.selected {
        border-color: #10b981;
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        box-shadow: 0 8px 24px -8px rgba(16, 185, 129, 0.3);
    }
    .dept-card.selected .dept-icon {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
        transform: scale(1.05);
    }
    .dept-card.selected .dept-icon i {
        color: white !important;
    }
    .dept-card.selected .dept-check {
        opacity: 1;
        transform: scale(1);
    }
    
    /* Step indicator */
    .step-indicator {
        transition: all 0.3s ease;
    }
    .step-indicator.active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }
    .step-indicator.completed {
        background: #10b981;
        color: white;
    }
    
    /* Form sections */
    .form-section {
        opacity: 0;
        transform: translateX(20px);
        transition: all 0.4s ease;
        display: none;
    }
    .form-section.active {
        opacity: 1;
        transform: translateX(0);
        display: block;
    }
    
    /* Category cards */
    .category-card {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        position: relative;
    }
    .category-card:hover {
        border-color: #10b981;
        background-color: #f0fdf4;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
    }
    .category-card.selected {
        border-color: #10b981;
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        box-shadow: 0 4px 16px rgba(16, 185, 129, 0.2);
    }
    .category-card.selected .cat-icon {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        transform: scale(1.05);
    }
    .category-card.selected .cat-icon i {
        color: white !important;
    }
    .category-card.selected .cat-check {
        opacity: 1;
        transform: scale(1);
    }
    .category-card .cat-check {
        transform: scale(0.8);
        transition: all 0.2s ease;
    }
    
    /* Sub-category cards */
    .subcategory-card {
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .subcategory-card:hover {
        border-color: #8B5CF6;
        background-color: #faf5ff;
    }
    .subcategory-card.selected {
        border-color: #8B5CF6;
        background: linear-gradient(135deg, #faf5ff 0%, #ede9fe 100%);
    }
    .subcategory-card.selected .subcat-check {
        opacity: 1;
    }
    
    /* Animations */
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .slide-in {
        animation: slideIn 0.4s ease forwards;
    }
    
    /* Line clamp */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    /* Employee card */
    .employee-card {
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .employee-card:hover {
        border-color: #10b981;
        background-color: #f0fdf4;
    }
    .employee-card.selected {
        border-color: #10b981;
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    }
    .employee-card.selected .emp-check {
        opacity: 1;
    }
</style>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-slate-50">
    <?php
    $headerTitle = 'Create New Ticket';
    $headerSubtitle = 'Create a ticket on behalf of an employee';
    $showQuickActions = false;
    $showSearch = false;
    include __DIR__ . '/../../includes/top_header.php';
    ?>

    <!-- Form Content -->
    <div class="p-6 max-w-5xl mx-auto">
        
        <!-- Error Messages -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl mb-6 flex items-center shadow-sm">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-exclamation-circle text-red-600"></i>
            </div>
            <div class="flex-1">
                <p class="font-medium">Error</p>
                <p class="text-sm"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form Container -->
        <div class="bg-white rounded-2xl shadow-lg border border-slate-200/80 overflow-hidden">
            
            <!-- Header -->
            <div class="relative bg-slate-800 px-8 py-6">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-600/10 to-teal-600/10"></div>
                <div class="relative flex items-center space-x-4">
                    <div class="w-14 h-14 bg-emerald-500 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-ticket-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Create Ticket for Employee</h1>
                        <p class="text-slate-300 text-sm mt-1">Submit a support request on behalf of an employee</p>
                    </div>
                </div>
            </div>

            <!-- Progress Steps -->
            <div class="bg-slate-50 border-b border-slate-200 px-8 py-7">
                <div class="flex items-center justify-center">
                    <div class="flex items-center">
                        <!-- Step 1 -->
                        <div class="flex items-center">
                            <div id="step1-indicator" class="step-indicator active w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold border-2 border-emerald-500 shadow-sm">1</div>
                            <span class="ml-3 text-sm font-semibold text-slate-700 hidden sm:inline">Employee</span>
                        </div>
                        
                        <!-- Line 1 -->
                        <div class="w-16 sm:w-28 h-1 mx-4 bg-slate-200 rounded-full transition-colors duration-300" id="step-line-1"></div>
                        
                        <!-- Step 2 -->
                        <div class="flex items-center">
                            <div id="step2-indicator" class="step-indicator w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-slate-100 text-slate-400 border-2 border-slate-200">2</div>
                            <span class="ml-3 text-sm font-medium text-slate-400 hidden sm:inline" id="step2-label">Department</span>
                        </div>
                        
                        <!-- Line 2 -->
                        <div class="w-16 sm:w-28 h-1 mx-4 bg-slate-200 rounded-full transition-colors duration-300" id="step-line-2"></div>
                        
                        <!-- Step 3 -->
                        <div class="flex items-center">
                            <div id="step3-indicator" class="step-indicator w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-slate-100 text-slate-400 border-2 border-slate-200">3</div>
                            <span class="ml-3 text-sm font-medium text-slate-400 hidden sm:inline" id="step3-label">Details</span>
                        </div>
                        
                        <!-- Line 3 -->
                        <div class="w-16 sm:w-28 h-1 mx-4 bg-slate-200 rounded-full transition-colors duration-300" id="step-line-3"></div>
                        
                        <!-- Step 4 -->
                        <div class="flex items-center">
                            <div id="step4-indicator" class="step-indicator w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-slate-100 text-slate-400 border-2 border-slate-200">4</div>
                            <span class="ml-3 text-sm font-medium text-slate-400 hidden sm:inline" id="step4-label">Review</span>
                        </div>
                    </div>
                </div>
            </div>

            <form action="create_ticket.php" method="POST" enctype="multipart/form-data" id="createTicketForm" class="p-8">
                
                <!-- STEP 1: Employee Selection -->
                <div id="step1" class="form-section active">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user text-emerald-600"></i>
                        </div>
                        <h2 class="text-xl font-bold text-slate-800">Select Employee</h2>
                    </div>
                    <p class="text-sm text-slate-500 mb-6 ml-13">Choose the employee who is experiencing the issue.</p>
                    
                    <!-- Search -->
                    <div class="mb-6">
                        <input type="text" id="employeeSearch" placeholder="Search by name or position..." 
                               class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:bg-white text-sm transition-all">
                    </div>
                    
                    <!-- Employee Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto" id="employeeGrid">
                        <?php foreach ($employees as $employee): ?>
                        <div class="employee-card relative p-4 border-2 border-gray-200 rounded-xl bg-white" 
                             data-employee-id="<?php echo $employee['id']; ?>"
                             data-name="<?php echo htmlspecialchars(strtolower(trim($employee['fname'] . ' ' . $employee['lname']))); ?>"
                             data-position="<?php echo htmlspecialchars(strtolower($employee['position'] ?? '')); ?>"
                             onclick="selectEmployee(this, <?php echo $employee['id']; ?>, '<?php echo htmlspecialchars(addslashes(trim($employee['fname'] . ' ' . $employee['lname']))); ?>')">
                            
                            <div class="emp-check absolute top-3 right-3 w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center opacity-0 transition-all">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                            
                            <div class="flex items-center space-x-3">
                                <div class="w-11 h-11 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center text-white font-semibold">
                                    <?php echo strtoupper(substr($employee['fname'], 0, 1) . substr($employee['lname'], 0, 1)); ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">
                                        <?php echo htmlspecialchars(trim($employee['fname'] . ' ' . $employee['lname'])); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 truncate">
                                        <?php echo htmlspecialchars($employee['position'] ?? 'Employee'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <input type="hidden" name="submitter_id" id="submitter_id" value="" required>
                    
                    <div class="flex justify-end mt-8 pt-6 border-t border-gray-100">
                        <button type="button" id="nextStep1" onclick="goToStep(2)" disabled
                                class="inline-flex items-center px-6 py-3 bg-slate-200 text-slate-400 rounded-xl cursor-not-allowed transition-all font-medium disabled:opacity-60">
                            <span>Select an employee to continue</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Department Selection -->
                <div id="step2" class="form-section">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-building text-blue-600"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-800">Select Department</h2>
                                <p class="text-sm text-slate-500 mt-0.5">Creating ticket for <span id="selectedEmployeeName" class="font-semibold text-emerald-600"></span></p>
                            </div>
                        </div>
                        <button type="button" onclick="goToStep(1)" class="text-sm text-slate-500 hover:text-emerald-600 flex items-center px-3 py-1.5 hover:bg-slate-100 rounded-lg transition-all">
                            <i class="fas fa-pen mr-1.5 text-xs"></i> Change
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($departments as $dept): ?>
                        <div class="dept-card relative p-6 border-2 border-gray-200 rounded-2xl bg-white hover:shadow-lg" 
                             data-department-id="<?php echo $dept['id']; ?>"
                             onclick="selectDepartment(this, <?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['code']); ?>')">
                            
                            <div class="dept-check absolute top-4 right-4 w-7 h-7 bg-emerald-500 rounded-full flex items-center justify-center opacity-0 transition-all transform scale-75 shadow-lg">
                                <i class="fas fa-check text-white text-sm"></i>
                            </div>
                            
                            <div class="flex items-start space-x-4">
                                <div class="dept-icon w-14 h-14 rounded-xl flex items-center justify-center transition-all shrink-0"
                                     style="background-color: <?php echo $dept['color']; ?>15;">
                                    <i class="fas fa-<?php echo htmlspecialchars($dept['icon']); ?> text-2xl transition-colors" style="color: <?php echo $dept['color']; ?>;"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($dept['name']); ?></h3>
                                    <p class="text-sm text-gray-500 mt-1 line-clamp-2"><?php echo htmlspecialchars($dept['description']); ?></p>
                                    
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <?php 
                                        $previewCategories = array_slice(array_filter($categories, function($cat) use ($dept) {
                                            return $cat['department_id'] == $dept['id'] && $cat['parent_id'] === null;
                                        }), 0, 3);
                                        foreach ($previewCategories as $cat): 
                                        ?>
                                        <span class="text-xs px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full font-medium"><?php echo htmlspecialchars($cat['name']); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <input type="hidden" name="department_id" id="department_id" value="">
                    
                    <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-100">
                        <button type="button" onclick="goToStep(1)" class="inline-flex items-center px-5 py-2.5 border border-slate-300 text-slate-600 hover:bg-slate-50 hover:border-slate-400 rounded-lg transition-all text-sm font-medium">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back
                        </button>
                        <button type="button" id="nextStep2" onclick="goToStep(3)" disabled
                                class="inline-flex items-center px-6 py-3 bg-slate-200 text-slate-400 rounded-xl cursor-not-allowed transition-all font-medium disabled:opacity-60">
                            <span>Select a department to continue</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 3: Ticket Details -->
                <div id="step3" class="form-section">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clipboard-list text-purple-600"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-800">Request Details</h2>
                                <p class="text-sm text-slate-500 mt-0.5">Provide details for <span id="selectedDeptName" class="font-semibold text-blue-600"></span></p>
                            </div>
                        </div>
                        <button type="button" onclick="goToStep(2)" class="text-sm text-slate-500 hover:text-emerald-600 flex items-center px-3 py-1.5 hover:bg-slate-100 rounded-lg transition-all">
                            <i class="fas fa-pen mr-1.5 text-xs"></i> Change
                        </button>
                    </div>
                    
                    <!-- Category Selection -->
                    <div class="mb-6">
                        <label class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                            <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-folder-open text-blue-600 text-sm"></i>
                            </div>
                            Category <span class="text-red-500 ml-1">*</span>
                        </label>
                        
                        <div id="categoryGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Categories populated via JavaScript -->
                        </div>
                        
                        <!-- Sub-category Selection -->
                        <div id="subCategorySection" class="hidden mt-4">
                            <div class="flex items-center justify-between mb-3">
                                <label class="flex items-center text-sm font-semibold text-gray-700">
                                    <div class="w-7 h-7 bg-purple-50 rounded-lg flex items-center justify-center mr-2">
                                        <i class="fas fa-list text-purple-600 text-xs"></i>
                                    </div>
                                    Specify Issue Type <span class="text-gray-400 font-normal ml-1">(Optional)</span>
                                </label>
                                <button type="button" onclick="clearSubCategory()" class="text-xs text-gray-500 hover:text-emerald-600">
                                    <i class="fas fa-times mr-1"></i>Skip
                                </button>
                            </div>
                            <div id="subCategoryGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            </div>
                        </div>
                        
                        <select id="category_id" name="category_id" required class="hidden">
                            <option value="">Select category...</option>
                        </select>
                    </div>
                    
                    <!-- Title -->
                    <div class="mb-6">
                        <label for="title" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                            <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-heading text-emerald-600 text-sm"></i>
                            </div>
                            Title <span class="text-red-500 ml-1">*</span>
                        </label>
                        <input type="text" id="title" name="title" required
                               class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:bg-white text-sm transition-all"
                               placeholder="Brief description of the issue">
                    </div>
                    
                    <!-- Priority -->
                    <div class="mb-6">
                        <label class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                            <div class="w-8 h-8 bg-orange-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-exclamation-triangle text-orange-600 text-sm"></i>
                            </div>
                            Priority <span class="text-red-500 ml-1">*</span>
                        </label>
                        
                        <!-- Auto-priority indicator -->
                        <div id="autoPriorityBanner" class="hidden mb-3 p-3 bg-blue-50 border border-blue-200 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-magic text-blue-600 mr-2"></i>
                                    <span class="text-sm text-blue-700">
                                        Priority auto-set to <strong id="autoPriorityLabel"></strong> based on issue type
                                    </span>
                                </div>
                                <span id="slaInfoText" class="text-xs text-blue-600"></span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="priority-option relative cursor-pointer">
                                <input type="radio" name="priority" value="low" class="hidden peer">
                                <div class="p-3 border-2 border-gray-200 rounded-xl text-center peer-checked:border-green-500 peer-checked:bg-green-50 transition-all hover:border-green-300">
                                    <span class="text-2xl">🟢</span>
                                    <p class="text-sm font-medium mt-1">Low</p>
                                    <p class="text-xs text-gray-500">Response: 1 day</p>
                                    <p class="text-xs text-gray-400">Resolution: 3–5 days</p>
                                </div>
                            </label>
                            <label class="priority-option relative cursor-pointer">
                                <input type="radio" name="priority" value="medium" class="hidden peer" checked>
                                <div class="p-3 border-2 border-gray-200 rounded-xl text-center peer-checked:border-yellow-500 peer-checked:bg-yellow-50 transition-all hover:border-yellow-300">
                                    <span class="text-2xl">🟡</span>
                                    <p class="text-sm font-medium mt-1">Medium</p>
                                    <p class="text-xs text-gray-500">Response: 4 hours</p>
                                    <p class="text-xs text-gray-400">Resolution: 2–3 days</p>
                                </div>
                            </label>
                            <label class="priority-option relative cursor-pointer">
                                <input type="radio" name="priority" value="high" class="hidden peer">
                                <div class="p-3 border-2 border-gray-200 rounded-xl text-center peer-checked:border-red-500 peer-checked:bg-red-50 transition-all hover:border-red-300">
                                    <span class="text-2xl">🔴</span>
                                    <p class="text-sm font-medium mt-1">High</p>
                                    <p class="text-xs text-gray-500">Response: 30 mins</p>
                                    <p class="text-xs text-gray-400">Resolution: 1 business day</p>
                                </div>
                            </label>
                        </div>
                        
                        <!-- Admin Override Option -->
                        <div id="adminOverrideSection" class="hidden mt-3">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="admin_priority_override" value="1" id="adminOverrideCheckbox" class="form-checkbox h-4 w-4 text-orange-600 rounded border-gray-300">
                                <span class="ml-2 text-sm text-gray-600">
                                    <i class="fas fa-shield-alt text-orange-500 mr-1"></i>
                                    Override auto-priority (admin control)
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Assign To (Admin Only) -->
                    <div class="mb-6">
                        <label class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                            <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-user-tag text-indigo-600 text-sm"></i>
                            </div>
                            Assign To <span class="text-gray-400 font-normal ml-1">(Optional)</span>
                        </label>
                        <select name="assigned_to" id="assigned_to" class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:bg-white text-sm cursor-pointer">
                            <option value="">Auto-assign (leave empty)</option>
                            <?php foreach ($itStaff as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>">
                                <?php echo htmlspecialchars($staff['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-2">Leave empty for automatic assignment based on department</p>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-6">
                        <label for="description" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                            <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-align-left text-purple-600 text-sm"></i>
                            </div>
                            Description <span class="text-red-500 ml-1">*</span>
                        </label>
                        <textarea id="description" name="description" rows="5" required
                                  class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:bg-white text-sm transition-all resize-none"
                                  placeholder="Provide detailed information about the issue..."></textarea>
                    </div>
                    
                    <!-- Attachment -->
                    <div class="mb-6">
                        <label class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                            <div class="w-8 h-8 bg-cyan-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-paperclip text-cyan-600 text-sm"></i>
                            </div>
                            Attachment <span class="text-gray-400 font-normal ml-1">(Optional)</span>
                        </label>
                        <label for="attachment" class="flex items-center justify-center w-full h-28 bg-slate-50 border-2 border-slate-200 border-dashed rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/50 transition-all group">
                            <div class="text-center" id="uploadPlaceholder">
                                <div class="w-12 h-12 bg-slate-200 group-hover:bg-emerald-100 rounded-lg flex items-center justify-center mx-auto mb-2 transition-all">
                                    <i class="fas fa-cloud-upload-alt text-slate-400 group-hover:text-emerald-500 text-xl transition-all"></i>
                                </div>
                                <p class="text-sm font-medium text-slate-600">Click to upload or drag and drop</p>
                                <p class="text-xs text-slate-400 mt-1">PNG, JPG, PDF, DOC up to 5MB</p>
                            </div>
                            <div class="hidden items-center space-x-3" id="filePreview">
                                <i class="fas fa-file-alt text-emerald-600 text-xl"></i>
                                <span class="text-sm font-medium text-gray-700" id="fileName"></span>
                                <button type="button" onclick="clearFile(event)" class="w-6 h-6 bg-red-50 hover:bg-red-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-times text-red-600 text-xs"></i>
                                </button>
                            </div>
                            <input id="attachment" name="attachment" type="file" class="hidden" accept=".png,.jpg,.jpeg,.pdf,.doc,.docx">
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                        <button type="button" onclick="goToStep(2)" class="inline-flex items-center px-5 py-2.5 border border-slate-300 text-slate-600 hover:bg-slate-50 hover:border-slate-400 rounded-lg transition-all text-sm font-medium">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back
                        </button>
                        <button type="button" onclick="goToStep(4)" id="nextStep3"
                                class="inline-flex items-center px-6 py-3 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg transition-all text-sm font-semibold shadow-md hover:shadow-lg">
                            <i class="fas fa-eye mr-2"></i>
                            Review Request
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 4: Review & Submit -->
                <div id="step4" class="form-section">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-double text-amber-600"></i>
                        </div>
                        <h2 class="text-xl font-bold text-slate-800">Review Ticket</h2>
                    </div>
                    <p class="text-sm text-slate-500 mb-6 ml-13">Please review the information before submitting.</p>
                    
                    <div class="bg-slate-50 rounded-xl border border-slate-200 overflow-hidden">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-px bg-slate-200">
                            <div class="bg-white p-4">
                                <p class="text-xs text-slate-500 uppercase tracking-wide font-medium mb-1">Employee</p>
                                <p class="text-sm font-semibold text-slate-800" id="reviewEmployee">-</p>
                            </div>
                            <div class="bg-white p-4">
                                <p class="text-xs text-slate-500 uppercase tracking-wide font-medium mb-1">Department</p>
                                <p class="text-sm font-semibold text-slate-800" id="reviewDepartment">-</p>
                            </div>
                            <div class="bg-white p-4">
                                <p class="text-xs text-slate-500 uppercase tracking-wide font-medium mb-1">Category</p>
                                <p class="text-sm font-semibold text-slate-800" id="reviewCategory">-</p>
                            </div>
                            <div class="bg-white p-4">
                                <p class="text-xs text-slate-500 uppercase tracking-wide font-medium mb-1">Priority</p>
                                <p class="text-sm font-semibold text-slate-800" id="reviewPriority">-</p>
                            </div>
                            <div class="bg-white p-4">
                                <p class="text-xs text-slate-500 uppercase tracking-wide font-medium mb-1">Assigned To</p>
                                <p class="text-sm font-semibold text-slate-800" id="reviewAssigned">Auto-assign</p>
                            </div>
                            <div class="bg-white p-4">
                                <p class="text-xs text-slate-500 uppercase tracking-wide font-medium mb-1">Attachment</p>
                                <p class="text-sm font-semibold text-slate-800" id="reviewAttachment">None</p>
                            </div>
                        </div>
                        <div class="border-t border-slate-200 bg-white p-4">
                            <p class="text-xs text-slate-500 uppercase tracking-wide font-medium mb-1">Title</p>
                            <p class="text-base font-semibold text-slate-800" id="reviewTitle">-</p>
                        </div>
                        <div class="border-t border-slate-200 bg-white p-4">
                            <p class="text-xs text-slate-500 uppercase tracking-wide font-medium mb-2">Description</p>
                            <p class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed" id="reviewDescription">-</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-100">
                        <button type="button" onclick="goToStep(3)" class="inline-flex items-center px-5 py-2.5 border border-slate-300 text-slate-600 hover:bg-slate-50 hover:border-slate-400 rounded-lg transition-all text-sm font-medium">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Details
                        </button>
                        <button type="submit" id="submitBtn"
                                class="inline-flex items-center px-8 py-3.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg transition-all text-sm font-semibold shadow-md hover:shadow-lg disabled:opacity-60 disabled:cursor-not-allowed">
                            <span id="submitText" class="flex items-center">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Create Ticket
                            </span>
                            <span id="submitLoading" class="hidden flex items-center">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Creating...
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Data from PHP
    const categoriesData = <?php echo json_encode($categories, JSON_NUMERIC_CHECK); ?>;
    const departmentsData = <?php echo json_encode($departments, JSON_NUMERIC_CHECK); ?>;
    const itStaffData = <?php echo json_encode($itStaff, JSON_NUMERIC_CHECK); ?>;
    const priorityMapData = <?php echo json_encode($priorityMap ?? [], JSON_NUMERIC_CHECK); ?>;
    const slaTargetsData = <?php echo json_encode($slaTargets ?? [], JSON_HEX_TAG); ?>;
    
    let selectedEmployeeId = null;
    let selectedEmployeeName = null;
    let selectedDepartmentId = null;
    let selectedDepartmentCode = null;
    let selectedCategoryId = null;
    let selectedParentCategoryId = null;
    let currentStep = 1;
    let autoPrioritySet = false;
    
    // Employee Search
    document.getElementById('employeeSearch').addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.employee-card').forEach(card => {
            const name = card.dataset.name;
            const position = card.dataset.position;
            if (name.includes(search) || position.includes(search)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
    
    // Employee Selection
    function selectEmployee(element, empId, empName) {
        document.querySelectorAll('.employee-card').forEach(card => {
            card.classList.remove('selected');
        });
        element.classList.add('selected');
        
        selectedEmployeeId = empId;
        selectedEmployeeName = empName;
        document.getElementById('submitter_id').value = empId;
        document.getElementById('selectedEmployeeName').textContent = empName;
        
        // Enable next button
        const nextBtn = document.getElementById('nextStep1');
        nextBtn.disabled = false;
        nextBtn.classList.remove('bg-slate-200', 'text-slate-400', 'cursor-not-allowed', 'disabled:opacity-60');
        nextBtn.classList.add('bg-emerald-600', 'text-white', 'hover:bg-emerald-700', 'shadow-md', 'hover:shadow-lg');
        nextBtn.innerHTML = '<span>Continue</span><i class="fas fa-arrow-right ml-2"></i>';
    }
    
    // Department Selection
    function selectDepartment(element, deptId, deptCode) {
        document.querySelectorAll('.dept-card').forEach(card => {
            card.classList.remove('selected');
        });
        element.classList.add('selected');
        
        selectedDepartmentId = deptId;
        selectedDepartmentCode = deptCode;
        document.getElementById('department_id').value = deptId;
        
        const dept = departmentsData.find(d => d.id == deptId);
        document.getElementById('selectedDeptName').textContent = dept ? dept.name : '';
        
        // Enable next button
        const nextBtn = document.getElementById('nextStep2');
        nextBtn.disabled = false;
        nextBtn.classList.remove('bg-slate-200', 'text-slate-400', 'cursor-not-allowed', 'disabled:opacity-60');
        nextBtn.classList.add('bg-emerald-600', 'text-white', 'hover:bg-emerald-700', 'shadow-md', 'hover:shadow-lg');
        nextBtn.innerHTML = '<span>Continue</span><i class="fas fa-arrow-right ml-2"></i>';
        
        populateCategories(deptId);
    }
    
    // Populate Categories
    function populateCategories(deptId) {
        const grid = document.getElementById('categoryGrid');
        const select = document.getElementById('category_id');
        const subSection = document.getElementById('subCategorySection');
        
        subSection.classList.add('hidden');
        selectedParentCategoryId = null;
        
        const deptCategories = categoriesData.filter(cat => {
            const matchesDept = Number(cat.department_id) === Number(deptId);
            const isParent = !cat.parent_id;
            return matchesDept && isParent;
        }).sort((a, b) => a.name.localeCompare(b.name));
        
        grid.innerHTML = '';
        select.innerHTML = '<option value="">Select category...</option>';
        
        if (deptCategories.length === 0) {
            grid.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500"><i class="fas fa-folder-open text-4xl mb-3 text-gray-300"></i><p>No categories available</p></div>';
            return;
        }
        
        deptCategories.forEach((cat, index) => {
            const hasChildren = categoriesData.some(c => Number(c.parent_id) === Number(cat.id));
            const childCount = categoriesData.filter(c => Number(c.parent_id) === Number(cat.id)).length;
            
            const card = document.createElement('div');
            card.className = 'category-card relative p-4 border-2 border-gray-200 rounded-xl bg-white';
            card.dataset.categoryId = cat.id;
            card.dataset.hasChildren = hasChildren;
            card.onclick = () => selectCategory(cat.id, hasChildren);
            
            const icon = cat.icon || 'folder';
            const color = cat.color || '#6B7280';
            const description = cat.description || '';
            
            card.innerHTML = `
                <div class="cat-check absolute top-3 right-3 w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center opacity-0 transition-all shadow-md">
                    <i class="fas fa-check text-white text-xs"></i>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="cat-icon w-11 h-11 rounded-xl flex items-center justify-center shrink-0 transition-all" style="background-color: ${color}15;">
                        <i class="fas fa-${icon} text-lg transition-colors" style="color: ${color};"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800">${cat.name}</p>
                        ${description ? `<p class="text-xs text-gray-500 mt-0.5 line-clamp-2">${description}</p>` : ''}
                        ${hasChildren ? `<p class="text-xs text-emerald-600 mt-1 font-medium"><i class="fas fa-layer-group mr-1"></i>${childCount} specific options</p>` : ''}
                    </div>
                </div>
            `;
            grid.appendChild(card);
            
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.name;
            select.appendChild(option);
        });
    }
    
    // Category Selection
    function selectCategory(catId, hasChildren) {
        document.querySelectorAll('.category-card').forEach(card => {
            card.classList.remove('selected');
        });
        document.querySelectorAll('.subcategory-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        const selectedCard = document.querySelector(`.category-card[data-category-id="${catId}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }
        
        selectedParentCategoryId = catId;
        selectedCategoryId = catId;
        document.getElementById('category_id').value = catId;
        
        // Auto-set priority based on category mapping
        applyAutoPriority(catId);
        
        if (hasChildren) {
            showSubCategories(catId);
        } else {
            document.getElementById('subCategorySection').classList.add('hidden');
        }
    }
    
    // Show Sub-categories
    function showSubCategories(parentId) {
        const subSection = document.getElementById('subCategorySection');
        const subGrid = document.getElementById('subCategoryGrid');
        const select = document.getElementById('category_id');
        const subCategories = categoriesData.filter(cat => Number(cat.parent_id) === Number(parentId))
            .sort((a, b) => a.name.localeCompare(b.name));
        
        if (subCategories.length === 0) {
            subSection.classList.add('hidden');
            return;
        }
        
        // Add sub-categories to hidden select
        subCategories.forEach(cat => {
            if (!select.querySelector(`option[value="${cat.id}"]`)) {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.name;
                select.appendChild(option);
            }
        });
        
        subGrid.innerHTML = '';
        
        subCategories.forEach((cat, index) => {
            const card = document.createElement('div');
            card.className = 'subcategory-card relative p-3 border-2 border-gray-200 rounded-lg bg-white';
            card.dataset.categoryId = cat.id;
            card.onclick = (e) => { e.stopPropagation(); selectSubCategory(cat.id); };
            
            const icon = cat.icon || 'tag';
            const color = cat.color || '#8B5CF6';
            
            card.innerHTML = `
                <div class="subcat-check absolute top-2 right-2 w-5 h-5 bg-purple-500 rounded-full flex items-center justify-center opacity-0 transition-all">
                    <i class="fas fa-check text-white text-xs"></i>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background-color: ${color}15;">
                        <i class="fas fa-${icon} text-sm" style="color: ${color};"></i>
                    </div>
                    <p class="text-sm font-medium text-gray-700">${cat.name}</p>
                </div>
            `;
            subGrid.appendChild(card);
        });
        
        subSection.classList.remove('hidden');
    }
    
    // Sub-category Selection
    function selectSubCategory(catId) {
        document.querySelectorAll('.subcategory-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        const selectedCard = document.querySelector(`.subcategory-card[data-category-id="${catId}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }
        
        selectedCategoryId = catId;
        document.getElementById('category_id').value = catId;
        
        // Auto-set priority based on subcategory mapping (more specific)
        applyAutoPriority(catId);
    }
    
    // Clear Sub-category
    function clearSubCategory() {
        document.querySelectorAll('.subcategory-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        if (selectedParentCategoryId) {
            selectedCategoryId = selectedParentCategoryId;
            document.getElementById('category_id').value = selectedParentCategoryId;
            // Revert to parent category priority
            applyAutoPriority(selectedParentCategoryId);
        }
        
        document.getElementById('subCategorySection').classList.add('hidden');
    }
    
    // Auto-set priority based on category priority map from SLA guide
    function applyAutoPriority(categoryId) {
        const mappedPriority = priorityMapData[categoryId];
        const banner = document.getElementById('autoPriorityBanner');
        const overrideSection = document.getElementById('adminOverrideSection');
        const overrideCheckbox = document.getElementById('adminOverrideCheckbox');
        
        if (mappedPriority && !overrideCheckbox?.checked) {
            // Select the mapped priority radio button
            const radio = document.querySelector(`input[name="priority"][value="${mappedPriority}"]`);
            if (radio) radio.checked = true;
            
            // Update banner
            const priorityLabels = { low: 'Low', medium: 'Medium', high: 'High' };
            const slaInfo = {
                high: 'Response: 30 min | Resolution: 1 business day',
                medium: 'Response: 4 hrs | Resolution: 2–3 days',
                low: 'Response: 1 day | Resolution: 3–5 days'
            };
            
            document.getElementById('autoPriorityLabel').textContent = priorityLabels[mappedPriority];
            document.getElementById('slaInfoText').textContent = slaInfo[mappedPriority];
            
            banner.classList.remove('hidden');
            overrideSection.classList.remove('hidden');
            autoPrioritySet = true;
        } else if (!mappedPriority) {
            banner.classList.add('hidden');
            overrideSection.classList.add('hidden');
            autoPrioritySet = false;
        }
    }
    
    // Admin override toggle
    document.getElementById('adminOverrideCheckbox')?.addEventListener('change', function() {
        if (this.checked) {
            // Admin wants to override - enable manual priority selection
            document.getElementById('autoPriorityBanner').classList.add('hidden');
        } else {
            // Re-apply auto priority
            if (selectedCategoryId) {
                applyAutoPriority(selectedCategoryId);
            }
        }
    });
    
    // Step Navigation
    function goToStep(step) {
        if (step === 2 && !selectedEmployeeId) {
            alert('Please select an employee');
            return;
        }
        
        if (step === 3 && !selectedDepartmentId) {
            alert('Please select a department');
            return;
        }
        
        if (step === 4) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const category = document.getElementById('category_id').value;
            
            if (!category) {
                alert('Please select a category');
                return;
            }
            if (!title) {
                alert('Please enter a title');
                document.getElementById('title').focus();
                return;
            }
            if (!description) {
                alert('Please enter a description');
                document.getElementById('description').focus();
                return;
            }
            
            populateReview();
        }
        
        document.querySelectorAll('.form-section').forEach(section => {
            section.classList.remove('active');
        });
        
        document.getElementById('step' + step).classList.add('active');
        updateStepIndicators(step);
        currentStep = step;
    }
    
    function updateStepIndicators(activeStep) {
        for (let i = 1; i <= 4; i++) {
            const indicator = document.getElementById(`step${i}-indicator`);
            const label = document.getElementById(`step${i}-label`);
            const line = document.getElementById(`step-line-${i-1}`);
            
            indicator.classList.remove('active', 'completed');
            indicator.classList.add('bg-gray-100', 'text-gray-400', 'border-gray-200');
            
            if (i < activeStep) {
                indicator.classList.remove('bg-gray-100', 'text-gray-400', 'border-gray-200');
                indicator.classList.add('completed');
                indicator.innerHTML = '<i class="fas fa-check text-sm"></i>';
                if (line) line.classList.add('bg-emerald-500');
                if (label) label.classList.remove('text-gray-400');
            } else if (i === activeStep) {
                indicator.classList.remove('bg-gray-100', 'text-gray-400', 'border-gray-200');
                indicator.classList.add('active');
                indicator.textContent = i;
                if (label) label.classList.remove('text-gray-400');
            } else {
                indicator.textContent = i;
                if (line) line.classList.remove('bg-emerald-500');
                if (label) label.classList.add('text-gray-400');
            }
        }
    }
    
    function populateReview() {
        document.getElementById('reviewEmployee').textContent = selectedEmployeeName || '-';
        
        const dept = departmentsData.find(d => d.id == selectedDepartmentId);
        document.getElementById('reviewDepartment').textContent = dept ? dept.name : '-';
        
        const cat = categoriesData.find(c => c.id == selectedCategoryId);
        document.getElementById('reviewCategory').textContent = cat ? cat.name : '-';
        
        const priority = document.querySelector('input[name="priority"]:checked');
        const priorityLabels = { low: '🟢 Low', medium: '🟡 Medium', high: '� High' };
        document.getElementById('reviewPriority').textContent = priority ? priorityLabels[priority.value] : '-';
        
        const assignedSelect = document.getElementById('assigned_to');
        const assignedText = assignedSelect.value ? assignedSelect.options[assignedSelect.selectedIndex].text : 'Auto-assign';
        document.getElementById('reviewAssigned').textContent = assignedText;
        
        document.getElementById('reviewTitle').textContent = document.getElementById('title').value || '-';
        document.getElementById('reviewDescription').textContent = document.getElementById('description').value || '-';
        
        const attachment = document.getElementById('attachment');
        document.getElementById('reviewAttachment').textContent = attachment.files.length > 0 ? attachment.files[0].name : 'None';
    }
    
    // File Upload
    document.getElementById('attachment').addEventListener('change', function(e) {
        const placeholder = document.getElementById('uploadPlaceholder');
        const preview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        
        if (this.files && this.files[0]) {
            placeholder.classList.add('hidden');
            preview.classList.remove('hidden');
            preview.classList.add('flex');
            fileName.textContent = this.files[0].name;
        } else {
            placeholder.classList.remove('hidden');
            preview.classList.add('hidden');
            preview.classList.remove('flex');
        }
    });
    
    function clearFile(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('attachment').value = '';
        document.getElementById('uploadPlaceholder').classList.remove('hidden');
        document.getElementById('filePreview').classList.add('hidden');
        document.getElementById('filePreview').classList.remove('flex');
    }
    
    // Form Submit
    document.getElementById('createTicketForm').addEventListener('submit', function(e) {
        document.getElementById('submitText').classList.add('hidden');
        document.getElementById('submitLoading').classList.remove('hidden');
        document.getElementById('submitBtn').disabled = true;
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>


