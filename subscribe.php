<?php
// Linkware subscriber handler
// POST (no auth): save a new subscriber to subscribers.json
// GET  (auth):    list / delete subscribers

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://linkware.org');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$subs_file = __DIR__ . '/subscribers.json';
$password  = 'linkware2025'; // Keep in sync with admin panel password

// ── POST: subscribe ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw   = file_get_contents('php://input');
    $data  = json_decode($raw, true);
    $email = isset($data['email']) ? filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL) : '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    $subs = [];
    if (file_exists($subs_file)) {
        $subs = json_decode(file_get_contents($subs_file), true) ?? [];
    }

    // Deduplicate by email (case-insensitive)
    foreach ($subs as $s) {
        if (strtolower($s['email']) === strtolower($email)) {
            echo json_encode(['success' => true, 'message' => 'Already subscribed.']);
            exit;
        }
    }

    array_unshift($subs, [
        'id'    => uniqid('sub_', true),
        'date'  => date('Y-m-d H:i:s'),
        'email' => $email,
    ]);
    file_put_contents($subs_file, json_encode($subs, JSON_PRETTY_PRINT));
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
if ($provided !== $password) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit;
}

$subs = [];
if (file_exists($subs_file)) {
    $subs = json_decode(file_get_contents($subs_file), true) ?? [];
}

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    echo json_encode(['success' => true, 'subscribers' => $subs]);

} elseif ($action === 'delete' && isset($_GET['id'])) {
    $id   = $_GET['id'];
    $subs = array_values(array_filter($subs, function ($s) use ($id) {
        return $s['id'] !== $id;
    }));
    file_put_contents($subs_file, json_encode($subs, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>
