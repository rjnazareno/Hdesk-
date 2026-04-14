-- One-time migration: ticket reply conversation + attachments support
-- Safe to run multiple times on MySQL 5.7+ / MariaDB

START TRANSACTION;

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
  KEY `idx_ticket` (`ticket_id`),
  KEY `idx_user` (`user_id`, `user_type`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add missing attachment columns if table already existed without them
SET @has_attachment_path := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ticket_replies' AND COLUMN_NAME = 'attachment_path'
);
SET @sql := IF(@has_attachment_path = 0,
  'ALTER TABLE `ticket_replies` ADD COLUMN `attachment_path` VARCHAR(255) DEFAULT NULL AFTER `message`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_attachment_name := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ticket_replies' AND COLUMN_NAME = 'attachment_name'
);
SET @sql := IF(@has_attachment_name = 0,
  'ALTER TABLE `ticket_replies` ADD COLUMN `attachment_name` VARCHAR(255) DEFAULT NULL AFTER `attachment_path`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_attachment_mime := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ticket_replies' AND COLUMN_NAME = 'attachment_mime'
);
SET @sql := IF(@has_attachment_mime = 0,
  'ALTER TABLE `ticket_replies` ADD COLUMN `attachment_mime` VARCHAR(150) DEFAULT NULL AFTER `attachment_name`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_attachment_kind := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ticket_replies' AND COLUMN_NAME = 'attachment_kind'
);
SET @sql := IF(@has_attachment_kind = 0,
  'ALTER TABLE `ticket_replies` ADD COLUMN `attachment_kind` ENUM(''image'', ''file'') DEFAULT NULL AFTER `attachment_mime`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add missing indexes if absent
SET @has_idx_ticket := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ticket_replies' AND INDEX_NAME = 'idx_ticket'
);
SET @sql := IF(@has_idx_ticket = 0,
  'CREATE INDEX `idx_ticket` ON `ticket_replies` (`ticket_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_idx_user := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ticket_replies' AND INDEX_NAME = 'idx_user'
);
SET @sql := IF(@has_idx_user = 0,
  'CREATE INDEX `idx_user` ON `ticket_replies` (`user_id`, `user_type`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_idx_created := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ticket_replies' AND INDEX_NAME = 'idx_created'
);
SET @sql := IF(@has_idx_created = 0,
  'CREATE INDEX `idx_created` ON `ticket_replies` (`created_at`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;

-- Verification query
SELECT
  (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ticket_replies') AS table_exists,
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ticket_replies' AND COLUMN_NAME IN ('attachment_path','attachment_name','attachment_mime','attachment_kind')) AS attachment_columns_present;
