-- Database Schema for IoT Weather Station
-- Table structure for table `devices`
CREATE TABLE IF NOT EXISTS `devices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `device_id` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `location` VARCHAR(100),
  `api_key` VARCHAR(64) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `last_seen` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `weather_readings`
CREATE TABLE IF NOT EXISTS `weather_readings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `device_id` VARCHAR(50) NOT NULL,
  `temperature` DECIMAL(5,2) NOT NULL,
  `humidity` DECIMAL(5,2) NOT NULL,
  `pressure` DECIMAL(7,2) NOT NULL,
  `altitude` DECIMAL(7,2) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_readings_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE CASCADE,
  INDEX `idx_device_date` (`device_id`, `created_at`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `alerts`
CREATE TABLE IF NOT EXISTS `alerts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `device_id` VARCHAR(50) NOT NULL,
  `alert_type` ENUM('temp', 'hum', 'press') NOT NULL,
  `condition_type` ENUM('above', 'below', 'change') NOT NULL,
  `threshold_value` DECIMAL(7,2) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  CONSTRAINT `fk_alerts_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE CASCADE,
  INDEX `idx_device_alerts` (`device_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `alert_logs`
CREATE TABLE IF NOT EXISTS `alert_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `alert_id` INT NOT NULL,
  `reading_id` INT NOT NULL,
  `triggered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_alert` FOREIGN KEY (`alert_id`) REFERENCES `alerts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_logs_reading` FOREIGN KEY (`reading_id`) REFERENCES `weather_readings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `reading_stats`
CREATE TABLE IF NOT EXISTS `reading_stats` (
  `device_id` VARCHAR(50) NOT NULL,
  `date` DATE NOT NULL,
  `total_readings` INT DEFAULT 0,
  `avg_temp` DECIMAL(5,2),
  `min_temp` DECIMAL(5,2),
  `max_temp` DECIMAL(5,2),
  `avg_humidity` DECIMAL(5,2),
  `avg_pressure` DECIMAL(7,2),
  PRIMARY KEY (`device_id`, `date`),
  CONSTRAINT `fk_stats_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
