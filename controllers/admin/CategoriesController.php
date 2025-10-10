<?php
/**
 * Categories Controller
 * Handles all business logic for category management
 */

class CategoriesController {
    private $auth;
    private $categoryModel;
    private $currentUser;

    public function __construct() {
        // Initialize authentication
        $this->auth = new Auth();
        $this->auth->requireLogin();
        $this->auth->requireITStaff();

        // Initialize models
        $this->categoryModel = new Category();

        // Get current user
        $this->currentUser = $this->auth->getCurrentUser();
    }

    /**
     * Display all categories with statistics
     */
    public function index() {
        // Get all categories with stats
        $data = [
            'currentUser' => $this->currentUser,
            'categories' => $this->categoryModel->getStats()
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
