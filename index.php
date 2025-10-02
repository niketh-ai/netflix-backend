<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key");

// Simple router
$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Route requests
if (strpos($request_uri, '/api/auth') !== false && $method === 'POST') {
    require_once 'api/auth.php';
} elseif (strpos($request_uri, '/api/config') !== false && $method === 'POST') {
    require_once 'api/config.php';
} elseif (strpos($request_uri, '/api/announcements') !== false && $method === 'POST') {
    require_once 'api/announcements.php';
} else {
    http_response_code(404);
    echo json_encode([
        "success" => false, 
        "message" => "Endpoint not found",
        "timestamp" => time()
    ]);
}
?>