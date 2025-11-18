-- Password Reset Tokens Table
-- Store secure tokens for password reset requests

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_email` VARCHAR(255) NOT NULL,
  `user_type` ENUM('employee', 'user') NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_email_type` (`user_email`, `user_type`),
  KEY `idx_token_expires` (`token`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clean up expired tokens (run periodically)
-- DELETE FROM password_reset_tokens WHERE expires_at < NOW() OR used = 1;
