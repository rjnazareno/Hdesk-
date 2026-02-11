<?php
/**
 * SLA (Service Level Agreement) Model
 * Handles SLA policy management, tracking, and breach detection
 */

class SLA {
    private $db;
    
    // Business hours configuration
    private const BUSINESS_START_HOUR = 8;  // 8:00 AM
    private const BUSINESS_END_HOUR = 17;   // 5:00 PM
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Check if a given date is a weekend (Saturday or Sunday)
     * 
     * @param DateTime $date The date to check
     * @return bool True if weekend
     */
    public function isWeekend(DateTime $date) {
        $dayOfWeek = (int)$date->format('N'); // 1=Monday, 7=Sunday
        return $dayOfWeek >= 6; // 6=Saturday, 7=Sunday
    }
    
    /**
     * Check if a given datetime is within business hours (Mon-Fri, 8AM-5PM)
     * 
     * @param DateTime $date The date to check
     * @return bool True if within business hours
     */
    public function isWithinBusinessHours(DateTime $date) {
        // Weekend check
        if ($this->isWeekend($date)) {
            return false;
        }
        
        $hour = (int)$date->format('H');
        return $hour >= self::BUSINESS_START_HOUR && $hour < self::BUSINESS_END_HOUR;
    }
    
    /**
     * Calculate business minutes between two dates (excludes weekends and after-hours)
     * 
     * @param DateTime $start Start date
     * @param DateTime $end End date
     * @param bool $businessHoursOnly Whether to only count business hours
     * @return int Business minutes elapsed
     */
    public function getBusinessMinutesBetween(DateTime $start, DateTime $end, $businessHoursOnly = true) {
        if (!$businessHoursOnly) {
            // Simple calculation - all minutes count
            return max(0, (int)round(($end->getTimestamp() - $start->getTimestamp()) / 60));
        }
        
        $businessMinutes = 0;
        $current = clone $start;
        
        // If start is after end, swap them
        if ($start > $end) {
            return 0;
        }
        
        while ($current < $end) {
            // Skip weekends entirely
            if ($this->isWeekend($current)) {
                $current->modify('+1 day');
                $current->setTime(self::BUSINESS_START_HOUR, 0, 0);
                continue;
            }
            
            $currentHour = (int)$current->format('H');
            $currentMinute = (int)$current->format('i');
            
            // If before business hours, jump to start
            if ($currentHour < self::BUSINESS_START_HOUR) {
                $current->setTime(self::BUSINESS_START_HOUR, 0, 0);
                continue;
            }
            
            // If after business hours, jump to next day
            if ($currentHour >= self::BUSINESS_END_HOUR) {
                $current->modify('+1 day');
                $current->setTime(self::BUSINESS_START_HOUR, 0, 0);
                continue;
            }
            
            // Calculate end of current business day
            $endOfBusinessDay = clone $current;
            $endOfBusinessDay->setTime(self::BUSINESS_END_HOUR, 0, 0);
            
            // Determine the actual end point for this iteration
            $effectiveEnd = ($end < $endOfBusinessDay) ? $end : $endOfBusinessDay;
            
            // Add the business minutes
            if ($effectiveEnd > $current) {
                $minutesToAdd = (int)round(($effectiveEnd->getTimestamp() - $current->getTimestamp()) / 60);
                $businessMinutes += max(0, $minutesToAdd);
            }
            
            // Move to next business day
            $current->modify('+1 day');
            $current->setTime(self::BUSINESS_START_HOUR, 0, 0);
        }
        
        return $businessMinutes;
    }
    
    /**
     * Get the next business time (skips weekends)
     * Returns the adjusted current time if it's a weekend or after hours
     * 
     * @param DateTime|null $date The date to adjust (defaults to now)
     * @return DateTime Adjusted business datetime
     */
    public function getNextBusinessTime(DateTime $date = null) {
        $current = $date ? clone $date : new DateTime();
        
        // Skip weekends
        while ($this->isWeekend($current)) {
            $current->modify('+1 day');
            $current->setTime(self::BUSINESS_START_HOUR, 0, 0);
        }
        
        $hour = (int)$current->format('H');
        
        // If before business hours, jump to start
        if ($hour < self::BUSINESS_START_HOUR) {
            $current->setTime(self::BUSINESS_START_HOUR, 0, 0);
        }
        
        // If after business hours, jump to next day
        if ($hour >= self::BUSINESS_END_HOUR) {
            $current->modify('+1 day');
            $current->setTime(self::BUSINESS_START_HOUR, 0, 0);
            // Make sure next day isn't a weekend
            while ($this->isWeekend($current)) {
                $current->modify('+1 day');
            }
        }
        
        return $current;
    }
    
