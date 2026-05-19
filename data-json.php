<?php
require_once __DIR__ . '/includes/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['amr_auth'])) {
    http_response_code(403);
    exit;
}

$file = __DIR__ . '/data/amr-data.json';

if (!file_exists($file)) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: private, max-age=300');
readfile($file);
