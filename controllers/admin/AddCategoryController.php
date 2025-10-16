<?php
/**
 * Add Category Controller
 * Handles category creation by admin/IT staff
 */

class AddCategoryController {
    private $auth;
    private $categoryModel;
    private $currentUser;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireITStaff();
        
        $this->categoryModel = new Category();
        $this->currentUser = $this->auth->getCurrentUser();
    }
    
    /**
     * Show add category form
     */
    public function index() {
        // Load view
        $this->loadView('admin/add_category', [
            'currentUser' => $this->currentUser
        ]);
    }
    
    /**
     * Handle category creation
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/add_category.php');
        }
        
        // Validate required fields
        if (empty($_POST['name'])) {
            $_SESSION['error'] = "Category name is required.";
            redirect('admin/add_category.php');
        }
        
        // Prepare category data
        $categoryData = [
            'name' => sanitize($_POST['name']),
            'description' => !empty($_POST['description']) ? sanitize($_POST['description']) : null,
            'icon' => !empty($_POST['icon']) ? sanitize($_POST['icon']) : 'fa-folder',
            'color' => !empty($_POST['color']) ? sanitize($_POST['color']) : '#6b7280'
        ];
        
        // Create category
        $categoryId = $this->categoryModel->create($categoryData);
        
        if ($categoryId) {
            $_SESSION['success'] = "Category '{$categoryData['name']}' added successfully!";
            redirect('admin/categories.php');
        } else {
            $_SESSION['error'] = "Failed to add category. Please try again.";
            redirect('admin/add_category.php');
        }
    }
    
    /**
     * Load view file
     */
    private function loadView($view, $data = []) {
        extract($data);
        require_once __DIR__ . '/../../views/' . $view . '.view.php';
    }
}
