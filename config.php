<?php
// config.php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'iot_weather');
define('DB_USER', 'iotuser');
define('DB_PASS', 'IotUser2025!'); // WARNING: Change this in production!

// Timezone
date_default_timezone_set('America/Bogota'); // Adjust to user's timezone if needed

// Error Reporting (Disable in production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
?>