    /**
     * Check if SLA should be considered breached, accounting for weekends
     * 
     * @param DateTime $dueDate The SLA due date
     * @param bool $isBusinessHoursOnly Whether business hours apply
     * @param DateTime|null $checkTime Time to check against (defaults to now)
     * @return bool True if breached
     */
    public function isSLABreached(DateTime $dueDate, $isBusinessHoursOnly, DateTime $checkTime = null) {
        $now = $checkTime ?? new DateTime();
        
        if (!$isBusinessHoursOnly) {
            // Simple comparison for 24/7 SLAs
            return $now > $dueDate;
        }
        
        // For business hours SLA, check if it's currently a weekend
        // If so, the SLA is effectively "paused"
        if ($this->isWeekend($now)) {
            // On weekends, never breach (SLA is paused)
            return false;
        }
        
        // Check if we're outside business hours
        $hour = (int)$now->format('H');
        if ($hour < self::BUSINESS_START_HOUR || $hour >= self::BUSINESS_END_HOUR) {
            return false; // Outside business hours, SLA paused
        }
        
        // During business hours, compare normally
        return $now > $dueDate;
    }
    
    /**
     * Get remaining business minutes until due date
     * 
     * @param DateTime $dueDate The due date
     * @param bool $isBusinessHoursOnly Whether business hours apply
     * @return int Remaining business minutes (negative if overdue)
     */
    public function getBusinessMinutesRemaining(DateTime $dueDate, $isBusinessHoursOnly) {
        $now = new DateTime();
        
        if (!$isBusinessHoursOnly) {
            // Simple calculation
            return (int)round(($dueDate->getTimestamp() - $now->getTimestamp()) / 60);
        }
        
        // If we're past the due date during business hours, calculate overdue
        if ($now > $dueDate) {
            $overdue = $this->getBusinessMinutesBetween($dueDate, $now, true);
            return -$overdue;
        }
        
        // Calculate remaining business minutes
        return $this->getBusinessMinutesBetween($now, $dueDate, true);
    }
    
    /**
     * Get SLA policy for a given priority
     * 
     * @param string $priority The ticket priority (low, medium, high)
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
                ORDER BY FIELD(priority, 'high', 'medium', 'low')";
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
            // Calculate remaining time and percentage (accounting for weekends)
            $now = new DateTime();
            $isBusinessHours = (bool)$sla['is_business_hours'];
            $sla['response_remaining'] = $this->calculateRemainingTime($now, $sla['response_due_at'], $sla['is_paused'], $isBusinessHours);
            $sla['resolution_remaining'] = $this->calculateRemainingTime($now, $sla['resolution_due_at'], $sla['is_paused'], $isBusinessHours);
            $sla['response_percentage'] = $this->calculateElapsedPercentage($sla['response_time_minutes'], $sla['target_response'], $sla['response_sla_status']);
            $sla['resolution_percentage'] = $this->calculateElapsedPercentage($sla['resolution_time_minutes'], $sla['target_resolution'], $sla['resolution_sla_status']);
            
            // Add weekend pause info
            $sla['is_weekend_paused'] = $isBusinessHours && $this->isWeekend($now);
            $sla['is_after_hours_paused'] = $isBusinessHours && !$this->isWithinBusinessHours($now);
        }
        
        return $sla;
    }
    
    /**
     * Calculate remaining time until deadline (accounting for weekends)
     * 
     * @param DateTime $now Current time
     * @param string $dueDate Due date string
     * @param bool $isPaused Whether SLA is paused
     * @param bool $isBusinessHoursOnly Whether to only count business hours (excludes weekends)
     * @return array Remaining time data
     */
    private function calculateRemainingTime($now, $dueDate, $isPaused, $isBusinessHoursOnly = false) {
        $due = new DateTime($dueDate);
        
        // Check if SLA is auto-paused (weekend or after hours)
        $isWeekendPaused = $isBusinessHoursOnly && ($this->isWeekend($now) || !$this->isWithinBusinessHours($now));
        $effectivelyPaused = $isPaused || $isWeekendPaused;
        
        if ($isBusinessHoursOnly) {
            // Calculate using business minutes only (excludes weekends)
            $totalMinutes = $this->getBusinessMinutesRemaining($due, true);
            $isOverdue = $totalMinutes < 0;
        } else {
            // Simple calendar time calculation
            $diff = $now->diff($due);
            $totalMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            if ($diff->invert) {
                $totalMinutes = -$totalMinutes;
            }
            $isOverdue = $diff->invert;
        }
        
        return [
            'minutes' => $totalMinutes,
            'hours' => floor(abs($totalMinutes) / 60),
            'remaining_minutes' => abs($totalMinutes) % 60,
            'is_overdue' => $isOverdue && !$effectivelyPaused,
            'is_weekend_paused' => $isWeekendPaused,
            'formatted' => $this->formatRemainingTime($totalMinutes, $isOverdue, $effectivelyPaused, $isWeekendPaused)
        ];
    }
    
