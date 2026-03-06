<?php
/**
 * CategoryPriorityMap Model
 * Maps categories (issue types/subcategories) to their default priorities
 * Based on IT Help Desk SLA Guide spreadsheet (March 2026)
 * 
 * SLA Response/Resolution Times (all priorities: 24h response):
 *   HR:  HIGH → 24h/24h  | MEDIUM → 24h/48–72h  | LOW → 24h/56–120h
 *   IT:  HIGH → 24h/48h  | MEDIUM → 24h/72–96h  | LOW → 24h/72–120h
 */

class CategoryPriorityMap {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get the default priority for a category (subcategory/issue type)
     * 
     * @param int $categoryId The category ID
     * @return string|null Priority ('low', 'medium', 'high') or null if not mapped
     */
    public function getDefaultPriority($categoryId) {
        $sql = "SELECT default_priority FROM category_priority_map WHERE category_id = :category_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':category_id' => $categoryId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['default_priority'] : null;
    }
    
    /**
     * Get all priority mappings (with category names)
     * Used in admin management views
     * 
     * @return array All mappings with category details
     */
    public function getAll() {
        $sql = "SELECT cpm.*, 
                       c.name as category_name, 
                       c.parent_id,
                       c.department_id,
                       pc.name as parent_category_name,
                       d.name as department_name,
                       d.code as department_code
                FROM category_priority_map cpm
                JOIN categories c ON cpm.category_id = c.id
                LEFT JOIN categories pc ON c.parent_id = pc.id
                LEFT JOIN departments d ON c.department_id = d.id
                ORDER BY d.name, pc.name, c.name";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all priority mappings as a simple categoryId => priority lookup
     * Used for JavaScript data in views
     * 
     * @return array Associative array [category_id => default_priority]
     */
    public function getAllAsLookup() {
        $sql = "SELECT category_id, default_priority FROM category_priority_map";
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $lookup = [];
        foreach ($results as $row) {
            $lookup[$row['category_id']] = $row['default_priority'];
        }
        return $lookup;
    }
    
    /**
     * Update the default priority for a category
     * 
     * @param int $categoryId The category ID
     * @param string $priority The priority ('low', 'medium', 'high')
     * @return bool Success status
     */
    public function updatePriority($categoryId, $priority) {
        $validPriorities = ['low', 'medium', 'high'];
        if (!in_array($priority, $validPriorities)) {
            return false;
        }
        
        $sql = "INSERT INTO category_priority_map (category_id, default_priority) 
                VALUES (:category_id, :priority)
                ON DUPLICATE KEY UPDATE default_priority = :priority2";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':category_id' => $categoryId,
            ':priority' => $priority,
            ':priority2' => $priority
        ]);
    }
    
    /**
     * Remove priority mapping for a category
     * 
     * @param int $categoryId The category ID
     * @return bool Success status
     */
    public function removePriority($categoryId) {
        $sql = "DELETE FROM category_priority_map WHERE category_id = :category_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':category_id' => $categoryId]);
    }
    
    /**
     * Get SLA targets for a given priority and optional department
     * Returns human-readable response and resolution times
     * 
     * @param string $priority The priority level
     * @param string|null $department Department code ('HR', 'IT') or null for HR defaults
     * @return array SLA target details
     */
    public static function getSLATargets($priority, $department = null) {
        // HR department SLA targets (also used as default)
        $hrTargets = [
            'high' => [
                'response' => '24 hours',
                'resolution' => '24 hours',
                'response_minutes' => 1440,
                'resolution_minutes' => 1440
            ],
            'medium' => [
                'response' => '24 hours',
                'resolution' => '48–72 hours',
                'response_minutes' => 1440,
                'resolution_minutes' => 4320
            ],
            'low' => [
                'response' => '24 hours',
                'resolution' => '56–120 hours',
                'response_minutes' => 1440,
                'resolution_minutes' => 7200
            ]
        ];

        // IT department SLA targets
        $itTargets = [
            'high' => [
                'response' => '24 hours',
                'resolution' => '48 hours',
                'response_minutes' => 1440,
                'resolution_minutes' => 2880
            ],
            'medium' => [
                'response' => '24 hours',
                'resolution' => '72–96 hours',
                'response_minutes' => 1440,
                'resolution_minutes' => 5760
            ],
            'low' => [
                'response' => '24 hours',
                'resolution' => '72–120 hours',
                'response_minutes' => 1440,
                'resolution_minutes' => 7200
            ]
        ];

        $dept = strtoupper($department ?? '');
        $targets = ($dept === 'IT') ? $itTargets : $hrTargets;

        return $targets[$priority] ?? $targets['medium'];
    }

    /**
     * Get all SLA targets keyed by priority for a specific department
     * Useful for passing to views as JSON
     *
     * @param string|null $department Department code ('HR', 'IT') or null
     * @return array ['high' => [...], 'medium' => [...], 'low' => [...]]
     */
    public static function getAllSLATargets($department = null) {
        return [
            'high'   => self::getSLATargets('high', $department),
            'medium' => self::getSLATargets('medium', $department),
            'low'    => self::getSLATargets('low', $department),
        ];
    }
    
    /**
     * Check if the category_priority_map table exists
     * Used for graceful degradation if migration hasn't been run
     * 
     * @return bool Whether the table exists
     */
    public function tableExists() {
        try {
            $sql = "SELECT 1 FROM category_priority_map LIMIT 1";
            $this->db->query($sql);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
