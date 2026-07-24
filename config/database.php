<?php
$host = getenv('DB_HOST') ?: 'dpg-d9hnugn15fvs739htcs0-a.oregon-postgres.render.com';
$dbname = getenv('DB_NAME') ?: 'tourism_db_p3b0';
$username = getenv('DB_USER') ?: 'tourism_db_p3b0_user';
$password = getenv('DB_PASS') ?: '5OgOVAiOQHY7HIo46PBP5JatmVsVE0TH';
$port = getenv('DB_PORT') ?: '5432';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function getConnection() {
    global $pdo;
    return $pdo;
}
