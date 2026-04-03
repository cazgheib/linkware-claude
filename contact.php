<?php
// Linkware contact form handler
// Sends email to admin@linkware.org AND saves message to MySQL

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://linkware.org');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$to   = 'admin@linkware.org';
$site = 'Linkware';

function clean($val) {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$name    = clean($_POST['name']    ?? '');
$org     = clean($_POST['org']     ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$service = clean($_POST['service'] ?? '');
$message = clean($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Save to database
$db = getDB();
$db->prepare('INSERT INTO messages (id,date,name,org,email,service,message,is_read) VALUES (?,?,?,?,?,?,?,0)')
   ->execute([uniqid('msg_', true), date('Y-m-d H:i:s'), $name, $org, $email, $service, $message]);

// Send email
$subject = 'New contact form submission — ' . $site;
$body    = "New enquiry from the Linkware contact form.\n";
$body   .= str_repeat('-', 50) . "\n\n";
$body   .= "Name:         {$name}\n";
if ($org)     $body .= "Organisation: {$org}\n";
$body   .= "Email:        {$email}\n";
if ($service) $body .= "Service:      {$service}\n";
$body   .= "\nMessage:\n{$message}\n\n";
$body   .= str_repeat('-', 50) . "\n";
$body   .= "Sent from linkware.org at " . date('Y-m-d H:i:s T') . "\n";

$headers  = "From: {$site} <noreply@linkware.org>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

mail($to, $subject, $body, $headers);

echo json_encode(['success' => true]);
?>
