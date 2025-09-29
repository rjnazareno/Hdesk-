<?php
/**
 * Temporary script to update existing hashed passwords to plain text
 * Run this once to convert your database passwords to plain text for testing
 * Delete this file after use for security
 */

require_once 'config/database.php';

try {
    $pdo = getDB();
    
    // Update IT Staff passwords
    $stmt = $pdo->prepare("UPDATE it_staff SET password = ? WHERE username = ?");
    $staff_updates = [
        ['admin123', 'admin']
    ];
    
    foreach ($staff_updates as $update) {
        $stmt->execute($update);
        echo "Updated IT Staff: {$update[1]} - Password: {$update[0]}\n";
    }
    
    // Update Employee passwords
    $stmt = $pdo->prepare("UPDATE employees SET password = ? WHERE username = ?");
    $employee_updates = [
        ['password123', 'john'],
        ['password123', 'jane'], 
        ['password123', 'mike']
    ];
    
    foreach ($employee_updates as $update) {
        $stmt->execute($update);
        echo "Updated Employee: {$update[1]} - Password: {$update[0]}\n";
    }
    
    echo "\n✅ All passwords updated successfully!\n";
    echo "🔐 Login credentials:\n";
    echo "   IT Staff - Username: admin, Password: admin123\n";
    echo "   Employee - Username: john, Password: password123\n";
    echo "   Employee - Username: jane, Password: password123\n";  
    echo "   Employee - Username: mike, Password: password123\n";
    echo "\n⚠️  Remember to delete this file after use for security!\n";
    
} catch (Exception $e) {
    echo "❌ Error updating passwords: " . $e->getMessage() . "\n";
}
?>