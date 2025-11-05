<?php
/**
 * SLA (Service Level Agreement) Model
 * Handles SLA policy management, tracking, and breach detection
 */

class SLA {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get SLA policy for a given priority
     * 
     * @param string $priority The ticket priority (low, medium, high, urgent)
     * @return array|false SLA policy or false if not found
     */
    public function getPolicyByPriority($priority) {
        $sql = "SELECT * FROM sla_policies WHERE priority = :priority AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':priority' => $priority]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all active SLA policies
     * 
     * @return array All active SLA policies
     */
    public function getAllPolicies() {
        $sql = "SELECT * FROM sla_policies WHERE is_active = 1 
                ORDER BY FIELD(priority, 'urgent', 'high', 'medium', 'low')";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create SLA tracking for a new ticket
     * 
     * @param int $ticketId The ticket ID
     * @param string $priority The ticket priority
     * @return bool Success status
     */
    public function createTracking($ticketId, $priority) {
        // Get SLA policy for this priority
        $policy = $this->getPolicyByPriority($priority);
        if (!$policy) {
            return false;
        }
        
        // Get the actual ticket creation time from database
        $ticketSql = "SELECT created_at FROM tickets WHERE id = :ticket_id";
        $ticketStmt = $this->db->prepare($ticketSql);
        $ticketStmt->execute([':ticket_id' => $ticketId]);
        $ticketData = $ticketStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticketData) {
            return false;
        }
        
        // Use the ticket's actual creation time for SLA calculation
        $ticketCreatedAt = new DateTime($ticketData['created_at']);
        $responseDue = $this->calculateDueDate($ticketCreatedAt, $policy['response_time'], $policy['is_business_hours']);
        $resolutionDue = $this->calculateDueDate($ticketCreatedAt, $policy['resolution_time'], $policy['is_business_hours']);
        
        // Insert tracking record
        $sql = "INSERT INTO sla_tracking 
                (ticket_id, sla_policy_id, response_due_at, resolution_due_at) 
                VALUES (:ticket_id, :policy_id, :response_due, :resolution_due)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':ticket_id' => $ticketId,
            ':policy_id' => $policy['id'],
            ':response_due' => $responseDue->format('Y-m-d H:i:s'),
            ':resolution_due' => $resolutionDue->format('Y-m-d H:i:s')
        ]);
        
        // Update ticket SLA status
        if ($result) {
            $this->updateTicketSLAStatus($ticketId);
        }
        
