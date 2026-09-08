<?php
// ==========================================================
// visit-tracker: Analytics API Endpoint (stats_api.php)
// ==========================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';

$pdo = getDBConnection();

// Special action: List available sites for dashboard site selector
$action = $_GET['action'] ?? '';
if ($action === 'list_sites') {
    $stmtSites = $pdo->query('SELECT id, domain, site_token, created_at FROM sites ORDER BY id ASC');
    $sites = $stmtSites->fetchAll();
    sendJsonResponse(['success' => true, 'sites' => $sites]);
}

// Special action: Register a new website and generate unique site token
if ($action === 'create_site' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload = json_decode($rawInput, true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $rawDomain = trim($payload['domain'] ?? '');
    if (empty($rawDomain)) {
        sendJsonResponse(['success' => false, 'error' => 'Domain name is required.'], 400);
    }

    // Clean domain: strip http://, https://, trailing slashes/paths
    $domain = preg_replace('#^https?://#i', '', $rawDomain);
    $domain = trim(explode('/', $domain)[0]);
    $domain = mb_substr($domain, 0, 255, 'UTF-8');

    if (empty($domain)) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid domain format.'], 400);
    }

    // Check if domain already exists
    $stmtCheck = $pdo->prepare('SELECT id, domain, site_token FROM sites WHERE domain = :domain LIMIT 1');
    $stmtCheck->execute([':domain' => $domain]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        sendJsonResponse([
            'success' => false,
            'error'   => "The domain '{$domain}' is already registered.",
            'site'    => $existing
        ], 409);
    }

    // Generate secure 32-character random token
    $newToken = bin2hex(random_bytes(16));

    $stmtInsert = $pdo->prepare('INSERT INTO sites (domain, site_token, created_at) VALUES (:domain, :token, NOW())');
    $stmtInsert->execute([
        ':domain' => $domain,
        ':token'  => $newToken
    ]);
    $newId = (int)$pdo->lastInsertId();

    sendJsonResponse([
        'success' => true,
        'message' => 'New site registered successfully.',
        'site'    => [
            'id'         => $newId,
            'domain'     => $domain,
            'site_token' => $newToken,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ], 201);
}

// Input parameters
$siteToken = isset($_GET['site_token']) ? trim($_GET['site_token']) : '';
$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate   = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Validate site_token presence
if (empty($siteToken)) {
    // If site_token is omitted, pick the first available site as fallback
    $firstSite = $pdo->query('SELECT site_token FROM sites ORDER BY id ASC LIMIT 1')->fetch();
    if ($firstSite) {
        $siteToken = $firstSite['site_token'];
    } else {
        sendJsonResponse([
            'success' => false,
            'error'   => 'Parameter site_token is required and no sites are registered in database.'
        ], 400);
    }
}

// 1. Verify site exists
$stmtSite = $pdo->prepare('SELECT id, domain, site_token, created_at FROM sites WHERE site_token = :token LIMIT 1');
$stmtSite->execute([':token' => $siteToken]);
$site = $stmtSite->fetch();

if (!$site) {
    sendJsonResponse([
        'success' => false,
        'error'   => 'Site not found for the provided site_token.'
    ], 404);
}

$siteId = (int)$site['id'];

// 2. Validate and normalize date range (default: last 30 days to today)
$today = date('Y-m-d');
$defaultStart = date('Y-m-d', strtotime('-29 days'));

// Simple date format validator (YYYY-MM-DD)
function isValidDate(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

if (!isValidDate($startDate)) {
    $startDate = $defaultStart;
}
if (!isValidDate($endDate)) {
    $endDate = $today;
}

// If start date is after end date, swap them
if (strtotime($startDate) > strtotime($endDate)) {
    $tmp = $startDate;
    $startDate = $endDate;
    $endDate = $tmp;
}

$startDateTime = $startDate . ' 00:00:00';
$endDateTime   = $endDate . ' 23:59:59';

// 3. Global aggregate metrics for selected date range and site
$stmtTotals = $pdo->prepare('
    SELECT 
        COUNT(DISTINCT v.visitor_hash) AS total_unique_visits,
        COUNT(v.id) AS total_pageviews,
        COUNT(DISTINCT p.id) AS total_pages_tracked
    FROM visits v
    JOIN pages p ON v.page_id = p.id
    WHERE p.site_id = :site_id
      AND v.visit_time BETWEEN :start_date AND :end_date
');
$stmtTotals->execute([
    ':site_id'    => $siteId,
    ':start_date' => $startDateTime,
    ':end_date'   => $endDateTime
]);
$totals = $stmtTotals->fetch() ?: [
    'total_unique_visits' => 0,
    'total_pageviews' => 0,
    'total_pages_tracked' => 0
];

// 4. Time-series breakdown (Hourly for single day, Daily for multi-day periods)
$isHourly = ($startDate === $endDate);

if ($isHourly) {
    // Group by hour (00:00 to 23:00)
    $stmtHourly = $pdo->prepare('
        SELECT 
            DATE_FORMAT(v.visit_time, "%H:00") AS time_slot,
            COUNT(DISTINCT v.visitor_hash) AS unique_visits,
            COUNT(v.id) AS total_pageviews
        FROM visits v
        JOIN pages p ON v.page_id = p.id
        WHERE p.site_id = :site_id
          AND v.visit_time BETWEEN :start_date AND :end_date
        GROUP BY DATE_FORMAT(v.visit_time, "%H:00")
        ORDER BY time_slot ASC
    ');
    $stmtHourly->execute([
        ':site_id'    => $siteId,
        ':start_date' => $startDateTime,
        ':end_date'   => $endDateTime
    ]);
    $hourlyRows = $stmtHourly->fetchAll();

    $hourlyDataMap = [];
    foreach ($hourlyRows as $row) {
        $hourlyDataMap[$row['time_slot']] = [
            'unique_visits'   => (int)$row['unique_visits'],
            'total_pageviews' => (int)$row['total_pageviews']
        ];
    }

    // Build complete 24-hour timeline (00:00 to 23:00)
    $dailySeries = [];
    for ($h = 0; $h < 24; $h++) {
        $hourStr = sprintf('%02d:00', $h);
        $dailySeries[] = [
            'date'            => $startDate,
            'time'            => $hourStr,
            'label'           => $hourStr,
            'unique_visits'   => $hourlyDataMap[$hourStr]['unique_visits'] ?? 0,
            'total_pageviews' => $hourlyDataMap[$hourStr]['total_pageviews'] ?? 0
        ];
    }
} else {
    // Multi-day: Group by date
    $stmtDaily = $pdo->prepare('
        SELECT 
            DATE(v.visit_time) AS visit_date,
            COUNT(DISTINCT v.visitor_hash) AS unique_visits,
            COUNT(v.id) AS total_pageviews
        FROM visits v
        JOIN pages p ON v.page_id = p.id
        WHERE p.site_id = :site_id
          AND v.visit_time BETWEEN :start_date AND :end_date
        GROUP BY DATE(v.visit_time)
        ORDER BY visit_date ASC
    ');
    $stmtDaily->execute([
        ':site_id'    => $siteId,
        ':start_date' => $startDateTime,
        ':end_date'   => $endDateTime
    ]);
    $dailyRows = $stmtDaily->fetchAll();

    // Map DB records by date string to fill days with 0 visits
    $dailyDataMap = [];
    foreach ($dailyRows as $row) {
        $dailyDataMap[$row['visit_date']] = [
            'unique_visits'   => (int)$row['unique_visits'],
            'total_pageviews' => (int)$row['total_pageviews']
        ];
    }

    // Generate continuous daily series
    $dailySeries = [];
    $currentCursor = new DateTime($startDate);
    $endCursor = new DateTime($endDate);

    while ($currentCursor <= $endCursor) {
        $dateStr = $currentCursor->format('Y-m-d');
        $parts = explode('-', $dateStr);
        $label = $parts[2] . '/' . $parts[1]; // dd/mm

        if (isset($dailyDataMap[$dateStr])) {
            $dailySeries[] = [
                'date'            => $dateStr,
                'label'           => $label,
                'unique_visits'   => $dailyDataMap[$dateStr]['unique_visits'],
                'total_pageviews' => $dailyDataMap[$dateStr]['total_pageviews']
            ];
        } else {
            $dailySeries[] = [
                'date'            => $dateStr,
                'label'           => $label,
                'unique_visits'   => 0,
                'total_pageviews' => 0
            ];
        }
        $currentCursor->modify('+1 day');
    }
}

// 5. List of pages with unique visits and total pageviews ordered descending
$stmtPages = $pdo->prepare('
    SELECT 
        p.id AS page_id,
        p.url,
        COUNT(DISTINCT v.visitor_hash) AS unique_visits,
        COUNT(v.id) AS total_pageviews,
        MAX(v.visit_time) AS last_visit
    FROM pages p
    JOIN visits v ON p.id = v.page_id
    WHERE p.site_id = :site_id
      AND v.visit_time BETWEEN :start_date AND :end_date
    GROUP BY p.id, p.url
    ORDER BY unique_visits DESC, total_pageviews DESC
');
$stmtPages->execute([
    ':site_id'    => $siteId,
    ':start_date' => $startDateTime,
    ':end_date'   => $endDateTime
]);
$pagesList = $stmtPages->fetchAll();

$pagesFormatted = [];
foreach ($pagesList as $pRow) {
    $pagesFormatted[] = [
        'page_id'         => (int)$pRow['page_id'],
        'url'             => $pRow['url'],
        'unique_visits'   => (int)$pRow['unique_visits'],
        'total_pageviews' => (int)$pRow['total_pageviews'],
        'last_visit'      => $pRow['last_visit']
    ];
}

// 6. Device type breakdown (Desktop, Mobile, Tablet)
$stmtDevices = $pdo->prepare('
    SELECT 
        v.device,
        COUNT(DISTINCT v.visitor_hash) AS unique_visits,
        COUNT(v.id) AS total_pageviews
    FROM visits v
    JOIN pages p ON v.page_id = p.id
    WHERE p.site_id = :site_id
      AND v.visit_time BETWEEN :start_date AND :end_date
    GROUP BY v.device
    ORDER BY unique_visits DESC
');
$stmtDevices->execute([
    ':site_id'    => $siteId,
    ':start_date' => $startDateTime,
    ':end_date'   => $endDateTime
]);
$deviceRows = $stmtDevices->fetchAll();

$devicesList = [];
foreach ($deviceRows as $dRow) {
    $devicesList[] = [
        'device'          => $dRow['device'],
        'unique_visits'   => (int)$dRow['unique_visits'],
        'total_pageviews' => (int)$dRow['total_pageviews']
    ];
}

// 7. Consolidate JSON response
sendJsonResponse([
    'success' => true,
    'site' => [
        'id'         => (int)$site['id'],
        'domain'     => $site['domain'],
        'site_token' => $site['site_token'],
        'created_at' => $site['created_at']
    ],
    'period' => [
        'start_date' => $startDate,
        'end_date'   => $endDate,
        'time_unit'  => $isHourly ? 'hour' : 'day',
        'days'       => $isHourly ? 1 : count($dailySeries)
    ],
    'totals' => [
        'unique_visits'       => (int)$totals['total_unique_visits'],
        'total_pageviews'     => (int)$totals['total_pageviews'],
        'pages_tracked_count' => (int)$totals['total_pages_tracked']
    ],
    'daily_series' => $dailySeries,
    'pages'        => $pagesFormatted,
    'devices'      => $devicesList
]);
