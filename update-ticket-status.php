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
$status = $data['status'] ?? null;

if (!$ticket_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Ticket ID and status required']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'tiksumadb');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get status ID from status name
$status_sql = "SELECT id FROM ticket_statuses WHERE status_name = ?";
$stmt = $conn->prepare($status_sql);
$stmt->bind_param("s", $status);
$stmt->execute();
$status_result = $stmt->get_result();

if ($status_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$status_data = $status_result->fetch_assoc();
$status_id = $status_data['id'];
$stmt->close();

// Update ticket status
$update_sql = "UPDATE tickets SET status_id = ? WHERE ticket_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $status_id, $ticket_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Ticket status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating ticket status: ' . $conn->error]);
}

$update_stmt->close();
$conn->close();
?>
