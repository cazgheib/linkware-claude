<?php
// Linkware articles API
// POST (auth):             create or update an article
// GET action=published:    list published articles (no auth, used by public site)
// GET action=list (auth):  list all articles including drafts
// GET action=get&id (no auth): fetch single published article with body
// GET action=delete&id (auth): delete article
// GET action=toggle&id (auth): toggle published/draft

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://linkware.org');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Password');

function auth() {
    $p = $_SERVER['HTTP_X_ADMIN_PASSWORD'] ?? ($_GET['password'] ?? '');
    if ($p !== ADMIN_PASSWORD) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorised']);
        exit;
    }
}

function row_to_article($r, $include_body = true) {
    $a = [
        'id'       => $r['id'],
        'title'    => $r['title'],
        'category' => $r['category'],
        'excerpt'  => $r['excerpt'],
        'status'   => $r['status'],
        'date'     => $r['date'],
        'readTime' => (string)$r['read_time'],
    ];
    if ($include_body) $a['body'] = $r['body'];
    return $a;
}

$db = getDB();

// ── POST: save (create or update) ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    auth();
    $data     = json_decode(file_get_contents('php://input'), true) ?? [];
    $id       = trim($data['id'] ?? '');
    $title    = trim($data['title'] ?? '');
    $excerpt  = trim($data['excerpt'] ?? '');
    $body     = trim($data['body'] ?? '');
    $category = $data['category'] ?? 'implementation';
    $status   = $data['status'] ?? 'draft';
    $date     = $data['date'] ?? date('Y-m-d');
    $readTime = max(1, (int)($data['readTime'] ?? 5));

    if (!$title || !$excerpt || !$body) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Title, excerpt and body are required.']);
        exit;
    }

    if ($id) {
        $db->prepare('UPDATE articles SET title=?,category=?,excerpt=?,body=?,status=?,date=?,read_time=? WHERE id=?')
           ->execute([$title, $category, $excerpt, $body, $status, $date, $readTime, $id]);
    } else {
        $id = 'art-' . uniqid('', true);
        $db->prepare('INSERT INTO articles (id,title,category,excerpt,body,status,date,read_time) VALUES (?,?,?,?,?,?,?,?)')
           ->execute([$id, $title, $category, $excerpt, $body, $status, $date, $readTime]);
    }
    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

// ── GET ──────────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? 'published';

if ($action === 'published') {
    $rows = $db->query('SELECT id,title,category,excerpt,status,date,read_time FROM articles WHERE status="published" ORDER BY date DESC')->fetchAll();
    echo json_encode(['success' => true, 'articles' => array_map(function($r){ return row_to_article($r, false); }, $rows)]);

} elseif ($action === 'list') {
    auth();
    $rows = $db->query('SELECT * FROM articles ORDER BY date DESC')->fetchAll();
    echo json_encode(['success' => true, 'articles' => array_map(function($r){ return row_to_article($r, true); }, $rows)]);

} elseif ($action === 'get' && isset($_GET['id'])) {
    $stmt = $db->prepare('SELECT * FROM articles WHERE id=? AND status="published"');
    $stmt->execute([$_GET['id']]);
    $r = $stmt->fetch();
    if (!$r) { http_response_code(404); echo json_encode(['success' => false]); exit; }
    echo json_encode(['success' => true, 'article' => row_to_article($r, true)]);

} elseif ($action === 'delete' && isset($_GET['id'])) {
    auth();
    $db->prepare('DELETE FROM articles WHERE id=?')->execute([$_GET['id']]);
    echo json_encode(['success' => true]);

} elseif ($action === 'toggle' && isset($_GET['id'])) {
    auth();
    $db->prepare('UPDATE articles SET status=IF(status="published","draft","published") WHERE id=?')->execute([$_GET['id']]);
    echo json_encode(['success' => true]);

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>
