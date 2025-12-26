-- Database Schema for IoT Weather Station
-- Optimized for Performance and Data Integrity

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 
-- Database: `iot_weather`
--
CREATE DATABASE IF NOT EXISTS `iot_weather` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `iot_weather`;

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE IF NOT EXISTS `devices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `device_id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `api_key` VARCHAR(64) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `last_seen` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_device_id` (`device_id`),
  UNIQUE KEY `idx_api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weather_readings`
--

CREATE TABLE IF NOT EXISTS `weather_readings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `device_id` VARCHAR(50) NOT NULL,
  `temperature` DECIMAL(5,2) NOT NULL,
  `humidity` DECIMAL(5,2) NOT NULL,
  `pressure` DECIMAL(7,2) NOT NULL,
  `altitude` DECIMAL(7,2) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_device_date` (`device_id`,`created_at`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE IF NOT EXISTS `alerts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `device_id` VARCHAR(50) NOT NULL,
  `alert_type` ENUM('temp','hum','press') NOT NULL,
  `condition_type` ENUM('above','below','change') NOT NULL,
  `threshold_value` DECIMAL(7,2) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_device_alerts` (`device_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alert_logs`
--

CREATE TABLE IF NOT EXISTS `alert_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `alert_id` INT(11) NOT NULL,
  `reading_id` INT(11) NOT NULL,
  `triggered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_logs_alert` (`alert_id`),
  KEY `fk_logs_reading` (`reading_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reading_stats`
--

CREATE TABLE IF NOT EXISTS `reading_stats` (
  `device_id` VARCHAR(50) NOT NULL,
  `date` DATE NOT NULL,
  `total_readings` INT(11) DEFAULT 0,
  `avg_temp` DECIMAL(5,2) DEFAULT NULL,
  `min_temp` DECIMAL(5,2) DEFAULT NULL,
  `max_temp` DECIMAL(5,2) DEFAULT NULL,
  `avg_humidity` DECIMAL(5,2) DEFAULT NULL,
  `avg_pressure` DECIMAL(7,2) DEFAULT NULL,
  PRIMARY KEY (`device_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Foreign Key Constraints
--

ALTER TABLE `weather_readings`
  ADD CONSTRAINT `fk_readings_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `alerts`
  ADD CONSTRAINT `fk_alerts_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `alert_logs`
  ADD CONSTRAINT `fk_logs_alert` FOREIGN KEY (`alert_id`) REFERENCES `alerts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_logs_reading` FOREIGN KEY (`reading_id`) REFERENCES `weather_readings` (`id`) ON DELETE CASCADE;

--
-- Dummy Data for Verification (Optional)
--
INSERT INTO `devices` (`device_id`, `name`, `location`, `api_key`, `is_active`, `last_seen`) VALUES
('STATION-01', 'Estación Principal', 'Jardín Norte', 'c81e728d9d4c2f636f067f89cc14862c', 1, NOW());

INSERT INTO `alerts` (`device_id`, `alert_type`, `condition_type`, `threshold_value`, `is_active`) VALUES
('STATION-01', 'temp', 'above', 30.00, 1),
('STATION-01', 'hum', 'below', 20.00, 1),
('STATION-01', 'press', 'change', 5.00, 1);

COMMIT;
