<?php
// includes/WeatherService.php
require_once __DIR__ . '/DB.php';

class WeatherService
{
    private $db;

    public function __construct()
    {
        $this->db = DB::getInstance();
    }

    /**
     * Determines Weather State based on metrics
     * Returns: [state_code, label, icon_name]
     */
    /**
     * Determines Weather State based on metrics
     * Returns: [state_code, label, icon_name]
     */
    public function calculateWeatherState($temp, $hum, $press)
    {
        // Logic (Strict):
        // Storm: Pressure < 990
        // Rain: Pressure < 1005 AND Humidity > 80
        // Sunny: Temperature > 25 AND Humidity < 60
        // Cloudy: Humidity > 60
        // Default: Variable

        if ($press < 990) {
            return ['code' => 'storm', 'label' => 'Tormenta', 'icon' => 'thunderstorms'];
        }
        if ($press < 1005 && $hum > 80) {
            return ['code' => 'rain', 'label' => 'Lluvioso', 'icon' => 'rain'];
        }
        if ($temp > 25 && $hum < 60) {
            return ['code' => 'sunny', 'label' => 'Soleado', 'icon' => 'clear-day'];
        }
        if ($hum > 60) {
            return ['code' => 'cloudy', 'label' => 'Nublado', 'icon' => 'cloudy'];
        }

        return ['code' => 'variable', 'label' => 'Variable', 'icon' => 'partly-cloudy-day'];
    }

    /**
     * Check if device is Online (seen in last 24h)
     */
    public function getOnlineStatus($lastSeenStr)
    {
        if (!$lastSeenStr)
            return false;
        $lastSeen = strtotime($lastSeenStr);
        return (time() - $lastSeen) < (24 * 3600);
    }

    public function getLatestReadings($deviceId, $limit = 300)
    {
        $sql = "SELECT temperature, humidity, pressure, created_at 
                FROM weather_readings 
                WHERE device_id = ? 
                ORDER BY created_at DESC 
                LIMIT " . intval($limit);

        $rows = $this->db->fetchAll($sql, [$deviceId]);
        return array_reverse($rows);
    }

    public function getReadingsByDate($deviceId, $date)
    {
        $startDate = $date . ' 00:00:00';
        $endDate = $date . ' 23:59:59';

        $sql = "SELECT temperature, humidity, pressure, created_at 
                FROM weather_readings 
                WHERE device_id = ? AND created_at BETWEEN ? AND ?
                ORDER BY created_at DESC";

        $rows = $this->db->fetchAll($sql, [$deviceId, $startDate, $endDate]);
        return array_reverse($rows);
    }

    public function calculateEMA($data, $field, $alpha = 0.3)
    {
        $ema = [];
        $previousEma = null;

        foreach ($data as $point) {
            $value = $point[$field];
            if ($previousEma === null) {
                $currentEma = $value;
            } else {
                $currentEma = ($value * $alpha) + ($previousEma * (1 - $alpha));
            }
            $ema[] = round($currentEma, 2);
            $previousEma = $currentEma;
        }
        // If we want to project the trend for the NEXT hour based on the last EMA slope:
        // For visual simplicity in chart.js, we return the EMA series overlay.
        return $ema;
    }

    public function getRecentAlerts($deviceId, $limit = 20)
    {
        $sql = "SELECT l.triggered_at, a.alert_type, a.condition_type, a.threshold_value, r.temperature, r.humidity, r.pressure
                FROM alert_logs l
                JOIN alerts a ON l.alert_id = a.id
                JOIN weather_readings r ON l.reading_id = r.id
                WHERE a.device_id = ?
                ORDER BY l.triggered_at DESC
                LIMIT " . intval($limit);
        return $this->db->fetchAll($sql, [$deviceId]);
    }

    public function getDeviceStatus($deviceId)
    {
        return $this->db->fetch("SELECT * FROM devices WHERE device_id = ?", [$deviceId]);
    }
    public function getActiveDevices()
    {
        return $this->db->fetchAll("SELECT device_id, name, location FROM devices WHERE is_active = 1 ORDER BY name ASC");
    }

    public function getRecentActivity($deviceId, $limit = 10)
    {
        // Combine Alerts and significant readings or just latest readings?
        // Requirement says: "crea un registro de alertas y de la actividad reciente."
        // Let's return just latest readings as activity log for now, separte from alerts.
        $sql = "SELECT created_at, temperature, humidity, pressure 
                FROM weather_readings 
                WHERE device_id = ? 
                ORDER BY created_at DESC 
                LIMIT " . intval($limit);
        return $this->db->fetchAll($sql, [$deviceId]);
    }
}
?>