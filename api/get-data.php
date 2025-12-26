<?php
// api/get-data.php
require_once __DIR__ . '/../includes/WeatherService.php';

header('Content-Type: application/json');

// Default Device ID Logic
$deviceId = $_GET['device_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');

try {
    $service = new WeatherService();

    // If no device ID specified, pick the first active one
    if (!$deviceId) {
        $activeDevices = $service->getActiveDevices();
        if (!empty($activeDevices)) {
            $deviceId = $activeDevices[0]['device_id'];
        } else {
            // Fallback if no devices exist at all
            $deviceId = 'STATION-01';
        }
    }

    // 1. Get Readings for the specific date (for Charts & stats)
    $readings = $service->getReadingsByDate($deviceId, $date);

    // 2. Get Latest Reading (Absolute or for that date)
    // If today, use absolute latest. If past date, use last of that day.
    $latestReading = null;
    if ($readings) {
        $latestReading = end($readings); // Last item in the array (since query returns ASC usually, but let's check Service)
        // Service returns array_reverse($rows), so index 0 is NEWEST.
        $latestReading = $readings[0];
    } else {
        // If no data for that date, try to get absolute latest just to show something? 
        // No, requirements say: "si no se ha seleccionado ninguna fecha, se mostrará el último registro recibido"
        // But here a date is always selected (default today).
        // Let's stick to returning empty if no data for date, OR handle "all time" if date is empty.
    }

    // 3. Calculate EMA (Trend) for Chart
    // User requested EMA alpha=0.3 on last 50-300 records. 
    // We'll pass the whole dataset for the day to the frontend, 
    // BUT we also need to return the EMA series calculated in PHP.

    $temps = array_column($readings, 'temperature');
    $emaTemp = $service->calculateEMA($readings, 'temperature', 0.3);

    // 4. Calculate Weather State
    $weatherState = ['code' => 'unknown', 'label' => '--', 'icon' => ''];
    if ($latestReading) {
        $weatherState = $service->calculateWeatherState(
            $latestReading['temperature'],
            $latestReading['humidity'],
            $latestReading['pressure']
        );
    }

    // 5. Get Device Status (Online/Offline)
    $deviceInfo = $service->getDeviceStatus($deviceId);
    $isOnline = $service->getOnlineStatus($deviceInfo['last_seen'] ?? null);

    // 6. Get Recent Alerts
    $alerts = $service->getRecentAlerts($deviceId, 10);

    // 7. Get Recent Activity (Log)
    $activity = $service->getRecentActivity($deviceId, 10);

    // 8. Get All Devices (for selector)
    $devices = $service->getActiveDevices();

    echo json_encode([
        'devices' => $devices, // List for dropdown
        'device' => [
            'id' => $deviceId, // Current
            'name' => $deviceInfo['name'] ?? 'Unknown',
            'is_online' => $isOnline,
            'last_seen' => $deviceInfo['last_seen'] ?? null
        ],
        'current' => $latestReading,
        'state' => $weatherState,
        'history' => $readings, // For Chart
        'ema_temperature' => $emaTemp, // Calculated Series
        'alerts' => $alerts,
        'activity' => $activity
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>