<?php
session_start();
require_once 'includes/message-functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;

if ($ticket_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
    exit;
}

// Get messages using function
$messages = get_messages($ticket_id);

echo json_encode(['success' => true, 'messages' => $messages]);
?>
