-- Create issue_plans table
CREATE TABLE IF NOT EXISTS `lottery`.`issue_plans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `issue_date` DATE NOT NULL,
  `total_tickets` INT NOT NULL,
  `batch_size` INT NOT NULL,
  `start_number` VARCHAR(20) NOT NULL,
  `notes` TEXT,
  `status` ENUM('draft', 'ready', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `lottery_products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create issue_queue table
CREATE TABLE IF NOT EXISTS `lottery`.`issue_queue` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plan_id` INT NOT NULL,
  `status` ENUM('pending', 'in_progress', 'completed', 'cancelled', 'failed') NOT NULL DEFAULT 'pending',
  `total_tickets` INT NOT NULL,
  `processed_tickets` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`plan_id`) REFERENCES `issue_plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create issue_history table
CREATE TABLE IF NOT EXISTS `lottery`.`issue_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plan_id` INT NOT NULL,
  `queue_id` INT NOT NULL,
  `issued_by` INT,
  `status` ENUM('started', 'in_progress', 'completed', 'cancelled', 'failed') NOT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`plan_id`) REFERENCES `issue_plans`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`queue_id`) REFERENCES `issue_queue`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;