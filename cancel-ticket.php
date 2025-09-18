<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'tiksumadb');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$username = $_SESSION['username'];

// Verify ticket belongs to user
$verify_sql = "SELECT t.ticket_id FROM tickets t WHERE t.ticket_id = ? AND t.username = ?";
$stmt = $conn->prepare($verify_sql);
$stmt->bind_param("is", $ticket_id, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Ticket not found or you don't have permission to cancel it.";
    header("Location: clarc-my-ticket.php");
    exit;
}

// Update ticket status to cancelled
$update_sql = "UPDATE tickets SET status_id = (SELECT id FROM ticket_statuses WHERE status_name = 'Cancelled') WHERE ticket_id = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("i", $ticket_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Ticket #$ticket_id has been cancelled successfully.";
} else {
    $_SESSION['error'] = "Failed to cancel ticket. Please try again.";
}

$stmt->close();
$conn->close();

header("Location: clarc-my-ticket.php");
exit;
?>