        return $result;
    }
    
    /**
     * Calculate due date based on minutes and business hours setting
     * 
     * @param DateTime $startDate Start date
     * @param int $minutes Minutes to add
     * @param bool $businessHoursOnly Whether to only count business hours
     * @return DateTime Due date
     */
    private function calculateDueDate($startDate, $minutes, $businessHoursOnly) {
        $dueDate = clone $startDate;
        $minutes = (int)$minutes; // Ensure minutes is an integer
        
        if (!$businessHoursOnly) {
            // Simple 24/7 calculation
            $dueDate->add(new DateInterval('PT' . $minutes . 'M'));
            return $dueDate;
        }
        
        // Business hours: Mon-Fri, 8:00 AM - 5:00 PM
        $businessStartHour = 8;
        $businessEndHour = 17;
        $businessHoursPerDay = $businessEndHour - $businessStartHour; // 9 hours
        
        $remainingMinutes = $minutes;
        
        while ($remainingMinutes > 0) {
            // Skip weekends
            while ($dueDate->format('N') >= 6) { // 6=Saturday, 7=Sunday
                $dueDate->add(new DateInterval('P1D'));
                $dueDate->setTime($businessStartHour, 0, 0);
            }
            
            $currentHour = (int)$dueDate->format('H');
            $currentMinute = (int)$dueDate->format('i');
            
            // If before business hours, jump to start
            if ($currentHour < $businessStartHour) {
                $dueDate->setTime($businessStartHour, 0, 0);
                continue;
            }
            
            // If after business hours, jump to next day
            if ($currentHour >= $businessEndHour) {
                $dueDate->add(new DateInterval('P1D'));
                $dueDate->setTime($businessStartHour, 0, 0);
                continue;
            }
            
            // Calculate remaining minutes in current business day
            $endOfDay = clone $dueDate;
            $endOfDay->setTime($businessEndHour, 0, 0);
            $minutesUntilEndOfDay = (int)round(($endOfDay->getTimestamp() - $dueDate->getTimestamp()) / 60);
            
            if ($remainingMinutes <= $minutesUntilEndOfDay) {
                // Can fit in current day - ensure we use integer minutes
                $minutesToAdd = (int)$remainingMinutes;
                $dueDate->add(new DateInterval('PT' . $minutesToAdd . 'M'));
                $remainingMinutes = 0;
            } else {
                // Need to continue to next business day
                $remainingMinutes -= $minutesUntilEndOfDay;
                $dueDate->add(new DateInterval('P1D'));
                $dueDate->setTime($businessStartHour, 0, 0);
            }
        }
        
        return $dueDate;
    }
    
    /**
     * Get SLA tracking for a ticket
     * 
     * @param int $ticketId The ticket ID
     * @return array|false SLA tracking data or false
     */
    public function getTicketSLA($ticketId) {
        $sql = "SELECT st.*, sp.priority, sp.response_time as target_response, 
                sp.resolution_time as target_resolution, sp.is_business_hours
                FROM sla_tracking st
                JOIN sla_policies sp ON st.sla_policy_id = sp.id
                WHERE st.ticket_id = :ticket_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ticket_id' => $ticketId]);
        $sla = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sla) {
            // Calculate remaining time and percentage
            $now = new DateTime();
            $sla['response_remaining'] = $this->calculateRemainingTime($now, $sla['response_due_at'], $sla['is_paused']);
            $sla['resolution_remaining'] = $this->calculateRemainingTime($now, $sla['resolution_due_at'], $sla['is_paused']);
            $sla['response_percentage'] = $this->calculateElapsedPercentage($sla['response_time_minutes'], $sla['target_response'], $sla['response_sla_status']);
            $sla['resolution_percentage'] = $this->calculateElapsedPercentage($sla['resolution_time_minutes'], $sla['target_resolution'], $sla['resolution_sla_status']);
        }
        
        return $sla;
    }
    
    /**
     * Calculate remaining time until deadline
     * 
     * @param DateTime $now Current time
     * @param string $dueDate Due date string
     * @param bool $isPaused Whether SLA is paused
     * @return array Remaining time data
     */
    private function calculateRemainingTime($now, $dueDate, $isPaused) {
        $due = new DateTime($dueDate);
        $diff = $now->diff($due);
        
        $totalMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        
        if ($diff->invert) {
            // Overdue
            $totalMinutes = -$totalMinutes;
        }
        
        return [
            'minutes' => $totalMinutes,
            'hours' => floor(abs($totalMinutes) / 60),
            'remaining_minutes' => abs($totalMinutes) % 60,
            'is_overdue' => $diff->invert && !$isPaused,
            'formatted' => $this->formatRemainingTime($totalMinutes, $diff->invert, $isPaused)
        ];
    }
    
    /**
     * Format remaining time for display
     * 
     * @param int $totalMinutes Total minutes
     * @param bool $isOverdue Whether time is overdue
     * @param bool $isPaused Whether SLA is paused
     * @return string Formatted time string
     */
    private function formatRemainingTime($totalMinutes, $isOverdue, $isPaused) {
        if ($isPaused) {
            return 'Paused';
        }
        
        $hours = floor(abs($totalMinutes) / 60);
        $minutes = abs($totalMinutes) % 60;
        
        $formatted = '';
        if ($hours > 0) {
            $formatted .= $hours . 'h ';
        }
        $formatted .= $minutes . 'm';
        
        if ($isOverdue) {
            return 'BREACHED ' . $formatted . ' ago';
        } else {
            return $formatted . ' remaining';
        }
    }
    
    /**
     * Calculate elapsed percentage for progress bars
     * 
     * @param int|null $actualMinutes Actual time taken
     * @param int $targetMinutes Target time
     * @param string $status Current SLA status
     * @return float Percentage (0-100)
     */
    private function calculateElapsedPercentage($actualMinutes, $targetMinutes, $status) {
        if ($status === 'met' || $status === 'breached') {
            return 100;
        }
        
        if ($actualMinutes === null) {
            $now = new DateTime();
            // Would need ticket creation time here - simplified for now
            return 50; // Placeholder
        }
        
        $percentage = ($actualMinutes / $targetMinutes) * 100;
        return min(100, $percentage);
    }
    
    /**
     * Record first response to a ticket
     * 
     * @param int $ticketId The ticket ID
     * @return bool Success status
     */
    public function recordFirstResponse($ticketId) {
        $sql = "UPDATE sla_tracking 
                SET first_response_at = NOW(),
                    response_time_minutes = TIMESTAMPDIFF(MINUTE, 
                        (SELECT created_at FROM tickets WHERE id = :ticket_id), 
                        NOW()
                    ),
                    response_sla_status = CASE
                        WHEN NOW() <= response_due_at THEN 'met'
                        ELSE 'breached'
                    END
                WHERE ticket_id = :ticket_id2 
                AND first_response_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':ticket_id' => $ticketId,
            ':ticket_id2' => $ticketId
        ]);
        
        // Check if breached and log
        if ($result) {
            $this->checkAndLogBreach($ticketId, 'response');
            $this->updateTicketSLAStatus($ticketId);
        }
        
        return $result;
    }
    
    /**
     * Record ticket resolution
     * 
     * @param int $ticketId The ticket ID
     * @return bool Success status
     */
    public function recordResolution($ticketId) {
        $sql = "UPDATE sla_tracking 
                SET resolved_at = NOW(),
                    resolution_time_minutes = TIMESTAMPDIFF(MINUTE, 
                        (SELECT created_at FROM tickets WHERE id = :ticket_id), 
                        NOW()
                    ) - total_pause_minutes,
                    resolution_sla_status = CASE
                        WHEN NOW() <= resolution_due_at THEN 'met'
                        ELSE 'breached'
                    END
                WHERE ticket_id = :ticket_id2";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':ticket_id' => $ticketId,
            ':ticket_id2' => $ticketId
        ]);
        
        // Check if breached and log
        if ($result) {
            $this->checkAndLogBreach($ticketId, 'resolution');
            $this->updateTicketSLAStatus($ticketId);
        }
        
        return $result;
    }
    
    /**
     * Pause SLA tracking (e.g., waiting for customer)
     * 
     * @param int $ticketId The ticket ID
     * @param string $reason Reason for pausing
     * @return bool Success status
     */
    public function pauseSLA($ticketId, $reason = 'Waiting for customer') {
        $sql = "UPDATE sla_tracking 
                SET is_paused = 1,
                    paused_at = NOW(),
                    pause_reason = :reason
                WHERE ticket_id = :ticket_id 
                AND is_paused = 0";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':ticket_id' => $ticketId,
            ':reason' => $reason
        ]);
    }
    
    /**
     * Resume SLA tracking
     * 
     * @param int $ticketId The ticket ID
     * @return bool Success status
     */
    public function resumeSLA($ticketId) {
        $sql = "UPDATE sla_tracking 
                SET is_paused = 0,
                    total_pause_minutes = total_pause_minutes + 
                        TIMESTAMPDIFF(MINUTE, paused_at, NOW()),
                    paused_at = NULL,
                    pause_reason = NULL,
                    response_due_at = DATE_ADD(response_due_at, 
                        INTERVAL TIMESTAMPDIFF(MINUTE, paused_at, NOW()) MINUTE),
                    resolution_due_at = DATE_ADD(resolution_due_at, 
                        INTERVAL TIMESTAMPDIFF(MINUTE, paused_at, NOW()) MINUTE)
                WHERE ticket_id = :ticket_id 
                AND is_paused = 1";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([':ticket_id' => $ticketId]);
        
        if ($result) {
            $this->updateTicketSLAStatus($ticketId);
        }
        
        return $result;
    }
    
    /**
     * Check for SLA breaches and log them
     * 
     * @param int $ticketId The ticket ID
     * @param string $type 'response' or 'resolution'
     * @return bool Whether breach was logged
     */
    private function checkAndLogBreach($ticketId, $type) {
        $sla = $this->getTicketSLA($ticketId);
        if (!$sla) return false;
        
        $isBreached = false;
        $targetTime = null;
        $actualTime = null;
        
        if ($type === 'response') {
            $isBreached = $sla['response_sla_status'] === 'breached';
            $targetTime = $sla['response_due_at'];
            $actualTime = $sla['first_response_at'];
        } else {
            $isBreached = $sla['resolution_sla_status'] === 'breached';
            $targetTime = $sla['resolution_due_at'];
            $actualTime = $sla['resolved_at'];
        }
        
        if ($isBreached && $actualTime) {
            $target = new DateTime($targetTime);
            $actual = new DateTime($actualTime);
            $delayMinutes = ($actual->getTimestamp() - $target->getTimestamp()) / 60;
            
            $sql = "INSERT INTO sla_breaches 
                    (ticket_id, sla_tracking_id, breach_type, target_time, actual_time, delay_minutes)
                    VALUES (:ticket_id, :tracking_id, :type, :target, :actual, :delay)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':ticket_id' => $ticketId,
                ':tracking_id' => $sla['id'],
                ':type' => $type,
                ':target' => $targetTime,
                ':actual' => $actualTime,
                ':delay' => $delayMinutes
            ]);
        }
        
        return false;
    }
    
    /**
     * Update overall ticket SLA status
     * 
     * @param int $ticketId The ticket ID
     * @return bool Success status
     */
    private function updateTicketSLAStatus($ticketId) {
        $sql = "UPDATE tickets t
                JOIN sla_tracking st ON t.id = st.ticket_id
                SET t.sla_status = CASE
                    WHEN st.resolution_sla_status = 'breached' 
                        OR st.response_sla_status = 'breached' THEN 'breached'
                    WHEN NOW() > DATE_SUB(st.resolution_due_at, INTERVAL 1 HOUR) 
                        AND st.resolved_at IS NULL THEN 'at_risk'
                    WHEN st.resolution_sla_status = 'met' 
                        AND st.response_sla_status = 'met' THEN 'met'
                    ELSE 'pending'
                END
                WHERE t.id = :ticket_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':ticket_id' => $ticketId]);
    }
    
    /**
     * Get tickets at risk of breaching SLA
     * 
     * @param int $minutesThreshold Minutes until breach (default 60)
     * @return array Tickets at risk
     */
    public function getAtRiskTickets($minutesThreshold = 60) {
        $sql = "SELECT t.id, t.ticket_number, t.title, t.priority, t.status,
                st.resolution_due_at,
                TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) as minutes_remaining
                FROM tickets t
                JOIN sla_tracking st ON t.id = st.ticket_id
                WHERE t.status NOT IN ('closed', 'resolved')
                AND st.is_paused = 0
                AND st.resolved_at IS NULL
                AND TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) <= :threshold
                AND TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) > 0
                ORDER BY st.resolution_due_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':threshold' => $minutesThreshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get breached tickets
     * 
     * @return array Breached tickets
     */
    public function getBreachedTickets() {
        $sql = "SELECT t.id, t.ticket_number, t.title, t.priority, t.status,
                st.resolution_due_at,
                TIMESTAMPDIFF(MINUTE, st.resolution_due_at, NOW()) as minutes_overdue,
                sb.breach_type, sb.delay_minutes
                FROM tickets t
                JOIN sla_tracking st ON t.id = st.ticket_id
                LEFT JOIN sla_breaches sb ON t.id = sb.ticket_id
                WHERE t.status NOT IN ('closed', 'resolved')
                AND (st.response_sla_status = 'breached' 
                     OR st.resolution_sla_status = 'breached'
                     OR NOW() > st.resolution_due_at)
                ORDER BY st.resolution_due_at ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get SLA statistics
     * 
     * @param array $filters Optional filters (date_from, date_to, priority, assigned_to)
     * @return array SLA statistics
     */
    public function getStatistics($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $where[] = "t.created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "t.created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['priority'])) {
            $where[] = "t.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $where[] = "t.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['assigned_to'];
        }
        
        $whereClause = implode(" AND ", $where);
        
        $sql = "SELECT 
                COUNT(*) as total_tickets,
                SUM(CASE WHEN st.response_sla_status = 'met' THEN 1 ELSE 0 END) as response_met,
                SUM(CASE WHEN st.response_sla_status = 'breached' THEN 1 ELSE 0 END) as response_breached,
                SUM(CASE WHEN st.resolution_sla_status = 'met' THEN 1 ELSE 0 END) as resolution_met,
                SUM(CASE WHEN st.resolution_sla_status = 'breached' THEN 1 ELSE 0 END) as resolution_breached,
                AVG(st.response_time_minutes) as avg_response_time,
                AVG(st.resolution_time_minutes) as avg_resolution_time,
                ROUND((SUM(CASE WHEN st.response_sla_status = 'met' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as response_compliance_rate,
                ROUND((SUM(CASE WHEN st.resolution_sla_status = 'met' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as resolution_compliance_rate
                FROM tickets t
                JOIN sla_tracking st ON t.id = st.ticket_id
                WHERE $whereClause
                AND st.resolved_at IS NOT NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update SLA policy
     * 
     * @param int $policyId Policy ID
     * @param array $data Policy data
     * @return bool Success status
     */
    public function updatePolicy($policyId, $data) {
        $sql = "UPDATE sla_policies 
                SET response_time = :response_time,
                    resolution_time = :resolution_time,
                    is_business_hours = :is_business_hours,
                    is_active = :is_active
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $policyId,
            ':response_time' => $data['response_time'],
            ':resolution_time' => $data['resolution_time'],
            ':is_business_hours' => $data['is_business_hours'],
            ':is_active' => $data['is_active']
        ]);
    }
}
