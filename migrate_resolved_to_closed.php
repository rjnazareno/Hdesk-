<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance()->getConnection();

// Update existing 'resolved' tickets to 'closed'
$stmt = $db->prepare("UPDATE tickets SET status = 'closed' WHERE status = 'resolved'");
$stmt->execute();
echo 'Updated ' . $stmt->rowCount() . " resolved->closed tickets.\n";

// Update the ENUM to remove 'resolved'
$db->exec("ALTER TABLE tickets MODIFY COLUMN status ENUM('pending','in_progress','closed') NOT NULL DEFAULT 'pending'");
echo "ENUM updated successfully.\n";
