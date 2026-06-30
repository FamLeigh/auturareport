<?php
// Gated JSON feed for the Buyer Market Report. Requires login AND the
// customer-data access code (same gate as the report page).
require_once __DIR__ . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['amr_auth']) || empty($_SESSION['cr_auth'])) {
    http_response_code(403);
    exit;
}

$file = __DIR__ . '/data/amr-buyers.json';
if (!file_exists($file)) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: private, max-age=300');
readfile($file);
