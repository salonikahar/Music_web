-- Add mobile_number column to users table
ALTER TABLE `users` ADD COLUMN `mobile_number` varchar(20) DEFAULT NULL;

-- Create password_reset_otps table for storing OTP records
CREATE TABLE IF NOT EXISTS `password_reset_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `mobile_number` varchar(20) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create password_reset_attempts table for rate limiting
CREATE TABLE IF NOT EXISTS `password_reset_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `attempt_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional: Create index for faster OTP lookups
CREATE INDEX `idx_user_otp` ON `password_reset_otps`(`user_id`, `is_used`, `expires_at`);
CREATE INDEX `idx_user_attempts` ON `password_reset_attempts`(`user_id`, `attempt_at`);
