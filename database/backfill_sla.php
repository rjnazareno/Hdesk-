<?php
/**
 * Backfill SLA Tracking for Existing Tickets
 * Run this once to add SLA tracking to tickets created before SLA system was implemented
 */

require_once __DIR__ . '/../config/config.php';

$slaModel = new SLA();
$ticketModel = new Ticket();

echo "=== Backfilling SLA Tracking for Existing Tickets ===\n\n";

// Get all tickets without SLA tracking
$sql = "SELECT t.id, t.ticket_number, t.priority, t.created_at, t.status
        FROM tickets t
        LEFT JOIN sla_tracking st ON t.id = st.ticket_id
        WHERE st.id IS NULL
        ORDER BY t.created_at ASC";

$db = Database::getInstance()->getConnection();
$stmt = $db->query($sql);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($tickets) . " tickets without SLA tracking\n\n";

$successCount = 0;
$errorCount = 0;

foreach ($tickets as $ticket) {
    echo "Processing Ticket #{$ticket['ticket_number']} (ID: {$ticket['id']})...\n";
    echo "  Priority: {$ticket['priority']}\n";
    echo "  Created: {$ticket['created_at']}\n";
    
    try {
        // Create SLA tracking
        $result = $slaModel->createTracking($ticket['id'], $ticket['priority']);
        
        if ($result) {
            $successCount++;
            echo "  ✓ SLA tracking created successfully\n";
            
            // If ticket is already resolved/closed, record resolution
            if ($ticket['status'] === 'resolved' || $ticket['status'] === 'closed') {
                $slaModel->recordResolution($ticket['id']);
                echo "  ✓ Resolution recorded\n";
            }
            // If ticket is not pending, record first response
            elseif ($ticket['status'] !== 'pending') {
                $slaModel->recordFirstResponse($ticket['id']);
                echo "  ✓ First response recorded\n";
            }
        } else {
            $errorCount++;
            echo "  ✗ Failed to create SLA tracking\n";
        }
    } catch (Exception $e) {
        $errorCount++;
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Summary ===\n";
echo "Successfully processed: $successCount tickets\n";
echo "Errors: $errorCount tickets\n";
echo "\nBackfill complete!\n";
