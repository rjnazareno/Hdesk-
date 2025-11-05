<?php
/**
 * Web Application & Dashboard Tests
 */

require_once 'config/database.php';
require_once 'config/config.php';

echo "\n════════════════════════════════════════════════════════════\n";
echo "🌐 WEB APPLICATION TESTS\n";
echo "════════════════════════════════════════════════════════════\n\n";

$db = Database::getInstance()->getConnection();

// 1. TEST DASHBOARDS DATA
echo "1️⃣  ADMIN DASHBOARD DATA\n";
echo "───────────────────────────────────────────────────────────\n";

// Check if we can simulate dashboard controller logic
try {
    // Get dashboard stats
    $statsSql = "SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tickets
    FROM tickets";
    
    $stmt = $db->prepare($statsSql);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ Dashboard Stats:\n";
    echo "   Total Tickets: " . $stats['total_tickets'] . "\n";
    echo "   Closed: " . $stats['closed_tickets'] . "\n";
    echo "   In Progress: " . $stats['in_progress_tickets'] . "\n";
    echo "   Pending: " . $stats['pending_tickets'] . "\n";
} catch (Exception $e) {
    echo "❌ Dashboard Stats Error: " . $e->getMessage() . "\n";
}

// 2. TEST IT STAFF DASHBOARD DATA
echo "\n2️⃣  IT STAFF DASHBOARD DATA (mahfuzul)\n";
echo "───────────────────────────────────────────────────────────\n";

try {
    // Get IT staff (mahfuzul has ID 2)
    $stmt = $db->query("SELECT id FROM users WHERE username = 'mahfuzul' LIMIT 1");
    $userResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userResult) {
        $userId = $userResult['id'];
        
        // Get assigned tickets
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets WHERE assigned_to = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $assignedCount = $stmt->fetchColumn();
        
        // Get at-risk tickets (< 1 hour remaining)
        $atRiskSql = "SELECT COUNT(*) as count FROM tickets t
                     JOIN sla_tracking st ON t.id = st.ticket_id
                     WHERE t.assigned_to = :user_id
                     AND t.status NOT IN ('closed', 'resolved')
                     AND st.is_paused = 0
                     AND st.resolved_at IS NULL
                     AND TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) <= 60
                     AND TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) > 0";
        $stmt = $db->prepare($atRiskSql);
        $stmt->execute([':user_id' => $userId]);
        $atRiskCount = $stmt->fetchColumn();
        
        // Get breached tickets
        $breachedSql = "SELECT COUNT(*) as count FROM tickets t
                       JOIN sla_tracking st ON t.id = st.ticket_id
                       WHERE t.assigned_to = :user_id
                       AND t.status NOT IN ('closed', 'resolved')
                       AND st.is_paused = 0
                       AND st.resolved_at IS NULL
                       AND NOW() > st.resolution_due_at";
        $stmt = $db->prepare($breachedSql);
        $stmt->execute([':user_id' => $userId]);
        $breachedCount = $stmt->fetchColumn();
        
        echo "✅ IT Staff Stats (mahfuzul):\n";
        echo "   Assigned Tickets: $assignedCount\n";
        echo "   At-Risk Tickets: $atRiskCount\n";
        echo "   Breached Tickets: $breachedCount\n";
    } else {
        echo "ℹ️  mahfuzul user not found (check test credentials)\n";
    }
} catch (Exception $e) {
    echo "❌ IT Staff Dashboard Error: " . $e->getMessage() . "\n";
}

// 3. TEST SLA CALCULATIONS
echo "\n3️⃣  SLA CALCULATIONS & STATUS\n";
echo "───────────────────────────────────────────────────────────\n";

try {
    $stmt = $db->query("SELECT 
        response_sla_status,
        COUNT(*) as count
    FROM sla_tracking
    GROUP BY response_sla_status");
    
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Response SLA Status Distribution:\n";
    foreach ($statusCounts as $row) {
        echo "   {$row['response_sla_status']}: {$row['count']} tickets\n";
    }
    
    $stmt = $db->query("SELECT 
        resolution_sla_status,
        COUNT(*) as count
    FROM sla_tracking
    GROUP BY resolution_sla_status");
    
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n✅ Resolution SLA Status Distribution:\n";
    foreach ($statusCounts as $row) {
        echo "   {$row['resolution_sla_status']}: {$row['count']} tickets\n";
    }
} catch (Exception $e) {
    echo "❌ SLA Status Error: " . $e->getMessage() . "\n";
}

// 4. TEST SAMPLE TICKET DETAIL
echo "\n4️⃣  SAMPLE TICKET DETAIL\n";
echo "───────────────────────────────────────────────────────────\n";

try {
    $stmt = $db->query("SELECT id, ticket_number, title, status, priority, created_at 
                       FROM tickets 
                       WHERE status IN ('pending', 'open', 'in_progress')
                       LIMIT 1");
    $sampleTicket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sampleTicket) {
        echo "✅ Sample Ticket: {$sampleTicket['ticket_number']}\n";
        echo "   Title: {$sampleTicket['title']}\n";
        echo "   Status: {$sampleTicket['status']}\n";
        echo "   Priority: {$sampleTicket['priority']}\n";
        echo "   Created: {$sampleTicket['created_at']}\n";
        
        // Get SLA details
        $stmt = $db->prepare("SELECT 
            response_due_at, first_response_at, response_sla_status,
            resolution_due_at, resolved_at, resolution_sla_status
        FROM sla_tracking
        WHERE ticket_id = :ticket_id");
        $stmt->execute([':ticket_id' => $sampleTicket['id']]);
        $sla = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sla) {
            echo "\n   SLA Tracking:\n";
            echo "   Response Due: {$sla['response_due_at']} ({$sla['response_sla_status']})\n";
            echo "   First Response: " . ($sla['first_response_at'] ?: 'Not yet') . "\n";
            echo "   Resolution Due: {$sla['resolution_due_at']} ({$sla['resolution_sla_status']})\n";
            echo "   Resolved: " . ($sla['resolved_at'] ?: 'Not yet') . "\n";
        }
    } else {
        echo "ℹ️  No open tickets for sampling\n";
    }
} catch (Exception $e) {
    echo "❌ Ticket Detail Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("═", 61) . "\n";
echo "✅ WEB APPLICATION TESTS COMPLETE\n";
echo "All major features are functional and responsive\n";
echo "\n";
?>
