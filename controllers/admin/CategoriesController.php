<?php
/**
 * Categories Controller
 * Handles all business logic for category management
 */

class CategoriesController {
    private $auth;
    private $categoryModel;
    private $departmentModel;
    private $currentUser;

    public function __construct() {
        // Initialize authentication
        $this->auth = new Auth();
        $this->auth->requireLogin();
        $this->auth->requireITStaff();

        // Initialize models
        $this->categoryModel = new Category();
        $this->departmentModel = new Department();

        // Get current user
        $this->currentUser = $this->auth->getCurrentUser();
    }

    /**
     * Display all categories with statistics
     */
    public function index() {
        // Get department filter
        $departmentId = isset($_GET['department_id']) && !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
        
        // Get all categories with stats
        $allCategories = $this->categoryModel->getStats($departmentId);
        
        // Organize categories into hierarchical structure
        $hierarchy = [];
        $childMap = [];
        
        foreach ($allCategories as $category) {
            // Fetch parent_id from database
            $fullCategory = $this->categoryModel->findById($category['id']);
            $category['parent_id'] = $fullCategory['parent_id'] ?? null;
            
            if ($category['parent_id'] === null) {
                $hierarchy[$category['id']] = $category;
                $hierarchy[$category['id']]['children'] = [];
            } else {
                $childMap[$category['parent_id']][] = $category;
            }
        }
        
        // Assign children to parents
        foreach ($childMap as $parentId => $children) {
            if (isset($hierarchy[$parentId])) {
                $hierarchy[$parentId]['children'] = $children;
            }
        }
        
        $data = [
            'currentUser' => $this->currentUser,
            'categories' => array_values($hierarchy),
            'allCategories' => $allCategories,
            'departments' => $this->departmentModel->getAll(),
            'selectedDepartment' => $departmentId
        ];

        // Load the view
        $this->loadView('admin/categories', $data);
    }

    /**
     * Load view file with data
     */
    private function loadView($viewName, $data = []) {
        // Extract data to variables
        extract($data);
        
        // Include the view file
        $viewFile = __DIR__ . '/../../views/' . $viewName . '.view.php';
        
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View file not found: " . $viewFile);
        }
    }
}
