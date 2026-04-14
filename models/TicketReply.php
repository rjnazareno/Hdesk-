<?php
/**
 * TicketReply Model
 * Handles ticket conversation between admin/IT staff and customers
 */
class TicketReply {
    private $db;
    private static $schemaChecked = false;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureSchema();
    }

    /**
     * Ensure reply conversation schema exists in older deployments.
     */
    private function ensureSchema() {
        if (self::$schemaChecked) {
            return;
        }

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `ticket_replies` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `ticket_id` INT(11) NOT NULL,
                `user_id` INT(11) NOT NULL,
                `user_type` ENUM('employee', 'user') NOT NULL DEFAULT 'employee',
                `message` TEXT NOT NULL,
                `attachment_path` VARCHAR(255) DEFAULT NULL,
                `attachment_name` VARCHAR(255) DEFAULT NULL,
                `attachment_mime` VARCHAR(150) DEFAULT NULL,
                `attachment_kind` ENUM('image', 'file') DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_ticket` (`ticket_id`),
                INDEX `idx_user` (`user_id`, `user_type`),
                INDEX `idx_created` (`created_at`),
                CONSTRAINT `ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->ensureColumnExists('ticket_replies', 'attachment_path', "ALTER TABLE `ticket_replies` ADD COLUMN `attachment_path` VARCHAR(255) DEFAULT NULL AFTER `message`");
        $this->ensureColumnExists('ticket_replies', 'attachment_name', "ALTER TABLE `ticket_replies` ADD COLUMN `attachment_name` VARCHAR(255) DEFAULT NULL AFTER `attachment_path`");
        $this->ensureColumnExists('ticket_replies', 'attachment_mime', "ALTER TABLE `ticket_replies` ADD COLUMN `attachment_mime` VARCHAR(150) DEFAULT NULL AFTER `attachment_name`");
        $this->ensureColumnExists('ticket_replies', 'attachment_kind', "ALTER TABLE `ticket_replies` ADD COLUMN `attachment_kind` ENUM('image', 'file') DEFAULT NULL AFTER `attachment_mime`");

        $this->ensureIndexExists('ticket_replies', 'idx_ticket', "CREATE INDEX `idx_ticket` ON `ticket_replies` (`ticket_id`)");
        $this->ensureIndexExists('ticket_replies', 'idx_user', "CREATE INDEX `idx_user` ON `ticket_replies` (`user_id`, `user_type`)");
        $this->ensureIndexExists('ticket_replies', 'idx_created', "CREATE INDEX `idx_created` ON `ticket_replies` (`created_at`)");

        self::$schemaChecked = true;
    }

    private function ensureColumnExists($table, $column, $alterSql) {
        $stmt = $this->db->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column_name");
        $stmt->execute([':column_name' => $column]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->db->exec($alterSql);
        }
    }

    private function ensureIndexExists($table, $indexName, $createSql) {
        $stmt = $this->db->prepare("SHOW INDEX FROM `{$table}` WHERE Key_name = :index_name");
        $stmt->execute([':index_name' => $indexName]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->db->exec($createSql);
        }
    }

    /**
     * Create a new reply
     */
    public function create($data) {
        $sql = "INSERT INTO ticket_replies (
                    ticket_id,
                    user_id,
                    user_type,
                    message,
                    attachment_path,
                    attachment_name,
                    attachment_mime,
                    attachment_kind
                ) VALUES (
                    :ticket_id,
                    :user_id,
                    :user_type,
                    :message,
                    :attachment_path,
                    :attachment_name,
                    :attachment_mime,
                    :attachment_kind
                )";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':ticket_id' => $data['ticket_id'],
            ':user_id' => $data['user_id'],
            ':user_type' => $data['user_type'],
            ':message' => $data['message'] ?? '',
            ':attachment_path' => $data['attachment_path'] ?? null,
            ':attachment_name' => $data['attachment_name'] ?? null,
            ':attachment_mime' => $data['attachment_mime'] ?? null,
            ':attachment_kind' => $data['attachment_kind'] ?? null
        ]);
    }

    /**
     * Validate and upload a reply attachment.
     * Returns an array with uploaded file details, or ['error' => '...'].
     */
    public function uploadAttachment($file, $expectedKind = 'file') {
        if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Attachment upload failed. Please try again.'];
        }

        if (!isset($file['size']) || (int)$file['size'] > MAX_FILE_SIZE) {
            return ['error' => 'Attachment exceeds the maximum file size limit.'];
        }

        $originalName = basename((string)$file['name']);
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $extension = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));

        if (!$extension || !in_array($extension, ALLOWED_EXTENSIONS, true)) {
            return ['error' => 'Unsupported attachment type.'];
        }

        $mimeType = 'application/octet-stream';
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected = finfo_file($finfo, $file['tmp_name']);
            if ($detected) {
                $mimeType = $detected;
            }
            finfo_close($finfo);
        }

        if ($expectedKind === 'image' && strpos($mimeType, 'image/') !== 0) {
            return ['error' => 'Selected image attachment is not a valid image file.'];
        }

        $uploadDir = rtrim(UPLOAD_DIR, '\\/') . DIRECTORY_SEPARATOR . 'replies' . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            return ['error' => 'Unable to create reply upload directory.'];
        }

        $randomPart = uniqid('', true);
        if (function_exists('random_bytes')) {
            try {
                $randomPart = bin2hex(random_bytes(8));
            } catch (Exception $e) {
                $randomPart = uniqid('', true);
            }
        }

        $storedName = time() . '_' . $randomPart . '_' . $safeName;
        $targetPath = $uploadDir . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['error' => 'Failed to save attachment file.'];
        }

        return [
            'path' => 'replies/' . $storedName,
            'name' => $originalName,
            'mime' => $mimeType,
            'kind' => $expectedKind === 'image' ? 'image' : 'file'
        ];
    }

    /**
     * Get all replies for a ticket with sender names
     */
    public function getByTicketId($ticketId) {
        $sql = "SELECT r.*,
                    CASE 
                        WHEN r.user_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                        ELSE u.full_name
                    END as sender_name,
                    CASE 
                        WHEN r.user_type = 'employee' THEN e.admin_rights_hdesk
                        ELSE u.role
                    END as sender_role
                FROM ticket_replies r
                LEFT JOIN employees e ON r.user_id = e.id AND r.user_type = 'employee'
                LEFT JOIN users u ON r.user_id = u.id AND r.user_type = 'user'
                WHERE r.ticket_id = :ticket_id
                ORDER BY r.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ticket_id' => $ticketId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get reply count for a ticket
     */
    public function getReplyCount($ticketId) {
        $sql = "SELECT COUNT(*) as count FROM ticket_replies WHERE ticket_id = :ticket_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ticket_id' => $ticketId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }
}
