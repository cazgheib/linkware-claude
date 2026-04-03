<?php
// Linkware messages API — reads/writes from MySQL
// Protected by admin password

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://linkware.org');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$provided = $_SERVER['HTTP_X_ADMIN_PASSWORD'] ?? ($_POST['password'] ?? ($_GET['password'] ?? ''));
if ($provided !== ADMIN_PASSWORD) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit;
}

$db     = getDB();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $rows = $db->query('SELECT * FROM messages ORDER BY date DESC')->fetchAll();
    echo json_encode(['success' => true, 'messages' => array_map(function ($r) {
        return [
            'id'      => $r['id'],
            'date'    => $r['date'],
            'name'    => $r['name'],
            'org'     => $r['org'],
            'email'   => $r['email'],
            'service' => $r['service'],
            'message' => $r['message'],
            'read'    => (bool)$r['is_read'],
        ];
    }, $rows)]);

} elseif ($action === 'read' && isset($_GET['id'])) {
    $db->prepare('UPDATE messages SET is_read=1 WHERE id=?')->execute([$_GET['id']]);
    echo json_encode(['success' => true]);

} elseif ($action === 'delete' && isset($_GET['id'])) {
    $db->prepare('DELETE FROM messages WHERE id=?')->execute([$_GET['id']]);
    echo json_encode(['success' => true]);

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>
