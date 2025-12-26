<?php
// api/post-reading.php
require_once __DIR__ . '/../includes/DB.php';

header('Content-Type: application/json');

// 1. Validate Request Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// 2. Parsed Input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['api_key'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON or missing API Key']);
    exit;
}

$apiKey = $input['api_key'];
$temp = $input['temperature'] ?? null;
$hum = $input['humidity'] ?? null;
$press = $input['pressure'] ?? null;
$alt = $input['altitude'] ?? null;

// Validate Data Types (Basic)
if (!is_numeric($temp) || !is_numeric($hum) || !is_numeric($press)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data format. numeric values required.']);
    exit;
}

try {
    $db = DB::getInstance();
    $pdo = $db->getConnection();

    // 3. Authenticate Device using Index `idx_api_key`
    $stmt = $pdo->prepare("SELECT id, device_id FROM devices WHERE api_key = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$apiKey]);
    $device = $stmt->fetch();

    if (!$device) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $deviceId = $device['device_id'];

    // 4. Fetch Previous Reading (For 'change' alerts)
    // Optimization: using idx_device_date (device_id, created_at)
    $stmt = $pdo->prepare("SELECT temperature, humidity, pressure FROM weather_readings WHERE device_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$deviceId]);
    $lastReading = $stmt->fetch();

    // 5. Insert New Reading
    $insertStmt = $pdo->prepare("INSERT INTO weather_readings (device_id, temperature, humidity, pressure, altitude) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->execute([$deviceId, $temp, $hum, $press, $alt]);
    $newReadingId = $pdo->lastInsertId();

    // 6. Process Alerts
    // Optimization: using idx_device_alerts
    $alertStmt = $pdo->prepare("SELECT id, alert_type, condition_type, threshold_value FROM alerts WHERE device_id = ? AND is_active = 1");
    $alertStmt->execute([$deviceId]);
    $alerts = $alertStmt->fetchAll();

    $triggeredAlerts = [];

    foreach ($alerts as $alert) {
        $triggered = false;
        $val = 0;
        $lastVal = 0;

        // Determine value to check
        switch ($alert['alert_type']) {
            case 'temp':
                $val = $temp;
                $lastVal = $lastReading['temperature'] ?? $temp;
                break;
            case 'hum':
                $val = $hum;
                $lastVal = $lastReading['humidity'] ?? $hum;
                break;
            case 'press':
                $val = $press;
                $lastVal = $lastReading['pressure'] ?? $press;
                break;
        }

        // Check Condition
        switch ($alert['condition_type']) {
            case 'above':
                if ($val > $alert['threshold_value'])
                    $triggered = true;
                break;
            case 'below':
                if ($val < $alert['threshold_value'])
                    $triggered = true;
                break;
            case 'change':
                if (abs($val - $lastVal) > $alert['threshold_value'])
                    $triggered = true;
                break;
        }

        if ($triggered) {
            $logStmt = $pdo->prepare("INSERT INTO alert_logs (alert_id, reading_id) VALUES (?, ?)");
            $logStmt->execute([$alert['id'], $newReadingId]);
            $triggeredAlerts[] = [
                'alert_id' => $alert['id'],
                'type' => $alert['alert_type'],
                'condition' => $alert['condition_type']
            ];
        }
    }

    http_response_code(201);
    echo json_encode([
        'message' => 'Reading saved',
        'reading_id' => $newReadingId,
        'alerts_triggered' => count($triggeredAlerts)
    ]);

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
?>