    /**
     * Format remaining time for display
     * 
     * @param int $totalMinutes Total minutes
     * @param bool $isOverdue Whether time is overdue
     * @param bool $isPaused Whether SLA is paused
     * @param bool $isWeekendPaused Whether SLA is paused due to weekend
     * @return string Formatted time string
     */
    private function formatRemainingTime($totalMinutes, $isOverdue, $isPaused, $isWeekendPaused = false) {
        if ($isPaused && !$isWeekendPaused) {
            return 'Paused';
        }
        
        if ($isWeekendPaused) {
            // Show remaining time with weekend pause indicator
            $hours = floor(abs($totalMinutes) / 60);
            $minutes = abs($totalMinutes) % 60;
            $formatted = '';
            if ($hours > 0) {
                $formatted .= $hours . 'h ';
            }
            $formatted .= $minutes . 'm';
            return $formatted . ' (paused - weekend)';
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
     * Record first response to a ticket (accounts for weekends/business hours)
     * 
     * @param int $ticketId The ticket ID
     * @return bool Success status
     */
    public function recordFirstResponse($ticketId) {
        // First, get the SLA tracking info and policy
        $sla = $this->getTicketSLA($ticketId);
        if (!$sla || $sla['first_response_at'] !== null) {
            return false; // Already responded or no SLA tracking
        }
        
        // Get ticket creation time
        $ticketSql = "SELECT created_at FROM tickets WHERE id = :ticket_id";
        $ticketStmt = $this->db->prepare($ticketSql);
        $ticketStmt->execute([':ticket_id' => $ticketId]);
        $ticket = $ticketStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticket) {
            return false;
        }
        
        $now = new DateTime();
        $createdAt = new DateTime($ticket['created_at']);
        $responseDue = new DateTime($sla['response_due_at']);
        $isBusinessHoursOnly = (bool)$sla['is_business_hours'];
        
        // Calculate actual response time in business minutes
        $responseTimeMinutes = $this->getBusinessMinutesBetween($createdAt, $now, $isBusinessHoursOnly);
        
        // Determine if breached using weekend-aware check
        $isBreached = $this->isSLABreached($responseDue, $isBusinessHoursOnly, $now);
        $slaStatus = $isBreached ? 'breached' : 'met';
        
        $sql = "UPDATE sla_tracking 
                SET first_response_at = NOW(),
                    response_time_minutes = :response_minutes,
                    response_sla_status = :sla_status
                WHERE ticket_id = :ticket_id 
                AND first_response_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':ticket_id' => $ticketId,
            ':response_minutes' => $responseTimeMinutes,
            ':sla_status' => $slaStatus
        ]);
        
        // Check if breached and log
        if ($result) {
            $this->checkAndLogBreach($ticketId, 'response');
            $this->updateTicketSLAStatus($ticketId);
        }
        
