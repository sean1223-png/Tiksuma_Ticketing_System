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

$conn = new mysqli('localhost', 'root', '', 'tiksumadb');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get current user's username
$current_user = $_SESSION['username'];

// Update the ticket with assigned_to field
$sql = "UPDATE tickets SET assigned_to = ? WHERE ticket_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $current_user, $ticket_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Ticket assigned successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error assigning ticket: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
