<?php
/**
 * Category Model
 * Handles all category-related database operations
 */

class Category {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all active categories
     */
    public function getAll() {
        $sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get category by ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Create new category
     */
    public function create($data) {
        $sql = "INSERT INTO categories (name, description, icon, color) 
                VALUES (:name, :description, :icon, :color)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? null,
            ':color' => $data['color'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update category
     */
    public function update($id, $data) {
        $sql = "UPDATE categories 
                SET name = :name, description = :description, icon = :icon, color = :color
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? null,
            ':color' => $data['color'] ?? null
        ]);
    }
    
    /**
     * Delete category (soft delete)
     */
    public function delete($id) {
        $sql = "UPDATE categories SET is_active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get category statistics
     */
    public function getStats() {
        $sql = "SELECT 
                c.id,
                c.name,
                c.color,
                COUNT(t.id) as ticket_count,
                SUM(CASE WHEN t.status = 'open' OR t.status = 'in_progress' THEN 1 ELSE 0 END) as open_tickets
                FROM categories c
                LEFT JOIN tickets t ON c.id = t.category_id
                WHERE c.is_active = 1
                GROUP BY c.id, c.name, c.color
                ORDER BY ticket_count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
