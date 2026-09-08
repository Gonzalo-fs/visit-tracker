<?php
// ==========================================================
// visit-tracker: Tracking API Endpoint (track.php)
// ==========================================================

// Configure CORS headers to allow requests from external clients
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours

// Handle CORS preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Require configuration file
require_once __DIR__ . '/config.php';

// Validate HTTP method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'error' => 'Method not allowed. Only POST is accepted.'], 405);
}

// Read and decode JSON payload
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    // Fallback to $_POST if request was not sent as application/json payload
    $data = $_POST;
}

// Extract and sanitize input fields
$siteToken = isset($data['site_token']) ? trim($data['site_token']) : '';
$rawUrl    = isset($data['url']) ? trim($data['url']) : '';
$device    = isset($data['device']) ? trim($data['device']) : '';

// Validate site token parameter
if (empty($siteToken)) {
    sendJsonResponse(['success' => false, 'error' => 'The site_token parameter is required.'], 400);
}

// Normalize page URL
if (empty($rawUrl)) {
    $rawUrl = '/';
}
// Truncate length to max 255 characters
$pageUrl = mb_substr($rawUrl, 0, 255, 'UTF-8');

// Obtain database connection
$pdo = getDBConnection();

// 1. Validate site token
$stmtSite = $pdo->prepare('SELECT id, domain FROM sites WHERE site_token = :token LIMIT 1');
$stmtSite->execute([':token' => $siteToken]);
$site = $stmtSite->fetch();

if (!$site) {
    sendJsonResponse(['success' => false, 'error' => 'Invalid or unregistered site token.'], 404);
}

$siteId = (int)$site['id'];

// 2. Find or create page record for this site
$stmtPage = $pdo->prepare('SELECT id FROM pages WHERE site_id = :site_id AND url = :url LIMIT 1');
$stmtPage->execute([
    ':site_id' => $siteId,
    ':url'     => $pageUrl
]);
$page = $stmtPage->fetch();

if ($page) {
    $pageId = (int)$page['id'];
} else {
    // Insert new page record for this site
    $stmtInsertPage = $pdo->prepare('INSERT INTO pages (site_id, url, created_at) VALUES (:site_id, :url, NOW())');
    $stmtInsertPage->execute([
        ':site_id' => $siteId,
        ':url'     => $pageUrl
    ]);
    $pageId = (int)$pdo->lastInsertId();
}

// 3. Extract IP address and User-Agent for GDPR-compliant hash calculation
$ip = '0.0.0.0';
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    // Extract first IP if request passed through proxies
    $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($ipList[0]);
} elseif (!empty($_SERVER['REMOTE_ADDR'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
}

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User Agent';

// 4. Calculate anonymous, non-reversible visitor_hash (GDPR compliant)
$visitorHash = hash('sha256', $ip . $userAgent . SECRET_SALT);

// 5. Normalize device type (Mobile / Desktop / Tablet)
$validDevices = ['Mobile', 'Desktop', 'Tablet'];
if (!in_array($device, $validDevices, true)) {
    // User-Agent fallback detection
    if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $userAgent)) {
        $device = 'Tablet';
    } elseif (preg_match('/Mobile|iP(hone|od)|Android|BlackBerry|IEMobile|Kindle|NetFront|Silk-Accelerated|(hpw|web)OS|Fennec|Minimo|Opera M(obi|ini)|Blazer/i', $userAgent)) {
        $device = 'Mobile';
    } else {
        $device = 'Desktop';
    }
}

// 6. Record the visit entry in the database
$stmtVisit = $pdo->prepare('
    INSERT INTO visits (page_id, visitor_hash, device, visit_time)
    VALUES (:page_id, :visitor_hash, :device, NOW())
');
$stmtVisit->execute([
    ':page_id'      => $pageId,
    ':visitor_hash' => $visitorHash,
    ':device'       => $device
]);

$visitId = (int)$pdo->lastInsertId();

// 7. Return successful response
sendJsonResponse([
    'success' => true,
    'message' => 'Visit logged successfully',
    'data'    => [
        'visit_id'   => $visitId,
        'page_id'    => $pageId,
        'url'        => $pageUrl,
        'device'     => $device,
        'visit_time' => date('Y-m-d H:i:s')
    ]
], 201);
