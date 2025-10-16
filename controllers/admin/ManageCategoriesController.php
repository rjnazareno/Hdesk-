<?php
/**
 * Manage Categories Controller
 * Handles category management (view, edit, delete)
 */

class ManageCategoriesController {
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
     * Show all categories management page
     */
    public function index() {
        // Get all categories with statistics
        $categories = $this->categoryModel->getStats();
        
        // Load view
        $this->loadView('admin/manage_categories', [
            'currentUser' => $this->currentUser,
            'categories' => $categories
        ]);
    }
    
    /**
     * Handle category update
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/manage_categories.php');
        }
        
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        
        if (!$categoryId) {
            $_SESSION['error'] = "Invalid category ID.";
            redirect('admin/manage_categories.php');
        }
        
        // Validate required fields
        if (empty($_POST['name'])) {
            $_SESSION['error'] = "Category name is required.";
            redirect('admin/manage_categories.php');
        }
        
        // Prepare update data
        $updateData = [
            'name' => sanitize($_POST['name']),
            'description' => !empty($_POST['description']) ? sanitize($_POST['description']) : null,
            'icon' => !empty($_POST['icon']) ? sanitize($_POST['icon']) : 'fa-folder',
            'color' => !empty($_POST['color']) ? sanitize($_POST['color']) : '#6b7280'
        ];
        
        // Update category
        if ($this->categoryModel->update($categoryId, $updateData)) {
            $_SESSION['success'] = "Category updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update category.";
        }
        
        redirect('admin/manage_categories.php');
    }
    
    /**
     * Handle category deletion
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/manage_categories.php');
        }
        
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        
        if (!$categoryId) {
            $_SESSION['error'] = "Invalid category ID.";
            redirect('admin/manage_categories.php');
        }
        
        // Check if category has tickets
        $category = $this->categoryModel->findById($categoryId);
        $stats = $this->categoryModel->getStats();
        
        // Find the category in stats to check ticket count
        $hasTickets = false;
        foreach ($stats as $stat) {
            if ($stat['id'] == $categoryId && $stat['ticket_count'] > 0) {
                $hasTickets = true;
                break;
            }
        }
        
        if ($hasTickets) {
            $_SESSION['error'] = "Cannot delete category with existing tickets. Please reassign tickets first.";
            redirect('admin/manage_categories.php');
        }
        
        // Delete category (soft delete)
        if ($this->categoryModel->delete($categoryId)) {
            $_SESSION['success'] = "Category deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete category.";
        }
        
        redirect('admin/manage_categories.php');
    }
    
    /**
     * Load view file
     */
    private function loadView($view, $data = []) {
        extract($data);
        require_once __DIR__ . '/../../views/' . $view . '.view.php';
    }
}
