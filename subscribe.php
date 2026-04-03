<?php
// Linkware subscriber handler
// POST (no auth): save a new subscriber to MySQL + forward to Beehiiv server-side
// GET  (auth):    list / delete subscribers

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://linkware.org');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// ── POST: subscribe ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data  = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    $db = getDB();

    // Deduplicate
    $stmt = $db->prepare('SELECT id FROM subscribers WHERE email=?');
    $stmt->execute([strtolower($email)]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Already subscribed.']);
        exit;
    }

    $db->prepare('INSERT INTO subscribers (id,date,email) VALUES (?,?,?)')
       ->execute([uniqid('sub_', true), date('Y-m-d H:i:s'), strtolower($email)]);

    // Forward to Beehiiv server-side (avoids browser CORS/auth issues)
    $bh_payload = json_encode([
        'email'               => $email,
        'reactivate_existing' => false,
        'send_welcome_email'  => true,
        'utm_source'          => 'linkware.org',
        'utm_medium'          => 'website',
    ]);
    $ch = curl_init('https://api.beehiiv.com/v2/publications/pub_a711845b-ee5c-4a9e-8cd2-27f8435f5305/subscriptions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $bh_payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);

    echo json_encode(['success' => true]);
    exit;
}

// ── GET: list / delete (admin only) ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$provided = $_SERVER['HTTP_X_ADMIN_PASSWORD'] ?? ($_GET['password'] ?? '');
if ($provided !== ADMIN_PASSWORD) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit;
}

$db     = getDB();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $rows = $db->query('SELECT * FROM subscribers ORDER BY date DESC')->fetchAll();
    echo json_encode(['success' => true, 'subscribers' => array_map(function ($r) {
        return ['id' => $r['id'], 'date' => $r['date'], 'email' => $r['email']];
    }, $rows)]);

} elseif ($action === 'delete' && isset($_GET['id'])) {
    $db->prepare('DELETE FROM subscribers WHERE id=?')->execute([$_GET['id']]);
    echo json_encode(['success' => true]);

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>
