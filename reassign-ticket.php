<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$ticket_id = $data['ticket_id'] ?? null;
$assignee = $data['assignee'] ?? null;

if (!$ticket_id || !$assignee) {
    echo json_encode(['success' => false, 'message' => 'Ticket ID and assignee required']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'tiksumadb');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Verify that the assignee username exists
$user_check_sql = "SELECT username FROM users WHERE username = ?";
$stmt = $conn->prepare($user_check_sql);
$stmt->bind_param("s", $assignee);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
$stmt->close();

// Update the ticket with new assignee
$update_sql = "UPDATE tickets SET assigned_to = ? WHERE ticket_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $assignee, $ticket_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Ticket reassigned successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error reassigning ticket: ' . $conn->error]);
}

$update_stmt->close();
$conn->close();
?>
