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
                                <h2 class="text-lg font-semibold text-gray-800">Request Details</h2>
                                <p class="text-sm text-gray-500 mt-1">Provide details about your request for <span id="selectedDeptName" class="font-medium text-emerald-600"></span></p>
                            </div>
                            <button type="button" onclick="goToStep(1)" class="text-sm text-gray-500 hover:text-emerald-600 flex items-center">
                                <i class="fas fa-arrow-left mr-1"></i> Change Department
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
                                <!-- Parent categories will be populated via JavaScript -->
                            </div>
                            
                            <!-- Sub-category Selection (hidden by default) -->
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
                                    <!-- Sub-categories will be populated via JavaScript -->
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
                                   class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm transition-all"
                                   placeholder="Brief description of your request">
                        </div>
                        
                        <!-- Priority (Auto-assigned, Read-only) -->
                        <div class="mb-6">
                            <label for="priority" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                                <div class="w-8 h-8 bg-orange-50 rounded-lg flex items-center justify-center mr-2">
                                    <i class="fas fa-exclamation-triangle text-orange-600 text-sm"></i>
                                </div>
                                Priority <span class="text-red-500 ml-1">*</span>
                            </label>
                            
                            <!-- Auto-priority notice (always visible) -->
                            <div class="mb-3 p-3 bg-amber-50 border border-amber-300 rounded-xl">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-amber-600 mr-2 mt-0.5"></i>
                                    <div class="flex-1">
                                        <span class="text-sm text-amber-800 font-medium">
                                            Priority is automatically assigned based on your issue type
                                        </span>
                                        <p class="text-xs text-amber-700 mt-1">
                                            You can change the priority if needed. Our team will review and adjust if necessary
                                        </p>
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
                            
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <label class="priority-option relative cursor-pointer">
                                    <input type="radio" name="priority" value="low" class="hidden peer">
                                    <div class="p-3 border-2 border-gray-200 rounded-xl text-center peer-checked:border-green-500 peer-checked:bg-green-50 transition-all hover:border-green-300 hover:bg-green-50/50">
                                        <span class="text-2xl">🟢</span>
                                        <p class="text-sm font-medium mt-1">Low</p>
                                        <p class="text-xs text-gray-500">Response: 1 day</p>
                                        <p class="text-xs text-gray-400">Resolution: 3–5 days</p>
                                    </div>
                                </label>
                                <label class="priority-option relative cursor-pointer">
                                    <input type="radio" name="priority" value="medium" class="hidden peer" checked>
                                    <div class="p-3 border-2 border-gray-200 rounded-xl text-center peer-checked:border-yellow-500 peer-checked:bg-yellow-50 transition-all hover:border-yellow-300 hover:bg-yellow-50/50">
                                        <span class="text-2xl">🟡</span>
                                        <p class="text-sm font-medium mt-1">Medium</p>
                                        <p class="text-xs text-gray-500">Response: 4 hours</p>
                                        <p class="text-xs text-gray-400">Resolution: 2–3 days</p>
                                    </div>
                                </label>
                                <label class="priority-option relative cursor-pointer">
                                    <input type="radio" name="priority" value="high" class="hidden peer">
                                    <div class="p-3 border-2 border-gray-200 rounded-xl text-center peer-checked:border-red-500 peer-checked:bg-red-50 transition-all hover:border-red-300 hover:bg-red-50/50">
                                        <span class="text-2xl">🔴</span>
                                        <p class="text-sm font-medium mt-1">High</p>
                                        <p class="text-xs text-gray-500">Response: 30 mins</p>
                                        <p class="text-xs text-gray-400">Resolution: 1 business day</p>
                                    </div>
                                </label>
                            </div>
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
                                      class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm transition-all resize-none"
                                      placeholder="Please provide detailed information about your request..."></textarea>
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
                                    <p class="text-xs text-gray-500 uppercase">Category</p>
                                    <p class="text-sm font-medium text-gray-800" id="reviewCategory">-</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Priority</p>
                                    <p class="text-sm font-medium text-gray-800" id="reviewPriority">-</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Attachment</p>
                                    <p class="text-sm font-medium text-gray-800" id="reviewAttachment">None</p>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 pt-4">
                                <p class="text-xs text-gray-500 uppercase">Title</p>
                                <p class="text-sm font-medium text-gray-800" id="reviewTitle">-</p>
                            </div>
                            <div class="border-t border-gray-200 pt-4">
                                <p class="text-xs text-gray-500 uppercase">Description</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap" id="reviewDescription">-</p>
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
        // Store categories data from PHP
        const categoriesData = <?php echo json_encode($categories, JSON_NUMERIC_CHECK); ?>;
        const departmentsData = <?php echo json_encode($departments, JSON_NUMERIC_CHECK); ?>;
        const priorityMapData = <?php echo json_encode($priorityMap ?? [], JSON_NUMERIC_CHECK); ?>;
        const slaTargetsData = <?php echo json_encode($slaTargets ?? [], JSON_HEX_TAG); ?>;
        
        let selectedDepartmentId = null;
        let selectedDepartmentCode = null;
        let selectedCategoryId = null;
        let selectedParentCategoryId = null;
        let currentStep = 1;
        let autoPrioritySet = false;
        
        // Department selection
        function selectDepartment(element, deptId, deptCode) {
            // Remove selection from all cards
            document.querySelectorAll('.dept-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked card
            element.classList.add('selected');
            
            // Store selection
            selectedDepartmentId = deptId;
            selectedDepartmentCode = deptCode;
            document.getElementById('department_id').value = deptId;
            
            // Update department name in step 2
            const dept = departmentsData.find(d => d.id == deptId);
            document.getElementById('selectedDeptName').textContent = dept ? dept.name : '';
            
            // Enable next button
            const nextBtn = document.getElementById('nextStep1');
            nextBtn.disabled = false;
            nextBtn.classList.remove('bg-gray-200', 'text-gray-400', 'cursor-not-allowed');
            nextBtn.classList.add('bg-gradient-to-r', 'from-emerald-500', 'to-teal-600', 'text-white', 'hover:from-emerald-600', 'hover:to-teal-700', 'shadow-lg', 'shadow-emerald-500/30');
            
            // Populate categories for this department
            populateCategories(deptId);
        }
        
        // Populate categories based on department
        function populateCategories(deptId) {
            const grid = document.getElementById('categoryGrid');
            const select = document.getElementById('category_id');
            const subSection = document.getElementById('subCategorySection');
            
            // Hide sub-category section when repopulating
            subSection.classList.add('hidden');
            selectedParentCategoryId = null;
            
            // Filter categories for this department (parent categories only - where parent_id is null/empty/0)
            const deptCategories = categoriesData.filter(cat => {
                const matchesDept = Number(cat.department_id) === Number(deptId);
                const isParent = !cat.parent_id; // null, undefined, 0, '' all evaluate to false
                return matchesDept && isParent;
            });
            
            // Sort categories alphabetically
            deptCategories.sort((a, b) => a.name.localeCompare(b.name));
            
            // Clear existing
            grid.innerHTML = '';
            select.innerHTML = '<option value="">Select category...</option>';
            
            if (deptCategories.length === 0) {
                grid.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500"><i class="fas fa-folder-open text-4xl mb-3 text-gray-300"></i><p>No categories available for this department</p></div>';
                return;
            }
            
            deptCategories.forEach((cat, index) => {
                // Check if this category has children
                const hasChildren = categoriesData.some(c => Number(c.parent_id) === Number(cat.id));
                const childCount = categoriesData.filter(c => Number(c.parent_id) === Number(cat.id)).length;
                
                // Grid card - improved design
                const card = document.createElement('div');
                card.className = 'category-card relative p-4 border-2 border-gray-200 rounded-xl bg-white' + (hasChildren ? ' has-children' : '');
                card.dataset.categoryId = cat.id;
                card.dataset.hasChildren = hasChildren;
                card.style.animationDelay = `${index * 0.05}s`;
                card.onclick = () => selectCategory(cat.id, hasChildren);
                
                // Get icon and color with fallbacks
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
                
                // Select option
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.name;
                select.appendChild(option);
            });
        }
        
        // Get sub-categories for a parent
        function getSubCategories(parentId) {
            return categoriesData.filter(cat => Number(cat.parent_id) === Number(parentId))
                .sort((a, b) => a.name.localeCompare(b.name));
        }
        
        // Show sub-categories
        function showSubCategories(parentId) {
            const subSection = document.getElementById('subCategorySection');
            const subGrid = document.getElementById('subCategoryGrid');
            const select = document.getElementById('category_id');
            const subCategories = getSubCategories(parentId);
            
            if (subCategories.length === 0) {
                subSection.classList.add('hidden');
                return;
            }
            
            // Add sub-categories to the hidden select so their values can be set
            subCategories.forEach(cat => {
                // Check if option already exists
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
                card.style.animationDelay = `${index * 0.03}s`;
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
            
            // Smooth scroll to sub-categories
            setTimeout(() => {
                subSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }
        
        // Category selection
        function selectCategory(catId, hasChildren) {
            // Remove selection from all parent category cards
            document.querySelectorAll('.category-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Clear sub-category selection
            document.querySelectorAll('.subcategory-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked card
            const selectedCard = document.querySelector(`.category-card[data-category-id="${catId}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            // Store parent category selection
            selectedParentCategoryId = catId;
            selectedCategoryId = catId;
            document.getElementById('category_id').value = catId;
            
            // Auto-set priority based on category mapping
            applyAutoPriority(catId);
            
            // If category has children, show sub-categories
            if (hasChildren) {
                showSubCategories(catId);
            } else {
                document.getElementById('subCategorySection').classList.add('hidden');
            }
        }
        
        // Sub-category selection
        function selectSubCategory(catId) {
            // Remove selection from all sub-category cards
            document.querySelectorAll('.subcategory-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked card
            const selectedCard = document.querySelector(`.subcategory-card[data-category-id="${catId}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            // Update the final selected category (more specific)
            selectedCategoryId = catId;
            document.getElementById('category_id').value = catId;
            
            // Auto-set priority based on subcategory mapping (more specific)
            applyAutoPriority(catId);
        }
        
        // Clear sub-category selection (use parent category)
        function clearSubCategory() {
            document.querySelectorAll('.subcategory-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Revert to parent category
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
            
            if (mappedPriority) {
                // Select the mapped priority radio button (disabled, so this only updates UI)
                const radio = document.querySelector(`input[name="priority"][value="${mappedPriority}"]`);
                if (radio) {
                    // Remove checked from all first
                    document.querySelectorAll('input[name="priority"]').forEach(r => r.checked = false);
                    radio.checked = true;
                }
                
                // Update banner text
                const priorityLabels = { low: 'Low', medium: 'Medium', high: 'High' };
                const slaInfo = {
                    high: 'Response: 30 min | Resolution: 1 business day',
                    medium: 'Response: 4 hrs | Resolution: 2–3 days',
                    low: 'Response: 1 day | Resolution: 3–5 days'
                };
                
                document.getElementById('autoPriorityLabel').textContent = priorityLabels[mappedPriority];
                document.getElementById('slaInfoText').textContent = slaInfo[mappedPriority];
                
                banner.classList.remove('hidden');
                autoPrioritySet = true;
            } else {
                // If no mapping, default to medium
                const mediumRadio = document.querySelector('input[name="priority"][value="medium"]');
                if (mediumRadio) {
                    document.querySelectorAll('input[name="priority"]').forEach(r => r.checked = false);
                    mediumRadio.checked = true;
                }
                banner.classList.add('hidden');
                autoPrioritySet = false;
            }
        }
        
        // Step navigation
        function goToStep(step) {
            // Validate before moving to next step
            if (step === 2 && !selectedDepartmentId) {
                alert('Please select a department');
                return;
            }
            
            if (step === 3) {
                // Validate step 2 fields
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
                
                // Populate review section
                populateReview();
            }
            
            // Hide all sections
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show target section
            document.getElementById('step' + step).classList.add('active');
            
            // Update step indicators
            updateStepIndicators(step);
            
            currentStep = step;
        }
        
        function updateStepIndicators(activeStep) {
            for (let i = 1; i <= 3; i++) {
                const indicator = document.getElementById(`step${i}-indicator`);
                const label = document.getElementById(`step${i}-label`);
                const line = document.getElementById(`step-line-${i-1}`);
                
                indicator.classList.remove('active', 'completed');
                if (label) label.classList.remove('text-gray-700', 'text-emerald-600');
                if (label) label.classList.add('text-gray-400');
                
                if (i < activeStep) {
                    indicator.classList.add('completed');
                    indicator.innerHTML = '<i class="fas fa-check text-sm"></i>';
                    if (label) {
                        label.classList.remove('text-gray-400');
                        label.classList.add('text-emerald-600');
                    }
                    if (line) line.classList.add('bg-emerald-500');
                } else if (i === activeStep) {
                    indicator.classList.add('active');
                    indicator.textContent = i;
                    if (label) {
                        label.classList.remove('text-gray-400');
                        label.classList.add('text-gray-700');
                    }
                } else {
                    indicator.textContent = i;
                    indicator.classList.add('bg-gray-100', 'text-gray-400');
                }
            }
        }
        
        function populateReview() {
            // Department
            const dept = departmentsData.find(d => d.id == selectedDepartmentId);
            document.getElementById('reviewDepartment').textContent = dept ? dept.name : '-';
            
            // Category - show both parent and sub-category if applicable
            const cat = categoriesData.find(c => c.id == selectedCategoryId);
            let categoryText = '-';
            if (cat) {
                if (cat.parent_id && selectedParentCategoryId) {
                    const parentCat = categoriesData.find(c => c.id == selectedParentCategoryId);
                    categoryText = parentCat ? `${parentCat.name} → ${cat.name}` : cat.name;
                } else {
                    categoryText = cat.name;
                }
            }
            document.getElementById('reviewCategory').textContent = categoryText;
            
            // Priority
            const priority = document.querySelector('input[name="priority"]:checked');
            const priorityLabels = { low: '🟢 Low', medium: '🟡 Medium', high: '� High' };
            document.getElementById('reviewPriority').textContent = priority ? priorityLabels[priority.value] : '-';
            
            // Title & Description
            document.getElementById('reviewTitle').textContent = document.getElementById('title').value || '-';
            document.getElementById('reviewDescription').textContent = document.getElementById('description').value || '-';
            
            // Attachment
            const fileInput = document.getElementById('attachment');
            document.getElementById('reviewAttachment').textContent = fileInput.files.length > 0 ? fileInput.files[0].name : 'None';
        }
        
        // File upload handling
        const fileInput = document.getElementById('attachment');
        const uploadPlaceholder = document.getElementById('uploadPlaceholder');
        const filePreview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        
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
        
        // Form submission
        const form = document.getElementById('createTicketForm');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitLoading = document.getElementById('submitLoading');
        let isSubmitting = false;
        
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
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
