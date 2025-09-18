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

if (!$ticket_id) {
    echo json_encode(['success' => false, 'message' => 'Ticket ID required']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'tiksuma');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get current priority of the ticket
$priority_sql = "SELECT priority_id FROM tickets WHERE ticket_id = ?";
$stmt = $conn->prepare($priority_sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$priority_result = $stmt->get_result();

if ($priority_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
    exit;
}

$ticket_data = $priority_result->fetch_assoc();
$current_priority_id = $ticket_data['priority_id'];
$stmt->close();

// Get next higher priority (lower ID number means higher priority)
$next_priority_sql = "SELECT id FROM priority_levels WHERE id < ? ORDER BY id ASC LIMIT 1";
$stmt = $conn->prepare($next_priority_sql);
$stmt->bind_param("i", $current_priority_id);
$stmt->execute();
$next_priority_result = $stmt->get_result();

if ($next_priority_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Ticket is already at highest priority']);
    exit;
}

$next_priority_data = $next_priority_result->fetch_assoc();
$new_priority_id = $next_priority_data['id'];
$stmt->close();

// Update ticket priority
$update_sql = "UPDATE tickets SET priority_id = ? WHERE ticket_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $new_priority_id, $ticket_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Ticket escalated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error escalating ticket: ' . $conn->error]);
}

$update_stmt->close();
$conn->close();
?>
