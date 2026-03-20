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

    /* Guided dropdown styles */
    .dropdown-level {
        animation: fadeInUp 0.35s ease forwards;
    }
    .dropdown-level select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236B7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 40px;
    }
    .dropdown-level select:focus {
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
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
            <div class="relative bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-500 px-8 py-6">
                <div class="absolute inset-0 bg-black opacity-5"></div>
                <div class="relative flex items-center space-x-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <i class="fas fa-ticket-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Create Ticket for Employee</h1>
                        <p class="text-emerald-50 text-sm mt-1">Submit a support request on behalf of an employee</p>
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
                <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($_SESSION['admin_ticket_form_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                
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
                                <h2 class="text-xl font-bold text-slate-800">Select Your Concern</h2>
                                <p class="text-sm text-slate-500 mt-0.5">Follow the guided selection for <span id="selectedDeptName" class="font-semibold text-emerald-600"></span></p>
                            </div>
                        </div>
                        <button type="button" onclick="goToStep(2)" class="text-sm text-slate-500 hover:text-emerald-600 flex items-center px-3 py-1.5 hover:bg-slate-100 rounded-lg transition-all">
                            <i class="fas fa-pen mr-1.5 text-xs"></i> Change
                        </button>
                    </div>
                    
                    <!-- Breadcrumb trail -->
                    <div id="selectionBreadcrumb" class="hidden flex items-center flex-wrap gap-1 text-sm bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2.5 mb-5">
                        <i class="fas fa-route text-emerald-500 mr-1.5"></i>
                        <span id="breadcrumbText" class="text-emerald-700 font-medium"></span>
                    </div>

                    <!-- Guided Dropdown Sequence -->
                    <div class="space-y-5 mb-6">
                        
                        <!-- Level 1: Category -->
                        <div class="dropdown-level" id="level1Wrapper">
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center mr-2">
                                    <span class="text-sm font-bold text-blue-600">1</span>
                                </div>
                                Category <span class="text-red-500 ml-1">*</span>
                            </label>
                            <select id="categorySelect" 
                                    class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm transition-all bg-white"
                                    onchange="onCategoryChange(this.value)">
                                <option value="">-- Select a category --</option>
                            </select>
                            <!-- Other text input for category -->
                            <div id="categoryOtherInput" class="hidden mt-2">
                                <input type="text" id="categoryOtherText" 
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                       placeholder="Please describe the concern..." oninput="updateAutoTitle()">
                            </div>
                        </div>
                        
                        <!-- Level 2: Subcategory -->
                        <div class="dropdown-level hidden" id="level2Wrapper">
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center mr-2">
                                    <span class="text-sm font-bold text-purple-600">2</span>
                                </div>
                                Subcategory <span class="text-red-500 ml-1">*</span>
                            </label>
                            <select id="subcategorySelect" 
                                    class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm transition-all bg-white"
                                    onchange="onSubcategoryChange(this.value)">
                                <option value="">-- Select a subcategory --</option>
                            </select>
                            <!-- Other text input for subcategory -->
                            <div id="subcategoryOtherInput" class="hidden mt-2">
                                <input type="text" id="subcategoryOtherText" 
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm"
                                       placeholder="Please specify the subcategory..." oninput="updateAutoTitle()">
                            </div>
                        </div>
                    </div>

                    <!-- Hidden fields (title auto-generated by JS, category set by dropdown) -->
                    <input type="hidden" id="title" name="title" value="">
                    <input type="hidden" id="category_id" name="category_id" value="">
                    
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
                    
                    <!-- Priority -->
                    <div class="mb-6">
                        <label class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                            <div class="w-8 h-8 bg-orange-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-exclamation-triangle text-orange-600 text-sm"></i>
                            </div>
                            Priority <span class="text-red-500 ml-1">*</span>
                        </label>
                        
                        <!-- Auto-priority notice -->
                        <div class="mb-3 p-3 bg-amber-50 border border-amber-300 rounded-xl">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-amber-600 mr-2 mt-0.5"></i>
                                <div class="flex-1">
                                    <span class="text-sm text-amber-800 font-medium">Priority is automatically assigned based on issue type</span>
                                    <p class="text-xs text-amber-700 mt-1">You can change the priority or use the admin override below</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Auto-priority indicator -->
                        <div id="autoPriorityBanner" class="hidden mb-3 p-3 bg-blue-50 border border-blue-200 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-magic text-blue-600 mr-2"></i>
                                    <span class="text-sm text-blue-700">
                                        Priority set to <strong id="autoPriorityLabel"></strong> based on issue type
                                    </span>
                                </div>
                                <span id="slaInfoText" class="text-xs text-blue-600"></span>
                            </div>
                        </div>
                        
                        <div>
                            <label for="prioritySelect" class="block text-sm font-medium text-gray-700 mb-2">Priority Level</label>
                            <select id="prioritySelect" name="priority" class="w-full p-3 border-2 border-gray-200 rounded-xl bg-white focus:border-emerald-500 focus:ring-0 transition-colors">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                            <p id="prioritySlaHint" class="text-xs text-gray-500 mt-2">Response: 24 hours | Resolution: 48-72 hours</p>
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
                    
                    <div class="bg-gray-50 rounded-xl p-6 space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Employee</p>
                                <p class="text-sm font-semibold text-gray-800" id="reviewEmployee">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Department</p>
                                <p class="text-sm font-semibold text-gray-800" id="reviewDepartment">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Priority</p>
                                <p class="text-sm font-semibold text-gray-800" id="reviewPriority">-</p>
                            </div>
                        </div>
                        <!-- Selection path -->
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-2">Concern Path</p>
                            <div id="reviewSelectionPath" class="flex items-center flex-wrap gap-2 text-sm">
                                <span class="text-gray-400">-</span>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-2">Description</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed" id="reviewDescription">-</p>
                        </div>
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Attachment</p>
                            <p class="text-sm font-semibold text-gray-800" id="reviewAttachment">None</p>
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
    const slaTargetsDefault = <?php echo json_encode($slaTargetsDefault ?? [], JSON_HEX_TAG); ?>;
    
    let selectedEmployeeId = null;
    let selectedEmployeeName = null;
    let selectedDepartmentId = null;
    let selectedDepartmentCode = null;
    let currentStep = 1;
    
    // Tracks the selection at each dropdown level
    let selectedLevel1 = null; // category id or 'other'
    let selectedLevel2 = null; // subcategory id or 'other'
    
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
        
        // Populate the Level 1 (Category) dropdown
        populateCategoryDropdown(deptId);
        
        // Update SLA hint
        updatePrioritySlaHint();
    }
    
    // ─── Helpers ───
    function getChildCategories(parentId) {
        var allChildren = categoriesData.filter(function(c) {
            return Number(c.parent_id) === Number(parentId);
        });
        var result = [];
        var seenNames = {};
        
        allChildren.forEach(function(child) {
            if (child.name.startsWith('*')) {
                // Hidden intermediate: collect ITS visible children instead
                categoriesData.forEach(function(gc) {
                    if (Number(gc.parent_id) === Number(child.id) && !gc.name.startsWith('*')) {
                        if (!seenNames[gc.name]) {
                            seenNames[gc.name] = true;
                            result.push(gc);
                        }
                    }
                });
            } else {
                if (!seenNames[child.name]) {
                    seenNames[child.name] = true;
                    result.push(child);
                }
            }
        });
        
        return result.sort(function(a, b) { return a.name.localeCompare(b.name); });
    }
    
    function getCategoryName(catId) {
        const cat = categoriesData.find(c => c.id == catId);
        if (!cat) return '';
        // Strip hidden * prefix if present
        return cat.name.startsWith('*') ? cat.name.substring(1).trim() : cat.name;
    }
    
    // ─── Populate Level 1: Category ───
    function populateCategoryDropdown(deptId) {
        const select = document.getElementById('categorySelect');
        const parentCats = categoriesData
            .filter(c => Number(c.department_id) === Number(deptId) && !c.parent_id && !c.name.startsWith('*'))
            .sort((a, b) => a.name.localeCompare(b.name));
        
        select.innerHTML = '<option value="">-- Select a category --</option>';
        parentCats.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat.id;
            opt.textContent = cat.name;
            select.appendChild(opt);
        });
        // Add "Other" option
        const otherOpt = document.createElement('option');
        otherOpt.value = 'other';
        otherOpt.textContent = 'Other (not listed above)';
        select.appendChild(otherOpt);
        
        // Reset downstream
        resetLevel(2);
        selectedLevel1 = null;
        updateAutoTitle();
    }
    
    // ─── Level 1: Category Changed ───
    function onCategoryChange(value) {
        resetLevel(2);
        selectedLevel1 = value || null;
        
        if (value === 'other') {
            document.getElementById('categoryOtherInput').classList.remove('hidden');
            document.getElementById('level2Wrapper').classList.add('hidden');
            document.getElementById('category_id').value = '';
            applyAutoPriority(null);
        } else if (value) {
            document.getElementById('categoryOtherInput').classList.add('hidden');
            document.getElementById('category_id').value = value;
            applyAutoPriority(value);
            
            // Check if this category has children (subcategories)
            const children = getChildCategories(value);
            if (children.length > 0) {
                populateSubcategoryDropdown(children);
                document.getElementById('level2Wrapper').classList.remove('hidden');
            } else {
                document.getElementById('level2Wrapper').classList.add('hidden');
            }
        } else {
            document.getElementById('categoryOtherInput').classList.add('hidden');
            document.getElementById('level2Wrapper').classList.add('hidden');
            document.getElementById('category_id').value = '';
            applyAutoPriority(null);
        }
        
        updateAutoTitle();
        updateBreadcrumb();
    }
    
    // ─── Populate Level 2: Subcategory ───
    function populateSubcategoryDropdown(children) {
        const select = document.getElementById('subcategorySelect');
        select.innerHTML = '<option value="">-- Select a subcategory --</option>';
        children.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat.id;
            opt.textContent = cat.name;
            select.appendChild(opt);
        });
        const otherOpt = document.createElement('option');
        otherOpt.value = 'other';
        otherOpt.textContent = 'Other (not listed above)';
        select.appendChild(otherOpt);
    }
    
    // ─── Level 2: Subcategory Changed ───
    function onSubcategoryChange(value) {
        selectedLevel2 = value || null;
        
        if (value === 'other') {
            document.getElementById('subcategoryOtherInput').classList.remove('hidden');
            if (selectedLevel1 && selectedLevel1 !== 'other') {
                document.getElementById('category_id').value = selectedLevel1;
            }
            applyAutoPriority(selectedLevel1);
        } else if (value) {
            document.getElementById('subcategoryOtherInput').classList.add('hidden');
            document.getElementById('category_id').value = value;
            applyAutoPriority(value);
        } else {
            document.getElementById('subcategoryOtherInput').classList.add('hidden');
            if (selectedLevel1 && selectedLevel1 !== 'other') {
                document.getElementById('category_id').value = selectedLevel1;
                applyAutoPriority(selectedLevel1);
            }
        }
        
        updateAutoTitle();
        updateBreadcrumb();
    }
    
    // ─── Reset a dropdown level ───
    function resetLevel(level) {
        if (level <= 2) {
            selectedLevel2 = null;
            document.getElementById('subcategorySelect').innerHTML = '<option value="">-- Select a subcategory --</option>';
            document.getElementById('subcategoryOtherInput').classList.add('hidden');
            document.getElementById('subcategoryOtherText').value = '';
            document.getElementById('level2Wrapper').classList.add('hidden');
        }
    }
    
    // ─── Auto-Generate Title ───
    function updateAutoTitle() {
        const parts = [];
        
        // Level 1
        if (selectedLevel1 === 'other') {
            const otherText = document.getElementById('categoryOtherText').value.trim();
            parts.push(otherText ? 'Other: ' + otherText : 'Other');
        } else if (selectedLevel1) {
            parts.push(getCategoryName(selectedLevel1));
        }
        
        // Level 2
        if (selectedLevel2 === 'other') {
            const otherText = document.getElementById('subcategoryOtherText').value.trim();
            parts.push(otherText ? 'Other: ' + otherText : 'Other');
        } else if (selectedLevel2) {
            parts.push(getCategoryName(selectedLevel2));
        }
        
        const title = parts.join(' - ');
        document.getElementById('title').value = title || '';
    }
    
    // ─── Breadcrumb ───
    function updateBreadcrumb() {
        const breadcrumb = document.getElementById('selectionBreadcrumb');
        const textEl = document.getElementById('breadcrumbText');
        const parts = [];
        
        if (selectedLevel1 === 'other') {
            const t = document.getElementById('categoryOtherText').value.trim();
            parts.push(t ? 'Other: ' + t : 'Other');
        } else if (selectedLevel1) {
            parts.push(getCategoryName(selectedLevel1));
        }
        
        if (selectedLevel2 === 'other') {
            const t = document.getElementById('subcategoryOtherText').value.trim();
            parts.push(t ? 'Other: ' + t : 'Other');
        } else if (selectedLevel2) {
            parts.push(getCategoryName(selectedLevel2));
        }
        
        if (parts.length > 0) {
            const colors = ['text-blue-700', 'text-purple-700'];
            textEl.innerHTML = parts.map(function(p, i) {
                var separator = i < parts.length - 1 ? ' <i class="fas fa-chevron-right text-emerald-400 text-xs mx-1"></i> ' : '';
                return '<span class="' + colors[i] + ' font-medium">' + p + '</span>' + separator;
            }).join('');
            breadcrumb.classList.remove('hidden');
        } else {
            breadcrumb.classList.add('hidden');
        }
    }
    
    // ─── Auto-Priority ───
    function applyAutoPriority(categoryId) {
        const banner = document.getElementById('autoPriorityBanner');
        const overrideSection = document.getElementById('adminOverrideSection');
        const overrideCheckbox = document.getElementById('adminOverrideCheckbox');
        const mappedPriority = categoryId ? priorityMapData[categoryId] : null;
        const prioritySelect = document.getElementById('prioritySelect');
        
        // Get department-specific SLA info
        const deptCode = selectedDepartmentCode ? selectedDepartmentCode.toUpperCase() : 'HR';
        const deptSla = slaTargetsData[deptCode] || slaTargetsData['HR'] || slaTargetsDefault || {};

        if (mappedPriority && !overrideCheckbox?.checked) {
            if (prioritySelect) {
                prioritySelect.value = mappedPriority;
            }
            const labels = { low: 'Low', medium: 'Medium', high: 'High' };
            const slaHigh = deptSla.high || {};
            const slaMed = deptSla.medium || {};
            const slaLow = deptSla.low || {};
            const slaInfo = {
                high: 'Response: ' + (slaHigh.response || '24h') + ' | Resolution: ' + (slaHigh.resolution || '24h'),
                medium: 'Response: ' + (slaMed.response || '24h') + ' | Resolution: ' + (slaMed.resolution || '48–72h'),
                low: 'Response: ' + (slaLow.response || '24h') + ' | Resolution: ' + (slaLow.resolution || '56–120h')
            };
            document.getElementById('autoPriorityLabel').textContent = labels[mappedPriority];
            document.getElementById('slaInfoText').textContent = slaInfo[mappedPriority];
            banner.classList.remove('hidden');
            if (overrideSection) overrideSection.classList.remove('hidden');
        } else if (!mappedPriority) {
            if (prioritySelect) {
                prioritySelect.value = 'medium';
            }
            banner.classList.add('hidden');
            if (overrideSection) overrideSection.classList.add('hidden');
        }

        updatePrioritySlaHint();
    }

    // ─── Update SLA Hint for Selected Priority ───
    function updatePrioritySlaHint() {
        const prioritySelect = document.getElementById('prioritySelect');
        const selectedPriority = prioritySelect ? prioritySelect.value : 'medium';
        const deptCode = selectedDepartmentCode ? selectedDepartmentCode.toUpperCase() : 'HR';
        const deptSla = slaTargetsData[deptCode] || slaTargetsData['HR'] || slaTargetsDefault || {};

        const sla = (deptSla && deptSla[selectedPriority]) ? deptSla[selectedPriority] : null;
        const hint = document.getElementById('prioritySlaHint');
        if (hint) {
            const response = sla && sla.response ? sla.response : '24 hours';
            const resolution = sla && sla.resolution ? sla.resolution : '48-72 hours';
            hint.textContent = 'Response: ' + response + ' | Resolution: ' + resolution;
        }
    }
    
    // Admin override toggle
    document.getElementById('adminOverrideCheckbox')?.addEventListener('change', function() {
        if (this.checked) {
            // Admin wants to override - hide auto banner
            document.getElementById('autoPriorityBanner').classList.add('hidden');
        } else {
            // Re-apply auto priority
            const catId = document.getElementById('category_id').value;
            if (catId) {
                applyAutoPriority(catId);
            }
        }
    });

    // Priority select change listener
    document.getElementById('prioritySelect')?.addEventListener('change', function() {
        updatePrioritySlaHint();

        // Automatically enable override when admin manually changes priority
        const overrideCheckbox = document.getElementById('adminOverrideCheckbox');
        if (overrideCheckbox && !overrideCheckbox.checked) {
            overrideCheckbox.checked = true;
            // Hide the auto-priority banner since we're now overriding
            document.getElementById('autoPriorityBanner')?.classList.add('hidden');
        }
    });
    
    // Step Navigation
    function goToStep(step) {
        if (step === 2 && !selectedEmployeeId) {
            showAlert('Please select an employee', 'warning');
            return;
        }
        
        if (step === 3 && !selectedDepartmentId) {
            showAlert('Please select a department', 'warning');
            return;
        }
        
        if (step === 4) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const categoryId = document.getElementById('category_id').value;
            
            if (!selectedLevel1) {
                showAlert('Please select a category', 'warning');
                return;
            }
            if (selectedLevel1 === 'other' && !document.getElementById('categoryOtherText').value.trim()) {
                showAlert('Please describe the concern in the "Other" text field', 'warning');
                document.getElementById('categoryOtherText').focus();
                return;
            }
            if (!title) {
                showAlert('Please make a selection to generate the ticket title', 'warning');
                return;
            }
            if (!description) {
                showAlert('Please enter a description', 'warning');
                document.getElementById('description').focus();
                return;
            }
            
            // If category_id is empty ("Other" selected at Level 1), use fallback
            if (!categoryId) {
                var generalParent = categoriesData.find(function(c) {
                    return Number(c.department_id) === Number(selectedDepartmentId) 
                        && !c.parent_id 
                        && (c.name === 'General Inquiry' || c.name === 'IT General Inquiry');
                });
                if (generalParent) {
                    var othersSub = categoriesData.find(function(c) {
                        return Number(c.parent_id) === Number(generalParent.id) && c.name === 'Others';
                    });
                    document.getElementById('category_id').value = othersSub ? othersSub.id : generalParent.id;
                } else {
                    var fallback = categoriesData.find(function(c) {
                        return Number(c.department_id) === Number(selectedDepartmentId) && !c.parent_id && !c.name.startsWith('*');
                    });
                    document.getElementById('category_id').value = fallback ? fallback.id : '0';
                }
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
        
        // Selection path (visual breadcrumb in review)
        const pathContainer = document.getElementById('reviewSelectionPath');
        const pathParts = [];
        
        if (selectedLevel1 === 'other') {
            const t1 = document.getElementById('categoryOtherText').value.trim();
            pathParts.push(t1 ? 'Other: ' + t1 : 'Other');
        } else if (selectedLevel1) {
            pathParts.push(getCategoryName(selectedLevel1));
        }
        if (selectedLevel2 === 'other') {
            const t2 = document.getElementById('subcategoryOtherText').value.trim();
            pathParts.push(t2 ? 'Other: ' + t2 : 'Other');
        } else if (selectedLevel2) {
            pathParts.push(getCategoryName(selectedLevel2));
        }
        
        const colors = ['bg-blue-100 text-blue-700', 'bg-purple-100 text-purple-700'];
        pathContainer.innerHTML = pathParts.map(function(p, i) {
            const arrow = i < pathParts.length - 1 ? '<i class="fas fa-arrow-right text-gray-400 text-xs"></i>' : '';
            return '<span class="px-2.5 py-1 rounded-lg text-xs font-medium ' + colors[i] + '">' + p + '</span>' + arrow;
        }).join(' ');
        
        // Priority
        const priority = document.getElementById('prioritySelect');
        const priorityLabels = { low: '🟢 Low', medium: '🟡 Medium', high: '🔴 High' };
        document.getElementById('reviewPriority').textContent = priority ? priorityLabels[priority.value] : '-';
        
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
    
    // ─── Custom Alert Modal ───
    function showAlert(message, type) {
        type = type || 'warning';
        var overlay = document.getElementById('alertOverlay');
        var icon = document.getElementById('alertIcon');
        var msg = document.getElementById('alertMessage');
        var btn = document.getElementById('alertOkBtn');
        
        msg.textContent = message;
        
        if (type === 'error') {
            icon.className = 'fas fa-times-circle text-4xl text-red-500';
            btn.className = 'w-full px-6 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all font-medium text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2';
        } else if (type === 'success') {
            icon.className = 'fas fa-check-circle text-4xl text-emerald-500';
            btn.className = 'w-full px-6 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-all font-medium text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2';
        } else {
            icon.className = 'fas fa-exclamation-triangle text-4xl text-amber-500';
            btn.className = 'w-full px-6 py-2.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-all font-medium text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2';
        }
        
        overlay.classList.remove('hidden');
        requestAnimationFrame(function() {
            overlay.classList.add('show');
        });
        btn.focus();
    }
    
    function closeAlert() {
        var overlay = document.getElementById('alertOverlay');
        overlay.classList.remove('show');
        setTimeout(function() { overlay.classList.add('hidden'); }, 200);
    }
    
    document.getElementById('alertOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeAlert();
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !document.getElementById('alertOverlay').classList.contains('hidden')) closeAlert();
    });

    // Form Submit
    document.getElementById('createTicketForm').addEventListener('submit', function(e) {
        // Final check: ensure title is set
        if (!document.getElementById('title').value.trim()) {
            e.preventDefault();
            showAlert('Please complete your category selections to generate a title.', 'warning');
            goToStep(3);
            return false;
        }
        
        document.getElementById('submitText').classList.add('hidden');
        document.getElementById('submitLoading').classList.remove('hidden');
        document.getElementById('submitBtn').disabled = true;
    });
</script>

<!-- Alert Modal -->
<div id="alertOverlay" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0);transition:background .2s ease">
    <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6 transform scale-95 transition-all duration-200" id="alertBox" style="opacity:0;transition:opacity .2s ease,transform .2s ease">
        <div class="text-center">
            <div class="mb-4">
                <i id="alertIcon" class="fas fa-exclamation-triangle text-4xl text-amber-500"></i>
            </div>
            <p id="alertMessage" class="text-gray-700 text-sm leading-relaxed mb-6"></p>
            <button id="alertOkBtn" onclick="closeAlert()" class="w-full px-6 py-2.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-all font-medium text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                OK
            </button>
        </div>
    </div>
</div>
<style>
    #alertOverlay.show { background: rgba(0,0,0,0.5) !important; }
    #alertOverlay.show #alertBox { opacity: 1 !important; transform: scale(1) !important; }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>


