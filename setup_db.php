<?php
// setup_db.php
require_once 'config.php';

try {
    $dsn = "mysql:host=" . DB_HOST;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);

    $sql = file_get_contents(__DIR__ . '/database/schema.sql');

    // Split by semicolon to execute statements individually if needed, 
    // but PDO might handle multiple statements if enabled. 
    // Safest is to split.
    $statements = explode(';', $sql);

    foreach ($statements as $stmt) {
        if (trim($stmt)) {
            $pdo->exec($stmt);
        }
    }

    echo "Database initialized successfully.\n";

} catch (PDOException $e) {
    echo "DB Setup Error: " . $e->getMessage() . "\n";
}
?>