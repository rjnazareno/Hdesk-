<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Request - <?php echo defined('APP_NAME') ? APP_NAME : 'ServiceDesk'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* Category cards - improved */
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
        .category-card {
            animation: fadeInUp 0.3s ease forwards;
        }
        .category-card:nth-child(1) { animation-delay: 0.05s; }
        .category-card:nth-child(2) { animation-delay: 0.1s; }
        .category-card:nth-child(3) { animation-delay: 0.15s; }
        .category-card:nth-child(4) { animation-delay: 0.2s; }
        .category-card:nth-child(5) { animation-delay: 0.25s; }
        .category-card:nth-child(6) { animation-delay: 0.3s; }
        
        /* Line clamp for descriptions */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Sub-category cards */
        .subcategory-card {
            transition: all 0.2s ease;
            cursor: pointer;
            animation: fadeInUp 0.25s ease forwards;
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
        
        /* Has children indicator */
        .has-children::after {
            content: '';
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
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
        .auto-title-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 1px solid #bbf7d0;
        }
        .auto-title-box.has-title {
            border-color: #10b981;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php $basePath = ''; include __DIR__ . '/../../includes/customer_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <?php include __DIR__ . '/../../includes/customer_header.php'; ?>

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

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl mb-6 flex items-center shadow-sm">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-exclamation-circle text-red-600"></i>
                </div>
                <div class="flex-1">
                    <p class="font-medium">Error</p>
                    <p class="text-sm"><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Form Container -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                
                <!-- Header -->
                <div class="relative bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-500 px-8 py-6">
                    <div class="absolute inset-0 bg-black opacity-5"></div>
                    <div class="relative flex items-center space-x-4">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <i class="fas fa-headset text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">Submit a Request</h1>
                            <p class="text-emerald-50 text-sm mt-1">We're here to help. Choose your department and describe your issue.</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Steps - Inside Container -->
                <div class="bg-gray-50 border-b border-gray-100 px-8 py-5">
                    <div class="flex items-center justify-center">
                        <div class="flex items-center">
                            <!-- Step 1 -->
                            <div class="flex items-center">
                                <div id="step1-indicator" class="step-indicator active w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold border-2 border-emerald-500">1</div>
                                <span class="ml-2 text-sm font-medium text-gray-700 hidden sm:inline">Department</span>
                            </div>
                            
                            <!-- Line 1 -->
                            <div class="w-12 sm:w-20 h-0.5 mx-3 bg-gray-200 transition-colors duration-300" id="step-line-1"></div>
                            
                            <!-- Step 2 -->
                            <div class="flex items-center">
                                <div id="step2-indicator" class="step-indicator w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold bg-gray-100 text-gray-400 border-2 border-gray-200">2</div>
                                <span class="ml-2 text-sm font-medium text-gray-400 hidden sm:inline" id="step2-label">Details</span>
                            </div>
                            
                            <!-- Line 2 -->
                            <div class="w-12 sm:w-20 h-0.5 mx-3 bg-gray-200 transition-colors duration-300" id="step-line-2"></div>
                            
                            <!-- Step 3 -->
                            <div class="flex items-center">
                                <div id="step3-indicator" class="step-indicator w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold bg-gray-100 text-gray-400 border-2 border-gray-200">3</div>
                                <span class="ml-2 text-sm font-medium text-gray-400 hidden sm:inline" id="step3-label">Review</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="create_ticket.php" method="POST" enctype="multipart/form-data" id="createTicketForm" class="p-8">
                    
                    <!-- STEP 1: Department Selection -->
                    <div id="step1" class="form-section active">
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Which department can help you?</h2>
                        <p class="text-sm text-gray-500 mb-6">Select the department that best matches your request.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($departments as $dept): ?>
                            <div class="dept-card relative p-6 border-2 border-gray-200 rounded-2xl bg-white hover:shadow-lg" 
                                 data-department-id="<?php echo $dept['id']; ?>"
                                 onclick="selectDepartment(this, <?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['code']); ?>')">
                                
                                <!-- Check mark -->
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
                                        
                                        <!-- Sample categories preview -->
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
                        
                        <input type="hidden" name="department_id" id="department_id" value="" required>
                        
                        <div class="flex justify-end mt-8 pt-6 border-t border-gray-100">
                            <button type="button" id="nextStep1" onclick="goToStep(2)" disabled
                                    class="inline-flex items-center px-6 py-3 bg-gray-200 text-gray-400 rounded-xl cursor-not-allowed transition-all">
                                Continue
                                <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: Ticket Details -->
                    <div id="step2" class="form-section">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Select Your Concern</h2>
                                <p class="text-sm text-gray-500 mt-1">Follow the guided selection for <span id="selectedDeptName" class="font-medium text-emerald-600"></span></p>
                            </div>
                            <button type="button" onclick="goToStep(1)" class="text-sm text-gray-500 hover:text-emerald-600 flex items-center">
                                <i class="fas fa-arrow-left mr-1"></i> Change Department
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
                                           placeholder="Please describe your concern..." oninput="updateAutoTitle()">
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
                                           placeholder="Please specify your subcategory..." oninput="updateAutoTitle()">
                                </div>
                            </div>
                            
                            <!-- Level 3: Specific Concern -->
                            <div class="dropdown-level hidden" id="level3Wrapper">
                                <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                    <div class="w-8 h-8 bg-teal-50 rounded-lg flex items-center justify-center mr-2">
                                        <span class="text-sm font-bold text-teal-600">3</span>
                                    </div>
                                    Specific Concern <span class="text-red-500 ml-1">*</span>
                                </label>
                                <select id="specificConcernSelect" 
                                        class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm transition-all bg-white"
                                        onchange="onSpecificConcernChange(this.value)">
                                    <option value="">-- Select specific concern --</option>
                                </select>
                                <!-- Other text input for specific concern -->
                                <div id="specificOtherInput" class="hidden mt-2">
                                    <input type="text" id="specificOtherText" 
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm"
                                           placeholder="Please describe your specific concern..." oninput="updateAutoTitle()">
                                </div>
                            </div>
                        </div>

                        <!-- Auto-Generated Title -->
                        <div class="mb-6">
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center mr-2">
                                    <i class="fas fa-heading text-emerald-600 text-sm"></i>
                                </div>
                                Ticket Title
                            </label>
                            <div id="autoTitleDisplay" class="auto-title-box w-full px-4 py-3.5 rounded-xl text-sm text-gray-700 min-h-[48px] flex items-center transition-all">
                                <span class="text-gray-400 italic" id="autoTitlePlaceholder">Title will be generated from your selections above</span>
                                <span class="hidden font-medium text-gray-800" id="autoTitleText"></span>
                            </div>
                            <input type="hidden" id="title" name="title" value="">
                            <p class="text-xs text-gray-400 mt-1.5"><i class="fas fa-info-circle mr-1"></i>Auto-generated from your dropdown selections</p>
                        </div>

                        <!-- Hidden category_id (set by JS) -->
                        <input type="hidden" id="category_id" name="category_id" value="">
                        
                        <!-- Description (below title for better context) -->
                        <div class="mb-6">
                            <label for="description" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                                <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center mr-2">
                                    <i class="fas fa-align-left text-purple-600 text-sm"></i>
                                </div>
                                Description <span class="text-red-500 ml-1">*</span>
                            </label>
                            <textarea id="description" name="description" rows="5" required
                                      class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm transition-all resize-none"
                                      placeholder="Please provide detailed information about your request..."></textarea>
                        </div>
                        
                        <!-- Priority (Auto-assigned) -->
                        <div class="mb-6">
                            <label for="priority" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
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
                                        <span class="text-sm text-amber-800 font-medium">Priority is automatically assigned based on your issue type</span>
                                        <p class="text-xs text-amber-700 mt-1">You can change the priority if needed. Our team will review and adjust if necessary</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Auto-priority indicator -->
                            <div id="autoPriorityBanner" class="hidden mb-3 p-3 bg-blue-50 border border-blue-200 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-magic text-blue-600 mr-2"></i>
                                        <span class="text-sm text-blue-700">
                                            Priority set to <strong id="autoPriorityLabel"></strong> based on your issue type
                                        </span>
                                    </div>
                                    <span id="slaInfoText" class="text-xs text-blue-600"></span>
                                </div>
                            </div>
                            
                            <div>
                                <label for="prioritySelect" class="block text-sm font-medium text-gray-700 mb-2">Priority Level</label>
                                <select id="prioritySelect" name="priority" class="w-full p-3 border-2 border-gray-200 rounded-xl bg-white focus:border-cyan-500 focus:ring-0 transition-colors">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                                <p id="prioritySlaHint" class="text-xs text-gray-500 mt-2">Response: 24 hours | Resolution: 48-72 hours</p>
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
                            <label for="attachment" class="flex items-center justify-center w-full h-24 border-2 border-gray-200 border-dashed rounded-xl cursor-pointer hover:border-emerald-500 hover:bg-emerald-50 transition-all">
                                <div class="text-center" id="uploadPlaceholder">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2"></i>
                                    <p class="text-sm text-gray-600">Click to upload or drag and drop</p>
                                    <p class="text-xs text-gray-400">PNG, JPG, PDF, DOC up to 5MB</p>
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
                            <button type="button" onclick="goToStep(1)" class="inline-flex items-center px-6 py-3 border-2 border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl transition-all text-sm font-medium">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back
                            </button>
                            <button type="button" onclick="goToStep(3)" id="nextStep2"
                                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white hover:from-emerald-600 hover:to-teal-700 rounded-xl transition-all text-sm font-semibold shadow-lg shadow-emerald-500/30">
                                Review Request
                                <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: Review & Submit -->
                    <div id="step3" class="form-section">
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Review Your Request</h2>
                        <p class="text-sm text-gray-500 mb-6">Please review your information before submitting.</p>
                        
                        <div class="bg-gray-50 rounded-xl p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Department</p>
                                    <p class="text-sm font-medium text-gray-800" id="reviewDepartment">-</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Priority</p>
                                    <p class="text-sm font-medium text-gray-800" id="reviewPriority">-</p>
                                </div>
                            </div>
                            <!-- Selection path -->
                            <div class="border-t border-gray-200 pt-4">
                                <p class="text-xs text-gray-500 uppercase mb-2">Concern Path</p>
                                <div id="reviewSelectionPath" class="flex items-center flex-wrap gap-2 text-sm">
                                    <span class="text-gray-400">-</span>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 pt-4">
                                <p class="text-xs text-gray-500 uppercase">Ticket Title</p>
                                <p class="text-sm font-semibold text-gray-800" id="reviewTitle">-</p>
                            </div>
                            <div class="border-t border-gray-200 pt-4">
                                <p class="text-xs text-gray-500 uppercase">Description</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap" id="reviewDescription">-</p>
                            </div>
                            <div class="border-t border-gray-200 pt-4 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Attachment</p>
                                    <p class="text-sm font-medium text-gray-800" id="reviewAttachment">None</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-100">
                            <button type="button" onclick="goToStep(2)" class="inline-flex items-center px-6 py-3 border-2 border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl transition-all text-sm font-medium">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Edit Details
                            </button>
                            <button type="submit" id="submitBtn"
                                    class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white hover:from-emerald-600 hover:to-teal-700 rounded-xl transition-all text-sm font-semibold shadow-lg shadow-emerald-500/30">
                                <span id="submitText" class="flex items-center">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    Submit Request
                                </span>
                                <span id="submitLoading" class="hidden flex items-center">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Submitting...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ─── Data from PHP ───
        const categoriesData = <?php echo json_encode($categories, JSON_NUMERIC_CHECK); ?>;
        const departmentsData = <?php echo json_encode($departments, JSON_NUMERIC_CHECK); ?>;
        const priorityMapData = <?php echo json_encode($priorityMap ?? [], JSON_NUMERIC_CHECK); ?>;
        const slaTargetsData = <?php echo json_encode($slaTargets ?? [], JSON_HEX_TAG); ?>;
        const slaTargetsDefault = <?php echo json_encode($slaTargetsDefault ?? [], JSON_HEX_TAG); ?>;
        
        // ─── State ───
        let selectedDepartmentId = null;
        let selectedDepartmentCode = null;
        let currentStep = 1;
        
        // Tracks the selection at each dropdown level
        let selectedLevel1 = null; // category id or 'other'
        let selectedLevel2 = null; // subcategory id or 'other'
        let selectedLevel3 = null; // specific concern id or 'other'
        
        // ─── Department Selection (Step 1) ───
        function selectDepartment(element, deptId, deptCode) {
            document.querySelectorAll('.dept-card').forEach(c => c.classList.remove('selected'));
            element.classList.add('selected');
            
            selectedDepartmentId = deptId;
            selectedDepartmentCode = deptCode;
            document.getElementById('department_id').value = deptId;
            
            const dept = departmentsData.find(d => d.id == deptId);
            document.getElementById('selectedDeptName').textContent = dept ? dept.name : '';
            
            const nextBtn = document.getElementById('nextStep1');
            nextBtn.disabled = false;
            nextBtn.classList.remove('bg-gray-200', 'text-gray-400', 'cursor-not-allowed');
            nextBtn.classList.add('bg-gradient-to-r', 'from-emerald-500', 'to-teal-600', 'text-white', 'hover:from-emerald-600', 'hover:to-teal-700', 'shadow-lg', 'shadow-emerald-500/30');
            
            // Populate the Level 1 (Category) dropdown
            populateCategoryDropdown(deptId);
            
            // Update SLA hint text based on selected department and priority
            updatePrioritySlaHint();
        }
        
        // ─── Helpers ───
        function getChildCategories(parentId) {
            return categoriesData
                .filter(c => Number(c.parent_id) === Number(parentId))
                .sort((a, b) => a.name.localeCompare(b.name));
        }
        
        function getCategoryName(catId) {
            const cat = categoriesData.find(c => c.id == catId);
            return cat ? cat.name : '';
        }
        
        // ─── Populate Level 1: Category ───
        function populateCategoryDropdown(deptId) {
            const select = document.getElementById('categorySelect');
            const parentCats = categoriesData
                .filter(c => Number(c.department_id) === Number(deptId) && !c.parent_id)
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
            resetLevel(3);
            selectedLevel1 = null;
            updateAutoTitle();
        }
        
        // ─── Level 1: Category Changed ───
        function onCategoryChange(value) {
            resetLevel(2);
            resetLevel(3);
            selectedLevel1 = value || null;
            
            if (value === 'other') {
                document.getElementById('categoryOtherInput').classList.remove('hidden');
                document.getElementById('level2Wrapper').classList.add('hidden');
                document.getElementById('level3Wrapper').classList.add('hidden');
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
                    document.getElementById('level3Wrapper').classList.add('hidden');
                }
            } else {
                document.getElementById('categoryOtherInput').classList.add('hidden');
                document.getElementById('level2Wrapper').classList.add('hidden');
                document.getElementById('level3Wrapper').classList.add('hidden');
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
            resetLevel(3);
            selectedLevel2 = value || null;
            
            if (value === 'other') {
                document.getElementById('subcategoryOtherInput').classList.remove('hidden');
                document.getElementById('level3Wrapper').classList.add('hidden');
                if (selectedLevel1 && selectedLevel1 !== 'other') {
                    document.getElementById('category_id').value = selectedLevel1;
                }
                applyAutoPriority(selectedLevel1);
            } else if (value) {
                document.getElementById('subcategoryOtherInput').classList.add('hidden');
                document.getElementById('category_id').value = value;
                applyAutoPriority(value);
                
                // Check if this subcategory also has children (specific concerns)
                const children = getChildCategories(value);
                if (children.length > 0) {
                    populateSpecificConcernDropdown(children);
                    document.getElementById('level3Wrapper').classList.remove('hidden');
                } else {
                    document.getElementById('level3Wrapper').classList.add('hidden');
                }
            } else {
                document.getElementById('subcategoryOtherInput').classList.add('hidden');
                document.getElementById('level3Wrapper').classList.add('hidden');
                if (selectedLevel1 && selectedLevel1 !== 'other') {
                    document.getElementById('category_id').value = selectedLevel1;
                    applyAutoPriority(selectedLevel1);
                }
            }
            
            updateAutoTitle();
            updateBreadcrumb();
        }
        
        // ─── Populate Level 3: Specific Concern ───
        function populateSpecificConcernDropdown(children) {
            const select = document.getElementById('specificConcernSelect');
            select.innerHTML = '<option value="">-- Select specific concern --</option>';
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
        
        // ─── Level 3: Specific Concern Changed ───
        function onSpecificConcernChange(value) {
            selectedLevel3 = value || null;
            
            if (value === 'other') {
                document.getElementById('specificOtherInput').classList.remove('hidden');
                if (selectedLevel2 && selectedLevel2 !== 'other') {
                    document.getElementById('category_id').value = selectedLevel2;
                }
                applyAutoPriority(selectedLevel2);
            } else if (value) {
                document.getElementById('specificOtherInput').classList.add('hidden');
                document.getElementById('category_id').value = value;
                applyAutoPriority(value);
            } else {
                document.getElementById('specificOtherInput').classList.add('hidden');
                if (selectedLevel2 && selectedLevel2 !== 'other') {
                    document.getElementById('category_id').value = selectedLevel2;
                    applyAutoPriority(selectedLevel2);
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
            if (level <= 3) {
                selectedLevel3 = null;
                document.getElementById('specificConcernSelect').innerHTML = '<option value="">-- Select specific concern --</option>';
                document.getElementById('specificOtherInput').classList.add('hidden');
                document.getElementById('specificOtherText').value = '';
                document.getElementById('level3Wrapper').classList.add('hidden');
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
            
            // Level 3
            if (selectedLevel3 === 'other') {
                const otherText = document.getElementById('specificOtherText').value.trim();
                parts.push(otherText ? 'Other: ' + otherText : 'Other');
            } else if (selectedLevel3) {
                parts.push(getCategoryName(selectedLevel3));
            }
            
            const title = parts.join(' - ');
            const titleInput = document.getElementById('title');
            const display = document.getElementById('autoTitleDisplay');
            const placeholder = document.getElementById('autoTitlePlaceholder');
            const textEl = document.getElementById('autoTitleText');
            
            if (title) {
                titleInput.value = title;
                placeholder.classList.add('hidden');
                textEl.classList.remove('hidden');
                textEl.textContent = title;
                display.classList.add('has-title');
            } else {
                titleInput.value = '';
                placeholder.classList.remove('hidden');
                textEl.classList.add('hidden');
                textEl.textContent = '';
                display.classList.remove('has-title');
            }
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
            
            if (selectedLevel3 === 'other') {
                const t = document.getElementById('specificOtherText').value.trim();
                parts.push(t ? 'Other: ' + t : 'Other');
            } else if (selectedLevel3) {
                parts.push(getCategoryName(selectedLevel3));
            }
            
            if (parts.length > 0) {
                const colors = ['text-blue-700', 'text-purple-700', 'text-teal-700'];
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
            const mappedPriority = categoryId ? priorityMapData[categoryId] : null;
            const prioritySelect = document.getElementById('prioritySelect');
            
            // Get department-specific SLA info
            const deptCode = selectedDepartmentCode ? selectedDepartmentCode.toUpperCase() : 'HR';
            const deptSla = slaTargetsData[deptCode] || slaTargetsData['HR'] || slaTargetsDefault;

            if (mappedPriority) {
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
            } else {
                if (prioritySelect) {
                    prioritySelect.value = 'medium';
                }
                banner.classList.add('hidden');
            }

            updatePrioritySlaHint();
        }

        // ─── Update SLA Hint for Selected Priority ───
        function updatePrioritySlaHint() {
            const prioritySelect = document.getElementById('prioritySelect');
            const selectedPriority = prioritySelect ? prioritySelect.value : 'medium';
            const deptCode = selectedDepartmentCode ? selectedDepartmentCode.toUpperCase() : 'HR';
            const deptSla = slaTargetsData[deptCode] || slaTargetsData['HR'] || slaTargetsDefault;

            const sla = (deptSla && deptSla[selectedPriority]) ? deptSla[selectedPriority] : null;
            const hint = document.getElementById('prioritySlaHint');
            if (hint) {
                const response = sla && sla.response ? sla.response : '24 hours';
                const resolution = sla && sla.resolution ? sla.resolution : '48-72 hours';
                hint.textContent = 'Response: ' + response + ' | Resolution: ' + resolution;
            }
        }
        
        // ─── Step Navigation ───
        function goToStep(step) {
            if (step === 2 && !selectedDepartmentId) {
                alert('Please select a department');
                return;
            }
            
            if (step === 3) {
                var title = document.getElementById('title').value.trim();
                var description = document.getElementById('description').value.trim();
                var categoryId = document.getElementById('category_id').value;
                
                if (!selectedLevel1) {
                    alert('Please select a category');
                    return;
                }
                if (selectedLevel1 === 'other' && !document.getElementById('categoryOtherText').value.trim()) {
                    alert('Please describe your concern in the "Other" text field');
                    document.getElementById('categoryOtherText').focus();
                    return;
                }
                if (!title) {
                    alert('Please make a selection to generate the ticket title');
                    return;
                }
                if (!description) {
                    alert('Please enter a description');
                    document.getElementById('description').focus();
                    return;
                }
                
                // If category_id is empty ("Other" selected at Level 1), use the department's
                // General Inquiry / IT General Inquiry "Others" subcategory as fallback
                if (!categoryId) {
                    var generalParent = categoriesData.find(function(c) {
                        return Number(c.department_id) === Number(selectedDepartmentId) 
                            && !c.parent_id 
                            && (c.name === 'General Inquiry' || c.name === 'IT General Inquiry');
                    });
                    if (generalParent) {
                        // Try to find "Others" sub under General Inquiry
                        var othersSub = categoriesData.find(function(c) {
                            return Number(c.parent_id) === Number(generalParent.id) && c.name === 'Others';
                        });
                        document.getElementById('category_id').value = othersSub ? othersSub.id : generalParent.id;
                    } else {
                        // Last resort: first parent in this department
                        var fallback = categoriesData.find(function(c) {
                            return Number(c.department_id) === Number(selectedDepartmentId) && !c.parent_id;
                        });
                        document.getElementById('category_id').value = fallback ? fallback.id : '0';
                    }
                }
                
                populateReview();
            }
            
            document.querySelectorAll('.form-section').forEach(function(s) { s.classList.remove('active'); });
            document.getElementById('step' + step).classList.add('active');
            updateStepIndicators(step);
            currentStep = step;
        }
        
        function updateStepIndicators(activeStep) {
            for (var i = 1; i <= 3; i++) {
                var indicator = document.getElementById('step' + i + '-indicator');
                var label = document.getElementById('step' + i + '-label');
                var line = document.getElementById('step-line-' + (i - 1));
                
                indicator.classList.remove('active', 'completed');
                if (label) { label.classList.remove('text-gray-700', 'text-emerald-600'); label.classList.add('text-gray-400'); }
                
                if (i < activeStep) {
                    indicator.classList.add('completed');
                    indicator.innerHTML = '<i class="fas fa-check text-sm"></i>';
                    if (label) { label.classList.remove('text-gray-400'); label.classList.add('text-emerald-600'); }
                    if (line) line.classList.add('bg-emerald-500');
                } else if (i === activeStep) {
                    indicator.classList.add('active');
                    indicator.textContent = i;
                    if (label) { label.classList.remove('text-gray-400'); label.classList.add('text-gray-700'); }
                } else {
                    indicator.textContent = i;
                    indicator.classList.add('bg-gray-100', 'text-gray-400');
                }
            }
        }
        
        // ─── Populate Review ───
        function populateReview() {
            // Department
            var dept = departmentsData.find(function(d) { return d.id == selectedDepartmentId; });
            document.getElementById('reviewDepartment').textContent = dept ? dept.name : '-';
            
            // Selection path (visual breadcrumb in review)
            var pathContainer = document.getElementById('reviewSelectionPath');
            var pathParts = [];
            
            if (selectedLevel1 === 'other') {
                var t1 = document.getElementById('categoryOtherText').value.trim();
                pathParts.push(t1 ? 'Other: ' + t1 : 'Other');
            } else if (selectedLevel1) {
                pathParts.push(getCategoryName(selectedLevel1));
            }
            if (selectedLevel2 === 'other') {
                var t2 = document.getElementById('subcategoryOtherText').value.trim();
                pathParts.push(t2 ? 'Other: ' + t2 : 'Other');
            } else if (selectedLevel2) {
                pathParts.push(getCategoryName(selectedLevel2));
            }
            if (selectedLevel3 === 'other') {
                var t3 = document.getElementById('specificOtherText').value.trim();
                pathParts.push(t3 ? 'Other: ' + t3 : 'Other');
            } else if (selectedLevel3) {
                pathParts.push(getCategoryName(selectedLevel3));
            }
            
            var colors = ['bg-blue-100 text-blue-700', 'bg-purple-100 text-purple-700', 'bg-teal-100 text-teal-700'];
            pathContainer.innerHTML = pathParts.map(function(p, i) {
                var arrow = i < pathParts.length - 1 ? '<i class="fas fa-arrow-right text-gray-400 text-xs"></i>' : '';
                return '<span class="px-2.5 py-1 rounded-lg text-xs font-medium ' + colors[i] + '">' + p + '</span>' + arrow;
            }).join(' ');
            
            // Priority
            var priority = document.getElementById('prioritySelect');
            var priorityLabels = { low: '\uD83D\uDFE2 Low', medium: '\uD83D\uDFE1 Medium', high: '\uD83D\uDD34 High' };
            document.getElementById('reviewPriority').textContent = priority ? priorityLabels[priority.value] : '-';
            
            // Title and Description
            document.getElementById('reviewTitle').textContent = document.getElementById('title').value || '-';
            document.getElementById('reviewDescription').textContent = document.getElementById('description').value || '-';
            
            // Attachment
            var fileEl = document.getElementById('attachment');
            document.getElementById('reviewAttachment').textContent = fileEl.files.length > 0 ? fileEl.files[0].name : 'None';
        }
        
        // ─── File Upload ───
        var fileInput = document.getElementById('attachment');
        var uploadPlaceholder = document.getElementById('uploadPlaceholder');
        var filePreview = document.getElementById('filePreview');
        var fileName = document.getElementById('fileName');
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                fileName.textContent = this.files[0].name;
                uploadPlaceholder.classList.add('hidden');
                filePreview.classList.remove('hidden');
                filePreview.classList.add('flex');
            }
        });
        
        function clearFile(event) {
            event.preventDefault();
            event.stopPropagation();
            fileInput.value = '';
            uploadPlaceholder.classList.remove('hidden');
            filePreview.classList.add('hidden');
            filePreview.classList.remove('flex');
        }

        var prioritySelect = document.getElementById('prioritySelect');
        if (prioritySelect) {
            prioritySelect.addEventListener('change', function() {
                updatePrioritySlaHint();
            });
            updatePrioritySlaHint();
        }
        
        // ─── Form Submission ───
        var form = document.getElementById('createTicketForm');
        var submitBtn = document.getElementById('submitBtn');
        var submitText = document.getElementById('submitText');
        var submitLoading = document.getElementById('submitLoading');
        var isSubmitting = false;
        
        form.addEventListener('submit', function(e) {
            if (isSubmitting) { e.preventDefault(); return false; }
            
            // Final check: ensure title is set
            if (!document.getElementById('title').value.trim()) {
                e.preventDefault();
                alert('Please complete your category selections to generate a title.');
                goToStep(2);
                return false;
            }
            
            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
            submitText.classList.add('hidden');
            submitLoading.classList.remove('hidden');
        });
    </script>
</body>
</html>
