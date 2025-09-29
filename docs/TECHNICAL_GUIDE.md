# Technical Implementation Guide
## IT Ticket Management System

**Version:** 1.0  
**Target Audience:** Developers and System Administrators  
**Last Updated:** September 26, 2025  

---

## Table of Contents

1. [Quick Start Guide](#quick-start-guide)
2. [System Architecture](#system-architecture)
3. [Database Schema](#database-schema)
4. [Core Components](#core-components)
5. [API Documentation](#api-documentation)
6. [Security Implementation](#security-implementation)
7. [Troubleshooting](#troubleshooting)
8. [Performance Optimization](#performance-optimization)

---

## Quick Start Guide

### Prerequisites
- PHP 8.0+ with extensions: PDO, MySQL, Session
- MySQL 8.0+ or MariaDB 10.5+
- Apache/Nginx web server
- Modern web browser

### Installation Steps

1. **Clone/Download System Files**
   ```bash
   # Download to your web directory
   cd /var/www/html/ticketing-system
   # or for XAMPP
   cd C:\xampp\htdocs\ticketing-system
   ```

2. **Database Setup**
   ```sql
   -- Create database
   CREATE DATABASE ticketing_system;
   USE ticketing_system;
   
   -- Run the following SQL files in order:
   -- 1. employees.sql
   -- 2. it_staff.sql
   -- 3. tickets.sql
   -- 4. ticket_responses.sql
   -- 5. sample_data.sql (optional)
   ```

3. **Configuration**
   ```php
   // config/database.php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'ticketing_system');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```

4. **Set File Permissions**
   ```bash
   chmod 755 /path/to/ticketing-system
   chmod 644 *.php
   ```

5. **Access System**
   - Open: `http://localhost/ticketing-system/simple_login.php`
   - Default Admin: `admin` / `admin123`
   - Default Employee: `john` / `password123`

---

## System Architecture

### File Structure
```
ticketing-system/
├── config/
│   ├── database.php         # Database configuration
│   └── auth_session.php     # Session management
├── includes/
│   ├── functions.php        # Utility functions
│   └── security.php         # Security functions
├── simple_dashboard.php     # Main dashboard
├── view_ticket.php         # Ticket management
├── create_ticket.php       # New ticket form
├── simple_login.php        # Authentication
├── logout.php              # Session cleanup
└── README.md               # Basic instructions
```

### Core PHP Architecture

```php
/**
 * Application Bootstrap Pattern
 * Each page follows this structure:
 */

// 1. Include configuration
require_once 'config/database.php';
require_once 'includes/security.php';

// 2. Initialize session and authentication
session_start();
requireLogin(); // Redirect if not authenticated

// 3. Database connection
$pdo = getDB();

// 4. Process form submissions (if any)
if ($_POST) {
    // Handle form data with security validation
    $result = processFormData($_POST);
}

// 5. Fetch data for page display
$data = fetchPageData($pdo);

// 6. Display HTML with escaped output
echo escape($data['field']);
```

---

## Database Schema

### Complete Database Setup SQL

```sql
-- Create the ticketing_system database
CREATE DATABASE IF NOT EXISTS ticketing_system;
USE ticketing_system;

-- Employees table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    fname VARCHAR(50) NOT NULL,
    lname VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- IT Staff table
CREATE TABLE it_staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'technician', 'manager') DEFAULT 'technician',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
);

-- Tickets table
CREATE TABLE tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    assigned_to INT NULL,
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('hardware', 'software', 'network', 'security', 'other') DEFAULT 'other',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    acknowledged BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES it_staff(staff_id) ON DELETE SET NULL,
    
    INDEX idx_employee (employee_id),
    INDEX idx_assigned (assigned_to),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_category (category),
    INDEX idx_created (created_at)
);

-- Ticket responses table
CREATE TABLE ticket_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    responder_id INT NOT NULL,
    message TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    
    INDEX idx_ticket (ticket_id),
    INDEX idx_responder (responder_id),
    INDEX idx_internal (is_internal),
    INDEX idx_created (created_at)
);

-- Sample data insertion
INSERT INTO employees (username, fname, lname, email, password, status) VALUES
('john', 'John', 'Doe', 'john@company.com', '$2y$10$example_hash_here', 'active'),
('jane', 'Jane', 'Smith', 'jane@company.com', '$2y$10$example_hash_here', 'active');

INSERT INTO it_staff (name, username, email, password, role, is_active) VALUES
('Administrator', 'admin', 'admin@company.com', '$2y$10$example_hash_here', 'admin', TRUE),
('Tech Support', 'tech1', 'tech1@company.com', '$2y$10$example_hash_here', 'technician', TRUE);
```

### Database Relationships Diagram

```
employees (1) ────┐
                  │
                  ├─ tickets (N)
                  │
it_staff (1) ─────┘     │
                        │
                        └─ ticket_responses (N)
```

---

## Core Components

### Authentication System

```php
// includes/auth.php
class Authentication {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    public function login($username, $password, $user_type = 'employee') {
        $table = ($user_type === 'it_staff') ? 'it_staff' : 'employees';
        $id_field = ($user_type === 'it_staff') ? 'staff_id' : 'id';
        
        $stmt = $this->pdo->prepare("
            SELECT {$id_field} as id, username, password 
            FROM {$table} 
            WHERE username = ? AND (is_active = 1 OR status = 'active')
        ");
        
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $this->createSession($user, $user_type);
            return true;
        }
        return false;
    }
    
    private function createSession($user, $user_type) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user_type;
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
}
```

### Database Connection

```php
// config/database.php
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed");
        }
    }
    
    return $pdo;
}
```

### Security Functions

```php
// includes/security.php

/**
 * Escape output for HTML display
 */
function escape($string) {
    if ($string === null) return '';
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: simple_login.php');
        exit;
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['login_time']) && 
        (time() - $_SESSION['login_time']) > 1800) {
        session_destroy();
        header('Location: simple_login.php?timeout=1');
        exit;
    }
}

/**
 * Generate CSRF token
 */
function getCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is IT staff
 */
function isITStaff() {
    return isset($_SESSION['user_type']) && 
           $_SESSION['user_type'] === 'it_staff';
}
```

---

## API Documentation

### Internal API Endpoints

The system uses simple PHP includes rather than REST APIs for better reliability:

#### Ticket Operations

```php
// Get tickets for dashboard
function getTickets($pdo, $filters = []) {
    $sql = "SELECT t.*, e.fname, e.lname, s.name as assigned_name 
            FROM tickets t 
            LEFT JOIN employees e ON t.employee_id = e.id 
            LEFT JOIN it_staff s ON t.assigned_to = s.staff_id";
    
    $where = [];
    $params = [];
    
    if (!empty($filters['status'])) {
        $where[] = "t.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['priority'])) {
        $where[] = "t.priority = ?";
        $params[] = $filters['priority'];
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " ORDER BY t.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Update ticket status
function updateTicketStatus($pdo, $ticket_id, $status, $user_id) {
    $stmt = $pdo->prepare("
        UPDATE tickets 
        SET status = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE ticket_id = ?
    ");
    
    return $stmt->execute([$status, $ticket_id]);
}
```

### Form Processing Examples

```php
// Process ticket creation
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request";
    } else {
        $subject = trim($_POST['subject']);
        $description = trim($_POST['description']);
        $category = $_POST['category'];
        $priority = $_POST['priority'];
        
        // Validate inputs
        if (empty($subject) || empty($description)) {
            $error = "Subject and description are required";
        } else {
            // Insert new ticket
            $stmt = $pdo->prepare("
                INSERT INTO tickets (employee_id, subject, description, category, priority) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$_SESSION['user_id'], $subject, $description, $category, $priority])) {
                $success = "Ticket created successfully";
            } else {
                $error = "Failed to create ticket";
            }
        }
    }
}
```

---

## Security Implementation

### Input Validation

```php
// Comprehensive input validation
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = trim($data[$field] ?? '');
        
        // Required field check
        if ($rule['required'] && empty($value)) {
            $errors[$field] = "{$field} is required";
            continue;
        }
        
        // Length validation
        if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
            $errors[$field] = "{$field} must be at least {$rule['min_length']} characters";
        }
        
        if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
            $errors[$field] = "{$field} must not exceed {$rule['max_length']} characters";
        }
        
        // Email validation
        if ($rule['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = "Invalid email format";
        }
        
        // Enum validation
        if (isset($rule['options']) && !in_array($value, $rule['options'])) {
            $errors[$field] = "Invalid {$field} value";
        }
    }
    
    return $errors;
}

// Usage example
$rules = [
    'subject' => ['required' => true, 'max_length' => 200],
    'description' => ['required' => true, 'max_length' => 5000],
    'priority' => ['required' => true, 'options' => ['low', 'medium', 'high', 'urgent']],
    'category' => ['required' => true, 'options' => ['hardware', 'software', 'network', 'security', 'other']]
];

$errors = validateInput($_POST, $rules);
```

### SQL Injection Prevention

```php
// Always use prepared statements
function safeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Example usage
$tickets = safeQuery($pdo, "SELECT * FROM tickets WHERE employee_id = ?", [$user_id]);
```

### Session Security

```php
// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Start session with security measures
session_start();

// Regenerate session ID periodically
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 300) { // 5 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
```

---

## Troubleshooting

### Common Issues

#### 1. Database Connection Errors
```
Error: "Database connection failed"
Solutions:
- Check database credentials in config/database.php
- Verify MySQL service is running
- Check database user permissions
- Verify database exists and is accessible
```

#### 2. Session Issues
```
Error: "Session not starting" or "User logged out unexpectedly"
Solutions:
- Check PHP session configuration
- Verify write permissions on session directory
- Check session timeout settings
- Clear browser cookies and try again
```

#### 3. Login Problems
```
Error: "Invalid credentials" when credentials are correct
Solutions:
- Check password hashing (use password_verify)
- Verify user exists in correct table
- Check user status (active/inactive)
- Review authentication logic
```

### Debug Mode

```php
// Enable debug mode for development
define('DEBUG_MODE', true);

function debugLog($message, $data = null) {
    if (DEBUG_MODE) {
        error_log("DEBUG: " . $message);
        if ($data) {
            error_log("DATA: " . print_r($data, true));
        }
    }
}

// Usage
debugLog("User login attempt", ['username' => $username, 'user_type' => $user_type]);
```

### Error Logging

```php
// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_message = "Error: [{$errno}] {$errstr} in {$errfile} on line {$errline}";
    error_log($error_message);
    
    if (DEBUG_MODE) {
        echo "<pre>" . $error_message . "</pre>";
    }
}

set_error_handler("customErrorHandler");
```

---

## Performance Optimization

### Database Optimization

```sql
-- Add indexes for better query performance
CREATE INDEX idx_tickets_status_created ON tickets(status, created_at);
CREATE INDEX idx_tickets_employee_status ON tickets(employee_id, status);
CREATE INDEX idx_responses_ticket_created ON ticket_responses(ticket_id, created_at);

-- Optimize queries with EXPLAIN
EXPLAIN SELECT * FROM tickets WHERE status = 'open' ORDER BY priority DESC, created_at ASC;
```

### PHP Optimization

```php
// Use prepared statements for repeated queries
class TicketManager {
    private $pdo;
    private $updateStatusStmt;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->updateStatusStmt = $pdo->prepare("
            UPDATE tickets SET status = ? WHERE ticket_id = ?
        ");
    }
    
    public function updateStatus($ticket_id, $status) {
        return $this->updateStatusStmt->execute([$status, $ticket_id]);
    }
}
```

### Caching Strategy

```php
// Simple caching for dashboard statistics
function getDashboardStats($pdo, $use_cache = true) {
    $cache_file = 'cache/dashboard_stats.json';
    
    if ($use_cache && file_exists($cache_file) && 
        (time() - filemtime($cache_file)) < 300) { // 5 minutes
        return json_decode(file_get_contents($cache_file), true);
    }
    
    // Generate fresh stats
    $stats = [
        'open' => getTicketCount($pdo, 'open'),
        'in_progress' => getTicketCount($pdo, 'in_progress'),
        'resolved' => getTicketCount($pdo, 'resolved'),
        'closed' => getTicketCount($pdo, 'closed')
    ];
    
    // Cache results
    if (!file_exists('cache')) mkdir('cache');
    file_put_contents($cache_file, json_encode($stats));
    
    return $stats;
}
```

---

## Deployment Checklist

### Pre-Deployment
- [ ] Database schema up to date
- [ ] Configuration files properly set
- [ ] File permissions configured
- [ ] SSL certificate installed
- [ ] Backup procedures tested

### Post-Deployment
- [ ] All pages load correctly
- [ ] Login functionality works
- [ ] Database connections successful
- [ ] File uploads working (if applicable)
- [ ] Error logging enabled

### Production Settings

```php
// Production configuration
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');

define('DEBUG_MODE', false);
define('ENVIRONMENT', 'production');
```

---

This technical guide provides comprehensive implementation details for the IT Ticket Management System. For additional support or questions, refer to the main system documentation or contact the development team.

**Document Version:** 1.0  
**Last Updated:** September 26, 2025