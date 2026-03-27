<?php
// Linkware messages API
// Returns or updates messages from messages.json
// Protected by the same admin password

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://linkware.org');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$messages_file = __DIR__ . '/messages.json';

// Simple password check via header or POST param
$password      = 'linkware2025'; // Keep in sync with your admin panel password
$provided      = $_SERVER['HTTP_X_ADMIN_PASSWORD'] ?? ($_POST['password'] ?? $_GET['password'] ?? '');

if ($provided !== $password) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit;
}

$action = $_GET['action'] ?? 'list';

// Load messages
$messages = [];
if (file_exists($messages_file)) {
    $raw = file_get_contents($messages_file);
    $messages = json_decode($raw, true) ?? [];
}

if ($action === 'list') {
    echo json_encode(['success' => true, 'messages' => $messages]);

} elseif ($action === 'read' && isset($_GET['id'])) {
    // Mark as read
    $id = $_GET['id'];
    foreach ($messages as &$m) {
        if ($m['id'] === $id) { $m['read'] = true; break; }
    }
    file_put_contents($messages_file, json_encode($messages, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);

} elseif ($action === 'delete' && isset($_GET['id'])) {
    // Delete message
    $id = $_GET['id'];
    $messages = array_values(array_filter($messages, function($m) use ($id) {
        return $m['id'] !== $id;
    }));
    file_put_contents($messages_file, json_encode($messages, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>
