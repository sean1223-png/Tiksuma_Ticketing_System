<?php
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['ticket_id']) || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$ticket_id = intval($input['ticket_id']);
$message = trim($input['message']);

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit;
}

// Initialize messages array in session if not exists
if (!isset($_SESSION['ticket_messages'])) {
    $_SESSION['ticket_messages'] = [];
}

if (!isset($_SESSION['ticket_messages'][$ticket_id])) {
    $_SESSION['ticket_messages'][$ticket_id] = [];
}

// Add message
$message_data = [
    'id' => uniqid(),
    'sender' => $_SESSION['username'],
    'message' => $message,
    'timestamp' => date('Y-m-d H:i:s')
];

$_SESSION['ticket_messages'][$ticket_id][] = $message_data;

echo json_encode(['success' => true, 'message' => 'Message sent']);
?>
