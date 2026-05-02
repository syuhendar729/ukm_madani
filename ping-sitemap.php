<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || $input['action'] !== 'ping_sitemap') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

require_once 'config/sitemap-helper.php';

// 10% chance to ping (untuk mengurangi load)
if (rand(1, 10) == 1) {
    $pinged = pingSitemapWithRateLimit();
    echo json_encode([
        'success' => $pinged,
        'message' => $pinged ? 'Sitemap ping sent' : 'Ping skipped (rate limited)'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Ping skipped (random chance)'
    ]);
}
?>