        return $result;
    }
    
    /**
     * Record ticket resolution (accounts for weekends/business hours)
     * 
     * @param int $ticketId The ticket ID
     * @return bool Success status
     */
    public function recordResolution($ticketId) {
        // First, get the SLA tracking info and policy
        $sla = $this->getTicketSLA($ticketId);
        if (!$sla) {
            return false; // No SLA tracking
        }
        
        // Get ticket creation time
        $ticketSql = "SELECT created_at FROM tickets WHERE id = :ticket_id";
        $ticketStmt = $this->db->prepare($ticketSql);
        $ticketStmt->execute([':ticket_id' => $ticketId]);
        $ticket = $ticketStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticket) {
            return false;
        }
        
        $now = new DateTime();
        $createdAt = new DateTime($ticket['created_at']);
        $resolutionDue = new DateTime($sla['resolution_due_at']);
        $isBusinessHoursOnly = (bool)$sla['is_business_hours'];
        
        // Calculate actual resolution time in business minutes (minus paused time)
        $totalElapsed = $this->getBusinessMinutesBetween($createdAt, $now, $isBusinessHoursOnly);
        $resolutionTimeMinutes = $totalElapsed - (int)$sla['total_pause_minutes'];
        
        // Determine if breached using weekend-aware check
        $isBreached = $this->isSLABreached($resolutionDue, $isBusinessHoursOnly, $now);
        $slaStatus = $isBreached ? 'breached' : 'met';
        
        $sql = "UPDATE sla_tracking 
                SET resolved_at = NOW(),
                    resolution_time_minutes = :resolution_minutes,
                    resolution_sla_status = :sla_status
                WHERE ticket_id = :ticket_id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':ticket_id' => $ticketId,
            ':resolution_minutes' => $resolutionTimeMinutes,
            ':sla_status' => $slaStatus
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
     * Get tickets at risk of breaching SLA (accounts for weekends)
     * 
     * @param int $minutesThreshold Business minutes until breach (default 60)
     * @return array Tickets at risk
     */
    public function getAtRiskTickets($minutesThreshold = 60) {
        // Get all open tickets that haven't been resolved yet
        $sql = "SELECT t.id, t.ticket_number, t.title, t.priority, t.status,
                st.resolution_due_at, sp.is_business_hours
                FROM tickets t
                JOIN sla_tracking st ON t.id = st.ticket_id
                JOIN sla_policies sp ON st.sla_policy_id = sp.id
                WHERE t.status NOT IN ('closed', 'resolved')
                AND st.is_paused = 0
                AND st.resolved_at IS NULL
                AND st.resolution_sla_status = 'pending'
                ORDER BY st.resolution_due_at ASC";
        
        $stmt = $this->db->query($sql);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $atRiskTickets = [];
        $now = new DateTime();
        
        foreach ($tickets as $ticket) {
            $dueDate = new DateTime($ticket['resolution_due_at']);
            $isBusinessHours = (bool)$ticket['is_business_hours'];
            
            // Skip if currently on weekend (SLA paused)
            if ($isBusinessHours && $this->isWeekend($now)) {
                continue;
            }
            
            // Calculate business minutes remaining
            $minutesRemaining = $this->getBusinessMinutesRemaining($dueDate, $isBusinessHours);
            
            // Check if within threshold and not breached
            if ($minutesRemaining > 0 && $minutesRemaining <= $minutesThreshold) {
                $ticket['minutes_remaining'] = $minutesRemaining;
                $ticket['is_weekend_excluded'] = $isBusinessHours;
                unset($ticket['is_business_hours']);
                $atRiskTickets[] = $ticket;
            }
        }
        
        return $atRiskTickets;
    }
    
    /**
     * Get breached tickets (accounts for weekends)
     * 
     * @return array Breached tickets
     */
    public function getBreachedTickets() {
        // Get all open tickets with potential breach
        $sql = "SELECT t.id, t.ticket_number, t.title, t.priority, t.status,
                st.resolution_due_at, sp.is_business_hours,
                sb.breach_type, sb.delay_minutes,
                st.response_sla_status, st.resolution_sla_status
                FROM tickets t
                JOIN sla_tracking st ON t.id = st.ticket_id
                JOIN sla_policies sp ON st.sla_policy_id = sp.id
                LEFT JOIN sla_breaches sb ON t.id = sb.ticket_id
                WHERE t.status NOT IN ('closed', 'resolved')
                ORDER BY st.resolution_due_at ASC";
        
        $stmt = $this->db->query($sql);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $breachedTickets = [];
        $now = new DateTime();
        
        foreach ($tickets as $ticket) {
            // Skip if already marked as breached in database
            if ($ticket['response_sla_status'] === 'breached' || $ticket['resolution_sla_status'] === 'breached') {
                $dueDate = new DateTime($ticket['resolution_due_at']);
                $isBusinessHours = (bool)$ticket['is_business_hours'];
                $ticket['minutes_overdue'] = abs($this->getBusinessMinutesRemaining($dueDate, $isBusinessHours));
                $ticket['is_weekend_excluded'] = $isBusinessHours;
                unset($ticket['is_business_hours'], $ticket['response_sla_status'], $ticket['resolution_sla_status']);
                $breachedTickets[] = $ticket;
                continue;
            }
            
            $dueDate = new DateTime($ticket['resolution_due_at']);
            $isBusinessHours = (bool)$ticket['is_business_hours'];
            
            // Check if breached using weekend-aware logic
            if ($this->isSLABreached($dueDate, $isBusinessHours, $now)) {
                $ticket['minutes_overdue'] = abs($this->getBusinessMinutesRemaining($dueDate, $isBusinessHours));
                $ticket['is_weekend_excluded'] = $isBusinessHours;
                unset($ticket['is_business_hours'], $ticket['response_sla_status'], $ticket['resolution_sla_status']);
                $breachedTickets[] = $ticket;
            }
        }
        
        return $breachedTickets;
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
