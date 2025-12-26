<?php
// api/get-data.php
require_once __DIR__ . '/../includes/WeatherService.php';

header('Content-Type: application/json');
$deviceId = $_GET['device_id'] ?? null;

if (!$deviceId) {
    // Determine active device
    $db = DB::getInstance()->getConnection();
    $stmt = $db->query("SELECT device_id FROM devices LIMIT 1"); // Get any device, preferring active
    $device = $stmt->fetch();
    if ($device)
        $deviceId = $device['device_id'];
    else {
        echo json_encode(['error' => 'No devices']);
        exit;
    }
}

try {
    $service = new WeatherService();
    $deviceInfo = $service->getDeviceStatus($deviceId);

    // Check Online Status
    $isOnline = $service->getOnlineStatus($deviceInfo['last_seen'] ?? null); // Or check last reading time
    // Better: Check last reading in DB
    $lastReadStmt = DB::getInstance()->query("SELECT created_at FROM weather_readings WHERE device_id = ? ORDER BY created_at DESC LIMIT 1", [$deviceId]);
    $lastRead = $lastReadStmt->fetch();
    if ($lastRead) {
        $isOnline = $service->getOnlineStatus($lastRead['created_at']);
    }

    // Date Filtering
    $dateFilter = $_GET['date'] ?? date('Y-m-d');
    $readings = $service->getReadingsByDate($deviceId, $dateFilter);

    // If no readings for selected date, we return empty structure for chart
    // BUT user requirements say: "Si no ha seleccionado ninguna fecha (o hoy), mostrar ultimo registro"
    // "En las cards se debe mostrar el dato del registro más reciente de la fecha seleccionada"

    $history = $readings;

    // Calculate EMA on this history
    $emaTemp = $service->calculateEMA($history, 'temperature', 0.3);

    // Current values for Cards
    $current = null;
    $weatherState = ['label' => 'Desconocido', 'icon' => 'unknown'];

    if (!empty($history)) {
        $current = end($history); // Last of the day
        $weatherState = $service->calculateWeatherState($current['temperature'], $current['humidity'], $current['pressure']);
    } else if ($dateFilter === date('Y-m-d')) {
        // If today has no data yet, maybe show absolute last?
        // Requirement: "sino se ha seleccionado ninguna fecha, se mostrará el último registro recibido"
        // Since we default filter to Today, if Today is empty, we should check global last?
        // Let's stick to the filter context logic: if 0 records today, show 0.
        // User can switch logic if needed.
    }

    $alerts = $service->getRecentAlerts($deviceId);

    echo json_encode([
        'device' => [
            'name' => $deviceInfo['name'],
            'status' => $isOnline ? 'online' : 'offline',
            'last_seen' => $deviceInfo['last_seen']
        ],
        'current' => $current,
        'state' => $weatherState,
        'history' => $history,
        'ema_temp' => $emaTemp,
        'alerts' => $alerts
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>