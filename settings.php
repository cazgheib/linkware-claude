<?php
// Linkware settings API
// GET (no auth):  return public settings (site_title, site_tagline)
// GET action=all (auth): return all settings including admin_password
// POST (auth):    update one or more settings

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://linkware.org');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Password');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provided = $_SERVER['HTTP_X_ADMIN_PASSWORD'] ?? '';
    $row = $db->query('SELECT `value` FROM settings WHERE `key`="admin_password"')->fetch();
    if (!$row || $provided !== $row['value']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorised']);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $stmt = $db->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
    foreach ($data as $key => $value) {
        if (in_array($key, ['site_title', 'site_tagline', 'admin_password'])) {
            $stmt->execute([$key, $value]);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

$action = $_GET['action'] ?? 'public';

if ($action === 'all') {
    $provided = $_SERVER['HTTP_X_ADMIN_PASSWORD'] ?? ($_GET['password'] ?? '');
    $row = $db->query('SELECT `value` FROM settings WHERE `key`="admin_password"')->fetch();
    if (!$row || $provided !== $row['value']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorised']);
        exit;
    }
    $rows = $db->query('SELECT `key`, `value` FROM settings')->fetchAll();
    $map = [];
    foreach ($rows as $r) $map[$r['key']] = $r['value'];
    echo json_encode(['success' => true, 'settings' => $map]);
} else {
    // Public: only non-sensitive settings
    $rows = $db->query('SELECT `key`, `value` FROM settings WHERE `key` IN ("site_title","site_tagline")')->fetchAll();
    $map = [];
    foreach ($rows as $r) $map[$r['key']] = $r['value'];
    echo json_encode(['success' => true, 'settings' => $map]);
}
?>
